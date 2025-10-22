<?php

namespace app\common;

/**
 * 统一响应格式类
 */
class Response
{
    /**
     * 成功响应
     * @param mixed $data 返回的数据
     * @param string $message 提示信息
     * @param int $code 业务状态码
     * @return \think\response\Json
     */
    public static function success($data = [], string $message = 'success', int $code = 200)
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], 200);  // HTTP 200 OK
    }

    /**
     * 失败响应
     * @param string $message 错误信息
     * @param int $code 业务状态码（同时作为HTTP状态码）
     * @param mixed $data 返回的数据
     * @return \think\response\Json
     */
    public static function error(string $message = 'error', int $code = 400, $data = [])
    {
        // 确定HTTP状态码：业务code在合法的HTTP状态码范围内则使用，否则默认400
        $httpCode = ($code >= 400 && $code < 600) ? $code : 400;

        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], $httpCode);
    }

    /**
     * 分页数据响应
     * @param array $list 数据列表
     * @param int $total 总数
     * @param int $page 当前页码
     * @param int $pageSize 每页数量
     * @param string $message 提示信息
     * @return \think\response\Json
     */
    public static function paginate(array $list, int $total, int $page, int $pageSize, string $message = 'success')
    {
        return json([
            'code' => 200,
            'message' => $message,
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize)
            ],
            'timestamp' => time()
        ], 200);  // HTTP 200 OK
    }

    /**
     * 未授权响应
     * @param string $message 提示信息
     * @return \think\response\Json
     */
    public static function unauthorized(string $message = '未授权或登录已过期')
    {
        return json([
            'code' => 401,
            'message' => $message,
            'data' => [],
            'timestamp' => time()
        ], 401);
    }

    /**
     * 无权限响应
     * @param string $message 提示信息
     * @return \think\response\Json
     */
    public static function forbidden(string $message = '无权限访问')
    {
        return json([
            'code' => 403,
            'message' => $message,
            'data' => [],
            'timestamp' => time()
        ], 403);
    }

    /**
     * 未找到响应
     * @param string $message 提示信息
     * @return \think\response\Json
     */
    public static function notFound(string $message = '资源不存在')
    {
        return json([
            'code' => 404,
            'message' => $message,
            'data' => [],
            'timestamp' => time()
        ], 404);
    }
}
