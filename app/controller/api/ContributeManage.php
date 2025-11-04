<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Article;
use app\model\ContributeConfig;
use app\service\ContributeService;
use think\Request;

/**
 * 后台投稿管理控制器
 */
class ContributeManage extends BaseController
{
    /**
     * 投稿列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $auditStatus = $request->get('audit_status', '');
        $categoryId = $request->get('category_id', '');
        $keyword = $request->get('keyword', '');

        $query = Article::where('is_contribute', 1)
            ->with(['frontUser', 'category', 'auditor'])
            ->order('create_time', 'desc');

        if ($auditStatus !== '') {
            $query->where('audit_status', $auditStatus);
        }

        if ($categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        if ($keyword) {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 投稿详情
     */
    public function read($id)
    {
        $article = Article::where('is_contribute', 1)
            ->with(['frontUser', 'category', 'auditor'])
            ->find($id);

        if (!$article) {
            return Response::notFound('投稿不存在');
        }

        return Response::success($article->toArray());
    }

    /**
     * 审核通过
     */
    public function auditPass(Request $request, $id)
    {
        $article = Article::where('is_contribute', 1)->find($id);

        if (!$article) {
            return Response::notFound('投稿不存在');
        }

        $auditorId = $request->adminId; // 从中间件获取管理员ID
        $remark = $request->post('remark', '');

        try {
            $article->auditPass($auditorId, $remark);
            return Response::success([], '审核通过');

        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * 审核拒绝
     */
    public function auditReject(Request $request, $id)
    {
        $article = Article::where('is_contribute', 1)->find($id);

        if (!$article) {
            return Response::notFound('投稿不存在');
        }

        $auditorId = $request->adminId;
        $remark = $request->post('remark', '');

        if (empty($remark)) {
            return Response::error('请填写拒绝原因');
        }

        try {
            $article->auditReject($auditorId, $remark);
            return Response::success([], '已拒绝');

        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * 删除投稿
     */
    public function delete($id)
    {
        $article = Article::where('is_contribute', 1)->find($id);

        if (!$article) {
            return Response::notFound('投稿不存在');
        }

        try {
            $article->delete();
            return Response::success([], '删除成功');

        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 投稿配置列表
     */
    public function configIndex(Request $request)
    {
        $list = ContributeConfig::with(['category'])
            ->order('category_id', 'asc')
            ->select();

        return Response::success($list);
    }

    /**
     * 更新投稿配置
     */
    public function configUpdate(Request $request, $id)
    {
        $config = ContributeConfig::find($id);

        if (!$config) {
            return Response::notFound('配置不存在');
        }

        $data = $request->post();

        try {
            $allowFields = [
                'allow_contribute', 'need_audit', 'reward_points',
                'min_words', 'max_per_day', 'level_required'
            ];

            $updateData = [];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $config->save($updateData);

            return Response::success($config->toArray(), '更新成功');

        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 创建投稿配置
     */
    public function configCreate(Request $request)
    {
        $data = $request->post();

        if (empty($data['category_id'])) {
            return Response::error('请选择分类');
        }

        // 检查是否已存在
        if (ContributeConfig::where('category_id', $data['category_id'])->count() > 0) {
            return Response::error('该分类已有配置');
        }

        try {
            $config = ContributeConfig::create([
                'category_id'      => $data['category_id'],
                'allow_contribute' => $data['allow_contribute'] ?? 1,
                'need_audit'       => $data['need_audit'] ?? 1,
                'reward_points'    => $data['reward_points'] ?? 10,
                'min_words'        => $data['min_words'] ?? 100,
                'max_per_day'      => $data['max_per_day'] ?? 5,
                'level_required'   => $data['level_required'] ?? 1,
            ]);

            return Response::success($config->toArray(), '创建成功');

        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 投稿统计
     */
    public function statistics(Request $request)
    {
        $stats = ContributeService::getStatistics();

        return Response::success($stats);
    }
}
