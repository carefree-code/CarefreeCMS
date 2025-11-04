<?php
declare(strict_types=1);

namespace app\middleware;

use app\service\SystemLogger;
use Closure;
use think\Request;
use think\Response;

/**
 * 系统日志中间件
 * 自动记录API请求到系统日志
 */
class SystemLog
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // 执行请求
        $response = $next($request);

        // 记录日志（异步处理，不影响响应）
        try {
            $this->logRequest($request, $response, $startTime);
        } catch (\Exception $e) {
            // 日志记录失败不影响业务
            trace('系统日志记录失败: ' . $e->getMessage(), 'error');
        }

        return $response;
    }

    /**
     * 记录请求日志
     *
     * @param Request $request
     * @param Response $response
     * @param float $startTime
     */
    private function logRequest(Request $request, Response $response, float $startTime)
    {
        // 计算执行时间
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // 获取用户信息
        $user = $request->user ?? null;
        $userId = $user['id'] ?? null;

        // 获取响应状态
        $statusCode = $response->getCode();
        $isError = $statusCode >= 400;

        // 排除不需要记录的路径
        $excludePaths = [
            '/api/auth/info',  // 频繁的用户信息查询
        ];

        $path = $request->pathinfo();
        foreach ($excludePaths as $excludePath) {
            if (str_contains($path, $excludePath)) {
                return;
            }
        }

        // 确定日志级别和分类
        $level = SystemLogger::LEVEL_INFO;
        $category = SystemLogger::CATEGORY_API;

        if ($isError) {
            $level = SystemLogger::LEVEL_ERROR;
        } elseif ($duration > 1000) {
            $level = SystemLogger::LEVEL_WARNING;  // 慢请求
        }

        // 构建日志消息
        $method = $request->method();
        $url = $request->url(true);
        $message = "{$method} {$url}";

        if ($isError) {
            $message .= " - 状态码: {$statusCode}";
        }

        // 构建上下文数据
        $context = [
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'params' => $this->getSafeParams($request),
        ];

        // 如果是错误响应，添加响应内容
        if ($isError && method_exists($response, 'getData')) {
            $context['response'] = $response->getData();
        }

        // 记录日志
        SystemLogger::log($level, $category, $message, $context, $userId);
    }

    /**
     * 获取安全的请求参数（过滤敏感信息）
     *
     * @param Request $request
     * @return array
     */
    private function getSafeParams(Request $request): array
    {
        $params = $request->param();

        // 过滤敏感字段
        $sensitiveKeys = ['password', 'old_password', 'new_password', 'confirm_password', 'token', 'secret'];
        foreach ($sensitiveKeys as $key) {
            if (isset($params[$key])) {
                $params[$key] = '******';
            }
        }

        return $params;
    }
}
