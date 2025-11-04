<?php
namespace app\service\tag;

use app\model\MemberLevel;
use think\facade\Db;

/**
 * 会员等级标签服务类
 * 处理会员等级列表标签的数据查询
 */
class MemberLevelTagService
{
    /**
     * 获取会员等级列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 0;

        $query = MemberLevel::where('status', 1)
                            ->order('level', 'asc');

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $levels = $query->select()->toArray();

        // 统计每个等级的用户数
        foreach ($levels as &$level) {
            $level['user_count'] = Db::name('front_users')
                ->where('level', $level['level'])
                ->where('status', 1)
                ->count();
        }

        return $levels;
    }

    /**
     * 获取单个会员等级
     *
     * @param int $level 等级数值
     * @return array|null
     */
    public static function getOne($level)
    {
        $levelData = MemberLevel::where('level', $level)
            ->where('status', 1)
            ->find();

        if (!$levelData) {
            return null;
        }

        $result = $levelData->toArray();

        // 统计该等级的用户数
        $result['user_count'] = Db::name('front_users')
            ->where('level', $level)
            ->where('status', 1)
            ->count();

        return $result;
    }

    /**
     * 获取某个积分对应的等级
     *
     * @param int $points 积分
     * @return array|null
     */
    public static function getLevelByPoints($points)
    {
        $level = MemberLevel::where('status', 1)
            ->where('upgrade_points', '<=', $points)
            ->order('level', 'desc')
            ->find();

        return $level ? $level->toArray() : null;
    }

    /**
     * 获取下一个等级信息
     *
     * @param int $currentLevel 当前等级
     * @return array|null
     */
    public static function getNextLevel($currentLevel)
    {
        $nextLevel = MemberLevel::where('status', 1)
            ->where('level', '>', $currentLevel)
            ->order('level', 'asc')
            ->find();

        return $nextLevel ? $nextLevel->toArray() : null;
    }
}
