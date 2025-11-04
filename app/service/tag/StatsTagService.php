<?php
namespace app\service\tag;

use app\model\Article;
use app\model\Category;
use app\model\Tag;
use think\facade\Cache;

/**
 * 统计标签服务类
 * 提供网站各类统计数据
 */
class StatsTagService
{
    /**
     * 获取统计数据
     *
     * @param array $params 统计参数
     *   - type: 统计类型 (article-文章数, category-分类数, tag-标签数, view-总浏览量)
     *   - catid: 分类ID（用于统计指定分类的数据）
     * @return int|array
     */
    public static function get($params = [])
    {
        $type = $params['type'] ?? 'article';
        $catid = $params['catid'] ?? 0;

        // 尝试从缓存获取（使用has判断缓存是否存在）
        $cacheKey = 'stats_' . $type . '_catid_' . $catid;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = 0;

        switch ($type) {
            case 'article':
                // 文章总数
                $query = Article::where('status', 1);
                if ($catid > 0) {
                    $query->where('category_id', $catid);
                }
                $result = $query->count();
                break;

            case 'category':
                // 分类总数
                $query = Category::where('status', 1);
                if ($catid > 0) {
                    // 获取指定分类的子分类数
                    $query->where('parent_id', $catid);
                }
                $result = $query->count();
                break;

            case 'tag':
                // 标签总数
                $result = Tag::count();
                break;

            case 'view':
                // 总浏览量
                $query = Article::where('status', 1);
                if ($catid > 0) {
                    $query->where('category_id', $catid);
                }
                $result = $query->sum('view_count') ?: 0;
                break;

            case 'todayarticle':
                // 今日文章数
                $today = date('Y-m-d');
                $query = Article::where('status', 1)
                    ->whereTime('create_time', '>=', $today);
                if ($catid > 0) {
                    $query->where('category_id', $catid);
                }
                $result = $query->count();
                break;

            case 'todayview':
                // 今日浏览量（需要有访问记录表，这里简化处理）
                $result = 0;
                break;
        }

        // 缓存1小时
        Cache::set($cacheKey, $result, 3600);

        return $result;
    }

    /**
     * 获取全部统计信息
     *
     * @return array
     */
    public static function getAll()
    {
        $cacheKey = 'stats_all';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $stats = [
            'article_count' => self::get(['type' => 'article']),
            'category_count' => self::get(['type' => 'category']),
            'tag_count' => self::get(['type' => 'tag']),
            'total_views' => self::get(['type' => 'view']),
            'today_article' => self::get(['type' => 'todayarticle']),
        ];

        // 缓存1小时
        Cache::set($cacheKey, $stats, 3600);

        return $stats;
    }

    /**
     * 清除统计缓存
     *
     * @return void
     */
    public static function clearCache()
    {
        Cache::tag('stats')->clear();
    }
}
