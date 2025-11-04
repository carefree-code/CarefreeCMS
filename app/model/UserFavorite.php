<?php

namespace app\model;

use think\Model;

/**
 * 用户收藏模型
 */
class UserFavorite extends Model
{
    protected $name = 'user_favorites';
    protected $autoWriteTimestamp = 'create_time';
    protected $updateTime = false;

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 关联文章
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
