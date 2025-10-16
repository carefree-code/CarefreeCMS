<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Article;
use app\model\Category;
use app\model\Page;
use app\model\Config as ConfigModel;
use think\Request;
use think\facade\Config;

/**
 * Sitemap生成控制器
 */
class Sitemap extends BaseController
{
    // 网站基础URL（从系统配置读取）
    private $baseUrl = '';

    // sitemap文件访问URL前缀
    private $sitemapUrlPrefix = '';

    public function __construct()
    {
        // 获取系统配置的前端网站URL
        $siteUrl = ConfigModel::getConfig('site_url', '');

        if (!empty($siteUrl)) {
            // 如果配置了前端网站URL，使用前端网站URL
            $this->baseUrl = rtrim($siteUrl, '/');
            $this->sitemapUrlPrefix = $this->baseUrl;
        } else {
            // 如果没有配置，使用API域名 + /html
            $apiDomain = request()->domain();
            $this->baseUrl = $apiDomain . '/html';
            $this->sitemapUrlPrefix = $apiDomain . '/html';
        }
    }

    /**
     * 生成TXT格式sitemap
     */
    public function generateTxt()
    {
        try {
            $urls = $this->getAllUrls();

            // 生成txt内容
            $content = implode("\n", $urls);

            // 保存到文件
            $filePath = root_path() . 'html' . DIRECTORY_SEPARATOR . 'sitemap.txt';
            file_put_contents($filePath, $content);

            return Response::success([
                'file' => '/sitemap.txt',
                'url' => $this->sitemapUrlPrefix . '/sitemap.txt',
                'count' => count($urls)
            ], 'TXT格式sitemap生成成功');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成XML格式sitemap
     */
    public function generateXml()
    {
        try {
            $urls = $this->getAllUrls(true);

            // 生成XML内容
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($urls as $url) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
                if (isset($url['lastmod'])) {
                    $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
                }
                if (isset($url['changefreq'])) {
                    $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
                }
                if (isset($url['priority'])) {
                    $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
                }
                $xml .= '  </url>' . "\n";
            }

            $xml .= '</urlset>';

            // 保存到文件
            $filePath = root_path() . 'html' . DIRECTORY_SEPARATOR . 'sitemap.xml';
            file_put_contents($filePath, $xml);

            return Response::success([
                'file' => '/sitemap.xml',
                'url' => $this->sitemapUrlPrefix . '/sitemap.xml',
                'count' => count($urls)
            ], 'XML格式sitemap生成成功');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成HTML格式sitemap
     */
    public function generateHtml()
    {
        try {
            $data = $this->getStructuredData();

            // 生成HTML内容
            $html = '<!DOCTYPE html>' . "\n";
            $html .= '<html lang="zh-CN">' . "\n";
            $html .= '<head>' . "\n";
            $html .= '  <meta charset="UTF-8">' . "\n";
            $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
            $html .= '  <title>网站地图</title>' . "\n";
            $html .= '  <style>' . "\n";
            $html .= '    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }' . "\n";
            $html .= '    h1 { color: #333; border-bottom: 2px solid #409EFF; padding-bottom: 10px; }' . "\n";
            $html .= '    h2 { color: #555; margin-top: 30px; }' . "\n";
            $html .= '    ul { list-style: none; padding-left: 0; }' . "\n";
            $html .= '    li { margin: 8px 0; }' . "\n";
            $html .= '    a { color: #409EFF; text-decoration: none; }' . "\n";
            $html .= '    a:hover { text-decoration: underline; }' . "\n";
            $html .= '    .section { margin-bottom: 30px; }' . "\n";
            $html .= '    .category-item { margin-left: 20px; }' . "\n";
            $html .= '    .article-item { margin-left: 40px; font-size: 14px; }' . "\n";
            $html .= '    .count { color: #999; font-size: 12px; }' . "\n";
            $html .= '  </style>' . "\n";
            $html .= '</head>' . "\n";
            $html .= '<body>' . "\n";
            $html .= '  <h1>网站地图</h1>' . "\n";

            // 首页
            $html .= '  <div class="section">' . "\n";
            $html .= '    <h2>首页</h2>' . "\n";
            $html .= '    <ul><li><a href="' . $this->baseUrl . '/index.html">网站首页</a></li></ul>' . "\n";
            $html .= '  </div>' . "\n";

            // 文章分类和文章
            if (!empty($data['categories'])) {
                $html .= '  <div class="section">' . "\n";
                $html .= '    <h2>文章分类 <span class="count">(' . count($data['categories']) . '个分类)</span></h2>' . "\n";
                $html .= '    <ul>' . "\n";
                foreach ($data['categories'] as $category) {
                    $html .= '      <li class="category-item">' . "\n";
                    $html .= '        <a href="' . $this->baseUrl . '/category/' . $category['id'] . '.html">' . $category['name'] . '</a>' . "\n";
                    $html .= '        <span class="count">(' . $category['article_count'] . '篇文章)</span>' . "\n";
                    if (!empty($category['articles'])) {
                        $html .= '        <ul>' . "\n";
                        foreach ($category['articles'] as $article) {
                            $html .= '          <li class="article-item"><a href="' . $this->baseUrl . '/article/' . $article['id'] . '.html">' . $article['title'] . '</a></li>' . "\n";
                        }
                        $html .= '        </ul>' . "\n";
                    }
                    $html .= '      </li>' . "\n";
                }
                $html .= '    </ul>' . "\n";
                $html .= '  </div>' . "\n";
            }

            // 文章列表页
            if (!empty($data['article_pages'])) {
                $html .= '  <div class="section">' . "\n";
                $html .= '    <h2>文章列表 <span class="count">(共' . $data['article_pages'] . '页)</span></h2>' . "\n";
                $html .= '    <ul>' . "\n";
                for ($i = 1; $i <= $data['article_pages']; $i++) {
                    $fileName = $i === 1 ? 'articles.html' : "articles-{$i}.html";
                    $html .= '      <li><a href="' . $this->baseUrl . '/' . $fileName . '">第' . $i . '页</a></li>' . "\n";
                }
                $html .= '    </ul>' . "\n";
                $html .= '  </div>' . "\n";
            }

            // 单页面
            if (!empty($data['pages'])) {
                $html .= '  <div class="section">' . "\n";
                $html .= '    <h2>单页面 <span class="count">(' . count($data['pages']) . '个页面)</span></h2>' . "\n";
                $html .= '    <ul>' . "\n";
                foreach ($data['pages'] as $page) {
                    $html .= '      <li><a href="' . $this->baseUrl . '/' . $page['slug'] . '.html">' . $page['title'] . '</a></li>' . "\n";
                }
                $html .= '    </ul>' . "\n";
                $html .= '  </div>' . "\n";
            }

            $html .= '  <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px;">' . "\n";
            $html .= '    <p>生成时间：' . date('Y-m-d H:i:s') . '</p>' . "\n";
            $html .= '  </div>' . "\n";
            $html .= '</body>' . "\n";
            $html .= '</html>';

            // 保存到文件
            $filePath = root_path() . 'html' . DIRECTORY_SEPARATOR . 'sitemap.html';
            file_put_contents($filePath, $html);

            return Response::success([
                'file' => '/sitemap.html',
                'url' => $this->sitemapUrlPrefix . '/sitemap.html',
                'categories' => count($data['categories']),
                'pages' => count($data['pages'])
            ], 'HTML格式sitemap生成成功');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成所有格式的sitemap
     */
    public function generateAll()
    {
        try {
            $results = [];

            // 生成TXT
            $txtResult = $this->generateTxt();
            $results['txt'] = $txtResult->getData();

            // 生成XML
            $xmlResult = $this->generateXml();
            $results['xml'] = $xmlResult->getData();

            // 生成HTML
            $htmlResult = $this->generateHtml();
            $results['html'] = $htmlResult->getData();

            return Response::success($results, '所有格式sitemap生成成功');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 获取所有URL列表
     */
    private function getAllUrls($withMeta = false)
    {
        $urls = [];

        // 首页
        if ($withMeta) {
            $urls[] = [
                'loc' => $this->baseUrl . '/index.html',
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ];
        } else {
            $urls[] = $this->baseUrl . '/index.html';
        }

        // 文章列表页
        $articleCount = Article::where('status', 1)->count();  // 1 = 已发布
        $pageSize = 20; // 假设每页20篇
        $totalPages = ceil($articleCount / $pageSize);

        for ($i = 1; $i <= $totalPages; $i++) {
            // 第一页是 articles.html，其他页是 articles-2.html, articles-3.html
            $fileName = $i === 1 ? 'articles.html' : "articles-{$i}.html";

            if ($withMeta) {
                $urls[] = [
                    'loc' => $this->baseUrl . '/' . $fileName,
                    'changefreq' => 'daily',
                    'priority' => '0.8'
                ];
            } else {
                $urls[] = $this->baseUrl . '/' . $fileName;
            }
        }

        // 分类页
        $categories = Category::where('status', 1)->select();
        foreach ($categories as $category) {
            if ($withMeta) {
                $urls[] = [
                    'loc' => $this->baseUrl . '/category/' . $category->id . '.html',
                    'lastmod' => $category->update_time ? date('Y-m-d', strtotime($category->update_time)) : date('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.7'
                ];
            } else {
                $urls[] = $this->baseUrl . '/category/' . $category->id . '.html';
            }
        }

        // 文章页
        $articles = Article::where('status', 1)->select();  // 1 = 已发布
        foreach ($articles as $article) {
            if ($withMeta) {
                $urls[] = [
                    'loc' => $this->baseUrl . '/article/' . $article->id . '.html',
                    'lastmod' => $article->update_time ? date('Y-m-d', strtotime($article->update_time)) : date('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => '0.6'
                ];
            } else {
                $urls[] = $this->baseUrl . '/article/' . $article->id . '.html';
            }
        }

        // 单页面
        $pages = Page::where('status', 1)->select();  // 1 = 已发布
        foreach ($pages as $page) {
            if ($withMeta) {
                $urls[] = [
                    'loc' => $this->baseUrl . '/page/' . $page->slug . '.html',
                    'lastmod' => $page->update_time ? date('Y-m-d', strtotime($page->update_time)) : date('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => '0.5'
                ];
            } else {
                $urls[] = $this->baseUrl . '/' . $page->slug . '.html';
            }
        }

        return $urls;
    }

    /**
     * 获取结构化数据（用于HTML sitemap）
     */
    private function getStructuredData()
    {
        $data = [];

        // 获取分类及其文章
        $categories = Category::where('status', 1)
            ->order('sort', 'asc')
            ->select();

        $data['categories'] = [];
        foreach ($categories as $category) {
            $articles = Article::where('status', 1)  // 1 = 已发布
                ->where('category_id', $category->id)
                ->field('id,title')
                ->order('create_time', 'desc')
                ->limit(10) // 每个分类最多显示10篇文章
                ->select();

            $data['categories'][] = [
                'id' => $category->id,
                'name' => $category->name,
                'article_count' => Article::where('status', 1)  // 1 = 已发布
                    ->where('category_id', $category->id)
                    ->count(),
                'articles' => $articles->toArray()
            ];
        }

        // 文章列表页数
        $articleCount = Article::where('status', 1)->count();  // 1 = 已发布
        $data['article_pages'] = ceil($articleCount / 20);

        // 单页面
        $pages = Page::where('status', 1)  // 1 = 已发布
            ->field('id,title,slug')
            ->select();
        $data['pages'] = $pages->toArray();

        return $data;
    }
}
