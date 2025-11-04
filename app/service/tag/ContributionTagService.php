<?php
namespace app\service\tag;

use app\model\Contribution;
use think\facade\Db;

/**
 * 投稿标签服务类
 * 处理投稿列表标签的数据查询
 */
class ContributionTagService
{
    /**
     * 获取投稿列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - status: 审核状态 (0-待审核, 1-已通过, 2-已拒绝)
     *   - userid: 用户ID
     *   - orderby: 排序方式 (create_time, update_time)
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 10;
        $status = $params['status'] ?? '';
        $userid = $params['userid'] ?? '';
        $orderby = $params['orderby'] ?? 'create_time';

        $query = Contribution::alias('c');

        // 筛选条件
        if ($status !== '') {
            $query->where('c.status', $status);
        }

        if ($userid !== '' && $userid > 0) {
            $query->where('c.user_id', $userid);
        }

        // 关联用户信息
        $query->leftJoin('front_users u', 'c.user_id = u.id')
              ->field('c.*, u.nickname as author_nickname, u.avatar as author_avatar');

        // 排序
        switch ($orderby) {
            case 'update_time':
                $query->order('c.update_time', 'desc');
                break;
            case 'create_time':
            default:
                $query->order('c.create_time', 'desc');
                break;
        }

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $contributions = $query->select()->toArray();

        // 处理数据
        foreach ($contributions as &$contribution) {
            // 状态文本
            $contribution['status_text'] = self::getStatusText($contribution['status']);

            // 格式化时间
            $contribution['time_ago'] = self::timeAgo($contribution['create_time']);

            // 截取内容预览
            if (!empty($contribution['content'])) {
                $contribution['content_preview'] = mb_substr(strip_tags($contribution['content']), 0, 100, 'UTF-8');
                if (mb_strlen($contribution['content'], 'UTF-8') > 100) {
                    $contribution['content_preview'] .= '...';
                }
            }
        }

        return $contributions;
    }

    /**
     * 获取用户的投稿列表
     *
     * @param int $userid 用户ID
     * @param int $limit 数量限制
     * @return array
     */
    public static function getUserContributions($userid, $limit = 10)
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
     * 获取待审核的投稿
     *
     * @param int $limit 数量限制
     * @return array
     */
    public static function getPending($limit = 10)
    {
        return self::getList([
            'status' => 0,
            'limit' => $limit,
            'orderby' => 'create_time'
        ]);
    }

    /**
     * 获取已通过的投稿
     *
     * @param int $limit 数量限制
     * @return array
     */
    public static function getApproved($limit = 10)
    {
        return self::getList([
            'status' => 1,
            'limit' => $limit,
            'orderby' => 'update_time'
        ]);
    }

    /**
     * 获取用户待审核投稿数量
     *
     * @param int $userid 用户ID
     * @return int
     */
    public static function getUserPendingCount($userid)
    {
        if (!$userid) {
            return 0;
        }

        return Contribution::where('user_id', $userid)
            ->where('status', 0)
            ->count();
    }

    /**
     * 获取状态文本
     *
     * @param int $status 状态值
     * @return string
     */
    private static function getStatusText($status)
    {
        $statusMap = [
            0 => '待审核',
            1 => '已通过',
            2 => '已拒绝'
        ];

        return $statusMap[$status] ?? '未知';
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
