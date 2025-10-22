<?php

namespace app\controller;

use think\Response;
use think\facade\Log;
use think\facade\Request;

/**
 * 静态文件访问控制器
 */
class Html
{
    /**
     * 处理html目录下的静态文件访问
     */
    public function index($path = '')
    {
        // 从Request获取完整路径
        $pathinfo = Request::pathinfo();
        Log::info('Html controller accessed - Pathinfo: ' . $pathinfo . ', Path param: ' . $path);

        // 移除 'html/' 前缀
        if (strpos($pathinfo, 'html/') === 0) {
            $path = substr($pathinfo, 5);
        } else if (strpos($pathinfo, 'html') === 0) {
            $path = substr($pathinfo, 4);
            $path = ltrim($path, '/');
        }

        Log::info('Extracted path: ' . $path);

        // 构建实际文件路径
        $filePath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        Log::info('Looking for file: ' . $filePath);

        // 检查文件是否存在
        if (!file_exists($filePath) || !is_file($filePath)) {
            Log::warning('File not found: ' . $filePath);
            return response('File not found: ' . $filePath, 404);
        }

        // 获取文件扩展名
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        // 设置MIME类型
        $mimeTypes = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'bmp'  => 'image/bmp',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'html' => 'text/html',
            'htm'  => 'text/html',
            'txt'  => 'text/plain',
            'pdf'  => 'application/pdf',
            'zip'  => 'application/zip',
            'rar'  => 'application/x-rar-compressed',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

        // 读取文件内容
        $content = file_get_contents($filePath);

        Log::info('File found and read', ['size' => strlen($content), 'mimeType' => $mimeType]);

        // 返回文件
        return response($content, 200, ['Content-Type' => $mimeType]);
    }
}
