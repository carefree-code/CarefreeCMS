<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\Seo404Log;
use app\model\SeoRedirect;
use think\Request;

/**
 * 404错误日志管理控制器
 */
class Seo404LogController extends BaseController
{
    /**
     * 获取404日志列表（分页）
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->param('per_page', 15);
        $keyword = $request->param('keyword', '');
        $isFixed = $request->param('is_fixed');
        $sortBy = $request->param('sort_by', 'hit_count'); // hit_count 或 last_hit_time

        $query = Seo404Log::order($sortBy, 'desc');

        if ($keyword) {
            $query->where('url', 'like', "%{$keyword}%");
        }

        if ($isFixed !== null && $isFixed !== '') {
            $query->where('is_fixed', (int) $isFixed);
        }

        $list = $query->paginate($perPage);

        return $this->success($list);
    }

    /**
     * 获取404日志详情
     */
    public function read($id)
    {
        $log = Seo404Log::find($id);

        if (!$log) {
            return $this->error('日志不存在');
        }

        return $this->success($log);
    }

    /**
     * 标记为已修复
     */
    public function markFixed(Request $request, $id)
    {
        $log = Seo404Log::find($id);

        if (!$log) {
            return $this->error('日志不存在');
        }

        $method = $request->param('method'); // redirect, deleted, ignored
        $notes = $request->param('notes', '');

        if (!in_array($method, ['redirect', 'deleted', 'ignored'])) {
            return $this->error('无效的修复方式');
        }

        $log->markAsFixed($method, $notes);

        return $this->success(null, '标记成功');
    }

    /**
     * 批量标记为已修复
     */
    public function batchMarkFixed(Request $request)
    {
        $ids = $request->param('ids', []);
        $method = $request->param('method');
        $notes = $request->param('notes', '');

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要操作的日志');
        }

        if (!in_array($method, ['redirect', 'deleted', 'ignored'])) {
            return $this->error('无效的修复方式');
        }

        $logs = Seo404Log::where('id', 'in', $ids)->select();

        foreach ($logs as $log) {
            $log->markAsFixed($method, $notes);
        }

        return $this->success(null, '批量标记成功');
    }

    /**
     * 忽略404错误
     */
    public function ignore(Request $request, $id)
    {
        $log = Seo404Log::find($id);

        if (!$log) {
            return $this->error('日志不存在');
        }

        $notes = $request->param('notes', '');
        $log->ignore($notes);

        return $this->success(null, '已忽略');
    }

    /**
     * 创建重定向规则并标记为已修复
     */
    public function createRedirect(Request $request, $id)
    {
        $log = Seo404Log::find($id);

        if (!$log) {
            return $this->error('日志不存在');
        }

        $toUrl = $request->param('to_url');
        $redirectType = $request->param('redirect_type', 301);

        if (empty($toUrl)) {
            return $this->error('请指定目标URL');
        }

        // 创建重定向规则
        $redirect = SeoRedirect::create([
            'from_url' => $log->url,
            'to_url' => $toUrl,
            'redirect_type' => $redirectType,
            'match_type' => 'exact',
            'is_enabled' => 1,
            'description' => '从404日志创建',
            'hit_count' => 0
        ]);

        // 标记为已修复
        $log->markAsFixed('redirect', "重定向到: {$toUrl}");

        return $this->success($redirect, '重定向规则创建成功');
    }

    /**
     * 删除404日志
     */
    public function delete($id)
    {
        $log = Seo404Log::find($id);

        if (!$log) {
            return $this->error('日志不存在');
        }

        $log->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 批量删除404日志
     */
    public function batchDelete(Request $request)
    {
        $ids = $request->param('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的日志');
        }

        Seo404Log::destroy($ids);

        return $this->success(null, '批量删除成功');
    }

    /**
     * 清理旧日志
     */
    public function clean(Request $request)
    {
        $days = $request->param('days', 90);

        $count = Seo404Log::cleanOldLogs($days);

        return $this->success([
            'count' => $count
        ], "已清理 {$count} 条旧日志");
    }

    /**
     * 获取404统计
     */
    public function statistics()
    {
        $stats = Seo404Log::getStatistics();
        $topErrors = Seo404Log::getTopErrors(10);
        $recentErrors = Seo404Log::getRecentErrors(10);

        return $this->success([
            'stats' => $stats,
            'top_errors' => $topErrors,
            'recent_errors' => $recentErrors
        ]);
    }

    /**
     * 获取高频404错误
     */
    public function topErrors(Request $request)
    {
        $limit = $request->param('limit', 20);
        $errors = Seo404Log::getTopErrors($limit);

        return $this->success($errors);
    }

    /**
     * 获取最近的404错误
     */
    public function recentErrors(Request $request)
    {
        $limit = $request->param('limit', 20);
        $errors = Seo404Log::getRecentErrors($limit);

        return $this->success($errors);
    }

    /**
     * 导出404日志
     */
    public function export(Request $request)
    {
        $isFixed = $request->param('is_fixed');

        $query = Seo404Log::order('hit_count', 'desc');

        if ($isFixed !== null && $isFixed !== '') {
            $query->where('is_fixed', $isFixed);
        }

        $logs = $query->select();

        $csv = "URL,来源,IP,命中次数,首次出现,最后出现,是否修复,修复方式,备注\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                '"%s","%s","%s",%d,"%s","%s",%s,"%s","%s"' . "\n",
                $log->url,
                $log->referer,
                $log->ip,
                $log->hit_count,
                $log->first_hit_time,
                $log->last_hit_time,
                $log->is_fixed ? '是' : '否',
                $log->fixed_method ?? '',
                $log->notes ?? ''
            );
        }

        return $this->success([
            'content' => $csv,
            'filename' => '404_logs_' . date('YmdHis') . '.csv'
        ]);
    }
}
