<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Article;
use app\model\Category;
use app\model\Page;
use app\model\Tag;
use app\model\StaticBuildLog;
use app\model\Config;
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

        $this->outputPath = root_path() . 'html' . DIRECTORY_SEPARATOR;

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

            // 获取首页模板配置，默认使用 index
            $template = $this->config['index_template'] ?? 'index';

            // 渲染模板（使用模板套装路径）
            $content = View::fetch($this->getTemplatePath($template), [
                'articles' => $articles,
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
                    ]
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
            // 获取文章详情
            $article = Article::with(['category', 'user', 'tags'])
                ->where('id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->find();

            if (!$article) {
                return Response::error('文章不存在或未发布');
            }

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

            // 渲染模板（使用模板套装路径）
            $content = View::fetch($this->getTemplatePath('article'), [
                'article' => $article->toArray(),
                'prev' => $prev ? $prev->toArray() : null,
                'next' => $next ? $next->toArray() : null,
                'config' => $this->config,
                'is_home' => false,
                'title' => $article->title,
                'keywords' => $article->seo_keywords ?? '',
                'description' => $article->seo_description ?? $article->summary
            ]);

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
            $articles = Article::with(['user'])
                ->where('category_id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->order('create_time', 'desc')
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
                'description' => $category->description ?? $category->name
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
            // 获取标签信息
            $tag = Tag::find($id);
            if (!$tag) {
                return Response::error('标签不存在');
            }

            // 获取该标签下的文章
            $articles = $tag->articles()
                ->with(['user', 'category'])
                ->where('status', 1)  // 1 = 已发布
                ->order('create_time', 'desc')
                ->select()
                ->toArray();

            // 渲染模板（使用模板套装路径）
            $content = View::fetch($this->getTemplatePath('tag'), [
                'tag' => $tag->toArray(),
                'articles' => $articles,
                'config' => $this->config,
                'is_home' => false,
                'title' => $tag->name,
                'keywords' => $tag->name,
                'description' => $tag->description ?? $tag->name
            ]);

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
}
