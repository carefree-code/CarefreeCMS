<?php
namespace app\service\tag;

use app\model\FrontUser;
use think\facade\Db;

/**
 * 前台用户标签服务类
 * 处理前台用户列表标签的数据查询
 */
class FrontUserTagService
{
    /**
     * 获取前台用户列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - level: 会员等级
     *   - isvip: 是否VIP (0/1)
     *   - status: 状态 (0/1)
     *   - orderby: 排序方式 (points, create_time, login_time)
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 10;
        $level = $params['level'] ?? '';
        $isvip = $params['isvip'] ?? '';
        $status = $params['status'] ?? '';
        $orderby = $params['orderby'] ?? 'points';

        $query = FrontUser::alias('u');

        // 筛选条件
        if ($level !== '') {
            $query->where('u.level', $level);
        }

        if ($isvip !== '') {
            $query->where('u.is_vip', $isvip);
        }

        if ($status !== '') {
            $query->where('u.status', $status);
        } else {
            // 默认只显示正常状态的用户
            $query->where('u.status', 1);
        }

        // 关联会员等级表获取等级名称
        $query->leftJoin('member_levels ml', 'u.level = ml.level')
              ->field('u.*, ml.name as level_name');

        // 排序
        switch ($orderby) {
            case 'create_time':
                $query->order('u.create_time', 'desc');
                break;
            case 'login_time':
                $query->order('u.last_login_time', 'desc');
                break;
            case 'points':
            default:
                $query->order('u.points', 'desc');
                break;
        }

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $users = $query->select()->toArray();

        // 处理头像默认值
        foreach ($users as &$user) {
            if (empty($user['avatar'])) {
                $user['avatar'] = '/static/images/default-avatar.png';
            }
        }

        return $users;
    }

    /**
     * 获取单个用户信息
     *
     * @param int $uid 用户ID
     * @return array|null
     */
    public static function getOne($uid)
    {
        $user = FrontUser::alias('u')
            ->leftJoin('member_levels ml', 'u.level = ml.level')
            ->field('u.*, ml.name as level_name')
            ->where('u.id', $uid)
            ->where('u.status', 1)
            ->find();

        if (!$user) {
            return null;
        }

        $userData = $user->toArray();

        // 处理头像默认值
        if (empty($userData['avatar'])) {
            $userData['avatar'] = '/static/images/default-avatar.png';
        }

        return $userData;
    }

    /**
     * 获取VIP用户列表
     *
     * @param int $limit 数量限制
     * @return array
     */
    public static function getVipUsers($limit = 10)
    {
        return self::getList([
            'limit' => $limit,
            'isvip' => 1,
            'orderby' => 'points'
        ]);
    }

    /**
     * 获取积分排行榜
     *
     * @param int $limit 数量限制
     * @return array
     */
    public static function getTopUsers($limit = 10)
    {
        return self::getList([
            'limit' => $limit,
            'orderby' => 'points'
        ]);
    }
}
