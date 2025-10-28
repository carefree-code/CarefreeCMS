<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Article;
use app\model\Category;
use app\model\Page;
use app\model\Tag;
use app\model\Topic;
use app\model\StaticBuildLog;
use app\model\Config;
use app\service\TemplateAssetManager;
use think\facade\View;
use think\facade\Db;

class Build extends BaseController
{
    // 静态文件输出目录
    protected $outputPath;

    // 系统配置缓存
    protected $config = [];

    // 当前模板套装
    protected $currentTheme = 'default';

    /**
     * 控制器初始化方法
     */
    protected function initialize()
    {
        parent::initialize();

        $this->outputPath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR;

        // 确保输出目录存在
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }

        // 加载系统配置
        $this->loadConfig();

        // 加载当前模板套装
        $this->currentTheme = $this->config['current_template_theme'] ?? 'default';
    }

    /**
     * 加载系统配置
     */
    protected function loadConfig()
    {
        $configs = Config::getAllConfigs();

        // 映射配置键名
        $this->config = [
            'web_name' => $configs['site_name'] ?? 'CMS系统',
            'web_logo' => $configs['site_logo'] ?? '',
            'web_ico' => $configs['site_favicon'] ?? '',
            'web_basehost' => $configs['site_url'] ?? '',
            'web_copyright' => $configs['site_copyright'] ?? '',
            'web_recordnum' => $configs['site_icp'] ?? '',
            'web_garecordnum' => $configs['site_police'] ?? '',
            'web_title' => $configs['seo_title'] ?? '',
            'web_keywords' => $configs['seo_keywords'] ?? '',
            'web_description' => $configs['seo_description'] ?? '',
            'web_thirdcode_pc' => $configs['thirdparty_code_pc'] ?? '',
            'index_template' => $configs['index_template'] ?? 'index',  // 首页模板
            'current_template_theme' => $configs['current_template_theme'] ?? 'default',  // 当前模板套装
        ];
    }

    /**
     * 获取模板路径（带模板套装）
     * @param string $template 模板名称（如 index, category 等）
     * @return string 完整的模板路径
     */
    protected function getTemplatePath($template)
    {
        // 设置当前视图路径到模板套装目录，这样 extend 标签可以正确找到 layout.html
        $themePath = root_path() . 'templates' . DIRECTORY_SEPARATOR . $this->currentTheme . DIRECTORY_SEPARATOR;
        View::config([
            'view_path' => $themePath
        ]);

        // 使用 / 前缀表示从视图根目录开始查找，避免 ThinkPHP 自动添加控制器路径
        return '/' . $template;
    }

    /**
     * 生成首页
     */
    public function index()
    {
        try {
            // 获取最新文章
            $articles = Article::with(['category', 'user'])
                ->where('status', 1)  // 1 = 已发布
                ->order('create_time', 'desc')
                ->limit(10)
                ->select()
                ->toArray();

            // 获取轮播图（首页轮播图组，group_id = 1）
            $sliders = \app\model\Slider::where('group_id', 1)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            // 获取友情链接（首页友情链接组，group_id = 1）
            $links = \app\model\Link::where('group_id', 1)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            // 获取广告（首页顶部广告位，position_id = 1；首页侧边广告位，position_id = 2）
            $topAds = \app\model\Ad::where('position_id', 1)
                ->where('status', 1)
                ->whereTime('start_time', '<=', date('Y-m-d H:i:s'))
                ->where(function($query) {
                    $query->whereNull('end_time')->whereOr('end_time', '>=', date('Y-m-d H:i:s'));
                })
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            $sideAds = \app\model\Ad::where('position_id', 2)
                ->where('status', 1)
                ->whereTime('start_time', '<=', date('Y-m-d H:i:s'))
                ->where(function($query) {
                    $query->whereNull('end_time')->whereOr('end_time', '>=', date('Y-m-d H:i:s'));
                })
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            // 获取首页模板配置，默认使用 index
            $template = $this->config['index_template'] ?? 'index';

            // 渲染模板（使用模板套装路径）
            $content = View::fetch($this->getTemplatePath($template), [
                'articles' => $articles,
                'sliders' => $sliders,
                'links' => $links,
                'top_ads' => $topAds,
                'side_ads' => $sideAds,
                'config' => $this->config,
                'is_home' => true  // 标记为首页
            ]);

            // 保存文件
            $filePath = $this->outputPath . 'index.html';
            file_put_contents($filePath, $content);

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                StaticBuildLog::SCOPE_INDEX,
                0,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success([], '首页生成成功');
        } catch (\Exception $e) {
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                StaticBuildLog::SCOPE_INDEX,
                0,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成文章列表页
     */
    public function articles()
    {
        try {
            $pageSize = 20; // 每页文章数

            // 获取所有已发布的文章
            $totalQuery = Article::where('status', 1);
            $total = $totalQuery->count();
            $totalPages = ceil($total / $pageSize);

            // 获取侧边栏数据（所有页面共用）
            $categories = Category::select()->toArray();
            $tags = Tag::where('status', 1)->limit(20)->select()->toArray();
            $hotArticles = Article::where('status', 1)
                ->order('view_count', 'desc')
                ->limit(5)
                ->field('id,title,view_count')
                ->select()
                ->toArray();

            // 生成每一页
            for ($page = 1; $page <= $totalPages; $page++) {
                $articles = Article::with(['category', 'user', 'tags'])
                    ->where('status', 1)
                    ->order(['is_top' => 'desc', 'publish_time' => 'desc'])
                    ->page($page, $pageSize)
                    ->select()
                    ->toArray();

                // 渲染模板（使用模板套装路径）
                $content = View::fetch($this->getTemplatePath('articles'), [
                    'articles' => $articles,
                    'config' => $this->config,
                    'is_home' => false,
                    'title' => $page > 1 ? "文章列表 - 第{$page}页" : '文章列表',
                    'keywords' => '文章列表,全部文章',
                    'description' => '浏览所有文章',
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total' => $total,
                        'page_size' => $pageSize
                    ],
                    'categories' => $categories,
                    'tags' => $tags,
                    'hot_articles' => $hotArticles
                ]);

                // 保存文件
                // 第一页保存为 articles.html，其他页保存为 articles-2.html, articles-3.html
                $fileName = $page === 1 ? 'articles.html' : "articles-{$page}.html";
                $filePath = $this->outputPath . $fileName;
                file_put_contents($filePath, $content);
            }

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'articles',
                0,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success(['pages' => $totalPages], "文章列表生成成功，共{$totalPages}页");
        } catch (\Exception $e) {
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'articles',
                0,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成文章详情页
     */
    public function article($id = null, $buildType = null)
    {
        // 优先使用传入的ID，否则从请求中获取
        $id = $id ?? (int)$this->request->param('id', 0);
        $buildType = $buildType ?? StaticBuildLog::BUILD_TYPE_MANUAL;

        if (!$id) {
            return Response::error('缺少文章ID');
        }

        try {
            // 记录开始生成
            trace('开始生成文章页，ID: ' . $id . ', 模板套装: ' . $this->currentTheme, 'info');

            // 获取文章详情
            $article = Article::with(['category', 'user', 'tags'])
                ->where('id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->find();

            if (!$article) {
                return Response::error('文章不存在或未发布');
            }

            trace('文章数据加载完成: ' . json_encode($article->toArray(), JSON_UNESCAPED_UNICODE), 'info');

            // 获取上一篇和下一篇
            $prev = Article::where('id', '<', $id)
                ->where('status', 1)  // 1 = 已发布
                ->order('id', 'desc')
                ->field('id,title')
                ->find();

            $next = Article::where('id', '>', $id)
                ->where('status', 1)  // 1 = 已发布
                ->order('id', 'asc')
                ->field('id,title')
                ->find();

            // 获取相关文章（同分类的其他文章）
            $relatedArticles = Article::where('category_id', $article->category_id)
                ->where('id', '<>', $id)
                ->where('status', 1)
                ->order('view_count', 'desc')
                ->limit(3)
                ->field('id,title,cover_image,create_time')
                ->select()
                ->toArray();

            $templatePath = $this->getTemplatePath('article');
            $templateData = [
                'article' => $article->toArray(),
                'prev' => $prev ? $prev->toArray() : null,
                'next' => $next ? $next->toArray() : null,
                'related_articles' => $relatedArticles,
                'comment_count' => 0,  // 评论功能暂未实现
                'config' => $this->config,
                'is_home' => false,
                'title' => $article->title,
                'keywords' => $article->seo_keywords ?? '',
                'description' => $article->seo_description ?? $article->summary
            ];

            trace('模板路径: ' . $templatePath, 'info');
            trace('模板数据键: ' . implode(', ', array_keys($templateData)), 'info');

            // 渲染模板（使用模板套装路径）
            trace('开始渲染模板...', 'info');
            $content = View::fetch($templatePath, $templateData);
            trace('模板渲染成功，内容长度: ' . strlen($content), 'info');

            // 保存文件
            $filePath = $this->outputPath . 'article' . DIRECTORY_SEPARATOR . $id . '.html';
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($filePath, $content);

            // 记录日志
            StaticBuildLog::log(
                $buildType,
                StaticBuildLog::SCOPE_ARTICLE,
                (int)$id,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success([], '文章生成成功');
        } catch (\Exception $e) {
            trace('文章生成失败 [ID:' . $id . ']: ' . $e->getMessage(), 'error');
            trace('错误详情: ' . $e->getTraceAsString(), 'error');

            StaticBuildLog::log(
                $buildType,
                StaticBuildLog::SCOPE_ARTICLE,
                (int)$id,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成分类页
     */
    public function category($id = null)
    {
        // 优先使用传入的ID，否则从请求中获取
        $id = $id ?? (int)$this->request->param('id', 0);

        if (!$id) {
            return Response::error('缺少分类ID');
        }

        try {
            // 获取分类信息
            $category = Category::find($id);
            if (!$category) {
                return Response::error('分类不存在');
            }

            // 获取该分类下的文章
            $articles = Article::with(['user', 'tags'])
                ->where('category_id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->order('create_time', 'desc')
                ->select()
                ->toArray();

            // 获取侧边栏数据
            $categories = Category::select()->toArray();
            $tags = Tag::where('status', 1)->limit(20)->select()->toArray();
            $hotArticles = Article::where('status', 1)
                ->order('view_count', 'desc')
                ->limit(5)
                ->field('id,title,view_count')
                ->select()
                ->toArray();

            // 获取分类自定义模板，默认使用 category
            $template = $category->template ?? 'category';

            // 渲染模板（使用模板套装路径）
            $content = View::fetch($this->getTemplatePath($template), [
                'category' => $category->toArray(),
                'articles' => $articles,
                'config' => $this->config,
                'is_home' => false,
                'title' => $category->name,
                'keywords' => $category->name,
                'description' => $category->description ?? $category->name,
                'categories' => $categories,
                'tags' => $tags,
                'hot_articles' => $hotArticles
            ]);

            // 保存文件
            $filePath = $this->outputPath . 'category' . DIRECTORY_SEPARATOR . $id . '.html';
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($filePath, $content);

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                StaticBuildLog::SCOPE_CATEGORY,
                (int)$id,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success([], '分类页生成成功');
        } catch (\Exception $e) {
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                StaticBuildLog::SCOPE_CATEGORY,
                (int)$id,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成标签页
     */
    public function tag($id = null)
    {
        // 优先使用传入的ID，否则从请求中获取
        $id = $id ?? (int)$this->request->param('id', 0);

        if (!$id) {
            return Response::error('缺少标签ID');
        }

        try {
            // 记录开始生成
            trace('开始生成标签页，ID: ' . $id . ', 模板套装: ' . $this->currentTheme, 'info');

            // 获取标签信息
            $tag = Tag::find($id);
            if (!$tag) {
                return Response::error('标签不存在');
            }

            trace('标签数据加载完成: ' . json_encode($tag->toArray(), JSON_UNESCAPED_UNICODE), 'info');

            // 获取该标签下的文章
            $articles = $tag->articles()
                ->with(['user', 'category', 'tags'])
                ->where('status', 1)  // 1 = 已发布
                ->order('create_time', 'desc')
                ->select()
                ->toArray();

            trace('标签下文章数量: ' . count($articles), 'info');

            // 获取侧边栏数据
            $categories = Category::select()->toArray();
            $tags = Tag::where('status', 1)->limit(20)->select()->toArray();
            $hotArticles = Article::where('status', 1)
                ->order('view_count', 'desc')
                ->limit(5)
                ->field('id,title,view_count')
                ->select()
                ->toArray();

            $templatePath = $this->getTemplatePath('tag');
            $templateData = [
                'tag' => $tag->toArray(),
                'articles' => $articles,
                'config' => $this->config,
                'is_home' => false,
                'title' => $tag->name,
                'keywords' => $tag->name,
                'description' => $tag->description ?? $tag->name,
                'categories' => $categories,
                'tags' => $tags,
                'hot_articles' => $hotArticles
            ];

            trace('模板路径: ' . $templatePath, 'info');
            trace('模板数据键: ' . implode(', ', array_keys($templateData)), 'info');

            // 渲染模板（使用模板套装路径）
            trace('开始渲染模板...', 'info');
            $content = View::fetch($templatePath, $templateData);
            trace('模板渲染成功，内容长度: ' . strlen($content), 'info');

            // 保存文件
            $filePath = $this->outputPath . 'tag' . DIRECTORY_SEPARATOR . $id . '.html';
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($filePath, $content);

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'tag',
                (int)$id,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success([], '标签页生成成功');
        } catch (\Exception $e) {
            trace('标签页生成失败 [ID:' . $id . ']: ' . $e->getMessage(), 'error');
            trace('错误详情: ' . $e->getTraceAsString(), 'error');

            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'tag',
                (int)$id,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成所有标签页
     */
    public function tags()
    {
        try {
            $result = [
                'tags' => 0,
                'failed' => 0
            ];

            // 获取所有启用的标签
            $tags = Tag::where('status', 1)->select();  // 1 = 启用

            foreach ($tags as $tag) {
                try {
                    $this->tag($tag->id);
                    $result['tags']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'tags',
                0,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success($result, "标签页生成完成，共生成{$result['tags']}个页面");
        } catch (\Exception $e) {
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'tags',
                0,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成所有分类页
     */
    public function categories()
    {
        try {
            $result = [
                'categories' => 0,
                'failed' => 0
            ];

            // 获取所有分类
            $categories = Category::select();

            foreach ($categories as $category) {
                try {
                    $this->category($category->id);
                    $result['categories']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            return Response::success($result, '分类页生成完成');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成单个专题页
     */
    public function topic($id = null)
    {
        // 优先使用传入的ID，否则从请求中获取
        $id = $id ?? (int)$this->request->param('id', 0);

        if (!$id) {
            return Response::error('缺少专题ID');
        }

        try {
            // 获取专题详情
            $topic = Topic::where('id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->find();

            if (!$topic) {
                return Response::error('专题不存在或未发布');
            }

            // 获取专题下的文章（分页）
            $pageSize = 10;
            $page = 1;

            // 获取专题文章关联
            $articleIds = Db::name('topic_articles')
                ->where('topic_id', $id)
                ->order('sort', 'asc')
                ->column('article_id');

            if (!empty($articleIds)) {
                $articles = Article::where('status', 1)
                    ->whereIn('id', $articleIds)
                    ->with(['category', 'user', 'tags'])
                    ->select();
            } else {
                $articles = [];
            }

            // 获取其他推荐专题
            $topics = Topic::where('status', 1)
                ->where('is_recommended', 1)
                ->where('id', '<>', $id)
                ->limit(10)
                ->select();

            // 获取分类和标签（侧边栏）
            $categories = Category::limit(10)->select()->toArray();
            $tags = Tag::where('status', 1)->limit(20)->select()->toArray();
            $hotArticles = Article::where('status', 1)
                ->order('view_count', 'desc')
                ->limit(5)
                ->field('id,title,view_count')
                ->select()
                ->toArray();

            // 获取专题自定义模板，默认使用 topic
            $template = $topic->template ?? 'topic';

            // 渲染模板
            $content = View::fetch($this->getTemplatePath($template), [
                'topic' => $topic->toArray(),
                'articles' => $articles,
                'topics' => $topics,
                'categories' => $categories,
                'tags' => $tags,
                'hot_articles' => $hotArticles,
                'config' => $this->config,
                'is_home' => false,
                'title' => $topic->seo_title ?: $topic->name,
                'keywords' => $topic->seo_keywords ?? '',
                'description' => $topic->seo_description ?: $topic->description
            ]);

            // 保存文件 - 使用 topic-{id}.html 格式
            $filePath = $this->outputPath . 'topic-' . $topic->id . '.html';
            file_put_contents($filePath, $content);

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'topic',
                (int)$id,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success([], '专题页生成成功');
        } catch (\Exception $e) {
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'topic',
                (int)$id,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成所有专题页
     */
    public function topics()
    {
        try {
            $result = [
                'topics' => 0,
                'failed' => 0
            ];

            // 获取所有已发布的专题
            $topics = Topic::where('status', 1)->select();  // 1 = 已发布

            foreach ($topics as $topic) {
                try {
                    $this->topic($topic->id);
                    $result['topics']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            return Response::success($result, "专题页生成完成，共生成{$result['topics']}个页面");
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成所有单页面
     */
    public function pages()
    {
        try {
            $result = [
                'pages' => 0,
                'failed' => 0
            ];

            // 获取所有已发布的单页面
            $pages = Page::where('status', 1)->select();  // 1 = 已发布

            foreach ($pages as $page) {
                try {
                    $this->page($page->id);
                    $result['pages']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'pages',
                0,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success($result, "单页生成完成，共生成{$result['pages']}个页面");
        } catch (\Exception $e) {
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                'pages',
                0,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成单页面
     */
    public function page($id = null)
    {
        // 优先使用传入的ID，否则从请求中获取
        $id = $id ?? (int)$this->request->param('id', 0);

        if (!$id) {
            return Response::error('缺少页面ID');
        }

        try {
            // 获取页面详情
            $page = Page::where('id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->find();

            if (!$page) {
                return Response::error('页面不存在或未发布');
            }

            // 获取单页自定义模板，默认使用 page
            $template = $page->template ?? 'page';

            // 渲染模板（使用模板套装路径）
            $content = View::fetch($this->getTemplatePath($template), [
                'page' => $page->toArray(),
                'config' => $this->config,
                'is_home' => false,
                'title' => $page->title,
                'keywords' => $page->seo_keywords ?? '',
                'description' => $page->seo_description ?? ''
            ]);

            // 保存文件
            $filePath = $this->outputPath . $page->slug . '.html';
            file_put_contents($filePath, $content);

            // 记录日志
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                StaticBuildLog::SCOPE_PAGE,
                (int)$id,
                StaticBuildLog::STATUS_SUCCESS
            );

            return Response::success([], '页面生成成功');
        } catch (\Exception $e) {
            StaticBuildLog::log(
                StaticBuildLog::BUILD_TYPE_MANUAL,
                StaticBuildLog::SCOPE_PAGE,
                (int)$id,
                StaticBuildLog::STATUS_FAILED,
                $e->getMessage()
            );
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 批量生成所有
     */
    public function all()
    {
        try {
            $result = [
                'index' => 0,
                'article_list_pages' => 0,
                'articles' => 0,
                'categories' => 0,
                'tags' => 0,
                'topics' => 0,
                'pages' => 0,
                'failed' => 0
            ];

            // 生成首页
            $this->index();
            $result['index'] = 1;

            // 生成文章列表页
            $articlesRes = $this->articles();
            if ($articlesRes) {
                $result['article_list_pages'] = $articlesRes->getData()['data']['pages'] ?? 0;
            }

            // 生成所有已发布的文章
            $articles = Article::where('status', 1)->select();  // 1 = 已发布
            foreach ($articles as $article) {
                try {
                    $this->article($article->id, StaticBuildLog::BUILD_TYPE_MANUAL);
                    $result['articles']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            // 生成所有分类页
            $categories = Category::select();
            foreach ($categories as $category) {
                try {
                    $this->category($category->id);
                    $result['categories']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            // 生成所有标签页
            $tags = Tag::where('status', 1)->select();  // 1 = 启用
            foreach ($tags as $tag) {
                try {
                    $this->tag($tag->id);
                    $result['tags']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            // 生成所有专题页
            $topics = Topic::where('status', 1)->select();  // 1 = 已发布
            foreach ($topics as $topic) {
                try {
                    $this->topic($topic->id);
                    $result['topics']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            // 生成所有已发布的单页面
            $pages = Page::where('status', 1)->select();  // 1 = 已发布
            foreach ($pages as $page) {
                try {
                    $this->page($page->id);
                    $result['pages']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            // 同步模板资源文件（CSS、JS、图片等）
            try {
                $assetManager = new TemplateAssetManager($this->currentTheme, $this->outputPath);
                $assetResult = $assetManager->syncAllAssets();
                $result['assets_synced'] = $assetResult['success'] ? $assetResult['total_files'] : 0;
            } catch (\Exception $e) {
                $result['assets_synced'] = 0;
                $result['assets_error'] = $e->getMessage();
            }

            return Response::success($result, '批量生成完成');
        } catch (\Exception $e) {
            return Response::error('批量生成失败：' . $e->getMessage());
        }
    }

    /**
     * 获取生成日志
     */
    public function logs()
    {
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $type = $this->request->param('type', '');
        $status = $this->request->param('status', '');

        $query = StaticBuildLog::order('create_time', 'desc');

        if ($type) {
            $query->where('build_scope', $type);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        // 先获取总数
        $total = $query->count();

        // 再获取分页数据
        $list = $query->page($page, $pageSize)->select()->toArray();

        return Response::paginate($list, $total, $page, $pageSize);
    }

    /**
     * 批量删除日志
     */
    public function batchDeleteLogs()
    {
        $ids = $this->request->param('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return Response::error('请选择要删除的日志');
        }

        try {
            StaticBuildLog::destroy($ids);
            return Response::success([], '删除成功');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 清空日志
     */
    public function clearLogs()
    {
        $days = (int)$this->request->param('days', 30);

        if ($days < 7) {
            return Response::error('至少保留7天的日志');
        }

        try {
            $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $count = StaticBuildLog::where('create_time', '<', $date)->delete();
            return Response::success(['count' => $count], "成功清空{$count}条日志");
        } catch (\Exception $e) {
            return Response::error('清空失败：' . $e->getMessage());
        }
    }

    /**
     * 同步模板资源文件到静态目录
     * 将模板套装的 assets 目录（CSS、JS、图片等）复制到静态输出目录
     */
    public function syncAssets()
    {
        try {
            $assetManager = new TemplateAssetManager($this->currentTheme, $this->outputPath);
            $result = $assetManager->syncAllAssets();

            if ($result['success']) {
                // 记录日志
                $this->logBuildAction('资源同步', '全部资源', $result['total_files'] . '个文件', [
                    'files' => $result['log']
                ]);

                return Response::success($result, '资源同步成功');
            } else {
                return Response::error($result['message']);
            }
        } catch (\Exception $e) {
            return Response::error('资源同步失败：' . $e->getMessage());
        }
    }

    /**
     * 清理静态目录中的旧资源文件
     */
    public function cleanAssets()
    {
        try {
            $assetManager = new TemplateAssetManager($this->currentTheme, $this->outputPath);
            $result = $assetManager->cleanOldAssets();

            if ($result['success']) {
                return Response::success($result, '资源清理成功');
            } else {
                return Response::error($result['message']);
            }
        } catch (\Exception $e) {
            return Response::error('资源清理失败：' . $e->getMessage());
        }
    }

    /**
     * 获取模板资源文件列表
     */
    public function getAssetsList()
    {
        try {
            $assetManager = new TemplateAssetManager($this->currentTheme, $this->outputPath);
            $assets = $assetManager->getAssetsList();

            return Response::success($assets);
        } catch (\Exception $e) {
            return Response::error('获取资源列表失败：' . $e->getMessage());
        }
    }
}
