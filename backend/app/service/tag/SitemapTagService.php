<?php
namespace app\service\tag;

use app\model\Article;
use app\model\Category;
use app\model\Page;
use think\facade\Db;

/**
 * 站点地图标签服务类
 * 处理站点地图标签的数据查询
 */
class SitemapTagService
{
    /**
     * 获取站点地图数据
     *
     * @param array $params 查询参数
     *   - type: 类型（article-文章，category-分类，page-页面，all-全部）
     *   - format: 格式（html-HTML列表，xml-XML格式，json-JSON格式）
     * @return array
     */
    public static function getList($params = [])
    {
        $type = $params['type'] ?? 'all';
        $format = $params['format'] ?? 'html';

        try {
            $sitemap = [];

            // 根据类型获取数据
            switch ($type) {
                case 'article':
                    $sitemap = self::getArticleSitemap();
                    break;
                case 'category':
                    $sitemap = self::getCategorySitemap();
                    break;
                case 'page':
                    $sitemap = self::getPageSitemap();
                    break;
                case 'all':
                default:
                    $sitemap = array_merge(
                        self::getPageSitemap(),
                        self::getCategorySitemap(),
                        self::getArticleSitemap()
                    );
                    break;
            }

            // 格式化输出
            if ($format == 'xml') {
                return self::formatAsXml($sitemap);
            } elseif ($format == 'json') {
                return self::formatAsJson($sitemap);
            } else {
                return $sitemap;
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 获取文章站点地图
     *
     * @return array
     */
    private static function getArticleSitemap()
    {
        $articles = Article::where('status', 1)
            ->order('update_time', 'desc')
            ->limit(1000) // 限制数量，避免数据过大
            ->select()
            ->toArray();

        $sitemap = [];
        foreach ($articles as $article) {
            $sitemap[] = [
                'type' => 'article',
                'loc' => self::getArticleUrl($article['id']),
                'title' => $article['title'],
                'lastmod' => date('Y-m-d', is_numeric($article['update_time']) ? $article['update_time'] : strtotime($article['update_time'])),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }

        return $sitemap;
    }

    /**
     * 获取分类站点地图
     *
     * @return array
     */
    private static function getCategorySitemap()
    {
        $categories = Category::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        $sitemap = [];
        foreach ($categories as $category) {
            $sitemap[] = [
                'type' => 'category',
                'loc' => self::getCategoryUrl($category['id']),
                'title' => $category['name'],
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '0.9'
            ];
        }

        return $sitemap;
    }

    /**
     * 获取页面站点地图
     *
     * @return array
     */
    private static function getPageSitemap()
    {
        $pages = Page::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        $sitemap = [];

        // 添加首页
        $sitemap[] = [
            'type' => 'page',
            'loc' => request()->domain() . '/',
            'title' => '首页',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];

        // 添加自定义页面
        foreach ($pages as $page) {
            $sitemap[] = [
                'type' => 'page',
                'loc' => self::getPageUrl($page),  // 传递整个 page 数组
                'title' => $page['title'],
                'lastmod' => date('Y-m-d', is_numeric($page['update_time']) ? $page['update_time'] : strtotime($page['update_time'])),
                'changefreq' => 'monthly',
                'priority' => '0.7'
            ];
        }

        return $sitemap;
    }

    /**
     * 格式化为XML
     *
     * @param array $sitemap 站点地图数据
     * @return array
     */
    private static function formatAsXml($sitemap)
    {
        // 返回数据结构，实际的XML生成在模板中处理
        return [
            'format' => 'xml',
            'data' => $sitemap
        ];
    }

    /**
     * 格式化为JSON
     *
     * @param array $sitemap 站点地图数据
     * @return array
     */
    private static function formatAsJson($sitemap)
    {
        return [
            'format' => 'json',
            'data' => $sitemap
        ];
    }

    /**
     * 获取文章URL
     *
     * @param int $id 文章ID
     * @return string
     */
    private static function getArticleUrl($id)
    {
        return request()->domain() . '/article/' . $id . '.html';
    }

    /**
     * 获取分类URL
     *
     * @param int $id 分类ID
     * @return string
     */
    private static function getCategoryUrl($id)
    {
        return request()->domain() . '/category/' . $id . '.html';
    }

    /**
     * 获取页面URL
     *
     * @param mixed $idOrPage 页面ID或页面数组
     * @return string
     */
    private static function getPageUrl($idOrPage)
    {
        // 如果是ID，需要先查询完整数据
        if (!is_array($idOrPage)) {
            $idOrPage = \app\model\Page::find($idOrPage);
            if (!$idOrPage) {
                return request()->domain() . '/page-not-found.html';
            }
            $idOrPage = $idOrPage->toArray();
        }

        // 使用 UrlHelper 生成完整 URL
        return \app\service\UrlHelper::getPageUrl($idOrPage, null, true);
    }

    /**
     * 生成XML站点地图文件
     *
     * @param string $filepath 文件路径
     * @return bool
     */
    public static function generateXmlFile($filepath = '')
    {
        if (empty($filepath)) {
            $filepath = public_path() . 'sitemap.xml';
        }

        try {
            $sitemap = self::getList(['type' => 'all', 'format' => 'html']);

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($sitemap as $item) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars($item['loc']) . '</loc>' . "\n";
                $xml .= '    <lastmod>' . $item['lastmod'] . '</lastmod>' . "\n";
                $xml .= '    <changefreq>' . $item['changefreq'] . '</changefreq>' . "\n";
                $xml .= '    <priority>' . $item['priority'] . '</priority>' . "\n";
                $xml .= '  </url>' . "\n";
            }

            $xml .= '</urlset>';

            return file_put_contents($filepath, $xml) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取站点地图统计
     *
     * @return array
     */
    public static function getStats()
    {
        try {
            return [
                'total' => Article::where('status', 1)->count() +
                          Category::where('status', 1)->count() +
                          Page::where('status', 1)->count() + 1, // +1 for homepage
                'articles' => Article::where('status', 1)->count(),
                'categories' => Category::where('status', 1)->count(),
                'pages' => Page::where('status', 1)->count() + 1, // +1 for homepage
                'last_update' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 构建树形结构站点地图
     *
     * @return array
     */
    public static function getTreeStructure()
    {
        try {
            $tree = [];

            // 首页
            $tree[] = [
                'title' => '首页',
                'url' => request()->domain() . '/',
                'children' => []
            ];

            // 分类及其文章
            $categories = Category::where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            foreach ($categories as $category) {
                $articles = Article::where('status', 1)
                    ->where('category_id', $category['id'])
                    ->order('create_time', 'desc')
                    ->limit(10) // 每个分类只显示最新10篇
                    ->select()
                    ->toArray();

                $children = [];
                foreach ($articles as $article) {
                    $children[] = [
                        'title' => $article['title'],
                        'url' => self::getArticleUrl($article['id']),
                        'children' => []
                    ];
                }

                $tree[] = [
                    'title' => $category['name'],
                    'url' => self::getCategoryUrl($category['id']),
                    'children' => $children
                ];
            }

            // 自定义页面
            $pages = Page::where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            foreach ($pages as $page) {
                $tree[] = [
                    'title' => $page['title'],
                    'url' => self::getPageUrl($page),
                    'children' => []
                ];
            }

            return $tree;
        } catch (\Exception $e) {
            return [];
        }
    }
}
