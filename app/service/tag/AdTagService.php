<?php
namespace app\service\tag;

use app\model\Ad;
use think\facade\Cache;

/**
 * 广告标签服务类
 * 处理广告标签的数据查询
 */
class AdTagService
{
    /**
     * 获取广告列表
     *
     * @param array $params 查询参数
     *   - position: 广告位置ID
     *   - limit: 数量限制
     * @return array
     */
    public static function getList($params = [])
    {
        $position = $params['position'] ?? 1;
        $limit = $params['limit'] ?? 0;

        // 尝试从缓存获取
        $cacheKey = 'ads_position_' . $position . '_limit_' . $limit;
        $ads = Cache::get($cacheKey);

        if ($ads !== false) {
            return $ads;
        }

        $now = date('Y-m-d H:i:s');

        $query = Ad::where('status', 1)
            ->where('position_id', $position)
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

        $ads = $query->select()->toArray();

        // 处理images字段（可能是JSON）
        foreach ($ads as &$ad) {
            if (!empty($ad['images']) && is_string($ad['images'])) {
                $images = json_decode($ad['images'], true);
                if (is_array($images) && !empty($images)) {
                    $ad['images'] = $images[0]; // 取第一张图片
                }
            }
        }

        // 缓存30分钟
        Cache::set($cacheKey, $ads, 1800);

        return $ads;
    }

    /**
     * 获取单个广告
     *
     * @param int $id 广告ID
     * @return array|null
     */
    public static function getOne($id)
    {
        $now = date('Y-m-d H:i:s');

        $ad = Ad::where('id', $id)
            ->where('status', 1)
            ->where(function($query) use ($now) {
                $query->whereNull('start_time')
                      ->whereOr('start_time', '<=', $now);
            })
            ->where(function($query) use ($now) {
                $query->whereNull('end_time')
                      ->whereOr('end_time', '>=', $now);
            })
            ->find();

        if (!$ad) {
            return null;
        }

        $ad = $ad->toArray();

        // 处理images字段
        if (!empty($ad['images']) && is_string($ad['images'])) {
            $images = json_decode($ad['images'], true);
            if (is_array($images) && !empty($images)) {
                $ad['images'] = $images[0];
            }
        }

        return $ad;
    }

    /**
     * 增加浏览量
     *
     * @param int $id 广告ID
     * @return bool
     */
    public static function incrementView($id)
    {
        return Ad::where('id', $id)->inc('view_count')->update();
    }

    /**
     * 增加点击量
     *
     * @param int $id 广告ID
     * @return bool
     */
    public static function incrementClick($id)
    {
        return Ad::where('id', $id)->inc('click_count')->update();
    }

    /**
     * 清除广告缓存
     *
     * @param int|null $position 广告位置ID，为空则清除所有
     * @return void
     */
    public static function clearCache($position = null)
    {
        if ($position !== null) {
            // 清除指定位置的缓存
            for ($limit = 0; $limit <= 100; $limit += 10) {
                Cache::delete('ads_position_' . $position . '_limit_' . $limit);
            }
        } else {
            // 清除所有广告缓存
            Cache::tag('ads')->clear();
        }
    }
}
