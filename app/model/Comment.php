<?php

namespace app\model;

use think\Model;

/**
 * 评论模型
 */
class Comment extends Model
{
    protected $name = 'comments';
    protected $autoWriteTimestamp = true;

    // 状态常量
    const STATUS_PENDING = 0;  // 待审核
    const STATUS_APPROVED = 1; // 已通过
    const STATUS_REJECTED = 2; // 已拒绝

    protected $type = [
        'article_id'  => 'integer',
        'user_id'     => 'integer',
        'parent_id'   => 'integer',
        'is_guest'    => 'integer',
        'like_count'  => 'integer',
        'is_admin'    => 'integer',
        'status'      => 'integer',
    ];

    /**
     * 关联文章
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * 关联前台用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 关联父评论
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * 关联子评论（回复）
     */
    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->with(['user', 'children'])
            ->order('create_time', 'asc');
    }

    /**
     * 获取器：状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_PENDING  => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
        ];
        return $status[$data['status']] ?? '未知';
    }

    /**
     * 获取器：评论者名称
     */
    public function getAuthorNameAttr($value, $data)
    {
        // 如果是注册用户
        if (!empty($data['user_id']) && isset($this->user)) {
            return $this->user->nickname ?: $this->user->username;
        }
        // 游客评论
        return $data['user_name'] ?: '匿名用户';
    }

    /**
     * 获取器：评论者头像
     */
    public function getAuthorAvatarAttr($value, $data)
    {
        // 如果是注册用户且有头像
        if (!empty($data['user_id']) && isset($this->user) && $this->user->avatar) {
            return $this->user->avatar;
        }
        // 返回默认头像
        return '/static/images/default-avatar.png';
    }

    /**
     * 搜索器：文章ID
     */
    public function searchArticleIdAttr($query, $value)
    {
        $query->where('article_id', $value);
    }

    /**
     * 搜索器：用户ID
     */
    public function searchUserIdAttr($query, $value)
    {
        $query->where('user_id', $value);
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 搜索器：是否游客
     */
    public function searchIsGuestAttr($query, $value)
    {
        $query->where('is_guest', $value);
    }

    /**
     * 搜索器：关键词（搜索内容）
     */
    public function searchKeywordAttr($query, $value)
    {
        $query->where('content', 'like', '%' . $value . '%');
    }

    /**
     * 获取评论树（包含子评论）
     * @param int $articleId 文章ID
     * @param int $status 状态
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public static function getCommentTree(int $articleId, int $status = self::STATUS_APPROVED, int $page = 1, int $limit = 20): array
    {
        // 获取顶级评论（parent_id = 0）
        $comments = self::with(['user', 'children'])
            ->where('article_id', $articleId)
            ->where('parent_id', 0)
            ->where('status', $status)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();

        return [
            'list'  => $comments,
            'total' => self::where('article_id', $articleId)
                ->where('parent_id', 0)
                ->where('status', $status)
                ->count(),
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 统计文章评论数
     * @param int $articleId
     * @param int $status
     * @return int
     */
    public static function countByArticle(int $articleId, int $status = self::STATUS_APPROVED): int
    {
        return self::where('article_id', $articleId)
            ->where('status', $status)
            ->count();
    }

    /**
     * 审核评论
     * @param int $id
     * @param int $status
     * @return bool
     */
    public static function audit(int $id, int $status): bool
    {
        $comment = self::find($id);
        if (!$comment) {
            return false;
        }

        $oldStatus = $comment->status;
        $comment->status = $status;
        $result = $comment->save();

        // 如果从待审核变为已通过，更新文章评论数
        if ($oldStatus == self::STATUS_PENDING && $status == self::STATUS_APPROVED) {
            Article::where('id', $comment->article_id)->inc('comment_count')->update();

            // 如果是注册用户，更新用户评论数
            if ($comment->user_id) {
                FrontUser::where('id', $comment->user_id)->inc('comment_count')->update();
            }
        }

        // 如果从已通过变为其他状态，减少文章评论数
        if ($oldStatus == self::STATUS_APPROVED && $status != self::STATUS_APPROVED) {
            Article::where('id', $comment->article_id)->dec('comment_count')->update();

            // 如果是注册用户，减少用户评论数
            if ($comment->user_id && FrontUser::find($comment->user_id)->comment_count > 0) {
                FrontUser::where('id', $comment->user_id)->dec('comment_count')->update();
            }
        }

        return $result;
    }

    /**
     * 删除评论（同时删除子评论）
     * @param int $id
     * @return bool
     */
    public static function deleteWithChildren(int $id): bool
    {
        $comment = self::find($id);
        if (!$comment) {
            return false;
        }

        // 获取所有子评论ID
        $childIds = self::where('parent_id', $id)->column('id');

        // 递归删除子评论
        if ($childIds) {
            foreach ($childIds as $childId) {
                self::deleteWithChildren($childId);
            }
        }

        // 如果是已通过的评论，更新文章评论数
        if ($comment->status == self::STATUS_APPROVED) {
            Article::where('id', $comment->article_id)->dec('comment_count')->update();

            // 如果是注册用户，更新用户评论数
            if ($comment->user_id && FrontUser::find($comment->user_id)->comment_count > 0) {
                FrontUser::where('id', $comment->user_id)->dec('comment_count')->update();
            }
        }

        // 删除当前评论
        return $comment->delete();
    }
}
