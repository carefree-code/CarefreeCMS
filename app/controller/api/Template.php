<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Config;
use app\model\TemplateHistory;
use think\facade\Db;

class Template extends BaseController
{
    /**
     * 获取可用模板列表（当前模板套装下的）
     */
    public function list()
    {
        try {
            // 获取当前模板套装
            $currentTheme = Config::getConfig('current_template_theme', 'default');

            // 从数据库获取模板列表
            $templates = Db::name('templates')
                ->where('status', 1)
                ->order('is_default', 'desc')  // 默认模板排在前面
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return Response::success($templates);
        } catch (\Exception $e) {
            return Response::error('获取模板列表失败：' . $e->getMessage());
        }
    }

    /**
     * 扫描templates目录，获取所有模板套装
     */
    public function scanThemes()
    {
        try {
            // 使用相对于当前文件的路径，确保正确定位到 api/templates/
            $templatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $themes = [];

            if (is_dir($templatesPath)) {
                $dirs = scandir($templatesPath);
                foreach ($dirs as $dir) {
                    // 只扫描目录，跳过文件和特殊目录
                    if ($dir != '.' && $dir != '..' && is_dir($templatesPath . $dir)) {
                        // 检查是否有 theme.json 配置文件
                        $themeConfigPath = $templatesPath . $dir . '/theme.json';
                        $themeInfo = [
                            'key' => $dir,
                            'name' => ucfirst($dir),
                            'description' => '',
                            'author' => '',
                            'version' => '1.0.0',
                            'preview' => ''
                        ];

                        if (file_exists($themeConfigPath)) {
                            $config = json_decode(file_get_contents($themeConfigPath), true);
                            if ($config) {
                                $themeInfo = array_merge($themeInfo, $config);
                            }
                        }

                        // 扫描该套装下的模板文件
                        $templateFiles = [];
                        $themeDir = $templatesPath . $dir . '/';
                        if (is_dir($themeDir)) {
                            $files = scandir($themeDir);
                            foreach ($files as $file) {
                                if (pathinfo($file, PATHINFO_EXTENSION) === 'html' && $file !== 'layout.html') {
                                    $templateFiles[] = pathinfo($file, PATHINFO_FILENAME);
                                }
                            }
                        }

                        $themeInfo['templates'] = $templateFiles;
                        $themes[] = $themeInfo;
                    }
                }
            }

            return Response::success($themes);
        } catch (\Exception $e) {
            return Response::error('扫描模板套装失败：' . $e->getMessage());
        }
    }

    /**
     * 切换模板套装
     */
    public function switchTheme()
    {
        try {
            $themeKey = $this->request->post('theme_key', '');

            if (empty($themeKey)) {
                return Response::error('请指定模板套装');
            }

            // 验证模板套装是否存在
            $themePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $themeKey . DIRECTORY_SEPARATOR;
            if (!is_dir($themePath)) {
                return Response::error('模板套装不存在');
            }

            // 开启事务
            Db::startTrans();
            try {
                // 1. 更新当前模板套装配置
                Config::setConfig('current_template_theme', $themeKey);

                // 2. 自动设置各个位置的模板为该套装的默认模板

                // 首页模板 - 设置为 index
                if (file_exists($themePath . 'index.html')) {
                    Config::setConfig('index_template', 'index');
                }

                // 3. 更新所有分类的模板为 category（如果该套装有category.html）
                if (file_exists($themePath . 'category.html')) {
                    Db::name('categories')->where('id', '>', 0)->update(['template' => 'category']);
                }

                // 4. 更新所有单页的模板为 page（如果该套装有page.html）
                if (file_exists($themePath . 'page.html')) {
                    Db::name('pages')->where('id', '>', 0)->update(['template' => 'page']);
                }

                Db::commit();

                return Response::success([], "模板套装已切换为：{$themeKey}，所有页面模板已自动设置为默认模板");
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return Response::error('切换模板套装失败：' . $e->getMessage());
        }
    }

    /**
     * 获取当前模板套装
     */
    public function getCurrentTheme()
    {
        try {
            $currentTheme = Config::getConfig('current_template_theme', 'default');

            $themePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $currentTheme . DIRECTORY_SEPARATOR;
            $themeInfo = [
                'key' => $currentTheme,
                'name' => ucfirst($currentTheme),
                'description' => '',
                'author' => '',
                'version' => '1.0.0'
            ];

            // 读取 theme.json
            $themeConfigPath = $themePath . 'theme.json';
            if (file_exists($themeConfigPath)) {
                $config = json_decode(file_get_contents($themeConfigPath), true);
                if ($config) {
                    $themeInfo = array_merge($themeInfo, $config);
                }
            }

            return Response::success($themeInfo);
        } catch (\Exception $e) {
            return Response::error('获取当前模板套装失败：' . $e->getMessage());
        }
    }

    /**
     * 扫描指定模板套装下的模板文件
     */
    public function scanTemplates()
    {
        try {
            $themeKey = $this->request->get('theme_key', '');

            if (empty($themeKey)) {
                // 获取当前模板套装
                $themeKey = Config::getConfig('current_template_theme', 'default');
            }

            $themePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $themeKey . DIRECTORY_SEPARATOR;
            $templates = [];

            if (is_dir($themePath)) {
                $files = scandir($themePath);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'html') {
                        $templateKey = pathinfo($file, PATHINFO_FILENAME);

                        // 跳过 layout.html
                        if ($templateKey === 'layout') {
                            continue;
                        }

                        $templates[] = [
                            'template_key' => $templateKey,
                            'name' => ucfirst($templateKey),
                            'file' => $file,
                            'theme' => $themeKey
                        ];
                    }
                }
            }

            return Response::success($templates);
        } catch (\Exception $e) {
            return Response::error('扫描模板文件失败：' . $e->getMessage());
        }
    }

    /**
     * 获取模板文件树（用于在线编辑）
     */
    public function getFileTree()
    {
        try {
            $themeKey = $this->request->get('theme_key', '');

            if (empty($themeKey)) {
                $themeKey = Config::getConfig('current_template_theme', 'default');
            }

            $templatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $themePath = $templatesPath . $themeKey . DIRECTORY_SEPARATOR;

            if (!is_dir($themePath)) {
                return Response::error('模板套装不存在');
            }

            $tree = $this->buildFileTree($themePath, $themeKey);

            return Response::success($tree);
        } catch (\Exception $e) {
            return Response::error('获取文件树失败：' . $e->getMessage());
        }
    }

    /**
     * 构建文件树
     */
    private function buildFileTree($path, $themeKey, $relativePath = '')
    {
        $tree = [];
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $path . $file;
            $relPath = $relativePath ? $relativePath . '/' . $file : $file;

            if (is_dir($fullPath)) {
                // 递归处理子目录
                $tree[] = [
                    'name' => $file,
                    'path' => $relPath,
                    'type' => 'directory',
                    'children' => $this->buildFileTree($fullPath . DIRECTORY_SEPARATOR, $themeKey, $relPath)
                ];
            } else {
                // 文件
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $size = filesize($fullPath);
                $modified = filemtime($fullPath);

                $tree[] = [
                    'name' => $file,
                    'path' => $relPath,
                    'type' => 'file',
                    'extension' => $ext,
                    'size' => $size,
                    'modified' => date('Y-m-d H:i:s', $modified)
                ];
            }
        }

        return $tree;
    }

    /**
     * 读取模板文件内容
     */
    public function readFile()
    {
        try {
            $themeKey = $this->request->get('theme_key', '');
            $filePath = $this->request->get('file_path', '');

            if (empty($themeKey) || empty($filePath)) {
                return Response::error('参数不完整');
            }

            // 安全检查：防止路径穿越攻击
            if (strpos($filePath, '..') !== false || strpos($filePath, '\\') !== false) {
                return Response::error('非法文件路径');
            }

            $templatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $fullPath = $templatesPath . $themeKey . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);

            if (!file_exists($fullPath)) {
                return Response::error('文件不存在');
            }

            if (!is_file($fullPath)) {
                return Response::error('不是有效的文件');
            }

            $content = file_get_contents($fullPath);

            return Response::success([
                'content' => $content,
                'path' => $filePath,
                'theme' => $themeKey,
                'size' => filesize($fullPath),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ]);
        } catch (\Exception $e) {
            return Response::error('读取文件失败：' . $e->getMessage());
        }
    }

    /**
     * 保存模板文件
     */
    public function saveFile()
    {
        try {
            $themeKey = $this->request->post('theme_key', '');
            $filePath = $this->request->post('file_path', '');
            $content = $this->request->post('content', '');
            $description = $this->request->post('description', ''); // 修改描述（可选）

            if (empty($themeKey) || empty($filePath)) {
                return Response::error('参数不完整');
            }

            // 安全检查
            if (strpos($filePath, '..') !== false || strpos($filePath, '\\') !== false) {
                return Response::error('非法文件路径');
            }

            $templatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $fullPath = $templatesPath . $themeKey . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);

            // 创建历史记录（保存到数据库）
            if (file_exists($fullPath)) {
                $oldContent = file_get_contents($fullPath);

                // 获取当前登录用户ID
                $userId = $this->request->userId ?? null;

                // 保存历史记录到数据库
                TemplateHistory::createHistory(
                    $themeKey,
                    $filePath,
                    $oldContent,
                    $userId,
                    $description ?: '保存模板文件'
                );
            }

            // 确保目录存在
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // 保存文件
            if (file_put_contents($fullPath, $content) === false) {
                return Response::error('保存文件失败：写入权限不足');
            }

            return Response::success([
                'path' => $filePath,
                'size' => filesize($fullPath),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ], '文件保存成功');
        } catch (\Exception $e) {
            return Response::error('保存文件失败：' . $e->getMessage());
        }
    }

    /**
     * 创建新文件
     */
    public function createFile()
    {
        try {
            $themeKey = $this->request->post('theme_key', '');
            $fileName = $this->request->post('file_name', '');
            $fileType = $this->request->post('file_type', 'html'); // html, css, js

            if (empty($themeKey) || empty($fileName)) {
                return Response::error('参数不完整');
            }

            // 安全检查
            if (strpos($fileName, '..') !== false || strpos($fileName, '\\') !== false || strpos($fileName, '/') !== false) {
                return Response::error('非法文件名');
            }

            // 添加扩展名
            if (!pathinfo($fileName, PATHINFO_EXTENSION)) {
                $fileName .= '.' . $fileType;
            }

            $templatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $fullPath = $templatesPath . $themeKey . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($fullPath)) {
                return Response::error('文件已存在');
            }

            // 创建文件
            $template = $this->getFileTemplate($fileType);
            if (file_put_contents($fullPath, $template) === false) {
                return Response::error('创建文件失败');
            }

            return Response::success([
                'path' => $fileName,
                'name' => $fileName
            ], '文件创建成功');
        } catch (\Exception $e) {
            return Response::error('创建文件失败：' . $e->getMessage());
        }
    }

    /**
     * 删除文件
     */
    public function deleteFile()
    {
        try {
            $themeKey = $this->request->post('theme_key', '');
            $filePath = $this->request->post('file_path', '');

            if (empty($themeKey) || empty($filePath)) {
                return Response::error('参数不完整');
            }

            // 安全检查
            if (strpos($filePath, '..') !== false || strpos($filePath, '\\') !== false) {
                return Response::error('非法文件路径');
            }

            $templatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $fullPath = $templatesPath . $themeKey . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);

            if (!file_exists($fullPath)) {
                return Response::error('文件不存在');
            }

            // 创建备份到回收站
            $trashPath = $templatesPath . '.trash' . DIRECTORY_SEPARATOR;
            if (!is_dir($trashPath)) {
                mkdir($trashPath, 0755, true);
            }

            $backupFile = $trashPath . $themeKey . '_' . str_replace('/', '_', $filePath) . '.' . date('YmdHis');
            copy($fullPath, $backupFile);

            // 删除文件
            if (!unlink($fullPath)) {
                return Response::error('删除文件失败');
            }

            return Response::success([], '文件已删除（备份已保存）');
        } catch (\Exception $e) {
            return Response::error('删除文件失败：' . $e->getMessage());
        }
    }

    /**
     * 获取文件模板
     */
    private function getFileTemplate($type)
    {
        $templates = [
            'html' => '{extend name="layout" /}

{block name="content"}
<div class="content-box">
    <h2>新页面</h2>
    <p>在这里添加内容...</p>
</div>
{/block}',
            'css' => '/* 样式文件 */

body {
    font-family: Arial, sans-serif;
}',
            'js' => '// JavaScript 文件

console.log("Hello World");'
        ];

        return $templates[$type] ?? '';
    }

    /**
     * 获取文件历史记录列表
     */
    public function getBackups()
    {
        try {
            $themeKey = $this->request->get('theme_key', '');
            $filePath = $this->request->get('file_path', '');

            if (empty($themeKey) || empty($filePath)) {
                return Response::error('参数不完整');
            }

            // 从数据库获取历史记录
            $historyList = TemplateHistory::getHistoryList($themeKey, $filePath);

            // 格式化返回数据
            $backupList = [];
            foreach ($historyList as $history) {
                $backupList[] = [
                    'id' => $history['id'],
                    'version' => $history['version'],
                    'file_name' => $history['file_name'],
                    'size' => $history['file_size'],
                    'description' => $history['change_description'] ?? '',
                    'user_id' => $history['user_id'],
                    'create_time' => $history['create_time'],
                    'modified' => $history['create_time'] // 兼容前端字段
                ];
            }

            return Response::success($backupList);
        } catch (\Exception $e) {
            return Response::error('获取历史记录失败：' . $e->getMessage());
        }
    }

    /**
     * 获取历史版本内容
     */
    public function getHistoryContent()
    {
        try {
            $historyId = $this->request->get('history_id', 0);

            if (empty($historyId)) {
                return Response::error('参数不完整');
            }

            $history = TemplateHistory::getHistoryById((int)$historyId);

            if (!$history) {
                return Response::error('历史记录不存在');
            }

            return Response::success([
                'id' => $history->id,
                'version' => $history->version,
                'content' => $history->content,
                'file_name' => $history->file_name,
                'file_path' => $history->file_path,
                'size' => $history->file_size,
                'description' => $history->change_description,
                'create_time' => $history->create_time
            ]);
        } catch (\Exception $e) {
            return Response::error('获取历史内容失败：' . $e->getMessage());
        }
    }

    /**
     * 恢复历史版本
     */
    public function restoreHistory()
    {
        try {
            $historyId = $this->request->post('history_id', 0);

            if (empty($historyId)) {
                return Response::error('参数不完整');
            }

            $history = TemplateHistory::getHistoryById((int)$historyId);

            if (!$history) {
                return Response::error('历史记录不存在');
            }

            // 恢复文件内容
            $templatesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $fullPath = $templatesPath . $history->theme_key . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $history->file_path);

            // 在恢复之前，先保存当前版本作为历史
            if (file_exists($fullPath)) {
                $currentContent = file_get_contents($fullPath);
                $userId = $this->request->userId ?? null;

                TemplateHistory::createHistory(
                    $history->theme_key,
                    $history->file_path,
                    $currentContent,
                    $userId,
                    '恢复前的备份'
                );
            }

            // 恢复历史版本
            if (file_put_contents($fullPath, $history->content) === false) {
                return Response::error('恢复文件失败：写入权限不足');
            }

            return Response::success([
                'path' => $history->file_path,
                'version' => $history->version
            ], '文件已恢复到版本 ' . $history->version);
        } catch (\Exception $e) {
            return Response::error('恢复历史版本失败：' . $e->getMessage());
        }
    }
}
