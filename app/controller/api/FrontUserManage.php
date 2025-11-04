<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\FrontUser;
use app\model\UserPointLog;
use think\Request;
use think\facade\Db;

/**
 * 后台前台用户管理控制器
 */
class FrontUserManage extends BaseController
{
    /**
     * 用户列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status', '');
        $level = $request->get('level', '');
        $isVip = $request->get('is_vip', '');

        // 构建查询
        $query = FrontUser::withoutGlobalScope();

        // 搜索条件
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', '%' . $keyword . '%')
                  ->whereOr('nickname', 'like', '%' . $keyword . '%')
                  ->whereOr('email', 'like', '%' . $keyword . '%')
                  ->whereOr('phone', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($level !== '') {
            $query->where('level', $level);
        }

        if ($isVip !== '') {
            $query->where('is_vip', $isVip);
        }

        // 排序
        $query->order(['id' => 'desc']);

        // 分页
        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 创建用户
     */
    public function create(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['username'])) {
            return Response::error('用户名不能为空');
        }

        if (empty($data['password'])) {
            return Response::error('密码不能为空');
        }

        // 检查用户名是否已存在
        if (FrontUser::where('username', $data['username'])->find()) {
            return Response::error('用户名已存在');
        }

        // 检查邮箱是否已存在
        if (!empty($data['email']) && FrontUser::where('email', $data['email'])->find()) {
            return Response::error('邮箱已被使用');
        }

        // 检查手机号是否已存在
        if (!empty($data['phone']) && FrontUser::where('phone', $data['phone'])->find()) {
            return Response::error('手机号已被使用');
        }

        try {
            // 密码加密
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // 设置默认值
            if (!isset($data['nickname'])) {
                $data['nickname'] = $data['username'];
            }

            if (!isset($data['status'])) {
                $data['status'] = 1; // 默认启用
            }

            if (!isset($data['level'])) {
                $data['level'] = 0; // 默认等级0
            }

            if (!isset($data['points'])) {
                $data['points'] = 0; // 默认积分0
            }

            // 创建用户
            $user = FrontUser::create($data);

            return Response::success($user, '用户创建成功');
        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 用户详情
     */
    public function read($id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        return Response::success($user->toArray());
    }

    /**
     * 编辑用户
     */
    public function update(Request $request, $id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $data = $request->post();

        // 可编辑的字段
        $allowFields = [
            'nickname', 'real_name', 'email', 'phone', 'status',
            'level', 'points', 'is_vip', 'vip_expire_time'
        ];

        $updateData = [];
        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        try {
            $user->save($updateData);
            return Response::success($user->toArray(), '更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 禁用/启用用户
     */
    public function changeStatus(Request $request, $id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $status = $request->post('status');

        if (!in_array($status, [0, 1, 2])) {
            return Response::error('状态值不正确');
        }

        try {
            $user->status = $status;
            $user->save();

            $statusText = ['禁用', '正常', '待验证'];
            return Response::success([], '用户状态已设置为：' . $statusText[$status]);
        } catch (\Exception $e) {
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints(Request $request, $id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $points = $request->post('points', 0); // 正数为增加，负数为扣除
        $description = $request->post('description', '');

        if ($points == 0) {
            return Response::error('积分值不能为0');
        }

        try {
            if ($points > 0) {
                $user->addPoints($points, 'admin_add', $description ?: '管理员调整');
            } else {
                $user->deductPoints(abs($points), 'admin_deduct', $description ?: '管理员调整');
            }

            return Response::success([
                'new_points' => $user->points,
            ], '积分调整成功');
        } catch (\Exception $e) {
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 设置会员等级
     */
    public function setLevel(Request $request, $id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $level = $request->post('level', 1);

        if ($level < 1 || $level > 10) {
            return Response::error('等级范围应在1-10之间');
        }

        try {
            $user->level = $level;
            $user->save();

            return Response::success([
                'level' => $user->level,
                'level_text' => $user->level_text,
            ], '等级设置成功');
        } catch (\Exception $e) {
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 设置VIP
     */
    public function setVip(Request $request, $id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $isVip = $request->post('is_vip', 0);
        $vipExpireTime = $request->post('vip_expire_time', '');

        try {
            $user->is_vip = $isVip;

            if ($isVip == 1 && !empty($vipExpireTime)) {
                $user->vip_expire_time = $vipExpireTime;
            } else {
                $user->vip_expire_time = null;
            }

            $user->save();

            return Response::success([
                'is_vip' => $user->is_vip,
                'vip_expire_time' => $user->vip_expire_time,
            ], 'VIP设置成功');
        } catch (\Exception $e) {
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 删除用户（软删除）
     */
    public function delete($id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        try {
            $user->delete();
            return Response::success([], '用户已删除');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 恢复已删除的用户
     */
    public function restore($id)
    {
        $user = FrontUser::withTrashed()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        if (!$user->trashed()) {
            return Response::error('用户未被删除');
        }

        try {
            $user->restore();
            return Response::success([], '用户已恢复');
        } catch (\Exception $e) {
            return Response::error('恢复失败：' . $e->getMessage());
        }
    }

    /**
     * 彻底删除用户
     */
    public function forceDelete($id)
    {
        $user = FrontUser::withTrashed()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        try {
            // 删除相关数据
            Db::name('user_favorites')->where('user_id', $id)->delete();
            Db::name('user_likes')->where('user_id', $id)->delete();
            Db::name('user_read_history')->where('user_id', $id)->delete();
            Db::name('user_point_logs')->where('user_id', $id)->delete();
            Db::name('user_follows')->where('user_id', $id)->whereOr('follow_user_id', $id)->delete();
            Db::name('comments')->where('user_id', $id)->delete();

            // 彻底删除用户
            $user->force()->delete();

            return Response::success([], '用户已彻底删除');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 用户积分日志
     */
    public function pointLogs(Request $request, $id)
    {
        $user = FrontUser::withoutGlobalScope()->find($id);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $logs = UserPointLog::where('user_id', $id)
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return Response::success($logs);
    }

    /**
     * 用户统计信息
     */
    public function statistics()
    {
        $total = FrontUser::count();
        $activeUsers = FrontUser::where('status', 1)->count();
        $disabledUsers = FrontUser::where('status', 0)->count();
        $vipUsers = FrontUser::where('is_vip', 1)->count();

        // 今日新增
        $todayNew = FrontUser::whereTime('create_time', 'today')->count();

        // 本周新增
        $weekNew = FrontUser::whereTime('create_time', 'week')->count();

        // 本月新增
        $monthNew = FrontUser::whereTime('create_time', 'month')->count();

        return Response::success([
            'total'          => $total,
            'active'         => $activeUsers,
            'disabled'       => $disabledUsers,
            'vip'            => $vipUsers,
            'today_new'      => $todayNew,
            'week_new'       => $weekNew,
            'month_new'      => $monthNew,
        ]);
    }
}
