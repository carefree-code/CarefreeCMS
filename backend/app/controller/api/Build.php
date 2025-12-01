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
use app\model\Config;
use app\service\TemplateAssetManager;
use think\facade\View;
use think\facade\Db;

class Build extends BaseController
{
    // 静态文件输出目录
    protected $outputPath;

    // 站点ID
    protected $siteId = 0;

    // 模板解析器
    protected $resolver;

    // 实际模板目录名（如 default）
    protected $actualTemplateDir;

    // 缓存：主站点对象（避免重复查询）
    protected $cachedMainSite = null;

    // 缓存：静态域名（避免重复计算）
    protected $cachedStaticDomain = null;

    /**
     * 控制器初始化方法
     */
    protected function initialize()
    {
        parent::initialize();

        // 从请求获取站点ID
        $this->siteId = (int)$this->request->param('site_id', 0);

        // 创建模板解析器（会自动处理siteId=0的情况，获取主站点）
        $this->resolver = new \app\service\TemplateResolver($this->siteId);

        // 获取解析器确定的站点ID（如果传入0，解析器会找到主站点ID）
        $this->siteId = $this->resolver->getSiteId();

        // 设置站点上下文，确保所有模型查询使用正确的站点
        if ($this->siteId > 0) {
            \app\service\SiteContextService::switchSite($this->siteId);
        }

        // 根据站点设置输出路径
        $site = $this->resolver->getSite();
        if ($site && !empty($site->static_output_dir)) {
            // 使用站点配置的静态生成目录
            $siteFolder = $site->static_output_dir;
        } else {
            // 使用默认目录：主站为空（html根目录），其他站点为site_X
            $siteFolder = ($this->siteId > 0 && (!$site || $site->site_type != 1)) ? 'site_' . $this->siteId : '';
        }

        // 构建完整路径
        $this->outputPath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR;
        if (!empty($siteFolder)) {
            $this->outputPath .= $siteFolder . DIRECTORY_SEPARATOR;
        }

        // 确保输出目录存在
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }

        // 初始化ThinkPHP View配置
        $this->initView();
    }

    /**
     * 初始化ThinkPHP View配置
     */
    protected function initView()
    {
        // 获取当前模板包路径
        $packageCode = $this->resolver->getPackageCode();

        // 从数据库模板路径中提取实际目录名（如 default/topic.html -> default）
        $sampleTemplatePath = '';
        try {
            $sampleTemplatePath = $this->resolver->getTemplateViewPath('index');
        } catch (\Exception $e) {
            try {
                $sampleTemplatePath = $this->resolver->getTemplateViewPath('article');
            } catch (\Exception $e2) {
                try {
                    $sampleTemplatePath = $this->resolver->getTemplateViewPath('topic');
                } catch (\Exception $e3) {
                    // 都失败了，使用默认值
                }
            }
        }

        // 从路径中提取目录名（如 default/topic.html -> default）
        $actualDir = '';
        if ($sampleTemplatePath && strpos($sampleTemplatePath, '/') !== false) {
            $actualDir = substr($sampleTemplatePath, 0, strpos($sampleTemplatePath, '/'));
        }

        // 保存实际目录名，供资源同步使用
        $this->actualTemplateDir = $actualDir ?: $packageCode;

        trace('View初始化 - 包代码: ' . $packageCode, 'info');
        trace('View初始化 - 示例路径: ' . $sampleTemplatePath, 'info');
        trace('View初始化 - 实际目录: ' . $actualDir, 'info');
    }

    /**
     * 获取静态站点URL（用于媒体文件URL）
     * 因为 uploads 文件夹在根目录，所有站点共享，所以使用主站点的网站网址
     * @param \app\model\Site|null $site
     * @return string
     */
    protected function getStaticDomain($site = null)
    {
        // 如果已经缓存过静态域名，直接返回
        if ($this->cachedStaticDomain !== null) {
            return $this->cachedStaticDomain;
        }

        // 获取主站点（使用缓存方法）
        if ($this->cachedMainSite === null) {
            $this->cachedMainSite = \app\model\Site::getMainSite();
        }

        if (!$this->cachedMainSite) {
            $this->cachedStaticDomain = '';
            return '';
        }

        // 优先使用网站网址（site_url），包含完整协议和域名
        if (!empty($this->cachedMainSite->site_url)) {
            // 去掉末尾的斜杠并缓存
            $this->cachedStaticDomain = rtrim($this->cachedMainSite->site_url, '/');
        } else {
            // 默认返回空，使用相对路径
            $this->cachedStaticDomain = '';
        }

        return $this->cachedStaticDomain;
    }

    /**
     * 为模板数据添加基础路径信息
     * @param array $templateData
     * @return array
     */
    protected function addBaseUrlToTemplateData(array $templateData): array
    {
        $site = $this->resolver->getSite();
        $siteFolder = $site ? ($site->static_output_dir ?? '') : '';
        $templateData['base_url'] = !empty($siteFolder) ? '/' . $siteFolder : '';
        $templateData['site_prefix'] = $templateData['base_url'];
        return $templateData;
    }

    /**
     * 格式化文章数据，添加模板需要的字段
     * @param \think\Collection $articles
     * @return array
     */
    protected function formatArticles($articles)
    {
        $result = [];
        foreach ($articles as $article) {
            $data = $article->toArray();

            // 添加 views 字段（模板中使用 article.views）
            $data['views'] = $data['view_count'] ?? 0;

            // 添加 category_name 字段
            if (isset($article->category)) {
                $data['category_name'] = $article->category->name ?? '';
            }

            // 转换所有媒体URL为静态域名
            $data = $this->convertMediaUrls($data);

            $result[] = $data;
        }
        return $result;
    }

    /**
     * 递归转换数组中的所有媒体URL
     * @param mixed $data
     * @return mixed
     */
    protected function convertMediaUrls($data)
    {
        // 获取静态域名（已缓存，只在第一次调用时查询数据库）
        $staticDomain = $this->getStaticDomain();

        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $value = $this->convertMediaUrls($value);
            }
            return $data;
        }

        if (is_string($data)) {
            // 先快速检查是否包含 /uploads/，避免不必要的正则匹配
            if (strpos($data, '/uploads/') !== false) {
                // 只转换包含 /uploads/ 的完整 URL
                // 匹配格式：http://xxx/uploads/... 或 https://xxx/uploads/...
                if (preg_match('#^https?://[^/]+/uploads/#', $data)) {
                    if (!empty($staticDomain)) {
                        // 如果配置了主站点域名，替换为该域名
                        $data = preg_replace('#^https?://[^/]+#', $staticDomain, $data);
                    } else {
                        // 如果没有配置域名，转换为相对路径
                        $data = preg_replace('#^https?://[^/]+(/uploads/.+)$#', '$1', $data);
                    }
                }
            }
        }

        return $data;
    }


    /**
     * 获取模板路径（使用模板解析器）
     * @param string $templateType 模板类型（如 index, category 等）
     * @return string 模板视图路径（相对于templates目录，如 default/index）
     */
    protected function getTemplatePath($templateType)
    {
        try {
            // 使用解析器获取模板路径（相对于templates目录）
            // 路径包含目录名，如 default/topic.html
            $viewPath = $this->resolver->getTemplateViewPath($templateType);

            // ThinkPHP View需要完整的路径（包含目录），不需要去掉前缀
            // 但需要去掉.html后缀（ThinkPHP会自动添加）
            // default/topic.html -> default/topic

            trace('模板路径解析 - 类型: ' . $templateType . ', 最终路径: ' . $viewPath, 'info');

            return $viewPath;
        } catch (\Exception $e) {
            trace('模板解析失败: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 使用ThinkPHP View渲染模板
     * @param string $templatePath 模板路径（相对于templates目录，如 default/index）
     * @param array $data 模板数据
     * @return string 渲染后的HTML
     */
    protected function renderTemplate(string $templatePath, array $data): string
    {
        try {
            // 去掉.html后缀（ThinkPHP View会自动添加）
            $templatePath = str_replace('.html', '', $templatePath);

            // 设置模板变量
            View::assign($data);

            // 渲染模板并返回HTML字符串
            return View::fetch($templatePath);
        } catch (\Exception $e) {
            trace('模板渲染失败: ' . $e->getMessage(), 'error');
            trace('模板路径: ' . $templatePath, 'error');
            throw $e;
        }
    }

    /**
     * 获取模板全局配置数据
     * 返回包含site和config的数组，供模板使用
     * @return array ['site' => ..., 'config' => ...]
     */
    protected function getTemplateGlobalData(): array
    {
        // 获取站点的合并配置
        $config = $this->resolver->getConfig();

        // 获取站点信息
        $site = $this->resolver->getSite();

        $siteData = [];
        if ($site) {
            // 将站点对象转为数组
            $siteData = $site->toArray();

            // 转换站点数据中的媒体URL（logo, favicon等）
            $siteData = $this->convertMediaUrls($siteData);

            // 添加站点基础字段到config
            $config['site_name'] = $site->site_name ?? '';
            $config['site_code'] = $site->site_code ?? '';
            $config['logo'] = $siteData['logo'] ?? '';
            $config['favicon'] = $siteData['favicon'] ?? '';
            $config['site_url'] = $site->site_url ?? '';

            // 添加站点路径前缀（用于生成正确的链接）
            $siteFolder = $site->static_output_dir ?? '';
            $config['base_url'] = !empty($siteFolder) ? '/' . $siteFolder : '';
            $config['site_prefix'] = $config['base_url']; // 别名，方便模板使用

            // 添加SEO配置（支持嵌套和扁平两种访问方式）
            if ($site->seo_config && is_array($site->seo_config)) {
                $config['seo'] = $site->seo_config;
                foreach ($site->seo_config as $key => $value) {
                    $config['seo_' . $key] = $value;
                }
            }

            // 添加分析配置
            if ($site->analytics_config && is_array($site->analytics_config)) {
                $config['analytics'] = $site->analytics_config;
            }

            // 合并站点config字段
            if ($site->config && is_array($site->config)) {
                $config = array_merge($config, $site->config);
            }
        }

        // 转换config中的媒体URL
        $config = $this->convertMediaUrls($config);

        return [
            'site' => $siteData,
            'config' => $config
        ];
    }

    /**
     * 生成首页
     */
    public function index()
    {
        $startTime = microtime(true);
        trace('[性能] 开始生成首页', 'info');

        try {
            // 准备模板数据
            $stepStart = microtime(true);
            $templateData = $this->resolver->prepareTemplateData();
            $templateData['is_home'] = true;
            $templateData = $this->addBaseUrlToTemplateData($templateData);
            trace('[性能] 准备模板数据耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 获取首页需要的数据
            // 1. 最新文章
            $stepStart = microtime(true);
            $latest_articles = Article::bySite($this->siteId)
                ->with(['category', 'user'])
                ->where('status', 1)
                ->order('create_time', 'desc')
                ->limit(10)
                ->select();
            $latest_articles = $this->formatArticles($latest_articles);
            trace('[性能] 查询最新文章耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms, 数量: ' . count($latest_articles), 'info');

            // 2. 推荐文章
            $stepStart = microtime(true);
            $recommended_articles = Article::bySite($this->siteId)
                ->with(['category', 'user'])
                ->where('status', 1)
                ->where('is_recommend', 1)
                ->order('sort', 'asc')
                ->order('create_time', 'desc')
                ->limit(6)
                ->select();
            $recommended_articles = $this->formatArticles($recommended_articles);
            trace('[性能] 查询推荐文章耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms, 数量: ' . count($recommended_articles), 'info');

            // 3. 热门文章
            $stepStart = microtime(true);
            $hot_articles = Article::bySite($this->siteId)
                ->where('status', 1)
                ->order('view_count', 'desc')
                ->limit(10)
                ->select();
            $hot_articles = $this->formatArticles($hot_articles);
            trace('[性能] 查询热门文章耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms, 数量: ' . count($hot_articles), 'info');

            // 4. 分类列表
            $stepStart = microtime(true);
            $categories = Category::select()->toArray();
            $categories = $this->convertMediaUrls($categories);
            trace('[性能] 查询分类列表耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms, 数量: ' . count($categories), 'info');

            // 5. 标签列表
            $stepStart = microtime(true);
            $tags = Tag::where('status', 1)->limit(30)->select()->toArray();
            $tags = $this->convertMediaUrls($tags);
            trace('[性能] 查询标签列表耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms, 数量: ' . count($tags), 'info');

            // 合并数据到模板数据
            $templateData = array_merge($templateData, [
                'latest_articles' => $latest_articles,
                'recommended_articles' => $recommended_articles,
                'hot_articles' => $hot_articles,
                'categories' => $categories,
                'tags' => $tags,
            ]);

            // 转换模板数据中的所有媒体URL
            $stepStart = microtime(true);
            $templateData = $this->convertMediaUrls($templateData);
            trace('[性能] 转换媒体URL耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 合并全局配置数据
            $stepStart = microtime(true);
            $globalData = $this->getTemplateGlobalData();
            $templateData = array_merge($templateData, $globalData);
            trace('[性能] 合并全局配置耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 渲染模板
            $stepStart = microtime(true);
            $content = $this->renderTemplate($this->getTemplatePath('index'), $templateData);
            trace('[性能] 渲染模板耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms, 内容长度: ' . strlen($content), 'info');

            // 保存文件
            $stepStart = microtime(true);
            $filePath = $this->outputPath . 'index.html';
            file_put_contents($filePath, $content);
            trace('[性能] 保存文件耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            trace('[性能] 首页生成完成，总耗时: ' . $totalTime . 'ms', 'info');

            return Response::success([], '首页生成成功');
        } catch (\Exception $e) {
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            trace('[性能] 首页生成失败，耗时: ' . $totalTime . 'ms, 错误: ' . $e->getMessage(), 'error');
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

            // 获取文章总数用于计算分页（使用bySite确保查询当前站点的数据）
            $total = Article::bySite($this->siteId)
                ->where('status', 1)
                ->count();
            $totalPages = max(ceil($total / $pageSize), 1); // 至少生成1页

            // 生成每一页
            for ($page = 1; $page <= $totalPages; $page++) {
                // 获取当前页的文章
                $articles = Article::bySite($this->siteId)
                    ->with(['category', 'user'])
                    ->where('status', 1)
                    ->order('create_time', 'desc')
                    ->limit($pageSize)
                    ->page($page)
                    ->select();
                $articles = $this->formatArticles($articles);

                // 准备模板数据
                $templateData = $this->resolver->prepareTemplateData();
                $templateData = $this->addBaseUrlToTemplateData($templateData);
                $templateData = array_merge($templateData, [
                    'is_home' => false,
                    'title' => $page > 1 ? "文章列表 - 第{$page}页" : '文章列表',
                    'keywords' => '文章列表,全部文章',
                    'description' => '浏览所有文章',
                    'articles' => $articles,
                    'total' => $total,
                    'page' => $page,
                    'pages' => $totalPages,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total' => $total,
                        'page_size' => $pageSize
                    ]
                ]);

                // 合并全局配置数据
                $globalData = $this->getTemplateGlobalData();
                $templateData = array_merge($templateData, $globalData);

                // 渲染模板
                $content = $this->renderTemplate($this->getTemplatePath('articles'), $templateData);

                // 保存文件
                // 第一页保存为 articles.html，其他页保存为 articles-2.html, articles-3.html
                $fileName = $page === 1 ? 'articles.html' : "articles-{$page}.html";
                $filePath = $this->outputPath . $fileName;
                file_put_contents($filePath, $content);
            }

            return Response::success(['pages' => $totalPages], "文章列表生成成功，共{$totalPages}页");
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成文章详情页
     */
    public function article($id = null)
    {
        $startTime = microtime(true);

        // 优先使用传入的ID，否则从请求中获取
        $id = $id ?? (int)$this->request->param('id', 0);

        if (!$id) {
            return Response::error('缺少文章ID');
        }

        try {
            trace('[性能] 开始生成文章页，ID: ' . $id . ', 站点ID: ' . $this->siteId, 'info');

            // 获取文章详情（使用bySite确保查询当前站点的数据）
            $stepStart = microtime(true);
            $article = Article::bySite($this->siteId)
                ->with(['category', 'user', 'tags'])
                ->where('id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->find();

            if (!$article) {
                return Response::error('文章不存在或未发布');
            }
            trace('[性能] 查询文章详情耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 获取上一篇和下一篇
            $stepStart = microtime(true);
            $prev = Article::bySite($this->siteId)
                ->where('id', '<', $id)
                ->where('status', 1)  // 1 = 已发布
                ->order('id', 'desc')
                ->field('id,title')
                ->find();

            $next = Article::bySite($this->siteId)
                ->where('id', '>', $id)
                ->where('status', 1)  // 1 = 已发布
                ->order('id', 'asc')
                ->field('id,title')
                ->find();
            trace('[性能] 查询上下篇耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 准备基础模板数据
            $stepStart = microtime(true);
            $templateData = $this->resolver->prepareTemplateData();
            $templateData = $this->addBaseUrlToTemplateData($templateData);
            trace('[性能] 准备模板数据耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 格式化文章数据
            $stepStart = microtime(true);
            $articleData = $this->formatArticles(collect([$article]))[0];
            trace('[性能] 格式化文章数据耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 添加文章特定数据
            $templateData = array_merge($templateData, [
                'article' => $articleData,
                'prev' => $prev ? $prev->toArray() : null,
                'next' => $next ? $next->toArray() : null,
                'is_home' => false,
                'title' => $article->title,
                'keywords' => $article->seo_keywords ?? '',
                'description' => $article->seo_description ?? $article->summary
            ]);

            $templatePath = $this->getTemplatePath('article');

            // 合并全局配置数据
            $stepStart = microtime(true);
            $globalData = $this->getTemplateGlobalData();
            $templateData = array_merge($templateData, $globalData);
            trace('[性能] 合并全局配置耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 渲染模板
            $stepStart = microtime(true);
            $content = $this->renderTemplate($templatePath, $templateData);
            trace('[性能] 渲染模板耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms, 内容长度: ' . strlen($content), 'info');

            // 保存文件
            $stepStart = microtime(true);
            $filePath = $this->outputPath . 'article' . DIRECTORY_SEPARATOR . $id . '.html';
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($filePath, $content);
            trace('[性能] 保存文件耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            trace('[性能] 文章页生成完成 [ID:' . $id . ']，总耗时: ' . $totalTime . 'ms', 'info');

            return Response::success([], '文章生成成功');
        } catch (\Exception $e) {
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            trace('[性能] 文章生成失败 [ID:' . $id . ']，耗时: ' . $totalTime . 'ms, 错误: ' . $e->getMessage(), 'error');
            trace('错误详情: ' . $e->getTraceAsString(), 'error');

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

            // 获取该分类下的文章（使用bySite确保查询当前站点的数据）
            $articles = Article::bySite($this->siteId)
                ->with(['user', 'tags'])
                ->where('category_id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->order('create_time', 'desc')
                ->select();
            $articles = $this->formatArticles($articles);

            // 获取侧边栏数据
            $categories = Category::select()->toArray();
            $tags = Tag::where('status', 1)->limit(20)->select()->toArray();
            $hotArticles = Article::bySite($this->siteId)
                ->where('status', 1)
                ->order('view_count', 'desc')
                ->limit(5)
                ->field('id,title,view_count,cover_image,create_time')
                ->select();
            $hotArticles = $this->formatArticles($hotArticles);

            // 准备基础模板数据
            $templateData = $this->resolver->prepareTemplateData();
            $templateData = $this->addBaseUrlToTemplateData($templateData);

            // 添加分类特定数据
            $categoryData = $this->convertMediaUrls($category->toArray());
            $templateData = array_merge($templateData, [
                'category' => $categoryData,
                'articles' => $articles,
                'total' => count($articles),
                'page' => 1,
                'pages' => 1,
                'is_home' => false,
                'title' => $category->name,
                'keywords' => $category->name,
                'description' => $category->description ?? $category->name,
                'categories' => $this->convertMediaUrls($categories),
                'tags' => $this->convertMediaUrls($tags),
                'hot_articles' => $hotArticles
            ]);

            // 转换所有模板数据中的媒体URL
            $templateData = $this->convertMediaUrls($templateData);

            // 合并全局配置数据
            $globalData = $this->getTemplateGlobalData();
            $templateData = array_merge($templateData, $globalData);

            // 渲染模板
            $content = $this->renderTemplate($this->getTemplatePath('category'), $templateData);

            // 保存文件
            $filePath = $this->outputPath . 'category' . DIRECTORY_SEPARATOR . $id . '.html';
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($filePath, $content);

            return Response::success([], '分类页生成成功');
        } catch (\Exception $e) {
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
            trace('开始生成标签页，ID: ' . $id . ', 站点ID: ' . $this->siteId, 'info');

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
                ->select();
            $articles = $this->formatArticles($articles);

            trace('标签下文章数量: ' . count($articles), 'info');

            // 获取侧边栏数据
            $categories = Category::select()->toArray();
            $tags = Tag::where('status', 1)->limit(20)->select()->toArray();
            $hotArticles = Article::bySite($this->siteId)
                ->where('status', 1)
                ->order('view_count', 'desc')
                ->limit(5)
                ->field('id,title,view_count,cover_image,create_time')
                ->select();
            $hotArticles = $this->formatArticles($hotArticles);

            // 准备基础模板数据
            $templateData = $this->resolver->prepareTemplateData();
            $templateData = $this->addBaseUrlToTemplateData($templateData);

            // 添加标签特定数据
            $tagData = $this->convertMediaUrls($tag->toArray());
            $templateData = array_merge($templateData, [
                'tag' => $tagData,
                'articles' => $articles,
                'total' => count($articles),
                'page' => 1,
                'pages' => 1,
                'is_home' => false,
                'title' => $tag->name,
                'keywords' => $tag->name,
                'description' => $tag->description ?? $tag->name,
                'categories' => $this->convertMediaUrls($categories),
                'tags' => $this->convertMediaUrls($tags),
                'hot_articles' => $hotArticles
            ]);

            // 转换所有模板数据中的媒体URL
            $templateData = $this->convertMediaUrls($templateData);

            $templatePath = $this->getTemplatePath('tag');
            trace('模板路径: ' . $templatePath, 'info');
            trace('模板数据键: ' . implode(', ', array_keys($templateData)), 'info');

            // 合并全局配置数据
            $globalData = $this->getTemplateGlobalData();
            $templateData = array_merge($templateData, $globalData);

            // 渲染模板
            trace('开始渲染模板...', 'info');
            $content = $this->renderTemplate($templatePath, $templateData);
            trace('模板渲染成功，内容长度: ' . strlen($content), 'info');

            // 保存文件
            $filePath = $this->outputPath . 'tag' . DIRECTORY_SEPARATOR . $id . '.html';
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($filePath, $content);

            return Response::success([], '标签页生成成功');
        } catch (\Exception $e) {
            trace('标签页生成失败 [ID:' . $id . ']: ' . $e->getMessage(), 'error');
            trace('错误详情: ' . $e->getTraceAsString(), 'error');

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

            return Response::success($result, "标签页生成完成，共生成{$result['tags']}个页面");
        } catch (\Exception $e) {
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
            // 获取专题详情（使用bySite确保查询当前站点的数据）
            $topic = Topic::bySite($this->siteId)
                ->where('id', $id)
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
                $articles = Article::bySite($this->siteId)
                    ->where('status', 1)
                    ->whereIn('id', $articleIds)
                    ->with(['category', 'user', 'tags'])
                    ->select();
                $articles = $this->formatArticles($articles);
            } else {
                $articles = [];
            }

            // 获取其他推荐专题（使用bySite确保查询当前站点的数据）
            $topics = Topic::bySite($this->siteId)
                ->where('status', 1)
                ->where('is_recommended', 1)
                ->where('id', '<>', $id)
                ->limit(10)
                ->select()
                ->toArray();

            // 获取分类和标签（侧边栏）
            $categories = Category::limit(10)->select()->toArray();
            $tags = Tag::where('status', 1)->limit(20)->select()->toArray();
            $hotArticles = Article::bySite($this->siteId)
                ->where('status', 1)
                ->order('view_count', 'desc')
                ->limit(5)
                ->field('id,title,view_count,cover_image,create_time')
                ->select();
            $hotArticles = $this->formatArticles($hotArticles);

            // 准备基础模板数据
            $templateData = $this->resolver->prepareTemplateData();
            $templateData = $this->addBaseUrlToTemplateData($templateData);

            // 添加专题特定数据
            $topicData = $this->convertMediaUrls($topic->toArray());
            $templateData = array_merge($templateData, [
                'topic' => $topicData,
                'articles' => $articles,
                'total' => count($articles),
                'page' => 1,
                'pages' => 1,
                'topics' => $this->convertMediaUrls($topics),
                'categories' => $this->convertMediaUrls($categories),
                'tags' => $this->convertMediaUrls($tags),
                'hot_articles' => $hotArticles,
                'is_home' => false,
                'title' => $topic->seo_title ?: $topic->name,
                'keywords' => $topic->seo_keywords ?? '',
                'description' => $topic->seo_description ?: $topic->description
            ]);

            // 转换所有模板数据中的媒体URL
            $templateData = $this->convertMediaUrls($templateData);

            // 合并全局配置数据
            $globalData = $this->getTemplateGlobalData();
            $templateData = array_merge($templateData, $globalData);

            // 渲染模板
            $content = $this->renderTemplate($this->getTemplatePath('topic'), $templateData);

            // 保存文件 - 使用 topic-{id}.html 格式
            $filePath = $this->outputPath . 'topic-' . $topic->id . '.html';
            file_put_contents($filePath, $content);

            return Response::success([], '专题页生成成功');
        } catch (\Exception $e) {
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

            // 获取所有已发布的单页面（使用bySite确保查询当前站点的数据）
            $pages = Page::bySite($this->siteId)
                ->where('status', 1)
                ->select();  // 1 = 已发布

            foreach ($pages as $page) {
                try {
                    $this->page($page->id);
                    $result['pages']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }

            return Response::success($result, "单页生成完成，共生成{$result['pages']}个页面");
        } catch (\Exception $e) {
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
            // 获取页面详情（使用bySite确保查询当前站点的数据）
            $page = Page::bySite($this->siteId)
                ->where('id', $id)
                ->where('status', 1)  // 1 = 已发布
                ->find();

            if (!$page) {
                return Response::error('页面不存在或未发布');
            }

            // 准备基础模板数据
            $templateData = $this->resolver->prepareTemplateData();
            $templateData = $this->addBaseUrlToTemplateData($templateData);

            // 添加页面特定数据
            $templateData = array_merge($templateData, [
                'page' => $page->toArray(),
                'is_home' => false,
                'title' => $page->title,
                'keywords' => $page->seo_keywords ?? '',
                'description' => $page->seo_description ?? ''
            ]);

            // 合并全局配置数据
            $globalData = $this->getTemplateGlobalData();
            $templateData = array_merge($templateData, $globalData);

            // 渲染模板
            $content = $this->renderTemplate($this->getTemplatePath('page'), $templateData);

            // 保存文件（使用 UrlHelper 生成正确的路径）
            $filePath = \app\service\UrlHelper::getPageStaticPath($page, $this->siteId);

            // 确保目录存在
            \app\service\UrlHelper::ensureDirectory($filePath);

            file_put_contents($filePath, $content);

            return Response::success([], '页面生成成功');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage());
        }
    }

    /**
     * 批量生成所有
     */
    public function all()
    {
        $totalStartTime = microtime(true);
        trace('[性能] ========== 开始批量生成所有静态页面 ==========', 'info');
        trace('[性能] 站点ID: ' . $this->siteId, 'info');

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
            $stepStart = microtime(true);
            $this->index();
            $result['index'] = 1;
            trace('[性能] 首页生成完成，耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 生成文章列表页
            $stepStart = microtime(true);
            $articlesRes = $this->articles();
            if ($articlesRes) {
                $result['article_list_pages'] = $articlesRes->getData()['data']['pages'] ?? 0;
            }
            trace('[性能] 文章列表页生成完成，共' . $result['article_list_pages'] . '页，耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 生成所有已发布的文章（使用bySite确保查询当前站点的数据）
            $stepStart = microtime(true);
            $articles = Article::bySite($this->siteId)
                ->where('status', 1)
                ->select();  // 1 = 已发布
            $articleCount = count($articles);
            trace('[性能] 开始生成' . $articleCount . '篇文章...', 'info');

            $articleStartTime = microtime(true);
            foreach ($articles as $index => $article) {
                $singleStart = microtime(true);
                try {
                    $this->article($article->id);
                    $result['articles']++;
                    $singleTime = round((microtime(true) - $singleStart) * 1000, 2);

                    // 每10篇输出一次进度
                    if (($index + 1) % 10 == 0) {
                        $avgTime = round((microtime(true) - $articleStartTime) * 1000 / ($index + 1), 2);
                        $progress = round(($index + 1) / $articleCount * 100, 1);
                        trace('[性能] 文章生成进度: ' . ($index + 1) . '/' . $articleCount . ' (' . $progress . '%), 平均耗时: ' . $avgTime . 'ms/篇', 'info');
                    }
                } catch (\Exception $e) {
                    trace('[性能] 文章生成失败[ID:' . $article->id . ']: ' . $e->getMessage(), 'error');
                    $result['failed']++;
                }
            }
            $articleTotalTime = round((microtime(true) - $articleStartTime) * 1000, 2);
            $avgArticleTime = $articleCount > 0 ? round($articleTotalTime / $articleCount, 2) : 0;
            trace('[性能] 文章生成完成，共' . $result['articles'] . '篇，总耗时: ' . $articleTotalTime . 'ms，平均: ' . $avgArticleTime . 'ms/篇', 'info');

            // 生成所有分类页
            $stepStart = microtime(true);
            $categories = Category::select();
            $categoryCount = count($categories);
            trace('[性能] 开始生成' . $categoryCount . '个分类页...', 'info');

            foreach ($categories as $category) {
                try {
                    $this->category($category->id);
                    $result['categories']++;
                } catch (\Exception $e) {
                    trace('[性能] 分类生成失败[ID:' . $category->id . ']: ' . $e->getMessage(), 'error');
                    $result['failed']++;
                }
            }
            trace('[性能] 分类页生成完成，共' . $result['categories'] . '个，耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 生成所有标签页
            $stepStart = microtime(true);
            $tags = Tag::where('status', 1)->select();  // 1 = 启用
            $tagCount = count($tags);
            trace('[性能] 开始生成' . $tagCount . '个标签页...', 'info');

            foreach ($tags as $tag) {
                try {
                    $this->tag($tag->id);
                    $result['tags']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }
            trace('[性能] 标签页生成完成，共' . $result['tags'] . '个，耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 生成所有专题页（使用bySite确保查询当前站点的数据）
            $stepStart = microtime(true);
            $topics = Topic::bySite($this->siteId)
                ->where('status', 1)
                ->select();  // 1 = 已发布
            $topicCount = count($topics);
            trace('[性能] 开始生成' . $topicCount . '个专题页...', 'info');

            foreach ($topics as $topic) {
                try {
                    $this->topic($topic->id);
                    $result['topics']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }
            trace('[性能] 专题页生成完成，共' . $result['topics'] . '个，耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 生成所有已发布的单页面（使用bySite确保查询当前站点的数据）
            $stepStart = microtime(true);
            $pages = Page::bySite($this->siteId)
                ->where('status', 1)
                ->select();  // 1 = 已发布
            $pageCount = count($pages);
            trace('[性能] 开始生成' . $pageCount . '个单页...', 'info');

            foreach ($pages as $page) {
                try {
                    $this->page($page->id);
                    $result['pages']++;
                } catch (\Exception $e) {
                    $result['failed']++;
                }
            }
            trace('[性能] 单页生成完成，共' . $result['pages'] . '个，耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');

            // 同步模板资源文件（CSS、JS、图片等）
            $stepStart = microtime(true);
            try {
                $assetManager = new TemplateAssetManager($this->actualTemplateDir, $this->outputPath);
                $assetResult = $assetManager->syncAllAssets();
                $result['assets_synced'] = $assetResult['success'] ? $assetResult['total_files'] : 0;
                if (!$assetResult['success']) {
                    $result['assets_error'] = $assetResult['message'];
                    trace('[性能] 资源同步失败: ' . $assetResult['message'], 'error');
                } else {
                    trace('[性能] 资源同步完成，共' . $result['assets_synced'] . '个文件，耗时: ' . round((microtime(true) - $stepStart) * 1000, 2) . 'ms', 'info');
                }
            } catch (\Exception $e) {
                $result['assets_synced'] = 0;
                $result['assets_error'] = $e->getMessage();
                trace('[性能] 资源同步异常: ' . $e->getMessage(), 'error');
            }

            $totalTime = round((microtime(true) - $totalStartTime) * 1000, 2);
            $totalTimeSeconds = round($totalTime / 1000, 2);
            trace('[性能] ========== 批量生成完成 ==========', 'info');
            trace('[性能] 总耗时: ' . $totalTime . 'ms (' . $totalTimeSeconds . '秒)', 'info');
            trace('[性能] 生成统计: 首页' . $result['index'] . '个, 文章列表' . $result['article_list_pages'] . '页, 文章' . $result['articles'] . '篇, 分类' . $result['categories'] . '个, 标签' . $result['tags'] . '个, 专题' . $result['topics'] . '个, 单页' . $result['pages'] . '个, 失败' . $result['failed'] . '个', 'info');

            return Response::success($result, '批量生成完成');
        } catch (\Exception $e) {
            $totalTime = round((microtime(true) - $totalStartTime) * 1000, 2);
            trace('[性能] 批量生成失败，耗时: ' . $totalTime . 'ms, 错误: ' . $e->getMessage(), 'error');
            return Response::error('批量生成失败：' . $e->getMessage());
        }
    }


    /**
     * 同步模板资源文件到静态目录
     * 将模板套装的 assets 目录（CSS、JS、图片等）复制到静态输出目录
     */
    public function syncAssets()
    {
        try {
            $assetManager = new TemplateAssetManager($this->actualTemplateDir, $this->outputPath);
            $result = $assetManager->syncAllAssets();

            if ($result['success']) {
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
            $assetManager = new TemplateAssetManager($this->actualTemplateDir, $this->outputPath);
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
            $assetManager = new TemplateAssetManager($this->actualTemplateDir, $this->outputPath);
            $assets = $assetManager->getAssetsList();

            return Response::success($assets);
        } catch (\Exception $e) {
            return Response::error('获取资源列表失败：' . $e->getMessage());
        }
    }

    /**
     * 生成指定站点的所有静态页面
     * @param int $siteId 站点ID
     */
    public function buildSite(int $siteId)
    {
        $this->siteId = $siteId;
        $this->initialize();
        return $this->all();
    }

    /**
     * 批量生成所有站点
     */
    public function buildAllSites()
    {
        try {
            $sites = \app\model\Site::where('status', 1)->select();
            $result = [];

            foreach ($sites as $site) {
                try {
                    $siteResult = $this->buildSite($site->id);
                    $resultData = $siteResult->getData();
                    $siteKey = $site->site_url ?: $site->site_code;
                    $result[$siteKey] = $resultData['data'] ?? $resultData;
                } catch (\Exception $e) {
                    $siteKey = $site->site_url ?: $site->site_code;
                    $result[$siteKey] = ['error' => $e->getMessage()];
                }
            }

            return Response::success($result, '所有站点生成完成');
        } catch (\Exception $e) {
            return Response::error('批量生成失败：' . $e->getMessage());
        }
    }

    /**
     * 生成配置标签测试页
     * 用于测试Carefree ConfigTagService多站点支持
     */
    public function testConfig()
    {
        try {
            // 准备模板数据
            $templateData = $this->resolver->prepareTemplateData();

            // 合并全局配置数据
            $globalData = $this->getTemplateGlobalData();
            $templateData = array_merge($templateData, $globalData);

            // 渲染模板（config-test.html在包根目录）
            $content = $this->renderTemplate('config-test.html', $templateData);

            // 保存文件
            $filePath = $this->outputPath . 'config-test.html';
            file_put_contents($filePath, $content);

            return Response::success([
                'file' => $filePath,
                'site_id' => $this->siteId,
                'package_code' => $this->resolver->getPackageCode()
            ], '配置测试页生成成功');
        } catch (\Exception $e) {
            return Response::error('生成失败：' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        }
    }
}
