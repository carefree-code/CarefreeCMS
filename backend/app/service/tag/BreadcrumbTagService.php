<?php
namespace app\service\tag;

use app\model\Article;
use app\model\Category;
use app\model\Tag;
use app\model\Page;

/**
 * 面包屑导航标签服务类
 * 处理面包屑导航的生成
 */
class BreadcrumbTagService
{
    /**
     * 获取面包屑导航
     *
     * @param string $type 页面类型 (article/category/tag/page)
     * @param int $id 页面ID
     * @return array
     */
    public static function get($type = '', $id = 0)
    {
        $breadcrumbs = [];

        // 首页
        $breadcrumbs[] = [
            'title' => '首页',
            'url' => '/index.html',
            'is_current' => false
        ];

        // 根据页面类型添加面包屑
        switch ($type) {
            case 'article':
                $breadcrumbs = array_merge($breadcrumbs, self::getArticleBreadcrumbs($id));
                break;
            case 'category':
                $breadcrumbs = array_merge($breadcrumbs, self::getCategoryBreadcrumbs($id));
                break;
            case 'tag':
                $breadcrumbs = array_merge($breadcrumbs, self::getTagBreadcrumbs($id));
                break;
            case 'page':
                $breadcrumbs = array_merge($breadcrumbs, self::getPageBreadcrumbs($id));
                break;
        }

        return $breadcrumbs;
    }

    /**
     * 获取文章页面包屑
     *
     * @param int $id 文章ID
     * @return array
     */
    private static function getArticleBreadcrumbs($id)
    {
        $breadcrumbs = [];

        $article = Article::with(['category'])->find($id);
        if (!$article) {
            return $breadcrumbs;
        }

        // 添加分类（如果有）
        if ($article->category) {
            // 如果分类有父分类，递归添加
            $categoryBreadcrumbs = self::getCategoryChain($article->category);
            $breadcrumbs = array_merge($breadcrumbs, $categoryBreadcrumbs);
        }

        // 添加当前文章
        $breadcrumbs[] = [
            'title' => $article->title,
            'url' => '/article/' . $article->id . '.html',
            'is_current' => true
        ];

        return $breadcrumbs;
    }

    /**
     * 获取分类页面包屑
     *
     * @param int $id 分类ID
     * @return array
     */
    private static function getCategoryBreadcrumbs($id)
    {
        $breadcrumbs = [];

        $category = Category::find($id);
        if (!$category) {
            return $breadcrumbs;
        }

        // 获取分类链（包含所有父分类）
        $categoryChain = self::getCategoryChain($category);

        // 最后一个是当前分类
        if (!empty($categoryChain)) {
            $lastIndex = count($categoryChain) - 1;
            $categoryChain[$lastIndex]['is_current'] = true;
        }

        return $categoryChain;
    }

    /**
     * 获取标签页面包屑
     *
     * @param int $id 标签ID
     * @return array
     */
    private static function getTagBreadcrumbs($id)
    {
        $breadcrumbs = [];

        $tag = Tag::find($id);
        if (!$tag) {
            return $breadcrumbs;
        }

        // 添加标签列表页
        $breadcrumbs[] = [
            'title' => '标签',
            'url' => '/tags.html',
            'is_current' => false
        ];

        // 添加当前标签
        $breadcrumbs[] = [
            'title' => $tag->name,
            'url' => '/tag/' . $tag->id . '.html',
            'is_current' => true
        ];

        return $breadcrumbs;
    }

    /**
     * 获取单页页面包屑
     *
     * @param int $id 页面ID
     * @return array
     */
    private static function getPageBreadcrumbs($id)
    {
        $breadcrumbs = [];

        $page = Page::find($id);
        if (!$page) {
            return $breadcrumbs;
        }

        // 添加当前页面
        $breadcrumbs[] = [
            'title' => $page->title,
            'url' => \app\service\UrlHelper::getPageUrl($page),
            'is_current' => true
        ];

        return $breadcrumbs;
    }

    /**
     * 获取分类链（包含所有父分类）
     *
     * @param \app\model\Category $category 分类对象
     * @return array
     */
    private static function getCategoryChain($category)
    {
        $chain = [];

        // 递归获取父分类
        if ($category->parent_id > 0) {
            $parent = Category::find($category->parent_id);
            if ($parent) {
                $chain = array_merge($chain, self::getCategoryChain($parent));
            }
        }

        // 添加当前分类
        $chain[] = [
            'title' => $category->name,
            'url' => '/category/' . $category->id . '.html',
            'is_current' => false
        ];

        return $chain;
    }

    /**
     * 从全局变量中自动检测并生成面包屑
     * 通过检查模板中设置的变量来判断页面类型
     *
     * @return array
     */
    public static function getAuto()
    {
        // 尝试从模板引擎获取当前变量
        $vars = view()->getVars();

        // 判断页面类型
        if (isset($vars['article']) && isset($vars['article']['id'])) {
            return self::get('article', $vars['article']['id']);
        } elseif (isset($vars['category']) && isset($vars['category']['id'])) {
            return self::get('category', $vars['category']['id']);
        } elseif (isset($vars['tag']) && isset($vars['tag']['id'])) {
            return self::get('tag', $vars['tag']['id']);
        } elseif (isset($vars['page']) && isset($vars['page']['id'])) {
            return self::get('page', $vars['page']['id']);
        }

        // 默认只返回首页
        return [[
            'title' => '首页',
            'url' => '/index.html',
            'is_current' => true
        ]];
    }
}
