<?php

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 跨域请求支持中间件（安全加固版）
 */
class Cors
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
        // 获取请求来源
        $origin = $request->header('origin', '');

        // 从环境变量读取允许的来源白名单
        $allowedOriginsConfig = env('CORS_ALLOWED_ORIGINS', '');
        $allowedOrigins = !empty($allowedOriginsConfig)
            ? array_map('trim', explode(',', $allowedOriginsConfig))
            : [];

        $headers = [
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-Token',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Expose-Headers'    => 'Content-Length, Content-Type',
        ];

        // 安全的CORS配置
        if (!empty($allowedOrigins) && in_array($origin, $allowedOrigins)) {
            // 白名单模式：允许指定来源并支持凭证
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
        } elseif (empty($allowedOrigins)) {
            // 兼容模式：允许所有来源，但不支持凭证（避免浏览器策略冲突）
            $headers['Access-Control-Allow-Origin'] = '*';
            // 注意：当 Allow-Origin 为 * 时，不能设置 Allow-Credentials
        } elseif (!empty($origin) && (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false)) {
            // 开发环境兼容：允许所有localhost和127.0.0.1的请求
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
        } else {
            // 拒绝不在白名单中的来源，但仍设置头以避免浏览器报错
            // 在开发调试时，可以临时允许所有来源
            if (env('APP_DEBUG', false)) {
                $headers['Access-Control-Allow-Origin'] = $origin ?: '*';
                $headers['Access-Control-Allow-Credentials'] = $origin ? 'true' : 'false';
            }
        }

        // 处理OPTIONS预检请求
        if ($request->method(true) == 'OPTIONS') {
            $response = Response::create('', 'html', 204);
            $response->header($headers);
            return $response;
        }

        // 处理正常请求
        $response = $next($request);
        $response->header($headers);

        return $response;
    }
}
