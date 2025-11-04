<?php

namespace app\model;

use think\Model;

/**
 * 消息通知模型
 */
class Notification extends Model
{
    protected $name = 'notifications';

    // 设置字段信息
    protected $schema = [
        'id'            => 'int',
        'user_id'       => 'int',
        'type'          => 'string',
        'title'         => 'string',
        'content'       => 'string',
        'link'          => 'string',
        'from_user_id'  => 'int',
        'related_type'  => 'string',
        'related_id'    => 'int',
        'is_read'       => 'int',
        'read_time'     => 'datetime',
        'create_time'   => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'is_read'     => 'boolean',
        'user_id'     => 'integer',
        'from_user_id' => 'integer',
        'related_id'  => 'integer',
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'from_user_info',
    ];

    /**
     * 通知类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $types = [
            'system'  => '系统通知',
            'comment' => '评论通知',
            'like'    => '点赞通知',
            'follow'  => '关注通知',
            'reply'   => '回复通知',
            'audit'   => '审核通知',
            'order'   => '订单通知',
        ];

        return $types[$data['type']] ?? '未知';
    }

    /**
     * 来源用户信息
     */
    public function getFromUserInfoAttr($value, $data)
    {
        if (empty($data['from_user_id'])) {
            return null;
        }

        $user = FrontUser::field('id,username,nickname,avatar')
            ->find($data['from_user_id']);

        return $user ? $user->toArray() : null;
    }

    /**
     * 关联接收用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 关联来源用户
     */
    public function fromUser()
    {
        return $this->belongsTo(FrontUser::class, 'from_user_id');
    }

    /**
     * 标记为已读
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        $this->is_read = 1;
        $this->read_time = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 批量标记为已读
     */
    public static function markMultipleAsRead(array $ids, int $userId): int
    {
        return self::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->where('is_read', 0)
            ->update([
                'is_read'   => 1,
                'read_time' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * 全部标记为已读
     */
    public static function markAllAsRead(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', 0)
            ->update([
                'is_read'   => 1,
                'read_time' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * 获取未读数量
     */
    public static function getUnreadCount(int $userId, ?string $type = null): int
    {
        $query = self::where('user_id', $userId)
            ->where('is_read', 0);

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->count();
    }

    /**
     * 删除旧通知
     */
    public static function deleteOldNotifications(int $days = 30): int
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return self::where('create_time', '<', $date)
            ->where('is_read', 1)
            ->delete();
    }
}
