<?php

namespace app\model;

use think\Model;

/**
 * 用户积分日志模型
 */
class UserPointLog extends Model
{
    protected $name = 'user_point_logs';
    protected $autoWriteTimestamp = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'points'     => 'integer',
        'balance'    => 'integer',
        'related_id' => 'integer',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 获取器：积分变动文本
     */
    public function getPointsTextAttr($value, $data)
    {
        $points = $data['points'] ?? 0;
        return $points > 0 ? '+' . $points : $points;
    }

    /**
     * 获取器：类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $types = [
            'register' => '注册奖励',
            'login'    => '登录奖励',
            'post'     => '发布文章',
            'comment'  => '发表评论',
            'like'     => '点赞',
            'reward'   => '系统奖励',
            'consume'  => '积分消费',
        ];
        return $types[$data['type']] ?? $data['type'];
    }
}
