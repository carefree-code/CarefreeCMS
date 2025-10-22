<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\BaseController as Base;
use think\response\Json;

/**
 * API控制器基础类
 */
abstract class BaseController extends Base
{
    /**
     * 返回成功响应
     * @param mixed $data 响应数据
     * @param string $message 提示信息
     * @param int $code 状态码
     * @return Json
     */
    protected function success($data = null, string $message = '操作成功', int $code = 200): Json
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'time' => time(),
        ], 200);  // HTTP 200 OK
    }

    /**
     * 返回错误响应
     * @param string $message 错误信息
     * @param int $code 错误码（同时作为HTTP状态码）
     * @param mixed $data 附加数据
     * @return Json
     */
    protected function error(string $message = '操作失败', int $code = 400, $data = null): Json
    {
        // 确定HTTP状态码：业务code在合法的HTTP状态码范围内则使用，否则默认400
        $httpCode = ($code >= 400 && $code < 600) ? $code : 400;

        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'time' => time(),
        ], $httpCode);
    }
}
