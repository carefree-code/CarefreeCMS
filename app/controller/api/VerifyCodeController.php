<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\service\VerifyCodeService;
use think\Request;

/**
 * 验证码控制器
 */
class VerifyCodeController extends BaseController
{
    /**
     * 发送手机验证码
     */
    public function sendPhoneCode(Request $request)
    {
        $phone = $request->post('phone', '');
        $scene = $request->post('scene', 'register');

        if (empty($phone)) {
            return Response::error('请输入手机号');
        }

        // 验证场景
        $allowedScenes = ['register', 'login', 'reset', 'bind'];
        if (!in_array($scene, $allowedScenes)) {
            return Response::error('场景参数不正确');
        }

        $result = VerifyCodeService::sendPhoneCode($phone, $scene);

        if ($result['success']) {
            return Response::success($result['data'] ?? [], $result['message']);
        } else {
            return Response::error($result['message']);
        }
    }

    /**
     * 发送邮箱验证码
     */
    public function sendEmailCode(Request $request)
    {
        $email = $request->post('email', '');
        $scene = $request->post('scene', 'register');

        if (empty($email)) {
            return Response::error('请输入邮箱');
        }

        // 验证场景
        $allowedScenes = ['register', 'login', 'reset', 'bind'];
        if (!in_array($scene, $allowedScenes)) {
            return Response::error('场景参数不正确');
        }

        $result = VerifyCodeService::sendEmailCode($email, $scene);

        if ($result['success']) {
            return Response::success($result['data'] ?? [], $result['message']);
        } else {
            return Response::error($result['message']);
        }
    }

    /**
     * 验证手机验证码（测试用）
     */
    public function verifyPhoneCode(Request $request)
    {
        $phone = $request->post('phone', '');
        $code = $request->post('code', '');
        $scene = $request->post('scene', 'register');

        if (empty($phone) || empty($code)) {
            return Response::error('请输入完整信息');
        }

        $result = VerifyCodeService::verifyPhoneCode($phone, $code, $scene, false);

        if ($result) {
            return Response::success([], '验证通过');
        } else {
            return Response::error('验证码不正确或已过期');
        }
    }

    /**
     * 验证邮箱验证码（测试用）
     */
    public function verifyEmailCode(Request $request)
    {
        $email = $request->post('email', '');
        $code = $request->post('code', '');
        $scene = $request->post('scene', 'register');

        if (empty($email) || empty($code)) {
            return Response::error('请输入完整信息');
        }

        $result = VerifyCodeService::verifyEmailCode($email, $code, $scene, false);

        if ($result) {
            return Response::success([], '验证通过');
        } else {
            return Response::error('验证码不正确或已过期');
        }
    }
}
