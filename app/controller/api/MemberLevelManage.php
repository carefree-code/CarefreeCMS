<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\MemberLevel;
use app\model\MemberLevelLog;
use app\model\FrontUser;
use app\service\MemberLevelService;
use think\Request;

/**
 * 会员等级管理控制器
 */
class MemberLevelManage extends BaseController
{
    /**
     * 等级配置列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status', '');

        $query = MemberLevel::order('level', 'asc');

        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 等级配置详情
     */
    public function read($id)
    {
        $level = MemberLevel::find($id);

        if (!$level) {
            return Response::error('等级配置不存在');
        }

        return Response::success($level);
    }

    /**
     * 创建等级配置
     */
    public function create(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (!isset($data['level']) || !isset($data['name'])) {
            return Response::error('等级和名称不能为空');
        }

        // 检查等级是否已存在
        $exists = MemberLevel::where('level', $data['level'])->find();
        if ($exists) {
            return Response::error('该等级已存在');
        }

        try {
            // 处理privileges字段
            if (isset($data['privileges']) && is_array($data['privileges'])) {
                $data['privileges'] = json_encode($data['privileges']);
            }

            $level = MemberLevel::create($data);

            return Response::success($level, '创建成功');
        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新等级配置
     */
    public function update(Request $request, $id)
    {
        $level = MemberLevel::find($id);

        if (!$level) {
            return Response::error('等级配置不存在');
        }

        $data = $request->post();

        // 如果修改了等级值，检查新等级是否已被使用
        if (isset($data['level']) && $data['level'] != $level->level) {
            $exists = MemberLevel::where('level', $data['level'])->where('id', '<>', $id)->find();
            if ($exists) {
                return Response::error('该等级已被使用');
            }
        }

        try {
            // 处理privileges字段
            if (isset($data['privileges']) && is_array($data['privileges'])) {
                $data['privileges'] = json_encode($data['privileges']);
            }

            $level->save($data);

            return Response::success($level, '更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除等级配置
     */
    public function delete($id)
    {
        $level = MemberLevel::find($id);

        if (!$level) {
            return Response::error('等级配置不存在');
        }

        // 检查是否有用户使用该等级
        $userCount = FrontUser::where('level', $level->level)->count();
        if ($userCount > 0) {
            return Response::error("有 {$userCount} 个用户正在使用该等级，无法删除");
        }

        try {
            $level->delete();
            return Response::success(null, '删除成功');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 升级日志列表
     */
    public function logIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $userId = $request->get('user_id', '');
        $upgradeType = $request->get('upgrade_type', '');

        $query = MemberLevelLog::with(['user', 'operator'])->order('id', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($upgradeType) {
            $query->where('upgrade_type', $upgradeType);
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 执行批量升级
     */
    public function batchUpgrade(Request $request)
    {
        $limit = $request->post('limit', 100);

        try {
            $result = MemberLevelService::batchCheckAndUpgrade($limit);

            return Response::success($result, "批量升级完成，共检查 {$result['total']} 个用户，成功升级 {$result['upgraded']} 个");
        } catch (\Exception $e) {
            return Response::error('批量升级失败：' . $e->getMessage());
        }
    }

    /**
     * 检查单个用户等级
     */
    public function checkUser(Request $request, $userId)
    {
        try {
            $result = MemberLevelService::checkAndUpgradeUser($userId);

            return Response::success($result, $result['message']);
        } catch (\Exception $e) {
            return Response::error('检查失败：' . $e->getMessage());
        }
    }

    /**
     * 获取用户等级进度
     */
    public function userProgress(Request $request, $userId)
    {
        try {
            $user = FrontUser::find($userId);

            if (!$user) {
                return Response::error('用户不存在');
            }

            $progress = MemberLevel::getUserLevelProgress($user);

            return Response::success($progress);
        } catch (\Exception $e) {
            return Response::error('获取失败：' . $e->getMessage());
        }
    }

    /**
     * 统计信息
     */
    public function statistics()
    {
        try {
            // 等级分布统计
            $levelDistribution = [];
            $levels = MemberLevel::getAllEnabled();

            foreach ($levels as $level) {
                $count = FrontUser::where('level', $level['level'])->count();
                $levelDistribution[] = [
                    'level' => $level['level'],
                    'name' => $level['name'],
                    'count' => $count,
                ];
            }

            // 最近升级记录
            $recentUpgrades = MemberLevelLog::with(['user'])
                ->order('id', 'desc')
                ->limit(10)
                ->select()
                ->toArray();

            // 今日升级数
            $todayCount = MemberLevelLog::whereTime('create_time', 'today')->count();

            // 本周升级数
            $weekCount = MemberLevelLog::whereTime('create_time', 'week')->count();

            // 本月升级数
            $monthCount = MemberLevelLog::whereTime('create_time', 'month')->count();

            return Response::success([
                'level_distribution' => $levelDistribution,
                'recent_upgrades' => $recentUpgrades,
                'today_count' => $todayCount,
                'week_count' => $weekCount,
                'month_count' => $monthCount,
            ]);
        } catch (\Exception $e) {
            return Response::error('获取统计失败：' . $e->getMessage());
        }
    }
}
