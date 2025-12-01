<?php
namespace app\service\tag;

use app\model\Category;
use app\model\Page;
use think\facade\Cache;

/**
 * 导航标签服务类
 * 处理导航菜单标签的数据查询
 */
class NavTagService
{
    /**
     * 获取导航列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 0;

        // 尝试从缓存获取
        $cacheKey = 'site_nav_list';
        $navs = Cache::get($cacheKey);

        if ($navs !== false && empty($limit)) {
            return $navs;
        }

        // 构建导航菜单
        $navs = [];

        // 1. 首页
        $navs[] = [
            'id' => 'home',
            'title' => '首页',
            'url' => '/index.html',
            'type' => 'home',
            'sort' => 0
        ];

        // 2. 文章列表
        $navs[] = [
            'id' => 'articles',
            'title' => '文章',
            'url' => '/articles.html',
            'type' => 'articles',
            'sort' => 1
        ];

        // 3. 获取前5个顶级分类作为导航
        $categories = Category::where('status', 1)
            ->where('parent_id', 0)
            ->order('sort', 'asc')
            ->limit(5)
            ->select()
            ->toArray();

        $sort = 2;
        foreach ($categories as $category) {
            $navs[] = [
                'id' => 'category_' . $category['id'],
                'title' => $category['name'],
                'url' => '/category/' . $category['id'] . '.html',
                'type' => 'category',
                'sort' => $sort++,
                'category_id' => $category['id']
            ];
        }

        // 4. 获取自定义页面（如关于、联系）
        $pages = Page::where('status', 1)
            ->order('sort', 'asc')
            ->limit(3)
            ->select()
            ->toArray();

        foreach ($pages as $page) {
            $navs[] = [
                'id' => 'page_' . $page['id'],
                'title' => $page['title'],
                'url' => \app\service\UrlHelper::getPageUrl($page),
                'type' => 'page',
                'sort' => $sort++,
                'page_id' => $page['id']
            ];
        }

        // 按sort排序
        usort($navs, function($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });

        // 应用数量限制
        if ($limit > 0) {
            $navs = array_slice($navs, 0, $limit);
        }

        // 缓存30分钟
        if (empty($limit)) {
            Cache::set($cacheKey, $navs, 1800);
        }

        return $navs;
    }

    /**
     * 清除导航缓存
     *
     * @return void
     */
    public static function clearCache()
    {
        Cache::delete('site_nav_list');
    }
}
