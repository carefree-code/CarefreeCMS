<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Comment;
use app\model\Article;
use app\model\Config;
use app\service\SensitiveWordFilter;
use think\Request;
use think\facade\Db;

/**
 * 前台评论控制器
 */
class FrontComment extends BaseController
{
    /**
     * 获取文章评论列表（树形结构）
     */
    public function index(Request $request)
    {
        $articleId = $request->get('article_id', 0);
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        if (!$articleId) {
            return Response::error('文章ID不能为空');
        }

        // 检查文章是否存在
        $article = Article::find($articleId);
        if (!$article) {
            return Response::error('文章不存在');
        }

        // 获取评论树
        $result = Comment::getCommentTree($articleId, Comment::STATUS_APPROVED, $page, $limit);

        return Response::success([
            'list'  => $result['list'],
            'total' => $result['total'],
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 发表评论（支持注册用户和游客）
     */
    public function create(Request $request)
    {
        $articleId = $request->post('article_id', 0);
        $parentId = $request->post('parent_id', 0);
        $content = trim($request->post('content', ''));

        // 验证参数
        if (!$articleId) {
            return Response::error('文章ID不能为空');
        }

        if (empty($content)) {
            return Response::error('评论内容不能为空');
        }

        if (mb_strlen($content, 'utf-8') < 5) {
            return Response::error('评论内容至少5个字符');
        }

        if (mb_strlen($content, 'utf-8') > 500) {
            return Response::error('评论内容不能超过500个字符');
        }

        // 检查文章是否存在
        $article = Article::find($articleId);
        if (!$article) {
            return Response::error('文章不存在');
        }

        // 如果是回复，检查父评论是否存在
        if ($parentId > 0) {
            $parentComment = Comment::find($parentId);
            if (!$parentComment) {
                return Response::error('父评论不存在');
            }
            if ($parentComment->article_id != $articleId) {
                return Response::error('父评论与文章不匹配');
            }
        }

        // 获取系统配置
        $commentConfig = Config::getConfig('comment_settings', [
            'enable_guest_comment' => true,   // 是否允许游客评论
            'auto_approve'         => false,  // 是否自动审核通过
            'enable_sensitive_filter' => true, // 是否启用敏感词过滤
        ]);

        // 判断是否为注册用户（尝试从token解析）
        $userId = 0;
        $token = $request->header('Authorization', '');
        if (!empty($token) && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
            $payload = \app\common\Jwt::verify($token);
            if ($payload !== false) {
                // 转换为数组
                $payload = json_decode(json_encode($payload), true);
                if (isset($payload['id']) && isset($payload['type']) && $payload['type'] === 'front_user') {
                    $userId = $payload['id'];
                }
            }
        }
        $isGuest = empty($userId);

        // 如果不允许游客评论
        if ($isGuest && !$commentConfig['enable_guest_comment']) {
            return Response::unauthorized('请先登录后再评论');
        }

        // 游客评论需要提供昵称和邮箱
        $userName = null;
        $userEmail = null;
        if ($isGuest) {
            $userName = trim($request->post('user_name', ''));
            $userEmail = trim($request->post('user_email', ''));

            if (empty($userName)) {
                return Response::error('请输入您的昵称');
            }

            if (empty($userEmail)) {
                return Response::error('请输入您的邮箱');
            }

            if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                return Response::error('邮箱格式不正确');
            }
        }

        // 敏感词过滤
        if ($commentConfig['enable_sensitive_filter']) {
            if (SensitiveWordFilter::check($content)) {
                return Response::error('评论内容包含敏感词，请修改后重试');
            }
        }

        // 防刷：检查最近1分钟内是否有相同内容的评论
        $recentComment = Comment::where('content', $content)
            ->where('create_time', '>=', date('Y-m-d H:i:s', time() - 60))
            ->find();

        if ($recentComment) {
            return Response::error('请勿重复提交评论');
        }

        Db::startTrans();
        try {
            // 创建评论
            $data = [
                'article_id' => $articleId,
                'parent_id'  => $parentId,
                'content'    => $content,
                'user_ip'    => $request->ip(),
                'is_guest'   => $isGuest ? 1 : 0,
                'status'     => $commentConfig['auto_approve'] ? Comment::STATUS_APPROVED : Comment::STATUS_PENDING,
            ];

            if ($isGuest) {
                // 游客评论
                $data['user_name'] = $userName;
                $data['user_email'] = $userEmail;
            } else {
                // 注册用户评论
                $data['user_id'] = $userId;
            }

            $comment = Comment::create($data);

            // 如果自动审核通过，更新文章评论数和用户评论数
            if ($commentConfig['auto_approve']) {
                Article::where('id', $articleId)->inc('comment_count')->update();

                if (!$isGuest) {
                    Db::name('front_users')->where('id', $userId)->inc('comment_count')->update();
                }
            }

            Db::commit();

            return Response::success([
                'comment_id' => $comment->id,
                'status'     => $comment->status,
                'status_text' => $comment->status_text,
            ], $commentConfig['auto_approve'] ? '评论发表成功' : '评论已提交，等待审核');

        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('评论失败：' . $e->getMessage());
        }
    }

    /**
     * 点赞评论
     */
    public function like(Request $request)
    {
        $commentId = $request->post('comment_id', 0);
        $userId = $request->user['id'] ?? 0;

        if (!$commentId) {
            return Response::error('评论ID不能为空');
        }

        $comment = Comment::find($commentId);
        if (!$comment) {
            return Response::error('评论不存在');
        }

        if (!$userId) {
            return Response::error('请先登录');
        }

        // 检查是否已点赞
        $liked = Db::name('user_likes')
            ->where('user_id', $userId)
            ->where('target_type', 'comment')
            ->where('target_id', $commentId)
            ->find();

        if ($liked) {
            return Response::error('您已点赞过该评论');
        }

        Db::startTrans();
        try {
            // 添加点赞记录
            Db::name('user_likes')->insert([
                'user_id'     => $userId,
                'target_type' => 'comment',
                'target_id'   => $commentId,
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            // 更新评论点赞数
            Comment::where('id', $commentId)->inc('like_count')->update();

            Db::commit();
            return Response::success([], '点赞成功');
        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('点赞失败：' . $e->getMessage());
        }
    }

    /**
     * 取消点赞评论
     */
    public function unlike(Request $request)
    {
        $commentId = $request->post('comment_id', 0);
        $userId = $request->user['id'] ?? 0;

        if (!$commentId) {
            return Response::error('评论ID不能为空');
        }

        if (!$userId) {
            return Response::error('请先登录');
        }

        // 检查是否已点赞
        $liked = Db::name('user_likes')
            ->where('user_id', $userId)
            ->where('target_type', 'comment')
            ->where('target_id', $commentId)
            ->find();

        if (!$liked) {
            return Response::error('您还未点赞该评论');
        }

        Db::startTrans();
        try {
            // 删除点赞记录
            Db::name('user_likes')
                ->where('user_id', $userId)
                ->where('target_type', 'comment')
                ->where('target_id', $commentId)
                ->delete();

            // 更新评论点赞数
            $comment = Comment::find($commentId);
            if ($comment && $comment->like_count > 0) {
                Comment::where('id', $commentId)->dec('like_count')->update();
            }

            Db::commit();
            return Response::success([], '取消点赞成功');
        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 获取评论详情
     */
    public function read(Request $request, $id)
    {
        $comment = Comment::with(['user', 'article', 'parent'])->find($id);

        if (!$comment) {
            return Response::notFound('评论不存在');
        }

        return Response::success($comment);
    }

    /**
     * 举报评论
     */
    public function report(Request $request)
    {
        $commentId = $request->post('comment_id', 0);
        $reason = trim($request->post('reason', ''));
        $userId = $request->user['id'] ?? 0;

        if (!$commentId) {
            return Response::error('评论ID不能为空');
        }

        if (empty($reason)) {
            return Response::error('请填写举报原因');
        }

        $comment = Comment::find($commentId);
        if (!$comment) {
            return Response::error('评论不存在');
        }

        // TODO: 将举报信息保存到举报表或发送通知给管理员
        // 这里可以扩展举报功能

        return Response::success([], '举报成功，我们会尽快处理');
    }
}
