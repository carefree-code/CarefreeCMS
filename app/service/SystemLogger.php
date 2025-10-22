<?php
declare (strict_types = 1);

namespace app\service;

use app\model\SystemLog;
use app\model\LoginLog;
use app\model\SecurityLog;
use think\facade\Request;

/**
 * 系统日志服务
 */
class SystemLogger
{
    // 日志级别
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    // 日志分类
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_DATABASE = 'database';
    const CATEGORY_API = 'api';
    const CATEGORY_AUTH = 'auth';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_OPERATION = 'operation';

    /**
     * 记录系统日志
     * @param string $level 日志级别
     * @param string $category 日志分类
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @param int $userId 用户ID
     * @return SystemLog|false
     */
    public static function log($level, $category, $message, $context = [], $userId = null)
    {
        try {
            return SystemLog::create([
                'level' => $level,
                'category' => $category,
                'message' => $message,
                'context' => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
                'user_id' => $userId,
                'ip' => Request::ip(),
                'user_agent' => Request::header('user-agent'),
                'url' => Request::url(true),
                'method' => Request::method(),
                'create_time' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // 避免日志记录失败影响主流程
            return false;
        }
    }

    /**
     * 记录调试日志
     */
    public static function debug($category, $message, $context = [], $userId = null)
    {
        return self::log(self::LEVEL_DEBUG, $category, $message, $context, $userId);
    }

    /**
     * 记录信息日志
     */
    public static function info($category, $message, $context = [], $userId = null)
    {
        return self::log(self::LEVEL_INFO, $category, $message, $context, $userId);
    }

    /**
     * 记录警告日志
     */
    public static function warning($category, $message, $context = [], $userId = null)
    {
        return self::log(self::LEVEL_WARNING, $category, $message, $context, $userId);
    }

    /**
     * 记录错误日志
     */
    public static function error($category, $message, $context = [], $userId = null)
    {
        return self::log(self::LEVEL_ERROR, $category, $message, $context, $userId);
    }

    /**
     * 记录严重错误日志
     */
    public static function critical($category, $message, $context = [], $userId = null)
    {
        return self::log(self::LEVEL_CRITICAL, $category, $message, $context, $userId);
    }

    /**
     * 记录API调用日志
     */
    public static function logApiCall($endpoint, $params, $response, $duration, $userId = null)
    {
        return self::info(self::CATEGORY_API, "API调用: {$endpoint}", [
            'endpoint' => $endpoint,
            'params' => $params,
            'response' => $response,
            'duration' => $duration . 'ms'
        ], $userId);
    }

    /**
     * 记录SQL日志
     */
    public static function logSql($sql, $bindings, $duration)
    {
        return self::debug(self::CATEGORY_DATABASE, "SQL查询", [
            'sql' => $sql,
            'bindings' => $bindings,
            'duration' => $duration . 'ms'
        ]);
    }

    /**
     * 记录登录日志
     * @param int $userId 用户ID
     * @param string $username 用户名
     * @param bool $success 是否成功
     * @param string $failReason 失败原因
     * @return LoginLog|false
     */
    public static function logLogin($userId, $username, $success = true, $failReason = '')
    {
        try {
            return LoginLog::create([
                'user_id' => $success ? $userId : null,
                'username' => $username,
                'ip' => Request::ip(),
                'user_agent' => Request::header('user-agent'),
                'login_time' => date('Y-m-d H:i:s'),
                'status' => $success ? 'success' : 'failed',
                'fail_reason' => $failReason,
                'location' => self::getIpLocation(Request::ip())
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 记录安全日志
     * @param string $type 类型
     * @param string $level 级别
     * @param string $description 描述
     * @param array $requestData 请求数据
     * @param bool $isBlocked 是否已拦截
     * @return SecurityLog|false
     */
    public static function logSecurity($type, $level, $description, $requestData = [], $isBlocked = false)
    {
        try {
            return SecurityLog::create([
                'type' => $type,
                'level' => $level,
                'ip' => Request::ip(),
                'url' => Request::url(true),
                'method' => Request::method(),
                'user_agent' => Request::header('user-agent'),
                'request_data' => !empty($requestData) ? json_encode($requestData, JSON_UNESCAPED_UNICODE) : null,
                'description' => $description,
                'is_blocked' => $isBlocked ? 1 : 0,
                'create_time' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取IP地理位置（简单实现）
     * @param string $ip
     * @return string
     */
    private static function getIpLocation($ip)
    {
        // 内网IP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '内网IP';
        }

        // 这里可以集成第三方IP定位服务
        // 如：https://ip.taobao.com/outGetIpInfo
        // 或：https://whois.pconline.com.cn/ipJson.jsp

        return '未知';
    }

    /**
     * 获取日志统计
     * @param string $startTime 开始时间
     * @param string $endTime 结束时间
     * @return array
     */
    public static function getStatistics($startTime = '', $endTime = '')
    {
        $where = [];

        if (!empty($startTime)) {
            $where[] = ['create_time', '>=', $startTime];
        }

        if (!empty($endTime)) {
            $where[] = ['create_time', '<=', $endTime];
        }

        // 按级别统计
        $levelStats = SystemLog::where($where)
            ->field('level, COUNT(*) as count')
            ->group('level')
            ->select()
            ->toArray();

        // 按分类统计
        $categoryStats = SystemLog::where($where)
            ->field('category, COUNT(*) as count')
            ->group('category')
            ->select()
            ->toArray();

        // 每日统计
        $dailyStats = SystemLog::where($where)
            ->field('DATE(create_time) as date, COUNT(*) as count')
            ->group('DATE(create_time)')
            ->order('date', 'desc')
            ->limit(30)
            ->select()
            ->toArray();

        return [
            'level_stats' => $levelStats,
            'category_stats' => $categoryStats,
            'daily_stats' => $dailyStats,
            'total' => SystemLog::where($where)->count()
        ];
    }

    /**
     * 获取登录统计
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    public static function getLoginStatistics($startTime = '', $endTime = '')
    {
        $where = [];

        if (!empty($startTime)) {
            $where[] = ['login_time', '>=', $startTime];
        }

        if (!empty($endTime)) {
            $where[] = ['login_time', '<=', $endTime];
        }

        // 成功/失败统计
        $successCount = LoginLog::where($where)->where('status', 'success')->count();
        $failedCount = LoginLog::where($where)->where('status', 'failed')->count();

        // 每日统计
        $dailyStats = LoginLog::where($where)
            ->field('DATE(login_time) as date, COUNT(*) as count, SUM(CASE WHEN status="success" THEN 1 ELSE 0 END) as success_count')
            ->group('DATE(login_time)')
            ->order('date', 'desc')
            ->limit(30)
            ->select()
            ->toArray();

        // 失败原因统计
        $failReasonStats = LoginLog::where($where)
            ->where('status', 'failed')
            ->field('fail_reason, COUNT(*) as count')
            ->group('fail_reason')
            ->select()
            ->toArray();

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'total' => $successCount + $failedCount,
            'success_rate' => $successCount + $failedCount > 0 ? round($successCount / ($successCount + $failedCount) * 100, 2) : 0,
            'daily_stats' => $dailyStats,
            'fail_reason_stats' => $failReasonStats
        ];
    }

    /**
     * 清理旧日志
     * @param int $days 保留天数
     * @param string $logType 日志类型：system, login, security
     * @return int 删除数量
     */
    public static function cleanOldLogs($days = 30, $logType = 'system')
    {
        $time = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        switch ($logType) {
            case 'login':
                return LoginLog::where('login_time', '<', $time)->delete();
            case 'security':
                return SecurityLog::where('create_time', '<', $time)->delete();
            case 'system':
            default:
                return SystemLog::where('create_time', '<', $time)->delete();
        }
    }

    /**
     * 导出日志
     * @param array $where 查询条件
     * @param string $logType 日志类型
     * @return array
     */
    public static function exportLogs($where = [], $logType = 'system')
    {
        switch ($logType) {
            case 'login':
                $logs = LoginLog::where($where)->order('login_time', 'desc')->limit(10000)->select();
                break;
            case 'security':
                $logs = SecurityLog::where($where)->order('create_time', 'desc')->limit(10000)->select();
                break;
            case 'system':
            default:
                $logs = SystemLog::where($where)->order('create_time', 'desc')->limit(10000)->select();
                break;
        }

        return $logs->toArray();
    }
}
