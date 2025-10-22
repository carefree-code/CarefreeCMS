<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\service\SystemLogger;
use app\model\SystemLog;
use app\model\LoginLog;
use app\model\SecurityLog;
use think\Request;

/**
 * 日志管理控制器
 */
class LogController extends BaseController
{
    /**
     * 获取系统日志列表
     */
    public function getSystemLogs(Request $request)
    {
        $page = $request->param('page', 1);
        $perPage = $request->param('per_page', 20);

        $where = [];
        if ($request->has('level')) {
            $where['level'] = $request->param('level');
        }
        if ($request->has('category')) {
            $where['category'] = $request->param('category');
        }
        if ($request->has('user_id')) {
            $where['user_id'] = $request->param('user_id');
        }
        if ($request->has('ip')) {
            $where['ip'] = $request->param('ip');
        }
        if ($request->has('keyword')) {
            $where['keyword'] = $request->param('keyword');
        }
        if ($request->has('start_time')) {
            $where['start_time'] = $request->param('start_time');
        }
        if ($request->has('end_time')) {
            $where['end_time'] = $request->param('end_time');
        }

        $result = SystemLog::getList($where, $page, $perPage);

        return $this->success($result);
    }

    /**
     * 获取登录日志列表
     */
    public function getLoginLogs(Request $request)
    {
        $page = $request->param('page', 1);
        $perPage = $request->param('per_page', 20);

        $where = [];
        if ($request->has('username')) {
            $where['username'] = $request->param('username');
        }
        if ($request->has('status')) {
            $where['status'] = $request->param('status');
        }
        if ($request->has('ip')) {
            $where['ip'] = $request->param('ip');
        }
        if ($request->has('start_time')) {
            $where['start_time'] = $request->param('start_time');
        }
        if ($request->has('end_time')) {
            $where['end_time'] = $request->param('end_time');
        }

        $result = LoginLog::getList($where, $page, $perPage);

        return $this->success($result);
    }

    /**
     * 获取安全日志列表
     */
    public function getSecurityLogs(Request $request)
    {
        $page = $request->param('page', 1);
        $perPage = $request->param('per_page', 20);

        $where = [];
        if ($request->has('type')) {
            $where['type'] = $request->param('type');
        }
        if ($request->has('level')) {
            $where['level'] = $request->param('level');
        }
        if ($request->has('ip')) {
            $where['ip'] = $request->param('ip');
        }
        if ($request->has('is_blocked')) {
            $where['is_blocked'] = $request->param('is_blocked');
        }
        if ($request->has('start_time')) {
            $where['start_time'] = $request->param('start_time');
        }
        if ($request->has('end_time')) {
            $where['end_time'] = $request->param('end_time');
        }

        $result = SecurityLog::getList($where, $page, $perPage);

        return $this->success($result);
    }

    /**
     * 获取系统日志统计
     */
    public function getSystemLogStats(Request $request)
    {
        $startTime = $request->param('start_time', '');
        $endTime = $request->param('end_time', '');

        $stats = SystemLogger::getStatistics($startTime, $endTime);

        return $this->success($stats);
    }

    /**
     * 获取登录日志统计
     */
    public function getLoginLogStats(Request $request)
    {
        $startTime = $request->param('start_time', '');
        $endTime = $request->param('end_time', '');

        $stats = SystemLogger::getLoginStatistics($startTime, $endTime);

        return $this->success($stats);
    }

    /**
     * 获取高危IP列表
     */
    public function getHighRiskIps(Request $request)
    {
        $limit = $request->param('limit', 10);

        $ips = SecurityLog::getHighRiskIps($limit);

        return $this->success($ips);
    }

    /**
     * 删除系统日志
     */
    public function deleteSystemLog(Request $request, $id)
    {
        if (SystemLog::destroy($id)) {
            return $this->success(null, '删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 批量删除系统日志
     */
    public function batchDeleteSystemLogs(Request $request)
    {
        $ids = $request->param('ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的日志');
        }

        $count = SystemLog::batchDelete($ids);

        return $this->success(['count' => $count], "成功删除 {$count} 条日志");
    }

    /**
     * 删除登录日志
     */
    public function deleteLoginLog(Request $request, $id)
    {
        if (LoginLog::destroy($id)) {
            return $this->success(null, '删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 批量删除登录日志
     */
    public function batchDeleteLoginLogs(Request $request)
    {
        $ids = $request->param('ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的日志');
        }

        $count = LoginLog::batchDelete($ids);

        return $this->success(['count' => $count], "成功删除 {$count} 条日志");
    }

    /**
     * 删除安全日志
     */
    public function deleteSecurityLog(Request $request, $id)
    {
        if (SecurityLog::destroy($id)) {
            return $this->success(null, '删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 批量删除安全日志
     */
    public function batchDeleteSecurityLogs(Request $request)
    {
        $ids = $request->param('ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的日志');
        }

        $count = SecurityLog::batchDelete($ids);

        return $this->success(['count' => $count], "成功删除 {$count} 条日志");
    }

    /**
     * 清理旧日志
     */
    public function cleanOldLogs(Request $request)
    {
        $days = $request->param('days', 30);
        $logType = $request->param('log_type', 'system');

        $count = SystemLogger::cleanOldLogs($days, $logType);

        return $this->success(['count' => $count], "成功清理 {$count} 条日志");
    }

    /**
     * 导出日志
     */
    public function exportLogs(Request $request)
    {
        $logType = $request->param('log_type', 'system');
        $where = [];

        // 根据类型构建查询条件
        if ($logType === 'system') {
            if ($request->has('level')) {
                $where['level'] = $request->param('level');
            }
            if ($request->has('category')) {
                $where['category'] = $request->param('category');
            }
        } elseif ($logType === 'login') {
            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }
        } elseif ($logType === 'security') {
            if ($request->has('type')) {
                $where['type'] = $request->param('type');
            }
            if ($request->has('level')) {
                $where['level'] = $request->param('level');
            }
        }

        // 时间范围
        if ($request->has('start_time')) {
            $timeField = $logType === 'login' ? 'login_time' : 'create_time';
            $where[$timeField] = ['>=', $request->param('start_time')];
        }

        $logs = SystemLogger::exportLogs($where, $logType);

        // 转换为CSV格式
        $filename = $logType . '_logs_' . date('YmdHis') . '.csv';

        // 设置响应头
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        // 输出BOM头（解决Excel打开中文乱码）
        echo "\xEF\xBB\xBF";

        // 输出CSV
        $output = fopen('php://output', 'w');

        // 写入表头
        if (!empty($logs)) {
            fputcsv($output, array_keys($logs[0]));

            // 写入数据
            foreach ($logs as $log) {
                fputcsv($output, $log);
            }
        }

        fclose($output);
        exit;
    }
}
