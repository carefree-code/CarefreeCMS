<?php

namespace app\model;

use think\Model;

/**
 * 用户阅读历史模型
 */
class UserReadHistory extends Model
{
    protected $name = 'user_read_history';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'read_progress' => 'integer',
        'read_time'     => 'integer',
    ];

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
