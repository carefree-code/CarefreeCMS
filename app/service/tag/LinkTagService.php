<?php
namespace app\service\tag;

use app\model\Link;
use think\facade\Cache;

/**
 * 友情链接标签服务类
 * 处理友情链接标签的数据查询
 */
class LinkTagService
{
    /**
     * 获取友情链接列表
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
        $cacheKey = 'links_group_' . $group . '_limit_' . $limit;
        $links = Cache::get($cacheKey);

        if ($links !== false) {
            return $links;
        }

        $query = Link::where('status', 1)
            ->where('group_id', $group)
            ->order('sort', 'asc')
            ->order('id', 'desc');

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $links = $query->select()->toArray();

        // 缓存30分钟
        Cache::set($cacheKey, $links, 1800);

        return $links;
    }

    /**
     * 获取单个链接
     *
     * @param int $id 链接ID
     * @return array|null
     */
    public static function getOne($id)
    {
        return Link::where('id', $id)
            ->where('status', 1)
            ->find()
            ?->toArray();
    }

    /**
     * 清除链接缓存
     *
     * @param int|null $group 分组ID，为空则清除所有
     * @return void
     */
    public static function clearCache($group = null)
    {
        if ($group !== null) {
            // 清除指定分组的缓存（需要清除所有limit的变体）
            for ($limit = 0; $limit <= 100; $limit += 10) {
                Cache::delete('links_group_' . $group . '_limit_' . $limit);
            }
        } else {
            // 清除所有链接缓存
            Cache::tag('links')->clear();
        }
    }
}
