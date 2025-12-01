<?php
namespace app\service;

use app\model\Site;
use app\model\Page;
use app\model\Category;
use app\model\Article;

/**
 * URL 生成辅助类
 * 统一处理各类内容的 URL 生成，支持多站点
 */
class UrlHelper
{
    /**
     * 获取单页 URL
     *
     * @param array|object $page 单页数据
     * @param int|null $siteId 站点ID（可选，如果不提供则从页面数据中获取）
     * @param bool $fullUrl 是否返回完整URL（包含域名）
     * @return string
     */
    public static function getPageUrl($page, $siteId = null, $fullUrl = false)
    {
        // 获取站点ID
        if (is_null($siteId)) {
            $siteId = is_array($page) ? ($page['site_id'] ?? 1) : ($page->site_id ?? 1);
        }

        // 获取 URL 路径（优先使用 slug）
        $urlPath = is_array($page)
            ? (!empty($page['slug']) ? $page['slug'] : 'page-' . $page['id'])
            : (!empty($page->slug) ? $page->slug : 'page-' . $page->id);

        // 获取站点信息
        $site = Site::find($siteId);

        // 构建相对 URL
        if ($site && $site->site_type != 1 && !empty($site->site_code)) {
            // 子站点：/{site_code}/{slug}.html
            $relativeUrl = '/' . $site->site_code . '/' . $urlPath . '.html';
        } else {
            // 主站：/{slug}.html
            $relativeUrl = '/' . $urlPath . '.html';
        }

        // 如果需要完整URL，添加域名
        if ($fullUrl) {
            $domain = $site && $site->domain ? $site->domain : request()->domain();
            return rtrim($domain, '/') . $relativeUrl;
        }

        return $relativeUrl;
    }

    /**
     * 获取单页静态文件保存路径
     *
     * @param array|object $page 单页数据
     * @param int|null $siteId 站点ID
     * @return string 完整的文件系统路径
     */
    public static function getPageStaticPath($page, $siteId = null)
    {
        // 获取站点ID
        if (is_null($siteId)) {
            $siteId = is_array($page) ? ($page['site_id'] ?? 1) : ($page->site_id ?? 1);
        }

        // 获取 URL 路径
        $urlPath = is_array($page)
            ? (!empty($page['slug']) ? $page['slug'] : 'page-' . $page['id'])
            : (!empty($page->slug) ? $page->slug : 'page-' . $page->id);

        // 获取站点信息
        $site = Site::find($siteId);

        // 基础路径
        $basePath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR;

        // 根据站点类型确定保存路径
        if ($site && $site->site_type != 1 && !empty($site->site_code)) {
            // 子站点：html/{site_code}/{slug}.html
            return $basePath . $site->site_code . DIRECTORY_SEPARATOR . $urlPath . '.html';
        } else {
            // 主站：html/{slug}.html
            return $basePath . $urlPath . '.html';
        }
    }

    /**
     * 获取文章 URL
     *
     * @param array|object $article 文章数据
     * @param int|null $siteId 站点ID
     * @param bool $fullUrl 是否返回完整URL
     * @return string
     */
    public static function getArticleUrl($article, $siteId = null, $fullUrl = false)
    {
        // 获取站点ID
        if (is_null($siteId)) {
            $siteId = is_array($article) ? ($article['site_id'] ?? 1) : ($article->site_id ?? 1);
        }

        // 获取文章ID
        $articleId = is_array($article) ? $article['id'] : $article->id;

        // 获取站点信息
        $site = Site::find($siteId);

        // 构建相对 URL
        if ($site && $site->site_type != 1 && !empty($site->site_code)) {
            // 子站点：/{site_code}/article/{id}.html
            $relativeUrl = '/' . $site->site_code . '/article/' . $articleId . '.html';
        } else {
            // 主站：/article/{id}.html
            $relativeUrl = '/article/' . $articleId . '.html';
        }

        // 如果需要完整URL，添加域名
        if ($fullUrl) {
            $domain = $site && $site->domain ? $site->domain : request()->domain();
            return rtrim($domain, '/') . $relativeUrl;
        }

        return $relativeUrl;
    }

    /**
     * 获取分类 URL
     *
     * @param array|object $category 分类数据
     * @param int|null $siteId 站点ID
     * @param bool $fullUrl 是否返回完整URL
     * @return string
     */
    public static function getCategoryUrl($category, $siteId = null, $fullUrl = false)
    {
        // 获取站点ID
        if (is_null($siteId)) {
            $siteId = is_array($category) ? ($category['site_id'] ?? 1) : ($category->site_id ?? 1);
        }

        // 获取分类ID
        $categoryId = is_array($category) ? $category['id'] : $category->id;

        // 获取站点信息
        $site = Site::find($siteId);

        // 构建相对 URL
        if ($site && $site->site_type != 1 && !empty($site->site_code)) {
            // 子站点：/{site_code}/category/{id}.html
            $relativeUrl = '/' . $site->site_code . '/category/' . $categoryId . '.html';
        } else {
            // 主站：/category/{id}.html
            $relativeUrl = '/category/' . $categoryId . '.html';
        }

        // 如果需要完整URL，添加域名
        if ($fullUrl) {
            $domain = $site && $site->domain ? $site->domain : request()->domain();
            return rtrim($domain, '/') . $relativeUrl;
        }

        return $relativeUrl;
    }

    /**
     * 确保目录存在
     *
     * @param string $path 目录路径
     * @return bool
     */
    public static function ensureDirectory($path)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            return mkdir($dir, 0755, true);
        }
        return true;
    }

    /**
     * 获取站点的静态文件根目录
     *
     * @param int $siteId 站点ID
     * @return string
     */
    public static function getSiteStaticRoot($siteId = 1)
    {
        $site = Site::find($siteId);
        $basePath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR;

        if ($site && $site->site_type != 1 && !empty($site->site_code)) {
            // 子站点有独立目录
            return $basePath . $site->site_code . DIRECTORY_SEPARATOR;
        }

        // 主站使用根目录
        return $basePath;
    }

    /**
     * 验证 slug 格式
     *
     * @param string $slug
     * @return bool
     */
    public static function validateSlug($slug)
    {
        // slug 只允许小写字母、数字、连字符
        return preg_match('/^[a-z0-9-]+$/', $slug) === 1;
    }

    /**
     * 生成 slug（从标题自动生成）
     *
     * @param string $title 标题
     * @return string
     */
    public static function generateSlug($title)
    {
        // 转换为拼音（如果有拼音库）或者使用简单规则
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug ?: 'page-' . time();
    }
}
