<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Comment;
use app\model\Article;
use think\Request;
use think\facade\Db;

/**
 * 后台评论管理控制器
 */
class CommentManage extends BaseController
{
    /**
     * 获取评论列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        // 搜索条件
        $articleId = $request->get('article_id', 0);
        $userId = $request->get('user_id', 0);
        $status = $request->get('status', '');
        $isGuest = $request->get('is_guest', '');
        $keyword = $request->get('keyword', '');
        $startTime = $request->get('start_time', '');
        $endTime = $request->get('end_time', '');

        $query = Comment::with(['article', 'user']);

        // 应用搜索条件
        if ($articleId > 0) {
            $query->where('article_id', $articleId);
        }

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($isGuest !== '') {
            $query->where('is_guest', $isGuest);
        }

        if (!empty($keyword)) {
            $query->where('content', 'like', '%' . $keyword . '%');
        }

        if (!empty($startTime)) {
            $query->where('create_time', '>=', $startTime);
        }

        if (!empty($endTime)) {
            $query->where('create_time', '<=', $endTime . ' 23:59:59');
        }

        // 排序
        $query->order('create_time', 'desc');

        // 分页
        $comments = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($comments);
    }

    /**
     * 获取评论详情
     */
    public function read(Request $request, $id)
    {
        $comment = Comment::with(['article', 'user', 'parent', 'children'])->find($id);

        if (!$comment) {
            return Response::notFound('评论不存在');
        }

        return Response::success($comment);
    }

    /**
     * 审核评论
     */
    public function audit(Request $request, $id)
    {
        $status = $request->post('status', Comment::STATUS_APPROVED);

        if (!$id) {
            return Response::error('评论ID不能为空');
        }

        if (!in_array($status, [Comment::STATUS_APPROVED, Comment::STATUS_REJECTED])) {
            return Response::error('状态值不正确');
        }

        $comment = Comment::find($id);
        if (!$comment) {
            return Response::notFound('评论不存在');
        }

        if (Comment::audit($id, $status)) {
            return Response::success([], $status == Comment::STATUS_APPROVED ? '审核通过' : '审核拒绝');
        } else {
            return Response::error('审核失败');
        }
    }

    /**
     * 批量审核
     */
    public function batchAudit(Request $request)
    {
        $ids = $request->post('ids', []);
        $status = $request->post('status', Comment::STATUS_APPROVED);

        if (empty($ids) || !is_array($ids)) {
            return Response::error('请选择要审核的评论');
        }

        if (!in_array($status, [Comment::STATUS_APPROVED, Comment::STATUS_REJECTED])) {
            return Response::error('状态值不正确');
        }

        $successCount = 0;
        foreach ($ids as $id) {
            if (Comment::audit($id, $status)) {
                $successCount++;
            }
        }

        return Response::success([
            'total'   => count($ids),
            'success' => $successCount,
        ], "成功审核 {$successCount} 条评论");
    }

    /**
     * 删除评论
     */
    public function delete(Request $request, $id)
    {
        if (Comment::deleteWithChildren($id)) {
            return Response::success([], '删除成功');
        } else {
            return Response::error('删除失败');
        }
    }

    /**
     * 批量删除
     */
    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return Response::error('请选择要删除的评论');
        }

        $successCount = 0;
        foreach ($ids as $id) {
            if (Comment::deleteWithChildren($id)) {
                $successCount++;
            }
        }

        return Response::success([
            'total'   => count($ids),
            'success' => $successCount,
        ], "成功删除 {$successCount} 条评论");
    }

    /**
     * 评论统计
     */
    public function statistics(Request $request)
    {
        // 总评论数
        $totalCount = Comment::count();

        // 各状态评论数
        $pendingCount = Comment::where('status', Comment::STATUS_PENDING)->count();
        $approvedCount = Comment::where('status', Comment::STATUS_APPROVED)->count();
        $rejectedCount = Comment::where('status', Comment::STATUS_REJECTED)->count();

        // 今日评论数
        $todayCount = Comment::where('create_time', '>=', date('Y-m-d 00:00:00'))->count();

        // 本周评论数
        $weekCount = Comment::where('create_time', '>=', date('Y-m-d 00:00:00', strtotime('this week monday')))->count();

        // 本月评论数
        $monthCount = Comment::where('create_time', '>=', date('Y-m-01 00:00:00'))->count();

        // 注册用户评论数和游客评论数
        $userCommentCount = Comment::where('is_guest', 0)->count();
        $guestCommentCount = Comment::where('is_guest', 1)->count();

        // 最近7天评论趋势
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = Comment::whereTime('create_time', 'between', [$date . ' 00:00:00', $date . ' 23:59:59'])->count();
            $trend[] = [
                'date'  => $date,
                'count' => $count,
            ];
        }

        // 评论最多的文章 Top 10
        $topArticles = Comment::field('article_id, COUNT(*) as comment_count')
            ->with(['article'])
            ->where('status', Comment::STATUS_APPROVED)
            ->group('article_id')
            ->order('comment_count', 'desc')
            ->limit(10)
            ->select();

        return Response::success([
            'total_count'         => $totalCount,
            'pending_count'       => $pendingCount,
            'approved_count'      => $approvedCount,
            'rejected_count'      => $rejectedCount,
            'today_count'         => $todayCount,
            'week_count'          => $weekCount,
            'month_count'         => $monthCount,
            'user_comment_count'  => $userCommentCount,
            'guest_comment_count' => $guestCommentCount,
            'trend'               => $trend,
            'top_articles'        => $topArticles,
        ]);
    }

    /**
     * 回复评论（管理员）
     */
    public function reply(Request $request, $id)
    {
        $commentId = $id;
        $content = trim($request->post('content', ''));
        $userId = $request->user['id'] ?? 0;

        if (!$commentId) {
            return Response::error('评论ID不能为空');
        }

        if (empty($content)) {
            return Response::error('回复内容不能为空');
        }

        $comment = Comment::find($commentId);
        if (!$comment) {
            return Response::error('评论不存在');
        }

        Db::startTrans();
        try {
            // 创建回复评论
            $reply = Comment::create([
                'article_id' => $comment->article_id,
                'parent_id'  => $commentId,
                'content'    => $content,
                'user_ip'    => $request->ip(),
                'is_guest'   => 0,
                'is_admin'   => 1, // 标记为管理员评论
                'status'     => Comment::STATUS_APPROVED, // 管理员回复直接通过
            ]);

            // 更新文章评论数
            Article::where('id', $comment->article_id)->inc('comment_count')->update();

            Db::commit();
            return Response::success([
                'comment_id' => $reply->id,
            ], '回复成功');
        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('回复失败：' . $e->getMessage());
        }
    }

    /**
     * 更新评论内容（编辑）
     */
    public function update(Request $request, $id)
    {
        $content = trim($request->put('content', ''));

        if (empty($content)) {
            return Response::error('评论内容不能为空');
        }

        $comment = Comment::find($id);
        if (!$comment) {
            return Response::notFound('评论不存在');
        }

        try {
            $comment->content = $content;
            $comment->save();
            return Response::success([], '更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }
}
