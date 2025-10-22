<?php
declare (strict_types = 1);

namespace app\service;

use app\model\Article;
use app\model\Category;
use app\model\Tag;
use app\model\Page;

/**
 * 增强的Sitemap生成器
 * 支持标准sitemap、图片sitemap、新闻sitemap、多语言sitemap
 */
class EnhancedSitemapGenerator
{
    private $baseUrl;
    private $sitemapDir;

    public function __construct($baseUrl = '')
    {
        $this->baseUrl = $baseUrl ?: request()->domain();
        $this->sitemapDir = app()->getRootPath() . 'html/';

        // 确保目录存在
        if (!is_dir($this->sitemapDir)) {
            mkdir($this->sitemapDir, 0755, true);
        }
    }

    /**
     * 获取基础URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * 生成所有sitemap
     */
    public function generateAll()
    {
        $results = [];

        // 生成主sitemap
        $results['main'] = $this->generateMainSitemap();

        // 生成图片sitemap
        $results['images'] = $this->generateImageSitemap();

        // 生成新闻sitemap
        $results['news'] = $this->generateNewsSitemap();

        // 生成sitemap索引
        $results['index'] = $this->generateSitemapIndex();

        return $results;
    }

    /**
     * 生成主sitemap（标准格式）
     */
    public function generateMainSitemap()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        // 首页
        $this->addUrl($xml, $this->baseUrl, date('Y-m-d'), '1.0', 'daily');

        // 文章
        $articles = Article::where('status', 1)
            ->whereNotNull('publish_time')
            ->order('publish_time', 'desc')
            ->select();

        foreach ($articles as $article) {
            $url = $this->baseUrl . '/article/' . $article->id . '.html';
            $this->addUrl($xml, $url, $article->update_time, '0.8', 'weekly');
        }

        // 分类
        $categories = Category::where('status', 1)->select();
        foreach ($categories as $category) {
            $url = $this->baseUrl . '/category/' . $category->id . '.html';
            $this->addUrl($xml, $url, $category->update_time, '0.6', 'daily');
        }

        // 标签
        $tags = Tag::select();
        foreach ($tags as $tag) {
            $url = $this->baseUrl . '/tag/' . $tag->id . '.html';
            $this->addUrl($xml, $url, date('Y-m-d'), '0.5', 'weekly');
        }

        // 单页
        $pages = Page::where('status', 1)->select();
        foreach ($pages as $page) {
            $url = $this->baseUrl . '/page/' . $page->slug . '.html';
            $this->addUrl($xml, $url, $page->update_time, '0.7', 'monthly');
        }

        // 保存文件
        $filename = $this->sitemapDir . 'sitemap.xml';
        $xml->asXML($filename);

        return [
            'success' => true,
            'file' => $filename,
            'count' => count($xml->url)
        ];
    }

    /**
     * 生成图片sitemap
     */
    public function generateImageSitemap()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"></urlset>');

        $articles = Article::where('status', 1)
            ->whereNotNull('publish_time')
            ->order('publish_time', 'desc')
            ->select();

        foreach ($articles as $article) {
            $images = $this->extractImages($article);

            if (!empty($images)) {
                $url = $xml->addChild('url');
                $url->addChild('loc', $this->baseUrl . '/article/' . $article->id . '.html');

                foreach ($images as $image) {
                    $imageNode = $url->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                    $imageNode->addChild('image:loc', $this->normalizeUrl($image['url']), 'http://www.google.com/schemas/sitemap-image/1.1');

                    if (!empty($image['title'])) {
                        $imageNode->addChild('image:title', htmlspecialchars($image['title']), 'http://www.google.com/schemas/sitemap-image/1.1');
                    }

                    if (!empty($image['caption'])) {
                        $imageNode->addChild('image:caption', htmlspecialchars($image['caption']), 'http://www.google.com/schemas/sitemap-image/1.1');
                    }
                }
            }
        }

        $filename = $this->sitemapDir . 'sitemap-images.xml';
        $xml->asXML($filename);

        return [
            'success' => true,
            'file' => $filename,
            'count' => count($xml->url)
        ];
    }

    /**
     * 生成新闻sitemap（最近2天的文章）
     */
    public function generateNewsSitemap()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"></urlset>');

        // 获取最近2天的文章
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));
        $articles = Article::where('status', 1)
            ->where('publish_time', '>=', $twoDaysAgo)
            ->order('publish_time', 'desc')
            ->select();

        foreach ($articles as $article) {
            $url = $xml->addChild('url');
            $url->addChild('loc', $this->baseUrl . '/article/' . $article->id . '.html');

            $news = $url->addChild('news:news', null, 'http://www.google.com/schemas/sitemap-news/0.9');

            // 发布信息
            $publication = $news->addChild('news:publication', null, 'http://www.google.com/schemas/sitemap-news/0.9');
            $publication->addChild('news:name', config('site.site_name', 'My Site'), 'http://www.google.com/schemas/sitemap-news/0.9');
            $publication->addChild('news:language', 'zh-cn', 'http://www.google.com/schemas/sitemap-news/0.9');

            // 文章信息
            $news->addChild('news:publication_date', date('c', strtotime($article->publish_time)), 'http://www.google.com/schemas/sitemap-news/0.9');
            $news->addChild('news:title', htmlspecialchars($article->title), 'http://www.google.com/schemas/sitemap-news/0.9');

            if (!empty($article->seo_keywords)) {
                $news->addChild('news:keywords', htmlspecialchars($article->seo_keywords), 'http://www.google.com/schemas/sitemap-news/0.9');
            }
        }

        $filename = $this->sitemapDir . 'sitemap-news.xml';
        $xml->asXML($filename);

        return [
            'success' => true,
            'file' => $filename,
            'count' => count($xml->url)
        ];
    }

    /**
     * 生成sitemap索引文件
     */
    public function generateSitemapIndex()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');

        $sitemaps = [
            'sitemap.xml',
            'sitemap-images.xml',
            'sitemap-news.xml'
        ];

        foreach ($sitemaps as $sitemapFile) {
            $filePath = $this->sitemapDir . $sitemapFile;
            if (file_exists($filePath)) {
                $sitemap = $xml->addChild('sitemap');
                $sitemap->addChild('loc', $this->baseUrl . '/' . $sitemapFile);
                $sitemap->addChild('lastmod', date('c', filemtime($filePath)));
            }
        }

        $filename = $this->sitemapDir . 'sitemap-index.xml';
        $xml->asXML($filename);

        return [
            'success' => true,
            'file' => $filename,
            'count' => count($xml->sitemap)
        ];
    }

    /**
     * 生成多语言sitemap
     * @param string $lang 语言代码（如：zh-cn, en）
     */
    public function generateMultilingualSitemap($lang = 'zh-cn')
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"></urlset>');

        // 文章
        $articles = Article::where('status', 1)
            ->whereNotNull('publish_time')
            ->order('publish_time', 'desc')
            ->select();

        foreach ($articles as $article) {
            $url = $xml->addChild('url');
            $loc = $this->baseUrl . '/' . $lang . '/article/' . $article->id . '.html';
            $url->addChild('loc', $loc);

            // 添加替代语言链接
            $alternates = [
                'zh-cn' => $this->baseUrl . '/zh-cn/article/' . $article->id . '.html',
                'en' => $this->baseUrl . '/en/article/' . $article->id . '.html',
            ];

            foreach ($alternates as $alternateLang => $alternateUrl) {
                $link = $url->addChild('xhtml:link', null, 'http://www.w3.org/1999/xhtml');
                $link->addAttribute('rel', 'alternate');
                $link->addAttribute('hreflang', $alternateLang);
                $link->addAttribute('href', $alternateUrl);
            }
        }

        $filename = $this->sitemapDir . 'sitemap-' . $lang . '.xml';
        $xml->asXML($filename);

        return [
            'success' => true,
            'file' => $filename,
            'count' => count($xml->url)
        ];
    }

    /**
     * 添加URL到sitemap
     */
    private function addUrl($xml, $loc, $lastmod = '', $priority = '0.5', $changefreq = 'weekly')
    {
        $url = $xml->addChild('url');
        $url->addChild('loc', htmlspecialchars($loc));

        if ($lastmod) {
            $url->addChild('lastmod', date('Y-m-d', strtotime($lastmod)));
        }

        $url->addChild('changefreq', $changefreq);
        $url->addChild('priority', $priority);
    }

    /**
     * 从文章中提取图片
     */
    private function extractImages($article)
    {
        $images = [];

        // 封面图
        if (!empty($article->cover_image)) {
            $images[] = [
                'url' => $article->cover_image,
                'title' => $article->title,
                'caption' => ''
            ];
        }

        // 内容中的图片
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $article->content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $imgSrc) {
                // 提取alt属性作为标题
                $title = '';
                if (preg_match('/alt=["\']([^"\']*)["\']/', $matches[0][$index], $altMatch)) {
                    $title = $altMatch[1];
                }

                $images[] = [
                    'url' => $imgSrc,
                    'title' => $title,
                    'caption' => ''
                ];
            }
        }

        return array_slice($images, 0, 10); // 限制每篇文章最多10张图片
    }

    /**
     * 规范化URL（相对路径转绝对路径）
     */
    private function normalizeUrl($url)
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        if (strpos($url, '/') === 0) {
            return $this->baseUrl . $url;
        }

        return $this->baseUrl . '/' . $url;
    }

    /**
     * Ping搜索引擎
     */
    public function pingSearchEngines($sitemapUrl)
    {
        $results = [];

        // Google
        $googleUrl = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);
        $results['google'] = $this->sendPing($googleUrl);

        // Bing
        $bingUrl = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);
        $results['bing'] = $this->sendPing($bingUrl);

        // Baidu
        $baiduUrl = 'http://ping.baidu.com/ping/RPC2';
        $results['baidu'] = $this->sendBaiduPing($sitemapUrl);

        return $results;
    }

    /**
     * 发送ping请求
     */
    private function sendPing($url)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [
                'success' => $httpCode == 200,
                'code' => $httpCode,
                'message' => $httpCode == 200 ? 'Success' : 'Failed'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 发送百度ping
     */
    private function sendBaiduPing($sitemapUrl)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://data.zz.baidu.com/urls?site=' . $this->baseUrl . '&token=YOUR_TOKEN');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $sitemapUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
            $result = curl_exec($ch);
            curl_close($ch);

            return [
                'success' => true,
                'message' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 生成TXT格式sitemap
     * @return array
     */
    public function generateTxtSitemap()
    {
        try {
            $urls = [];

            // 首页
            $urls[] = $this->baseUrl;

            // 文章
            $articles = Article::where('status', 1)
                ->whereNotNull('publish_time')
                ->order('publish_time', 'desc')
                ->select();
            foreach ($articles as $article) {
                $urls[] = $this->baseUrl . '/article/' . $article->id . '.html';
            }

            // 分类
            $categories = Category::where('status', 1)->select();
            foreach ($categories as $category) {
                $urls[] = $this->baseUrl . '/category/' . $category->id . '.html';
            }

            // 标签
            $tags = Tag::where('status', 1)->select();
            foreach ($tags as $tag) {
                $urls[] = $this->baseUrl . '/tag/' . $tag->id . '.html';
            }

            // 单页
            $pages = Page::where('status', 1)->select();
            foreach ($pages as $page) {
                $urls[] = $this->baseUrl . '/' . $page->slug . '.html';
            }

            // 生成txt内容
            $content = implode("\n", $urls);

            // 保存到文件
            $filename = $this->sitemapDir . 'sitemap.txt';
            file_put_contents($filename, $content);

            return [
                'success' => true,
                'file' => $filename,
                'url' => $this->baseUrl . '/sitemap.txt',
                'count' => count($urls)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 生成HTML格式sitemap（用户友好的网页版）
     * @return array
     */
    public function generateHtmlSitemap()
    {
        try {
            $html = '<!DOCTYPE html>' . "\n";
            $html .= '<html lang="zh-CN">' . "\n";
            $html .= '<head>' . "\n";
            $html .= '  <meta charset="UTF-8">' . "\n";
            $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
            $html .= '  <title>网站地图</title>' . "\n";
            $html .= '  <style>' . "\n";
            $html .= '    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }' . "\n";
            $html .= '    h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }' . "\n";
            $html .= '    h2 { color: #4CAF50; margin-top: 30px; }' . "\n";
            $html .= '    ul { list-style: none; padding: 0; }' . "\n";
            $html .= '    li { padding: 8px 0; border-bottom: 1px solid #eee; }' . "\n";
            $html .= '    a { color: #2196F3; text-decoration: none; }' . "\n";
            $html .= '    a:hover { text-decoration: underline; }' . "\n";
            $html .= '    .section { margin-bottom: 40px; }' . "\n";
            $html .= '  </style>' . "\n";
            $html .= '</head>' . "\n";
            $html .= '<body>' . "\n";
            $html .= '  <h1>网站地图</h1>' . "\n";
            $html .= '  <div class="section">' . "\n";
            $html .= '    <h2>文章</h2>' . "\n";
            $html .= '    <ul>' . "\n";

            // 文章列表
            $articles = Article::where('status', 1)
                ->whereNotNull('publish_time')
                ->order('publish_time', 'desc')
                ->limit(100)
                ->select();
            foreach ($articles as $article) {
                $url = $this->baseUrl . '/article/' . $article->id . '.html';
                $html .= '      <li><a href="' . $url . '">' . htmlspecialchars($article->title) . '</a></li>' . "\n";
            }

            $html .= '    </ul>' . "\n";
            $html .= '  </div>' . "\n";

            // 分类列表
            $html .= '  <div class="section">' . "\n";
            $html .= '    <h2>分类</h2>' . "\n";
            $html .= '    <ul>' . "\n";
            $categories = Category::where('status', 1)->select();
            foreach ($categories as $category) {
                $url = $this->baseUrl . '/category/' . $category->id . '.html';
                $html .= '      <li><a href="' . $url . '">' . htmlspecialchars($category->name) . '</a></li>' . "\n";
            }
            $html .= '    </ul>' . "\n";
            $html .= '  </div>' . "\n";

            // 标签列表
            $html .= '  <div class="section">' . "\n";
            $html .= '    <h2>标签</h2>' . "\n";
            $html .= '    <ul>' . "\n";
            $tags = Tag::where('status', 1)->limit(50)->select();
            foreach ($tags as $tag) {
                $url = $this->baseUrl . '/tag/' . $tag->id . '.html';
                $html .= '      <li><a href="' . $url . '">' . htmlspecialchars($tag->name) . '</a></li>' . "\n";
            }
            $html .= '    </ul>' . "\n";
            $html .= '  </div>' . "\n";

            $html .= '</body>' . "\n";
            $html .= '</html>';

            // 保存到文件
            $filename = $this->sitemapDir . 'sitemap.html';
            file_put_contents($filename, $html);

            return [
                'success' => true,
                'file' => $filename,
                'url' => $this->baseUrl . '/sitemap.html'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
