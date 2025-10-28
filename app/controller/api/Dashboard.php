<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Article;
use app\model\Category;
use app\model\Tag;
use app\model\Media;
use app\model\AdminUser;
use app\model\Page;
use think\facade\Db;

/**
 * 仪表板统计控制器
 */
class Dashboard extends BaseController
{
    /**
     * 获取统计数据
     */
    public function stats()
    {
        try {
            $stats = [
                // 基础统计
                'articles' => Article::count(),
                'published_articles' => Article::where('status', 1)->count(),
                'draft_articles' => Article::where('status', 0)->count(),
                'categories' => Category::count(),
                'tags' => Tag::count(),
                'media' => Media::count(),
                'users' => AdminUser::count(),
                'pages' => Page::count(),

                // 今日统计
                'today_articles' => Article::whereTime('create_time', 'today')->count(),
                'today_views' => Article::whereTime('create_time', 'today')->sum('view_count'),

                // 系统运行时长（基于安装时间）
                'system_uptime' => $this->getSystemUptime(),
            ];

            return Response::success($stats);
        } catch (\Exception $e) {
            return Response::error('获取统计数据失败：' . $e->getMessage());
        }
    }

    /**
     * 获取服务器信息
     */
    public function serverInfo()
    {
        try {
            $info = [
                // PHP信息
                'php_version' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,

                // 服务器信息
                'server_os' => PHP_OS,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',

                // 数据库信息
                'database_type' => config('database.default'),
                'database_version' => $this->getDatabaseVersion(),

                // 磁盘信息
                'disk_total' => $this->formatBytes(disk_total_space('.')),
                'disk_free' => $this->formatBytes(disk_free_space('.')),
                'disk_usage_percent' => round((1 - disk_free_space('.') / disk_total_space('.')) * 100, 2),

                // 内存信息
                'memory_limit' => ini_get('memory_limit'),
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),

                // 其他
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ];

            return Response::success($info);
        } catch (\Exception $e) {
            return Response::error('获取服务器信息失败：' . $e->getMessage());
        }
    }

    /**
     * 获取系统信息
     */
    public function systemInfo()
    {
        try {
            $info = [
                'system_name' => '逍遥内容管理系统',
                'system_version' => '1.2.0',
                'system_author' => 'CareFree Team',
                'system_copyright' => '© 2025 CareFree CMS. All rights reserved.',
                'system_license' => 'MIT License',
                'thinkphp_version' => app()->version(),
            ];

            return Response::success($info);
        } catch (\Exception $e) {
            return Response::error('获取系统信息失败：' . $e->getMessage());
        }
    }

    /**
     * 获取数据库版本
     */
    private function getDatabaseVersion()
    {
        try {
            $result = Db::query('SELECT VERSION() as version');
            return $result[0]['version'] ?? 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * 格式化字节大小
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * 获取系统运行时长
     */
    private function getSystemUptime()
    {
        try {
            // 获取第一篇文章或第一个管理员的创建时间作为系统安装时间
            $installTime = AdminUser::order('id', 'asc')->value('create_time');

            if (!$installTime) {
                return '未知';
            }

            $installTimestamp = is_numeric($installTime) ? $installTime : strtotime($installTime);
            $diff = time() - $installTimestamp;

            $days = floor($diff / 86400);
            $hours = floor(($diff % 86400) / 3600);
            $minutes = floor(($diff % 3600) / 60);

            if ($days > 0) {
                return "{$days}天{$hours}小时";
            } elseif ($hours > 0) {
                return "{$hours}小时{$minutes}分钟";
            } else {
                return "{$minutes}分钟";
            }
        } catch (\Exception $e) {
            return '未知';
        }
    }
}
