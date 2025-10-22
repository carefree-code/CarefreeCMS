<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\service\SeoAnalyzer;
use app\service\EnhancedSitemapGenerator;
use app\model\Article;
use think\Request;

/**
 * SEO分析控制器
 */
class SeoAnalyzerController extends BaseController
{
    /**
     * 分析文章SEO
     */
    public function analyzeArticle(Request $request, $id = null)
    {
        // 如果提供了ID，从数据库读取
        if ($id) {
            $article = Article::find($id);
            if (!$article) {
                return $this->error('文章不存在');
            }

            $data = [
                'title' => $article->title,
                'seo_title' => $article->seo_title,
                'seo_description' => $article->seo_description,
                'seo_keywords' => $article->seo_keywords,
                'summary' => $article->summary,
                'content' => $article->content,
                'cover_image' => $article->cover_image
            ];
        } else {
            // 从请求中获取数据
            $data = $request->only([
                'title',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'summary',
                'content',
                'cover_image'
            ]);
        }

        $analysis = SeoAnalyzer::analyze($data);

        return $this->success($analysis);
    }

    /**
     * 计算关键词密度
     */
    public function keywordDensity(Request $request)
    {
        $content = $request->param('content');
        $keywords = $request->param('keywords', '');

        if (empty($content)) {
            return $this->error('内容不能为空');
        }

        $keywordArray = array_filter(array_map('trim', explode(',', $keywords)));

        if (empty($keywordArray)) {
            return $this->error('请提供关键词');
        }

        $result = SeoAnalyzer::calculateKeywordDensity($content, $keywordArray);

        return $this->success($result);
    }

    /**
     * 自动生成SEO标题
     */
    public function generateTitle(Request $request)
    {
        $title = $request->param('title');
        $keywords = $request->param('keywords', '');

        if (empty($title)) {
            return $this->error('标题不能为空');
        }

        $seoTitle = SeoAnalyzer::generateSeoTitle($title, $keywords);

        return $this->success([
            'seo_title' => $seoTitle
        ]);
    }

    /**
     * 自动生成SEO描述
     */
    public function generateDescription(Request $request)
    {
        $content = $request->param('content');
        $keywords = $request->param('keywords', '');
        $maxLength = $request->param('max_length', 160);

        if (empty($content)) {
            return $this->error('内容不能为空');
        }

        $seoDescription = SeoAnalyzer::generateSeoDescription($content, $keywords, $maxLength);

        return $this->success([
            'seo_description' => $seoDescription
        ]);
    }

    /**
     * 自动提取关键词
     */
    public function extractKeywords(Request $request)
    {
        $content = $request->param('content');
        $count = $request->param('count', 5);

        if (empty($content)) {
            return $this->error('内容不能为空');
        }

        $keywords = SeoAnalyzer::extractKeywords($content, $count);

        return $this->success([
            'keywords' => $keywords
        ]);
    }

    /**
     * 生成增强的sitemap
     */
    public function generateSitemap(Request $request)
    {
        $type = $request->param('type', 'all'); // all, main, images, news, index

        $generator = new EnhancedSitemapGenerator();

        try {
            switch ($type) {
                case 'main':
                    $result = $generator->generateMainSitemap();
                    break;
                case 'images':
                    $result = $generator->generateImageSitemap();
                    break;
                case 'news':
                    $result = $generator->generateNewsSitemap();
                    break;
                case 'index':
                    $result = $generator->generateSitemapIndex();
                    break;
                case 'all':
                default:
                    $result = $generator->generateAll();
                    break;
            }

            return $this->success($result, 'Sitemap生成成功');
        } catch (\Exception $e) {
            return $this->error('生成失败: ' . $e->getMessage());
        }
    }

    /**
     * Ping搜索引擎
     */
    public function pingSitemap(Request $request)
    {
        $sitemapUrl = $request->param('sitemap_url');

        if (empty($sitemapUrl)) {
            $sitemapUrl = request()->domain() . '/sitemap-index.xml';
        }

        $generator = new EnhancedSitemapGenerator();
        $results = $generator->pingSearchEngines($sitemapUrl);

        return $this->success($results, 'Ping完成');
    }

    /**
     * 批量分析多篇文章
     */
    public function batchAnalyze(Request $request)
    {
        $ids = $request->param('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请提供文章ID列表');
        }

        $results = [];

        foreach ($ids as $id) {
            $article = Article::find($id);
            if ($article) {
                $data = [
                    'title' => $article->title,
                    'seo_title' => $article->seo_title,
                    'seo_description' => $article->seo_description,
                    'seo_keywords' => $article->seo_keywords,
                    'summary' => $article->summary,
                    'content' => $article->content,
                    'cover_image' => $article->cover_image
                ];

                $analysis = SeoAnalyzer::analyze($data);

                $results[] = [
                    'id' => $article->id,
                    'title' => $article->title,
                    'score' => $analysis['score'],
                    'grade' => $analysis['grade']
                ];
            }
        }

        return $this->success($results);
    }

    /**
     * 获取SEO优化建议
     */
    public function getSuggestions(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return $this->error('文章不存在');
        }

        $data = [
            'title' => $article->title,
            'seo_title' => $article->seo_title,
            'seo_description' => $article->seo_description,
            'seo_keywords' => $article->seo_keywords,
            'summary' => $article->summary,
            'content' => $article->content,
            'cover_image' => $article->cover_image
        ];

        $analysis = SeoAnalyzer::analyze($data);

        // 只返回建议部分
        return $this->success([
            'score' => $analysis['score'],
            'grade' => $analysis['grade'],
            'suggestions' => $analysis['summary']['suggestions'],
            'issues' => $analysis['summary']['issues']
        ]);
    }

    /**
     * 自动优化文章SEO
     */
    public function autoOptimize(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return $this->error('文章不存在');
        }

        $updated = false;

        // 自动生成SEO标题
        if (empty($article->seo_title)) {
            $article->seo_title = SeoAnalyzer::generateSeoTitle($article->title, $article->seo_keywords);
            $updated = true;
        }

        // 自动生成SEO描述
        if (empty($article->seo_description)) {
            $article->seo_description = SeoAnalyzer::generateSeoDescription($article->content, $article->seo_keywords);
            $updated = true;
        }

        // 自动提取关键词
        if (empty($article->seo_keywords)) {
            $article->seo_keywords = SeoAnalyzer::extractKeywords($article->content);
            $updated = true;
        }

        if ($updated) {
            $article->save();
            return $this->success($article, 'SEO自动优化完成');
        }

        return $this->success($article, 'SEO信息已完整，无需优化');
    }
}
