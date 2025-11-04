<?php
namespace app\service\tag;

use app\model\Notification;
use think\facade\Db;

/**
 * 消息通知标签服务类
 * 处理消息通知列表标签的数据查询
 */
class NotificationTagService
{
    /**
     * 获取消息通知列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - userid: 用户ID
     *   - type: 通知类型 (system, reply, like, follow)
     *   - isread: 是否已读 (0/1)
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 10;
        $userid = $params['userid'] ?? '';
        $type = $params['type'] ?? '';
        $isread = $params['isread'] ?? '';

        $query = Notification::alias('n');

        // 筛选条件
        if ($userid !== '' && $userid > 0) {
            $query->where('n.user_id', $userid);
        }

        if ($type !== '') {
            $query->where('n.type', $type);
        }

        if ($isread !== '') {
            $query->where('n.is_read', $isread);
        }

        // 关联用户信息
        $query->leftJoin('front_users u', 'n.user_id = u.id')
              ->field('n.*, u.nickname as user_nickname, u.avatar as user_avatar');

        // 按创建时间倒序
        $query->order('n.create_time', 'desc');

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $notifications = $query->select()->toArray();

        // 处理数据
        foreach ($notifications as &$notification) {
            // 格式化时间
            $notification['time_ago'] = self::timeAgo($notification['create_time']);

            // 截取内容预览
            if (!empty($notification['content'])) {
                $notification['content_preview'] = mb_substr(strip_tags($notification['content']), 0, 50, 'UTF-8');
                if (mb_strlen($notification['content'], 'UTF-8') > 50) {
                    $notification['content_preview'] .= '...';
                }
            }
        }

        return $notifications;
    }

    /**
     * 获取用户未读消息数量
     *
     * @param int $userid 用户ID
     * @return int
     */
    public static function getUnreadCount($userid)
    {
        if (!$userid) {
            return 0;
        }

        return Notification::where('user_id', $userid)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * 获取用户最新通知
     *
     * @param int $userid 用户ID
     * @param int $limit 数量限制
     * @return array
     */
    public static function getUserLatest($userid, $limit = 5)
    {
        if (!$userid) {
            return [];
        }

        return self::getList([
            'userid' => $userid,
            'limit' => $limit
        ]);
    }

    /**
     * 获取用户未读通知
     *
     * @param int $userid 用户ID
     * @param int $limit 数量限制
     * @return array
     */
    public static function getUserUnread($userid, $limit = 10)
    {
        if (!$userid) {
            return [];
        }

        return self::getList([
            'userid' => $userid,
            'isread' => 0,
            'limit' => $limit
        ]);
    }

    /**
     * 时间友好显示
     *
     * @param string $datetime 时间
     * @return string
     */
    private static function timeAgo($datetime)
    {
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . '分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . '小时前';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . '天前';
        } else {
            return date('Y-m-d', $timestamp);
        }
    }
}
