<?php
namespace app\service\tag;

use think\facade\Db;
use think\facade\Cache;

/**
 * 作者标签服务类
 * 展示热门作者和作者列表
 */
class AuthorTagService
{
    /**
     * 获取作者列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - orderby: 排序方式（article-发文数, view-总浏览量, like-总点赞）
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 10;
        $orderby = $params['orderby'] ?? 'article';

        // 尝试从缓存获取
        $cacheKey = 'authors_limit_' . $limit . '_order_' . $orderby;
        $authors = Cache::get($cacheKey);

        if ($authors !== false) {
            return $authors;
        }

        // 构建查询
        $query = Db::table('users')
            ->alias('u')
            ->field('u.id, u.username, u.real_name, u.avatar, u.create_time, u.role_id, r.name as role_name')
            ->leftJoin('roles r', 'u.role_id = r.id')
            ->where('u.status', 1);

        // 添加文章统计
        $query->field([
            '(SELECT COUNT(*) FROM articles WHERE user_id = u.id AND status = 1) as article_count',
            '(SELECT COALESCE(SUM(view_count), 0) FROM articles WHERE user_id = u.id AND status = 1) as total_views',
            '(SELECT COALESCE(SUM(like_count), 0) FROM articles WHERE user_id = u.id AND status = 1) as total_likes'
        ]);

        // 只显示有文章的作者
        $query->having('article_count > 0');

        // 根据排序方式排序
        switch ($orderby) {
            case 'view':
                $query->order('total_views', 'desc');
                break;
            case 'like':
                $query->order('total_likes', 'desc');
                break;
            case 'article':
            default:
                $query->order('article_count', 'desc');
                break;
        }

        $query->order('u.id', 'desc')->limit($limit);

        $authors = $query->select()->toArray();

        // 处理作者数据
        foreach ($authors as &$author) {
            // 显示名称
            $author['display_name'] = !empty($author['real_name']) ? $author['real_name'] : $author['username'];

            // 处理头像
            if (empty($author['avatar'])) {
                $author['avatar'] = '/static/default-avatar.png';
            }

            // 生成作者主页URL
            $author['url'] = '/author/' . $author['id'] . '.html';

            // 计算平均浏览量
            if ($author['article_count'] > 0) {
                $author['avg_views'] = round($author['total_views'] / $author['article_count']);
            } else {
                $author['avg_views'] = 0;
            }
        }

        // 缓存1小时
        Cache::set($cacheKey, $authors, 3600);

        return $authors;
    }

    /**
     * 获取单个作者信息（包含文章列表）
     *
     * @param int $authorId 作者ID
     * @param int $articleLimit 文章数量限制
     * @return array|null
     */
    public static function getDetail($authorId, $articleLimit = 10)
    {
        if ($authorId <= 0) {
            return null;
        }

        $cacheKey = 'author_detail_' . $authorId . '_limit_' . $articleLimit;
        $result = Cache::get($cacheKey);

        if ($result !== false) {
            return $result;
        }

        // 获取作者基本信息
        $author = Db::table('users')
            ->alias('u')
            ->field('u.*, r.name as role_name')
            ->leftJoin('roles r', 'u.role_id = r.id')
            ->where('u.id', $authorId)
            ->where('u.status', 1)
            ->find();

        if (!$author) {
            Cache::set($cacheKey, null, 1800);
            return null;
        }

        // 获取统计信息
        $author['article_count'] = Db::table('articles')
            ->where('user_id', $authorId)
            ->where('status', 1)
            ->count();

        $author['total_views'] = Db::table('articles')
            ->where('user_id', $authorId)
            ->where('status', 1)
            ->sum('view_count') ?: 0;

        $author['total_likes'] = Db::table('articles')
            ->where('user_id', $authorId)
            ->where('status', 1)
            ->sum('like_count') ?: 0;

        // 获取该作者的最新文章
        $author['articles'] = Db::table('articles')
            ->alias('a')
            ->field('a.id, a.title, a.summary, a.cover_image, a.view_count, a.like_count, a.create_time, c.name as category_name')
            ->leftJoin('categories c', 'a.category_id = c.id')
            ->where('a.user_id', $authorId)
            ->where('a.status', 1)
            ->order('a.create_time', 'desc')
            ->limit($articleLimit)
            ->select()
            ->toArray();

        // 处理作者数据
        $author['display_name'] = !empty($author['real_name']) ? $author['real_name'] : $author['username'];

        if (empty($author['avatar'])) {
            $author['avatar'] = '/static/default-avatar.png';
        }

        $author['url'] = '/author/' . $author['id'] . '.html';

        if ($author['article_count'] > 0) {
            $author['avg_views'] = round($author['total_views'] / $author['article_count']);
        } else {
            $author['avg_views'] = 0;
        }

        // 缓存30分钟
        Cache::set($cacheKey, $author, 1800);

        return $author;
    }

    /**
     * 清除作者缓存
     *
     * @param int|null $authorId 作者ID，为空则清除所有
     * @return void
     */
    public static function clearCache($authorId = null)
    {
        if ($authorId !== null) {
            Cache::delete('authors_*');
            Cache::delete('author_detail_' . $authorId . '_*');
        } else {
            Cache::tag('authors')->clear();
        }
    }
}
