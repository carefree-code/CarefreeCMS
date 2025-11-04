<?php
namespace app\service\tag;

use think\facade\Db;
use think\facade\Cache;

/**
 * 用户信息标签服务类
 * 处理用户/作者信息展示
 */
class UserInfoService
{
    /**
     * 获取用户信息
     *
     * @param int $userId 用户ID
     * @return array|null
     */
    public static function get($userId)
    {
        if ($userId <= 0) {
            return null;
        }

        // 尝试从缓存获取
        $cacheKey = 'userinfo_' . $userId;
        $userInfo = Cache::get($cacheKey);

        if ($userInfo !== false) {
            return $userInfo;
        }

        // 从数据库获取用户信息
        $userInfo = Db::table('users')
            ->alias('u')
            ->field('u.id, u.username, u.real_name, u.email, u.avatar, u.role_id, u.status, u.create_time')
            ->where('u.id', $userId)
            ->find();

        if (!$userInfo) {
            Cache::set($cacheKey, null, 1800);
            return null;
        }

        // 获取用户的文章统计
        $articleCount = Db::table('articles')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->count();

        $userInfo['article_count'] = $articleCount;

        // 获取用户文章的总浏览量
        $totalViews = Db::table('articles')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->sum('view_count');

        $userInfo['total_views'] = $totalViews ?: 0;

        // 获取用户文章的总点赞数
        $totalLikes = Db::table('articles')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->sum('like_count');

        $userInfo['total_likes'] = $totalLikes ?: 0;

        // 获取角色名称
        if ($userInfo['role_id']) {
            $role = Db::table('roles')->where('id', $userInfo['role_id'])->find();
            $userInfo['role_name'] = $role ? $role['name'] : '未知';
        } else {
            $userInfo['role_name'] = '未知';
        }

        // 格式化显示名称
        $userInfo['display_name'] = !empty($userInfo['real_name']) ? $userInfo['real_name'] : $userInfo['username'];

        // 处理头像
        if (empty($userInfo['avatar'])) {
            $userInfo['avatar'] = '/static/default-avatar.png';
        }

        // 缓存1小时
        Cache::set($cacheKey, $userInfo, 3600);

        return $userInfo;
    }

    /**
     * 获取热门作者列表
     *
     * @param array $params
     * @return array
     */
    public static function getHotAuthors($params = [])
    {
        $limit = $params['limit'] ?? 10;

        $cacheKey = 'hot_authors_limit_' . $limit;
        $authors = Cache::get($cacheKey);

        if ($authors !== false) {
            return $authors;
        }

        // 获取发文最多的作者
        $authors = Db::table('articles')
            ->alias('a')
            ->field('a.user_id, u.username, u.real_name, u.avatar, COUNT(a.id) as article_count, SUM(a.view_count) as total_views')
            ->leftJoin('users u', 'a.user_id = u.id')
            ->where('a.status', 1)
            ->where('u.status', 1)
            ->group('a.user_id')
            ->order('article_count', 'desc')
            ->order('total_views', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        // 处理作者数据
        foreach ($authors as &$author) {
            $author['display_name'] = !empty($author['real_name']) ? $author['real_name'] : $author['username'];

            if (empty($author['avatar'])) {
                $author['avatar'] = '/static/default-avatar.png';
            }

            // 生成作者主页URL
            $author['url'] = '/author/' . $author['user_id'] . '.html';
        }

        // 缓存1小时
        Cache::set($cacheKey, $authors, 3600);

        return $authors;
    }

    /**
     * 清除用户信息缓存
     *
     * @param int|null $userId 用户ID，为空则清除所有
     * @return void
     */
    public static function clearCache($userId = null)
    {
        if ($userId !== null) {
            Cache::delete('userinfo_' . $userId);
        } else {
            Cache::tag('userinfo')->clear();
        }
    }
}
