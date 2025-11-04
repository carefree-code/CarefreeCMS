<?php
namespace app\service\tag;

use app\model\Slider;
use think\facade\Cache;

/**
 * 幻灯片标签服务类
 * 处理幻灯片标签的数据查询
 */
class SliderTagService
{
    /**
     * 获取幻灯片列表
     *
     * @param array $params 查询参数
     *   - group: 分组ID
     *   - limit: 数量限制
     * @return array
     */
    public static function getList($params = [])
    {
        $group = $params['group'] ?? 1;
        $limit = $params['limit'] ?? 0;

        // 尝试从缓存获取
        $cacheKey = 'sliders_group_' . $group . '_limit_' . $limit;
        $sliders = Cache::get($cacheKey);

        if ($sliders !== false) {
            return $sliders;
        }

        $now = date('Y-m-d H:i:s');

        $query = Slider::where('status', 1)
            ->where('group_id', $group)
            ->where(function($query) use ($now) {
                // 未设置开始时间 或 已到开始时间
                $query->whereNull('start_time')
                      ->whereOr('start_time', '<=', $now);
            })
            ->where(function($query) use ($now) {
                // 未设置结束时间 或 未到结束时间
                $query->whereNull('end_time')
                      ->whereOr('end_time', '>=', $now);
            })
            ->order('sort', 'asc')
            ->order('id', 'desc');

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $sliders = $query->select()->toArray();

        // 缓存30分钟
        Cache::set($cacheKey, $sliders, 1800);

        return $sliders;
    }

    /**
     * 获取单个幻灯片
     *
     * @param int $id 幻灯片ID
     * @return array|null
     */
    public static function getOne($id)
    {
        $now = date('Y-m-d H:i:s');

        return Slider::where('id', $id)
            ->where('status', 1)
            ->where(function($query) use ($now) {
                $query->whereNull('start_time')
                      ->whereOr('start_time', '<=', $now);
            })
            ->where(function($query) use ($now) {
                $query->whereNull('end_time')
                      ->whereOr('end_time', '>=', $now);
            })
            ->find()
            ?->toArray();
    }

    /**
     * 增加浏览量
     *
     * @param int $id 幻灯片ID
     * @return bool
     */
    public static function incrementView($id)
    {
        return Slider::where('id', $id)->inc('view_count')->update();
    }

    /**
     * 增加点击量
     *
     * @param int $id 幻灯片ID
     * @return bool
     */
    public static function incrementClick($id)
    {
        return Slider::where('id', $id)->inc('click_count')->update();
    }

    /**
     * 清除幻灯片缓存
     *
     * @param int|null $group 分组ID，为空则清除所有
     * @return void
     */
    public static function clearCache($group = null)
    {
        if ($group !== null) {
            // 清除指定分组的缓存
            for ($limit = 0; $limit <= 100; $limit += 10) {
                Cache::delete('sliders_group_' . $group . '_limit_' . $limit);
            }
        } else {
            // 清除所有幻灯片缓存
            Cache::tag('sliders')->clear();
        }
    }
}
