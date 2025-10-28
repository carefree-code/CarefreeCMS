<?php
declare(strict_types=1);

namespace app\service;

/**
 * 模板资源管理器
 * 负责同步模板资源文件（CSS、JS、图片等）到静态目录
 */
class TemplateAssetManager
{
    /**
     * 模板套装目录
     */
    protected $templatePath;

    /**
     * 静态输出目录
     */
    protected $outputPath;

    /**
     * 当前模板套装
     */
    protected $currentTheme;

    /**
     * 同步日志
     */
    protected $syncLog = [];

    public function __construct(string $currentTheme, string $outputPath)
    {
        $this->currentTheme = $currentTheme;
        $this->outputPath = $outputPath;
        $this->templatePath = root_path() . 'templates' . DIRECTORY_SEPARATOR . $currentTheme . DIRECTORY_SEPARATOR;
    }

    /**
     * 同步所有资源文件到静态目录
     * @return array 同步结果
     */
    public function syncAllAssets(): array
    {
        $this->syncLog = [];
        $assetsPath = $this->templatePath . 'assets';

        if (!is_dir($assetsPath)) {
            return [
                'success' => false,
                'message' => '模板资源目录不存在：' . $assetsPath,
                'log' => []
            ];
        }

        try {
            // 同步整个assets目录
            $this->syncDirectory($assetsPath, $this->outputPath . 'assets');

            return [
                'success' => true,
                'message' => '资源同步成功',
                'log' => $this->syncLog,
                'total_files' => count($this->syncLog)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '资源同步失败：' . $e->getMessage(),
                'log' => $this->syncLog
            ];
        }
    }

    /**
     * 递归同步目录
     * @param string $source 源目录
     * @param string $destination 目标目录
     */
    protected function syncDirectory(string $source, string $destination)
    {
        // 确保目标目录存在
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = scandir($source);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                // 递归同步子目录
                $this->syncDirectory($sourcePath, $destPath);
            } else {
                // 复制文件
                $this->syncFile($sourcePath, $destPath);
            }
        }
    }

    /**
     * 同步单个文件
     * @param string $source 源文件路径
     * @param string $destination 目标文件路径
     */
    protected function syncFile(string $source, string $destination)
    {
        $needsCopy = false;

        if (!file_exists($destination)) {
            $needsCopy = true;
            $reason = '新文件';
        } elseif (filemtime($source) > filemtime($destination)) {
            $needsCopy = true;
            $reason = '源文件更新';
        } elseif (filesize($source) !== filesize($destination)) {
            $needsCopy = true;
            $reason = '文件大小不同';
        }

        if ($needsCopy) {
            if (copy($source, $destination)) {
                $this->syncLog[] = [
                    'file' => basename($source),
                    'path' => str_replace($this->outputPath, '', $destination),
                    'size' => filesize($source),
                    'action' => 'copied',
                    'reason' => $reason ?? '更新'
                ];
            } else {
                $this->syncLog[] = [
                    'file' => basename($source),
                    'path' => str_replace($this->outputPath, '', $destination),
                    'action' => 'failed',
                    'reason' => '复制失败'
                ];
            }
        } else {
            $this->syncLog[] = [
                'file' => basename($source),
                'path' => str_replace($this->outputPath, '', $destination),
                'action' => 'skipped',
                'reason' => '文件已是最新'
            ];
        }
    }

    /**
     * 清理静态目录中的旧资源文件
     * @return array 清理结果
     */
    public function cleanOldAssets(): array
    {
        $cleaned = [];
        $assetsPath = $this->outputPath . 'assets';

        if (!is_dir($assetsPath)) {
            return [
                'success' => true,
                'message' => '无需清理',
                'files' => []
            ];
        }

        try {
            $this->removeDirectory($assetsPath, $cleaned);

            return [
                'success' => true,
                'message' => '清理完成',
                'files' => $cleaned
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '清理失败：' . $e->getMessage(),
                'files' => $cleaned
            ];
        }
    }

    /**
     * 递归删除目录
     * @param string $dir 目录路径
     * @param array $cleaned 已清理的文件列表
     */
    protected function removeDirectory(string $dir, array &$cleaned)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->removeDirectory($path, $cleaned);
                rmdir($path);
            } else {
                unlink($path);
                $cleaned[] = $path;
            }
        }

        rmdir($dir);
    }

    /**
     * 获取资源文件列表
     * @return array 资源文件信息
     */
    public function getAssetsList(): array
    {
        $assets = [
            'css' => [],
            'js' => [],
            'images' => [],
            'other' => []
        ];

        $assetsPath = $this->templatePath . 'assets';

        if (!is_dir($assetsPath)) {
            return $assets;
        }

        $this->collectAssets($assetsPath, $assets);

        return $assets;
    }

    /**
     * 收集资源文件信息
     * @param string $dir 目录路径
     * @param array $assets 资源数组
     */
    protected function collectAssets(string $dir, array &$assets)
    {
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->collectAssets($path, $assets);
            } else {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $info = [
                    'name' => $file,
                    'path' => str_replace($this->templatePath, '', $path),
                    'size' => filesize($path),
                    'modified' => date('Y-m-d H:i:s', filemtime($path))
                ];

                if ($ext === 'css') {
                    $assets['css'][] = $info;
                } elseif ($ext === 'js') {
                    $assets['js'][] = $info;
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'])) {
                    $assets['images'][] = $info;
                } else {
                    $assets['other'][] = $info;
                }
            }
        }
    }
}
