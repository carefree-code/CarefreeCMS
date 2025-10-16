<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Jwt;
use app\common\Logger;
use app\model\AdminUser;
use think\Request;
use think\facade\Db;

/**
 * 认证控制器
 */
class Auth extends BaseController
{
    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        // 获取请求参数
        $username = $request->post('username', '');
        $password = $request->post('password', '');

        // 验证参数
        if (empty($username) || empty($password)) {
            return Response::error('用户名和密码不能为空');
        }

        // 查询用户
        $user = AdminUser::where('username', $username)->find();

        if (!$user) {
            Logger::login($username, false, '用户不存在');
            return Response::error('用户不存在');
        }

        // 验证密码
        if (!$user->checkPassword($password)) {
            Logger::login($username, false, '密码错误');
            return Response::error('密码错误');
        }

        // 检查用户状态
        if ($user->status != 1) {
            Logger::login($username, false, '账号已被禁用');
            return Response::error('账号已被禁用');
        }

        // 更新登录信息
        $user->last_login_time = date('Y-m-d H:i:s');
        $user->last_login_ip = $request->ip();
        $user->save();

        // 生成token
        $token = Jwt::generate([
            'id'       => $user->id,
            'username' => $user->username,
            'role_id'  => $user->role_id,
        ]);

        // 记录登录成功日志
        Logger::login($username, true);

        // 返回用户信息和token
        return Response::success([
            'token'     => $token,
            'user_info' => [
                'id'         => $user->id,
                'username'   => $user->username,
                'real_name'  => $user->real_name,
                'email'      => $user->email,
                'avatar'     => $user->avatar,
                'role_id'    => $user->role_id,
            ]
        ], '登录成功');
    }

    /**
     * 退出登录
     */
    public function logout(Request $request)
    {
        // 记录退出登录日志
        $username = $request->user['username'] ?? '未知用户';
        Logger::logout($username);

        // JWT无状态，不需要服务端处理
        // 前端直接删除token即可
        return Response::success([], '退出成功');
    }

    /**
     * 获取当前登录用户信息
     */
    public function info(Request $request)
    {
        // 从中间件传递的用户信息获取ID
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        // 查询用户详细信息
        $user = AdminUser::with('role')->find($userId);

        if (!$user) {
            return Response::error('用户不存在');
        }

        return Response::success([
            'id'         => $user->id,
            'username'   => $user->username,
            'real_name'  => $user->real_name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'avatar'     => $user->avatar,
            'role_id'    => $user->role_id,
            'role_name'  => $user->role->name ?? '',
            'status'     => $user->status,
            'status_text'=> $user->status_text,
        ]);
    }

    /**
     * 修改密码
     */
    public function changePassword(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $oldPassword = $request->post('old_password', '');
        $newPassword = $request->post('new_password', '');

        if (!$userId) {
            return Response::unauthorized();
        }

        if (empty($oldPassword) || empty($newPassword)) {
            return Response::error('旧密码和新密码不能为空');
        }

        if (strlen($newPassword) < 6) {
            return Response::error('新密码长度不能少于6位');
        }

        // 查询用户
        $user = AdminUser::find($userId);
        if (!$user) {
            return Response::error('用户不存在');
        }

        // 验证旧密码
        if (!$user->checkPassword($oldPassword)) {
            return Response::error('旧密码错误');
        }

        // 更新密码
        $user->password = $newPassword;
        $user->save();

        // 记录修改密码日志
        Logger::changePassword();

        return Response::success([], '密码修改成功');
    }
}
