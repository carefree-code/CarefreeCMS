<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 登录日志模型
 */
class LoginLog extends Model
{
    protected $name = 'login_logs';

    protected $autoWriteTimestamp = false;

    /**
     * 获取日志列表
     * @param array $where
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getList($where = [], $page = 1, $perPage = 20)
    {
        // 确保分页参数为整数
        $page = (int) $page;
        $perPage = (int) $perPage;

        $query = self::order('login_time', 'desc');

        // 用户名筛选
        if (!empty($where['username'])) {
            $query->where('username', 'like', '%' . $where['username'] . '%');
        }

        // 状态筛选
        if (!empty($where['status'])) {
            $query->where('status', $where['status']);
        }

        // IP筛选
        if (!empty($where['ip'])) {
            $query->where('ip', 'like', '%' . $where['ip'] . '%');
        }

        // 时间范围
        if (!empty($where['start_time'])) {
            $query->where('login_time', '>=', $where['start_time']);
        }

        if (!empty($where['end_time'])) {
            $query->where('login_time', '<=', $where['end_time']);
        }

        $total = $query->count();
        $list = $query->page($page, $perPage)->select();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 获取最近登录记录
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function getRecentLogins($userId, $limit = 10)
    {
        return self::where('user_id', $userId)
            ->where('status', 'success')
            ->order('login_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取失败登录记录
     * @param string $username
     * @param int $minutes 时间范围（分钟）
     * @return int
     */
    public static function getFailedCount($username, $minutes = 30)
    {
        $time = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return self::where('username', $username)
            ->where('status', 'failed')
            ->where('login_time', '>=', $time)
            ->count();
    }

    /**
     * 批量删除
     * @param array $ids
     * @return int
     */
    public static function batchDelete($ids)
    {
        return self::where('id', 'in', $ids)->delete();
    }
}
