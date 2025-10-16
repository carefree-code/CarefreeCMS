<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Config;

/**
 * 模板检查工具
 */
class TemplateCheck extends BaseController
{
    public function check()
    {
        $info = [];

        // 检查根路径
        $info['root_path'] = root_path();
        $info['root_path_exists'] = is_dir(root_path());

        // 检查模板路径
        $templatePath = root_path() . 'templates' . DIRECTORY_SEPARATOR;
        $info['template_path'] = $templatePath;
        $info['template_path_exists'] = is_dir($templatePath);

        // 检查当前模板套装
        $currentTheme = Config::getConfig('current_template_theme', 'default');
        $info['current_theme'] = $currentTheme;

        // 检查当前套装目录
        $themePath = $templatePath . $currentTheme . DIRECTORY_SEPARATOR;
        $info['theme_path'] = $themePath;
        $info['theme_path_exists'] = is_dir($themePath);

        // 列出模板文件
        if (is_dir($themePath)) {
            $files = scandir($themePath);
            $info['template_files'] = array_filter($files, function($file) {
                return !in_array($file, ['.', '..']);
            });
        } else {
            $info['template_files'] = [];
        }

        // 检查必需的模板文件
        $requiredTemplates = ['index.html', 'article.html', 'category.html', 'page.html', 'layout.html'];
        $info['missing_templates'] = [];

        foreach ($requiredTemplates as $template) {
            $filePath = $themePath . $template;
            if (!file_exists($filePath)) {
                $info['missing_templates'][] = $template;
            }
        }

        return Response::success($info, '模板检查完成');
    }
}
