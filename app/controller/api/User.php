<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\AdminUser;
use app\model\AdminRole;
use think\Request;

/**
 * 用户管理控制器
 */
class User extends BaseController
{
    /**
     * 用户列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $username = $request->get('username', '');
        $roleId = $request->get('role_id', '');
        $status = $request->get('status', '');

        // 构建查询
        $query = AdminUser::with(['role']);

        // 搜索条件
        if (!empty($username)) {
            $query->where('username', 'like', '%' . $username . '%');
        }
        if ($roleId !== '') {
            $query->where('role_id', $roleId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 排序
        $query->order(['id' => 'desc']);

        // 分页
        $list = $query->page($page, $pageSize)->select();
        $total = AdminUser::when(!empty($username), function($query) use ($username) {
            $query->where('username', 'like', '%' . $username . '%');
        })
        ->when($roleId !== '', function($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })
        ->when($status !== '', function($query) use ($status) {
            $query->where('status', $status);
        })
        ->count();

        return Response::paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 用户详情
     */
    public function read($id)
    {
        $user = AdminUser::with(['role'])->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        return Response::success($user->toArray());
    }

    /**
     * 创建用户
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['username'])) {
            return Response::error('用户名不能为空');
        }
        if (empty($data['password'])) {
            return Response::error('密码不能为空');
        }
        if (empty($data['role_id'])) {
            return Response::error('请选择角色');
        }

        // 检查用户名是否已存在
        $exists = AdminUser::where('username', $data['username'])->find();
        if ($exists) {
            return Response::error('用户名已存在');
        }

        // 检查角色是否存在
        $role = AdminRole::find($data['role_id']);
        if (!$role) {
            return Response::error('角色不存在');
        }

        // 设置默认值
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }

        try {
            $user = AdminUser::create($data);
            return Response::success(['id' => $user->id], '用户创建成功');
        } catch (\Exception $e) {
            return Response::error('用户创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新用户
     */
    public function update(Request $request, $id)
    {
        $user = AdminUser::find($id);
        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $data = $request->post();

        // 如果修改角色，检查角色是否存在
        if (isset($data['role_id'])) {
            $role = AdminRole::find($data['role_id']);
            if (!$role) {
                return Response::error('角色不存在');
            }
        }

        // 如果密码为空，则不更新密码
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        try {
            $user->save($data);
            return Response::success([], '用户更新成功');
        } catch (\Exception $e) {
            return Response::error('用户更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除用户
     */
    public function delete($id)
    {
        $user = AdminUser::find($id);
        if (!$user) {
            return Response::notFound('用户不存在');
        }

        // 不能删除ID为1的超级管理员
        if ($id == 1) {
            return Response::error('不能删除超级管理员');
        }

        try {
            $user->delete();
            return Response::success([], '用户删除成功');
        } catch (\Exception $e) {
            return Response::error('用户删除失败：' . $e->getMessage());
        }
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request, $id)
    {
        $user = AdminUser::find($id);
        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $password = $request->post('password');
        if (empty($password)) {
            return Response::error('新密码不能为空');
        }

        try {
            $user->password = $password;
            $user->save();
            return Response::success([], '密码重置成功');
        } catch (\Exception $e) {
            return Response::error('密码重置失败：' . $e->getMessage());
        }
    }
}
