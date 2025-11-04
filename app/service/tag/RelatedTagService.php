<?php
namespace app\service\tag;

use app\model\Article;
use think\facade\Cache;

/**
 * 相关文章标签服务类
 * 基于分类和标签推荐相关文章
 */
class RelatedTagService
{
    /**
     * 获取相关文章列表
     *
     * @param array $params 查询参数
     *   - aid: 当前文章ID（必填）
     *   - limit: 数量限制
     *   - type: 推荐类型（category-同分类, tag-同标签, auto-自动）
     * @return array
     */
    public static function getList($params = [])
    {
        $aid = $params['aid'] ?? 0;
        $limit = $params['limit'] ?? 5;
        $type = $params['type'] ?? 'auto';

        if ($aid <= 0) {
            return [];
        }

        // 尝试从缓存获取
        $cacheKey = 'related_aid_' . $aid . '_limit_' . $limit . '_type_' . $type;
        $articles = Cache::get($cacheKey);

        if ($articles !== false) {
            return $articles;
        }

        // 获取当前文章信息
        $currentArticle = Article::with(['tags'])->find($aid);

        if (!$currentArticle) {
            return [];
        }

        $articles = [];

        switch ($type) {
            case 'category':
                // 同分类推荐
                $articles = self::getByCategory($currentArticle, $limit);
                break;

            case 'tag':
                // 同标签推荐
                $articles = self::getByTags($currentArticle, $limit);
                break;

            case 'auto':
            default:
                // 自动推荐：优先同标签，不足则同分类
                $articles = self::getByTags($currentArticle, $limit);

                // 如果同标签文章不足，补充同分类文章
                if (count($articles) < $limit) {
                    $remainingLimit = $limit - count($articles);
                    $existingIds = array_column($articles, 'id');
                    $existingIds[] = $aid;

                    $categoryArticles = self::getByCategory($currentArticle, $remainingLimit, $existingIds);
                    $articles = array_merge($articles, $categoryArticles);
                }
                break;
        }

        // 确保不包含当前文章
        $articles = array_filter($articles, function($article) use ($aid) {
            return $article['id'] != $aid;
        });

        // 重新索引数组
        $articles = array_values($articles);

        // 限制数量
        if (count($articles) > $limit) {
            $articles = array_slice($articles, 0, $limit);
        }

        // 缓存30分钟
        Cache::set($cacheKey, $articles, 1800);

        return $articles;
    }

    /**
     * 基于分类推荐文章
     */
    private static function getByCategory($currentArticle, $limit, $excludeIds = [])
    {
        $query = Article::where('status', 1)
            ->where('category_id', $currentArticle->category_id)
            ->where('id', '<>', $currentArticle->id)
            ->order('view_count', 'desc')
            ->order('create_time', 'desc')
            ->limit($limit);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->select()->toArray();
    }

    /**
     * 基于标签推荐文章
     */
    private static function getByTags($currentArticle, $limit)
    {
        if (empty($currentArticle->tags)) {
            return [];
        }

        // 获取当前文章的所有标签ID
        $tagIds = array_column($currentArticle->tags->toArray(), 'id');

        if (empty($tagIds)) {
            return [];
        }

        // 查找拥有相同标签的文章
        $articles = Article::with(['category', 'tags'])
            ->where('status', 1)
            ->where('id', '<>', $currentArticle->id)
            ->whereExists(function($query) use ($tagIds) {
                $query->table('article_tag')
                    ->whereRaw('article_tag.article_id = articles.id')
                    ->whereIn('article_tag.tag_id', $tagIds);
            })
            ->order('view_count', 'desc')
            ->order('create_time', 'desc')
            ->limit($limit * 2) // 多取一些，用于后续过滤
            ->select()
            ->toArray();

        // 按匹配的标签数量排序
        usort($articles, function($a, $b) use ($tagIds) {
            $aTagIds = array_column($a['tags'], 'id');
            $bTagIds = array_column($b['tags'], 'id');

            $aMatchCount = count(array_intersect($aTagIds, $tagIds));
            $bMatchCount = count(array_intersect($bTagIds, $tagIds));

            return $bMatchCount - $aMatchCount; // 降序
        });

        return array_slice($articles, 0, $limit);
    }

    /**
     * 清除相关文章缓存
     *
     * @param int|null $aid 文章ID，为空则清除所有
     * @return void
     */
    public static function clearCache($aid = null)
    {
        if ($aid !== null) {
            // 清除指定文章的缓存
            Cache::delete('related_aid_' . $aid . '_*');
        } else {
            // 清除所有相关文章缓存
            Cache::tag('related')->clear();
        }
    }
}
