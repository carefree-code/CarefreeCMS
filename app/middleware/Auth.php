<?php

namespace app\middleware;

use app\common\Jwt;
use app\common\Response;
use Closure;
use think\Request;

/**
 * JWT认证中间件
 */
class Auth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 从请求头获取token
        $token = $request->header('authorization', '');

        // 移除 "Bearer " 前缀
        if (stripos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        // 验证token是否存在
        if (empty($token)) {
            return Response::unauthorized('请提供访问令牌');
        }

        // 验证token
        $userData = Jwt::verify($token);

        if ($userData === false) {
            return Response::unauthorized('令牌无效或已过期');
        }

        // 将用户信息存储到请求对象中（提供多个属性名以保持向后兼容）
        $request->user = $userData;
        $request->adminInfo = $userData;  // 兼容旧代码中使用 adminInfo 的情况
        $request->userInfo = $userData;   // 提供另一个语义化的属性名

        // 执行请求
        $response = $next($request);

        // 检查token是否即将过期，如果是则生成新token并在响应头中返回
        if (Jwt::shouldRefresh($token)) {
            $newToken = Jwt::refresh($token);
            if ($newToken !== false) {
                // 在响应头中添加新token
                $response->header(['X-New-Token' => $newToken]);
            }
        }

        return $response;
    }
}
