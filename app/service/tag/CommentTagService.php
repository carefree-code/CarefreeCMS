<?php
namespace app\service\tag;

use think\facade\Db;
use think\facade\Cache;

/**
 * 评论标签服务类
 * 处理评论数据展示
 */
class CommentTagService
{
    /**
     * 获取最新评论列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - aid: 文章ID（指定文章的评论）
     *   - status: 评论状态（1-已审核，0-待审核）
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 10;
        $aid = $params['aid'] ?? 0;
        $status = $params['status'] ?? 1;

        // 尝试从缓存获取
        $cacheKey = 'comments_aid_' . $aid . '_limit_' . $limit . '_status_' . $status;
        $comments = Cache::get($cacheKey);

        if ($comments !== false) {
            return $comments;
        }

        $query = Db::table('comments')
            ->alias('c')
            ->field('c.*, a.title as article_title, a.id as article_id')
            ->leftJoin('articles a', 'c.article_id = a.id')
            ->where('c.status', $status)
            ->order('c.create_time', 'desc');

        // 如果指定文章ID
        if ($aid > 0) {
            $query->where('c.article_id', $aid);
        }

        $query->limit($limit);

        $comments = $query->select()->toArray();

        // 处理评论数据
        foreach ($comments as &$comment) {
            // 生成用户显示名称
            if ($comment['is_admin']) {
                $comment['display_name'] = '管理员';
            } elseif (!empty($comment['user_name'])) {
                $comment['display_name'] = $comment['user_name'];
            } else {
                $comment['display_name'] = '访客';
            }

            // 生成文章URL
            $comment['article_url'] = '/article/' . $comment['article_id'] . '.html';

            // 截取评论内容（如果太长）
            if (mb_strlen($comment['content'], 'utf-8') > 100) {
                $comment['short_content'] = mb_substr($comment['content'], 0, 100, 'utf-8') . '...';
            } else {
                $comment['short_content'] = $comment['content'];
            }

            // 格式化时间
            $comment['formatted_time'] = self::formatTime($comment['create_time']);
        }

        // 缓存10分钟
        Cache::set($cacheKey, $comments, 600);

        return $comments;
    }

    /**
     * 获取文章评论统计
     *
     * @param int $aid 文章ID
     * @return int
     */
    public static function getCount($aid = 0)
    {
        $cacheKey = 'comment_count_aid_' . $aid;
        $count = Cache::get($cacheKey);

        if ($count !== false) {
            return $count;
        }

        $query = Db::table('comments')->where('status', 1);

        if ($aid > 0) {
            $query->where('article_id', $aid);
        }

        $count = $query->count();

        // 缓存30分钟
        Cache::set($cacheKey, $count, 1800);

        return $count;
    }

    /**
     * 格式化时间为友好显示
     *
     * @param string $time
     * @return string
     */
    private static function formatTime($time)
    {
        $timestamp = strtotime($time);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' 分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' 小时前';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' 天前';
        } else {
            return date('Y-m-d', $timestamp);
        }
    }

    /**
     * 获取热门评论
     *
     * @param array $params
     * @return array
     */
    public static function getHot($params = [])
    {
        $limit = $params['limit'] ?? 10;

        $cacheKey = 'hot_comments_limit_' . $limit;
        $comments = Cache::get($cacheKey);

        if ($comments !== false) {
            return $comments;
        }

        $comments = Db::table('comments')
            ->alias('c')
            ->field('c.*, a.title as article_title, a.id as article_id')
            ->leftJoin('articles a', 'c.article_id = a.id')
            ->where('c.status', 1)
            ->order('c.like_count', 'desc')
            ->order('c.create_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        // 处理评论数据
        foreach ($comments as &$comment) {
            if ($comment['is_admin']) {
                $comment['display_name'] = '管理员';
            } elseif (!empty($comment['user_name'])) {
                $comment['display_name'] = $comment['user_name'];
            } else {
                $comment['display_name'] = '访客';
            }

            $comment['article_url'] = '/article/' . $comment['article_id'] . '.html';

            if (mb_strlen($comment['content'], 'utf-8') > 100) {
                $comment['short_content'] = mb_substr($comment['content'], 0, 100, 'utf-8') . '...';
            } else {
                $comment['short_content'] = $comment['content'];
            }

            $comment['formatted_time'] = self::formatTime($comment['create_time']);
        }

        // 缓存30分钟
        Cache::set($cacheKey, $comments, 1800);

        return $comments;
    }

    /**
     * 清除评论缓存
     *
     * @param int|null $aid 文章ID，为空则清除所有
     * @return void
     */
    public static function clearCache($aid = null)
    {
        if ($aid !== null) {
            Cache::delete('comments_aid_' . $aid . '_*');
            Cache::delete('comment_count_aid_' . $aid);
        } else {
            Cache::tag('comments')->clear();
        }
    }
}
