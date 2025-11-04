<?php
namespace app\service\tag;

use app\model\Tag;
use think\facade\Cache;
use think\facade\Db;

/**
 * 标签云服务类
 * 生成热门标签云展示
 */
class TagCloudService
{
    /**
     * 获取标签云数据
     *
     * @param array $params 查询参数
     *   - limit: 显示标签数量
     *   - orderby: 排序方式（count-使用次数, name-标签名称, random-随机）
     *   - minsize: 最小字体大小（默认12）
     *   - maxsize: 最大字体大小（默认28）
     * @return array
     */
    public static function get($params = [])
    {
        $limit = $params['limit'] ?? 30;
        $orderby = $params['orderby'] ?? 'count';
        $minSize = $params['minsize'] ?? 12;
        $maxSize = $params['maxsize'] ?? 28;

        // 尝试从缓存获取
        $cacheKey = 'tagcloud_limit_' . $limit . '_order_' . $orderby;
        $result = Cache::get($cacheKey);

        if ($result !== false) {
            return $result;
        }

        // 获取标签及其使用次数
        $tags = Db::table('tags')
            ->alias('t')
            ->field('t.id, t.name, t.slug, COUNT(at.article_id) as article_count')
            ->leftJoin('article_tag at', 't.id = at.tag_id')
            ->group('t.id')
            ->having('article_count > 0')
            ->order('article_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        if (empty($tags)) {
            Cache::set($cacheKey, [], 1800);
            return [];
        }

        // 计算使用次数的最大值和最小值
        $counts = array_column($tags, 'article_count');
        $minCount = min($counts);
        $maxCount = max($counts);

        // 计算每个标签的字体大小和级别
        foreach ($tags as &$tag) {
            // 计算字体大小（根据使用次数）
            if ($maxCount > $minCount) {
                $percent = ($tag['article_count'] - $minCount) / ($maxCount - $minCount);
                $tag['font_size'] = round($minSize + ($maxSize - $minSize) * $percent);
            } else {
                $tag['font_size'] = round(($minSize + $maxSize) / 2);
            }

            // 计算级别（1-5，用于CSS类名）
            if ($maxCount > $minCount) {
                $tag['level'] = ceil(($tag['article_count'] - $minCount) / ($maxCount - $minCount) * 5);
            } else {
                $tag['level'] = 3;
            }

            // 生成标签URL
            $tag['url'] = '/tag/' . $tag['id'] . '.html';
        }

        // 根据排序方式重新排序
        switch ($orderby) {
            case 'name':
                // 按标签名称排序
                usort($tags, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                break;

            case 'random':
                // 随机排序
                shuffle($tags);
                break;

            case 'count':
            default:
                // 已经按使用次数排序，无需处理
                break;
        }

        // 缓存30分钟
        Cache::set($cacheKey, $tags, 1800);

        return $tags;
    }

    /**
     * 渲染标签云HTML
     *
     * @param array $params 渲染参数
     * @return string
     */
    public static function render($params = [])
    {
        $tags = self::get($params);

        if (empty($tags)) {
            return '<div class="tag-cloud-empty">暂无标签</div>';
        }

        $html = '<div class="tag-cloud">';

        foreach ($tags as $tag) {
            $html .= sprintf(
                '<a href="%s" class="tag-item tag-level-%d" style="font-size: %dpx;" title="%s (%d篇文章)">%s</a>',
                $tag['url'],
                $tag['level'],
                $tag['font_size'],
                $tag['name'],
                $tag['article_count'],
                htmlspecialchars($tag['name'])
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * 清除标签云缓存
     *
     * @return void
     */
    public static function clearCache()
    {
        Cache::tag('tagcloud')->clear();
    }
}
