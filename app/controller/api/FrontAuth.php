<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Jwt;
use app\common\Logger;
use app\model\FrontUser;
use think\Request;
use think\facade\Db;
use think\facade\Validate;

/**
 * 前台用户认证控制器
 */
class FrontAuth extends BaseController
{
    /**
     * 用户注册
     */
    public function register(Request $request)
    {
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        $email = $request->post('email', '');
        $nickname = $request->post('nickname', '');

        // 验证参数
        $validate = Validate::rule([
            'username' => 'require|alphaNum|length:3,20',
            'password' => 'require|length:6,20',
            'email'    => 'require|email',
        ])->message([
            'username.require'   => '用户名不能为空',
            'username.alphaNum'  => '用户名只能包含字母和数字',
            'username.length'    => '用户名长度为3-20个字符',
            'password.require'   => '密码不能为空',
            'password.length'    => '密码长度为6-20个字符',
            'email.require'      => '邮箱不能为空',
            'email.email'        => '邮箱格式不正确',
        ]);

        if (!$validate->check(['username' => $username, 'password' => $password, 'email' => $email])) {
            return Response::error($validate->getError());
        }

        // 检查用户名是否已存在
        if (FrontUser::where('username', $username)->find()) {
            return Response::error('用户名已存在');
        }

        // 检查邮箱是否已存在
        if (FrontUser::where('email', $email)->find()) {
            return Response::error('邮箱已被注册');
        }

        Db::startTrans();
        try {
            // 创建用户
            $user = FrontUser::create([
                'username' => $username,
                'password' => $password,
                'email'    => $email,
                'nickname' => $nickname ?: $username,
                'status'   => 1, // 直接激活，如果需要邮箱验证则设为2
            ]);

            // 注册奖励积分
            $user->addPoints(10, 'register', '注册奖励');

            Db::commit();

            return Response::success([
                'user_id'  => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname,
            ], '注册成功');

        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('注册失败：' . $e->getMessage());
        }
    }

    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        $username = $request->post('username', '');
        $password = $request->post('password', '');

        // 验证参数
        if (empty($username) || empty($password)) {
            return Response::error('用户名和密码不能为空');
        }

        // 查询用户（支持用户名或邮箱登录）
        $user = FrontUser::where('username', $username)
            ->whereOr('email', $username)
            ->find();

        if (!$user) {
            return Response::error('用户不存在');
        }

        // 验证密码
        if (!$user->checkPassword($password)) {
            return Response::error('密码错误');
        }

        // 检查用户状态
        if ($user->status == 0) {
            return Response::error('账号已被禁用');
        }

        if ($user->status == 2) {
            return Response::error('账号未激活，请先验证邮箱');
        }

        // 更新登录信息
        $user->last_login_time = date('Y-m-d H:i:s');
        $user->last_login_ip = $request->ip();
        $user->login_count += 1;
        $user->save();

        // 每日首次登录奖励积分
        $lastLogin = $user->last_login_time ? strtotime($user->last_login_time) : 0;
        if (date('Y-m-d', $lastLogin) < date('Y-m-d')) {
            $user->addPoints(5, 'login', '每日登录奖励');
        }

        // 生成token
        $token = Jwt::generate([
            'id'       => $user->id,
            'username' => $user->username,
            'type'     => 'front_user', // 标识前台用户
        ]);

        // 返回用户信息和token
        return Response::success([
            'token'     => $token,
            'user_info' => [
                'id'              => $user->id,
                'username'        => $user->username,
                'nickname'        => $user->nickname,
                'email'           => $user->email,
                'avatar'          => $user->avatar,
                'points'          => $user->points,
                'level'           => $user->level,
                'level_text'      => $user->level_text,
                'is_vip'          => $user->is_vip,
                'email_verified'  => $user->email_verified,
            ]
        ], '登录成功');
    }

    /**
     * 退出登录
     */
    public function logout(Request $request)
    {
        // JWT无状态，前端直接删除token即可
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
        $user = FrontUser::find($userId);

        if (!$user) {
            return Response::error('用户不存在');
        }

        return Response::success([
            'id'              => $user->id,
            'username'        => $user->username,
            'nickname'        => $user->nickname,
            'real_name'       => $user->real_name,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'avatar'          => $user->avatar,
            'gender'          => $user->gender,
            'gender_text'     => $user->gender_text,
            'birthday'        => $user->birthday,
            'province'        => $user->province,
            'city'            => $user->city,
            'signature'       => $user->signature,
            'bio'             => $user->bio,
            'points'          => $user->points,
            'level'           => $user->level,
            'level_text'      => $user->level_text,
            'article_count'   => $user->article_count,
            'comment_count'   => $user->comment_count,
            'favorite_count'  => $user->favorite_count,
            'follower_count'  => $user->follower_count,
            'following_count' => $user->following_count,
            'email_verified'  => $user->email_verified,
            'phone_verified'  => $user->phone_verified,
            'status'          => $user->status,
            'status_text'     => $user->status_text,
            'is_vip'          => $user->is_vip,
            'vip_expire_time' => $user->vip_expire_time,
            'last_login_time' => $user->last_login_time,
            'create_time'     => $user->create_time,
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
        $confirmPassword = $request->post('confirm_password', '');

        if (!$userId) {
            return Response::unauthorized();
        }

        // 验证参数
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return Response::error('所有字段都不能为空');
        }

        if (strlen($newPassword) < 6) {
            return Response::error('新密码长度不能少于6位');
        }

        if ($newPassword !== $confirmPassword) {
            return Response::error('两次输入的密码不一致');
        }

        if ($oldPassword === $newPassword) {
            return Response::error('新密码不能与旧密码相同');
        }

        // 查询用户
        $user = FrontUser::find($userId);
        if (!$user) {
            return Response::error('用户不存在');
        }

        // 验证旧密码
        if (!$user->checkPassword($oldPassword)) {
            return Response::error('旧密码错误');
        }

        // 更新密码（模型的修改器会自动加密）
        $user->password = $newPassword;
        $user->save();

        return Response::success([], '密码修改成功');
    }

    /**
     * 发送邮箱验证邮件
     */
    public function sendVerifyEmail(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $user = FrontUser::find($userId);
        if (!$user) {
            return Response::error('用户不存在');
        }

        if ($user->email_verified) {
            return Response::error('邮箱已验证');
        }

        // 生成验证令牌
        $token = $user->generateEmailVerifyToken();

        // TODO: 发送验证邮件（需要配置邮件服务）
        // $verifyUrl = $request->domain() . '/api/front-auth/verify-email?token=' . $token;
        // Mail::send($user->email, '邮箱验证', $verifyUrl);

        return Response::success([
            'token' => $token, // 仅用于测试，生产环境不应返回
        ], '验证邮件已发送');
    }

    /**
     * 验证邮箱
     */
    public function verifyEmail(Request $request)
    {
        $token = $request->get('token', '');

        if (empty($token)) {
            return Response::error('验证令牌不能为空');
        }

        $user = FrontUser::where('email_verify_token', $token)->find();

        if (!$user) {
            return Response::error('无效的验证令牌');
        }

        if ($user->verifyEmail($token)) {
            return Response::success([], '邮箱验证成功');
        } else {
            return Response::error('验证令牌已过期');
        }
    }

    /**
     * 发送密码重置邮件
     */
    public function sendResetEmail(Request $request)
    {
        $email = $request->post('email', '');

        if (empty($email)) {
            return Response::error('邮箱不能为空');
        }

        $user = FrontUser::where('email', $email)->find();

        if (!$user) {
            return Response::error('该邮箱未注册');
        }

        // 生成重置令牌
        $token = $user->generateResetToken();

        // TODO: 发送重置邮件（需要配置邮件服务）
        // $resetUrl = $request->domain() . '/reset-password?token=' . $token;
        // Mail::send($user->email, '密码重置', $resetUrl);

        return Response::success([
            'token' => $token, // 仅用于测试，生产环境不应返回
        ], '重置邮件已发送');
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request)
    {
        $token = $request->post('token', '');
        $newPassword = $request->post('new_password', '');
        $confirmPassword = $request->post('confirm_password', '');

        // 验证参数
        if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
            return Response::error('所有字段都不能为空');
        }

        if (strlen($newPassword) < 6) {
            return Response::error('新密码长度不能少于6位');
        }

        if ($newPassword !== $confirmPassword) {
            return Response::error('两次输入的密码不一致');
        }

        // 查找用户
        $user = FrontUser::where('reset_token', $token)->find();

        if (!$user) {
            return Response::error('无效的重置令牌');
        }

        // 检查令牌是否过期
        if ($user->reset_token_expire && strtotime($user->reset_token_expire) < time()) {
            return Response::error('重置令牌已过期');
        }

        // 重置密码
        $user->password = $newPassword;
        $user->reset_token = null;
        $user->reset_token_expire = null;
        $user->save();

        return Response::success([], '密码重置成功');
    }
}
