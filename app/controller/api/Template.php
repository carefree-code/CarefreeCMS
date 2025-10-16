<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Config;
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
}
