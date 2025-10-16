<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Logger;
use app\model\Article as ArticleModel;
use app\model\ArticleTag;
use app\model\ArticleCategory;
use app\model\OperationLog;
use think\Request;
use think\facade\Db;

/**
 * 文章管理控制器
 */
class Article extends BaseController
{
    /**
     * 文章列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 20);
        $title = $request->get('title', '');
        $categoryId = $request->get('category_id', '');
        $userId = $request->get('user_id', '');  // 作者筛选
        $status = $request->get('status', '');
        $isTop = $request->get('is_top', '');
        $isRecommend = $request->get('is_recommend', '');
        $flag = $request->get('flag', '');  // 文章属性筛选
        $startTime = $request->get('start_time', '');  // 开始时间
        $endTime = $request->get('end_time', '');  // 结束时间

        // 构建查询
        $query = ArticleModel::with(['category', 'user', 'tags', 'categories']);

        // 搜索条件
        if (!empty($title)) {
            $query->where('title', 'like', '%' . $title . '%');
        }
        if ($categoryId !== '') {
            // 支持按主分类或副分类筛选
            $query->where(function($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                  ->whereOr('id', 'in', function($subQuery) use ($categoryId) {
                      $subQuery->table('article_categories')
                               ->where('category_id', $categoryId)
                               ->field('article_id');
                  });
            });
        }
        if ($userId !== '') {
            $query->where('user_id', $userId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($isTop !== '') {
            $query->where('is_top', $isTop);
        }
        if ($isRecommend !== '') {
            $query->where('is_recommend', $isRecommend);
        }
        // 按文章属性筛选
        if (!empty($flag)) {
            $query->where('flags', 'like', '%' . $flag . '%');
        }
        // 时间范围筛选
        if (!empty($startTime)) {
            $query->where('publish_time', '>=', $startTime);
        }
        if (!empty($endTime)) {
            $query->where('publish_time', '<=', $endTime . ' 23:59:59');
        }

        // 排序：置顶优先，然后按发布时间倒序
        $query->order(['is_top' => 'desc', 'sort' => 'desc', 'publish_time' => 'desc']);

        // 先获取总数（必须在分页查询之前）
        $total = $query->count();

        // 分页查询
        $list = $query->page($page, $pageSize)->select();

        // 处理数据，添加副分类信息
        $listData = $list->toArray();
        foreach ($listData as &$item) {
            if (isset($item['categories'])) {
                $item['sub_categories'] = array_filter($item['categories'], function($cat) {
                    return $cat['pivot']['is_main'] == 0;
                });
            }
        }

        return Response::paginate($listData, $total, $page, $pageSize);
    }

    /**
     * 文章详情
     */
    public function read($id)
    {
        $article = ArticleModel::with(['category', 'user', 'tags', 'categories'])->find($id);

        if (!$article) {
            return Response::notFound('文章不存在');
        }

        $articleData = $article->toArray();

        // 提取副分类信息
        if (isset($articleData['categories'])) {
            $articleData['sub_categories'] = array_filter($articleData['categories'], function($cat) {
                return $cat['pivot']['is_main'] == 0;
            });
            // 重新索引数组
            $articleData['sub_categories'] = array_values($articleData['sub_categories']);
        }

        // 生成完整的封面图片URL
        if (!empty($articleData['cover_image'])) {
            if (!str_starts_with($articleData['cover_image'], 'http')) {
                $siteUrl = \app\model\Config::getConfig('site_url', '');
                if (!empty($siteUrl)) {
                    $articleData['cover_image'] = rtrim($siteUrl, '/') . '/' . $articleData['cover_image'];
                } else {
                    $articleData['cover_image'] = request()->domain() . '/html/' . $articleData['cover_image'];
                }
            }
        }

        return Response::success($articleData);
    }

    /**
     * 创建文章
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['title']) || empty($data['content']) || empty($data['category_id'])) {
            return Response::error('标题、内容和分类不能为空');
        }

        // 获取标签数据
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        // 获取副分类数据
        $subCategories = $data['sub_categories'] ?? [];
        unset($data['sub_categories']);

        // 主分类ID
        $mainCategoryId = $data['category_id'];

        // 添加作者信息
        $data['user_id'] = $request->user['id'];

        // 如果是发布状态，设置发布时间
        if (isset($data['status']) && $data['status'] == 1 && empty($data['publish_time'])) {
            $data['publish_time'] = date('Y-m-d H:i:s');
        }

        // 自动提取SEO信息
        $this->autoExtractSeoInfo($data);

        Db::startTrans();
        try {
            // 创建文章
            $article = ArticleModel::create($data);

            // 关联标签
            if (!empty($tags) && is_array($tags)) {
                foreach ($tags as $tagId) {
                    ArticleTag::create([
                        'article_id' => $article->id,
                        'tag_id' => $tagId
                    ]);
                }
            }

            // 检查副分类功能是否开启
            $subCategoryEnabled = \app\model\Config::getConfig('article_sub_category', 'close');

            if ($subCategoryEnabled === 'open') {
                // 添加主分类
                ArticleCategory::create([
                    'article_id' => $article->id,
                    'category_id' => $mainCategoryId,
                    'is_main' => 1
                ]);

                // 添加副分类
                if (!empty($subCategories) && is_array($subCategories)) {
                    foreach ($subCategories as $categoryId) {
                        // 避免重复添加主分类
                        if ($categoryId != $mainCategoryId) {
                            ArticleCategory::create([
                                'article_id' => $article->id,
                                'category_id' => $categoryId,
                                'is_main' => 0
                            ]);
                        }
                    }
                }
            } else {
                // 仅保存主分类
                ArticleCategory::create([
                    'article_id' => $article->id,
                    'category_id' => $mainCategoryId,
                    'is_main' => 1
                ]);
            }

            Db::commit();

            // 记录日志
            Logger::create(OperationLog::MODULE_ARTICLE, '文章', $article->id);

            return Response::success(['id' => $article->id], '文章创建成功');

        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('文章创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新文章
     */
    public function update(Request $request, $id)
    {
        $article = ArticleModel::find($id);
        if (!$article) {
            return Response::notFound('文章不存在');
        }

        $data = $request->post();

        // 获取标签数据
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        // 获取副分类数据
        $subCategories = $data['sub_categories'] ?? null;
        unset($data['sub_categories']);

        // 主分类ID
        $mainCategoryId = $data['category_id'] ?? $article->category_id;

        // 如果从草稿变为发布状态，设置发布时间
        if (isset($data['status']) && $data['status'] == 1 && $article->status == 0 && empty($article->publish_time)) {
            $data['publish_time'] = date('Y-m-d H:i:s');
        }

        // 自动提取SEO信息
        $this->autoExtractSeoInfo($data);

        Db::startTrans();
        try {
            // 更新文章
            $article->save($data);

            // 更新标签关联
            if ($tags !== null && is_array($tags)) {
                // 删除旧的标签关联
                ArticleTag::where('article_id', $id)->delete();

                // 添加新的标签关联
                foreach ($tags as $tagId) {
                    ArticleTag::create([
                        'article_id' => $id,
                        'tag_id' => $tagId
                    ]);
                }
            }

            // 检查副分类功能是否开启
            $subCategoryEnabled = \app\model\Config::getConfig('article_sub_category', 'close');

            // 更新分类关联
            if ($subCategories !== null) {
                // 删除旧的分类关联
                ArticleCategory::where('article_id', $id)->delete();

                if ($subCategoryEnabled === 'open') {
                    // 添加主分类
                    ArticleCategory::create([
                        'article_id' => $id,
                        'category_id' => $mainCategoryId,
                        'is_main' => 1
                    ]);

                    // 添加副分类
                    if (is_array($subCategories)) {
                        foreach ($subCategories as $categoryId) {
                            // 避免重复添加主分类
                            if ($categoryId != $mainCategoryId) {
                                ArticleCategory::create([
                                    'article_id' => $id,
                                    'category_id' => $categoryId,
                                    'is_main' => 0
                                ]);
                            }
                        }
                    }
                } else {
                    // 仅保存主分类
                    ArticleCategory::create([
                        'article_id' => $id,
                        'category_id' => $mainCategoryId,
                        'is_main' => 1
                    ]);
                }
            }

            Db::commit();

            // 记录日志
            Logger::update(OperationLog::MODULE_ARTICLE, '文章', $id);

            // 如果文章已发布，自动生成静态页面
            if ($article->status == 1) {  // 1 = 已发布
                $this->autoGenerateStatic($id);
            }

            return Response::success([], '文章更新成功');

        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('文章更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除文章
     */
    public function delete($id)
    {
        $article = ArticleModel::find($id);
        if (!$article) {
            return Response::notFound('文章不存在');
        }

        // 检查回收站是否开启
        $recycleBinEnabled = \app\model\Config::getConfig('recycle_bin_enable', 'open');

        Db::startTrans();
        try {
            if ($recycleBinEnabled === 'open') {
                // 软删除：进入回收站
                $article->delete();
                $message = '文章已移入回收站';
            } else {
                // 物理删除
                // 删除标签关联
                ArticleTag::where('article_id', $id)->delete();

                // 删除分类关联
                ArticleCategory::where('article_id', $id)->delete();

                // 强制删除文章
                $article->force()->delete();
                $message = '文章删除成功';
            }

            Db::commit();

            // 记录日志
            Logger::delete(OperationLog::MODULE_ARTICLE, '文章', $id);

            return Response::success([], $message);

        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('文章删除失败：' . $e->getMessage());
        }
    }

    /**
     * 发布文章（草稿或已下线 -> 已发布）
     */
    public function publish($id)
    {
        $article = ArticleModel::find($id);
        if (!$article) {
            return Response::notFound('文章不存在');
        }

        $article->status = 1; // 已发布
        if (empty($article->publish_time)) {
            $article->publish_time = date('Y-m-d H:i:s');
        }
        $article->save();

        // 记录日志
        Logger::publish(OperationLog::MODULE_ARTICLE, '文章', $id);

        // 自动生成静态页面
        $this->autoGenerateStatic($id);

        return Response::success([], '文章发布成功');
    }

    /**
     * 下线文章（已发布 -> 已下线）
     */
    public function offline($id)
    {
        $article = ArticleModel::find($id);
        if (!$article) {
            return Response::notFound('文章不存在');
        }

        $article->status = 3; // 已下线
        $article->save();

        // 记录日志
        Logger::offline(OperationLog::MODULE_ARTICLE, '文章', $id);

        return Response::success([], '文章已下线');
    }

    /**
     * 自动提取SEO信息（摘要、关键词、描述）
     */
    private function autoExtractSeoInfo(&$data)
    {
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';

        // 去除HTML标签
        $plainText = strip_tags($content);

        // 解码HTML实体（如&nbsp; &amp; &lt; &gt; &quot;等）
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 去除多余空白（包括不可见字符）
        $plainText = preg_replace('/\s+/u', ' ', $plainText);
        $plainText = trim($plainText);

        // 去除零宽字符和其他特殊不可见字符
        $plainText = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $plainText);

        // 自动生成摘要（如果为空）
        if (empty($data['summary']) && !empty($plainText)) {
            $data['summary'] = mb_substr($plainText, 0, 200, 'UTF-8');
            if (mb_strlen($plainText, 'UTF-8') > 200) {
                $data['summary'] .= '...';
            }
        }

        // 自动生成SEO描述（如果为空）
        if (empty($data['seo_description']) && !empty($plainText)) {
            $data['seo_description'] = mb_substr($plainText, 0, 150, 'UTF-8');
            if (mb_strlen($plainText, 'UTF-8') > 150) {
                $data['seo_description'] .= '...';
            }
        }

        // 自动生成SEO关键词（如果为空）
        if (empty($data['seo_keywords'])) {
            $keywords = [];

            // 从标题中提取关键词
            if (!empty($title)) {
                // 解码标题中的HTML实体
                $cleanTitle = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // 分词（简单实现：按空格、标点分割）
                $titleWords = preg_split('/[\s,，.。;；:：!！?？、]+/u', $cleanTitle, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($titleWords as $word) {
                    $word = trim($word);
                    if (mb_strlen($word, 'UTF-8') >= 2 && mb_strlen($word, 'UTF-8') <= 10) {
                        $keywords[] = $word;
                    }
                }
            }

            // 从内容中提取（取前100字符）
            $contentSample = mb_substr($plainText, 0, 100, 'UTF-8');
            $contentWords = preg_split('/[\s,，.。;；:：!！?？、]+/u', $contentSample, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($contentWords as $word) {
                $word = trim($word);
                if (mb_strlen($word, 'UTF-8') >= 2 && mb_strlen($word, 'UTF-8') <= 10) {
                    $keywords[] = $word;
                }
            }

            // 去重并限制数量
            $keywords = array_unique($keywords);
            $keywords = array_slice($keywords, 0, 8);

            if (!empty($keywords)) {
                $data['seo_keywords'] = implode(',', $keywords);
            }
        }
    }

    /**
     * 自动生成静态页面
     */
    private function autoGenerateStatic($id)
    {
        try {
            // 使用 app() 辅助函数实例化控制器
            $staticBuild = app(Build::class);
            $staticBuild->article($id, \app\model\StaticBuildLog::BUILD_TYPE_AUTO);
        } catch (\Exception $e) {
            // 静态生成失败不影响主流程
            trace('自动生成静态页面失败: ' . $e->getMessage(), 'error');
        }
    }
}
