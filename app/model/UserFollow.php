<?php

namespace app\model;

use think\Model;

/**
 * 用户关注模型
 */
class UserFollow extends Model
{
    protected $name = 'user_follows';
    protected $autoWriteTimestamp = 'create_time';
    protected $updateTime = false;

    /**
     * 关联关注者
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 关联被关注者
     */
    public function followUser()
    {
        return $this->belongsTo(FrontUser::class, 'follow_user_id');
    }
}
