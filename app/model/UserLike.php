<?php

namespace app\model;

use think\Model;

/**
 * 用户点赞模型
 */
class UserLike extends Model
{
    protected $name = 'user_likes';
    protected $autoWriteTimestamp = 'create_time';
    protected $updateTime = false;

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }
}
