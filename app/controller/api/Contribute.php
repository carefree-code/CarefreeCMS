<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\middleware\AuthMiddleware;
use app\model\Article;
use app\service\ContributeService;
use think\Request;

/**
 * 前台投稿控制器
 */
class Contribute extends BaseController
{
    protected $middleware = [
        AuthMiddleware::class => ['except' => []],
    ];

    /**
     * 获取可投稿分类列表
     */
    public function categories(Request $request)
    {
        $userId = $request->userId;

        $categories = ContributeService::getAvailableCategories($userId);

        return Response::success($categories);
    }

    /**
     * 提交投稿
     */
    public function submit(Request $request)
    {
        $userId = $request->userId;
        $data = $request->post();

        $result = ContributeService::submit($userId, $data);

        if ($result['success']) {
            return Response::success($result['data'] ?? [], $result['message']);
        } else {
            return Response::error($result['message']);
        }
    }

    /**
     * 我的投稿列表
     */
    public function myContributions(Request $request)
    {
        $userId = $request->userId;
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $list = ContributeService::getUserContributions($userId, $page, $limit);

        return Response::success($list);
    }

    /**
     * 投稿详情
     */
    public function detail(Request $request, $id)
    {
        $userId = $request->userId;

        $article = Article::where('user_id', $userId)
            ->where('is_contribute', 1)
            ->find($id);

        if (!$article) {
            return Response::notFound('投稿不存在');
        }

        return Response::success($article->toArray());
    }

    /**
     * 删除我的投稿（仅限未通过审核的）
     */
    public function delete(Request $request, $id)
    {
        $userId = $request->userId;

        $article = Article::where('user_id', $userId)
            ->where('is_contribute', 1)
            ->find($id);

        if (!$article) {
            return Response::notFound('投稿不存在');
        }

        // 只能删除未通过审核的投稿
        if ($article->audit_status == 1) {
            return Response::error('已通过审核的投稿不能删除');
        }

        try {
            $article->delete();
            return Response::success([], '删除成功');

        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }
}
