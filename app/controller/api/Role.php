<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\AdminRole;
use app\model\AdminUser;
use think\Request;

/**
 * 角色管理控制器
 */
class Role extends BaseController
{
    /**
     * 角色列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $name = $request->get('name', '');
        $status = $request->get('status', '');

        // 构建查询
        $query = AdminRole::withCount(['users']);

        // 搜索条件
        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 排序
        $query->order(['sort' => 'asc', 'id' => 'asc']);

        // 分页
        $list = $query->page($page, $pageSize)->select();
        $total = AdminRole::when(!empty($name), function($query) use ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        })
        ->when($status !== '', function($query) use ($status) {
            $query->where('status', $status);
        })
        ->count();

        return Response::paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 获取所有角色（不分页）
     */
    public function all(Request $request)
    {
        $status = $request->get('status', 1);

        $query = AdminRole::order(['sort' => 'asc', 'id' => 'asc']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        $list = $query->select();

        return Response::success($list->toArray());
    }

    /**
     * 角色详情
     */
    public function read($id)
    {
        $role = AdminRole::withCount(['users'])->find($id);

        if (!$role) {
            return Response::notFound('角色不存在');
        }

        return Response::success($role->toArray());
    }

    /**
     * 创建角色
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['name'])) {
            return Response::error('角色名称不能为空');
        }

        // 检查角色名称是否已存在
        $exists = AdminRole::where('name', $data['name'])->find();
        if ($exists) {
            return Response::error('角色名称已存在');
        }

        // 设置默认值
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }
        if (!isset($data['sort'])) {
            $data['sort'] = 0;
        }

        try {
            $role = AdminRole::create($data);
            return Response::success(['id' => $role->id], '角色创建成功');
        } catch (\Exception $e) {
            return Response::error('角色创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新角色
     */
    public function update(Request $request, $id)
    {
        $role = AdminRole::find($id);
        if (!$role) {
            return Response::notFound('角色不存在');
        }

        // 不能修改ID为1的超级管理员角色
        if ($id == 1) {
            return Response::error('不能修改超级管理员角色');
        }

        $data = $request->post();

        // 检查角色名称是否与其他角色重复
        if (isset($data['name'])) {
            $exists = AdminRole::where('name', $data['name'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return Response::error('角色名称已存在');
            }
        }

        try {
            $role->save($data);
            return Response::success([], '角色更新成功');
        } catch (\Exception $e) {
            return Response::error('角色更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除角色
     */
    public function delete($id)
    {
        $role = AdminRole::find($id);
        if (!$role) {
            return Response::notFound('角色不存在');
        }

        // 不能删除ID为1的超级管理员角色
        if ($id == 1) {
            return Response::error('不能删除超级管理员角色');
        }

        // 检查是否有用户关联此角色
        $userCount = AdminUser::where('role_id', $id)->count();
        if ($userCount > 0) {
            return Response::error('该角色下有 ' . $userCount . ' 个用户，无法删除');
        }

        try {
            $role->delete();
            return Response::success([], '角色删除成功');
        } catch (\Exception $e) {
            return Response::error('角色删除失败：' . $e->getMessage());
        }
    }
}
