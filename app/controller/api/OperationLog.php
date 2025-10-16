<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\OperationLog as OperationLogModel;
use think\Request;

class OperationLog extends BaseController
{
    /**
     * 获取操作日志列表
     */
    public function index(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $pageSize = (int)$request->get('pageSize', 20);
        $module = $request->get('module', '');
        $action = $request->get('action', '');
        $username = $request->get('username', '');
        $status = $request->get('status', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        $query = OperationLogModel::order('create_time', 'desc');

        // 搜索条件
        if (!empty($module)) {
            $query->where('module', $module);
        }
        if (!empty($action)) {
            $query->where('action', $action);
        }
        if (!empty($username)) {
            $query->where('username', 'like', '%' . $username . '%');
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if (!empty($startDate)) {
            $query->where('create_time', '>=', $startDate . ' 00:00:00');
        }
        if (!empty($endDate)) {
            $query->where('create_time', '<=', $endDate . ' 23:59:59');
        }

        // 先获取总数
        $total = $query->count();

        // 分页查询
        $list = $query->page($page, $pageSize)->select()->toArray();

        return Response::paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取日志详情
     */
    public function read($id)
    {
        $log = OperationLogModel::find($id);
        if (!$log) {
            return Response::notFound('日志不存在');
        }

        return Response::success($log->toArray());
    }

    /**
     * 获取模块列表
     */
    public function modules()
    {
        $modules = OperationLogModel::getModuleNames();
        $data = [];
        foreach ($modules as $key => $name) {
            $data[] = [
                'value' => $key,
                'label' => $name
            ];
        }
        return Response::success($data);
    }

    /**
     * 获取操作类型列表
     */
    public function actions()
    {
        $actions = OperationLogModel::getActionNames();
        $data = [];
        foreach ($actions as $key => $name) {
            $data[] = [
                'value' => $key,
                'label' => $name
            ];
        }
        return Response::success($data);
    }

    /**
     * 清空日志（仅保留最近N天）
     */
    public function clear(Request $request)
    {
        $days = (int)$request->post('days', 30);
        if ($days < 7) {
            return Response::error('至少保留7天的日志');
        }

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $count = OperationLogModel::where('create_time', '<', $date)->delete();

        return Response::success(['count' => $count], "已清空{$days}天前的日志，共{$count}条");
    }

    /**
     * 批量删除日志
     */
    public function batchDelete(Request $request)
    {
        $ids = $request->param('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return Response::error('请选择要删除的日志');
        }

        try {
            $count = OperationLogModel::destroy($ids);
            return Response::success(['count' => $count], "成功删除{$count}条日志");
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }
}
