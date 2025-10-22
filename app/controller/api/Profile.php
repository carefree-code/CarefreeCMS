<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\AdminUser;
use think\Request;
use think\facade\Filesystem;

/**
 * 个人信息控制器
 */
class Profile extends BaseController
{
    /**
     * 获取个人信息
     */
    public function index(Request $request)
    {
        $user = AdminUser::with(['role'])->find($request->user['id']);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        // 隐藏密码字段
        $user->hidden(['password']);

        $userData = $user->toArray();

        // 生成完整的头像URL
        if (!empty($userData['avatar'])) {
            // 如果不是完整URL，则生成完整URL
            if (!str_starts_with($userData['avatar'], 'http')) {
                $siteUrl = \app\model\Config::getConfig('site_url', '');
                if (!empty($siteUrl)) {
                    $userData['avatar'] = rtrim($siteUrl, '/') . '/' . $userData['avatar'];
                } else {
                    $userData['avatar'] = $request->domain() . '/html/' . $userData['avatar'];
                }
            }
        }

        return Response::success($userData);
    }

    /**
     * 更新个人信息
     */
    public function update(Request $request)
    {
        $user = AdminUser::find($request->user['id']);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        // 可更新的字段
        $allowFields = ['real_name', 'email', 'phone'];
        $data = [];

        foreach ($allowFields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->param($field);
            }
        }

        // 验证邮箱格式
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return Response::error('邮箱格式不正确');
            }
        }

        // 验证手机号格式
        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
                return Response::error('手机号格式不正确');
            }
        }

        try {
            $user->save($data);
            return Response::success($user->toArray(), '个人信息更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 修改密码
     */
    public function updatePassword(Request $request)
    {
        $user = AdminUser::find($request->user['id']);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $oldPassword = $request->param('old_password');
        $newPassword = $request->param('new_password');
        $confirmPassword = $request->param('confirm_password');

        // 验证必填
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return Response::error('所有字段都不能为空');
        }

        // 验证旧密码
        if (!password_verify($oldPassword, $user->password)) {
            return Response::error('旧密码不正确');
        }

        // 验证新密码长度
        if (strlen($newPassword) < 6) {
            return Response::error('新密码长度不能少于6位');
        }

        // 验证两次密码是否一致
        if ($newPassword !== $confirmPassword) {
            return Response::error('两次输入的密码不一致');
        }

        // 验证新密码不能与旧密码相同
        if ($oldPassword === $newPassword) {
            return Response::error('新密码不能与旧密码相同');
        }

        try {
            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            $user->save();
            return Response::success([], '密码修改成功');
        } catch (\Exception $e) {
            return Response::error('修改失败：' . $e->getMessage());
        }
    }

    /**
     * 上传头像
     */
    public function uploadAvatar(Request $request)
    {
        $user = AdminUser::find($request->user['id']);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        // 获取上传的文件
        $file = $request->file('avatar');

        if (!$file) {
            return Response::error('请选择要上传的图片');
        }

        // 验证文件
        try {
            validate([
                'avatar' => [
                    'fileSize' => 2 * 1024 * 1024, // 2MB
                    'fileExt'  => 'jpg,jpeg,png,gif',
                ]
            ])->check(['avatar' => $file]);
        } catch (\think\exception\ValidateException $e) {
            return Response::error($e->getMessage());
        }

        try {
            // 获取文件信息
            $ext = strtolower($file->extension());

            // 生成日期目录
            $datePath = date('Y/m/d');
            $savePath = 'uploads/avatar/' . $datePath;

            // 生成唯一文件名
            $fileName = 'avatar_' . $request->user['id'] . '_' . date('YmdHis') . '.' . $ext;

            // 创建目录（如果不存在）- 保存到html目录
            $fullPath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . $savePath;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // 删除旧头像文件（如果存在）
            if ($user->avatar) {
                $oldAvatarPath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . $user->avatar;
                if (file_exists($oldAvatarPath)) {
                    @unlink($oldAvatarPath);
                }
            }

            // 移动文件
            $file->move($fullPath, $fileName);

            // 文件相对路径
            $filePath = $savePath . '/' . $fileName;

            // 更新用户头像
            $user->avatar = $filePath;
            $user->save();

            // 生成完整URL
            $siteUrl = \app\model\Config::getConfig('site_url', '');
            if (!empty($siteUrl)) {
                $avatarUrl = rtrim($siteUrl, '/') . '/' . $filePath;
            } else {
                $avatarUrl = $request->domain() . '/html/' . $filePath;
            }

            return Response::success([
                'avatar' => $filePath,
                'avatar_url' => $avatarUrl
            ], '头像上传成功');

        } catch (\Exception $e) {
            return Response::error('头像上传失败：' . $e->getMessage());
        }
    }
}
