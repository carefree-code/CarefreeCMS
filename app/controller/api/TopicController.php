<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\Topic;
use app\model\TopicArticle;
use app\model\Article;
use think\Request;
use think\facade\Validate;

/**
 * 专题管理控制器
 */
class TopicController extends BaseController
{
    /**
     * 获取专题列表
     */
    public function index(Request $request)
    {
        $page = $request->param('page', 1);
        $pageSize = $request->param('page_size', 10);
        $keyword = $request->param('keyword', '');
        $status = $request->param('status', '');
        $isRecommended = $request->param('is_recommended', '');

        $query = Topic::order('sort', 'asc')
            ->order('id', 'desc');

        // 关键词搜索
        if (!empty($keyword)) {
            $query->where('name|description', 'like', '%' . $keyword . '%');
        }

        // 状态筛选
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 推荐筛选
        if ($isRecommended !== '') {
            $query->where('is_recommended', $isRecommended);
        }

        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 获取所有专题（不分页）
     */
    public function all()
    {
        $list = Topic::where('status', Topic::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->select();

        return $this->success([
            'list' => $list,
        ]);
    }

    /**
     * 获取专题详情
     */
    public function read($id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        return $this->success($topic);
    }

    /**
     * 创建专题
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'name',
            'slug',
            'description',
            'cover_image',
            'template',
            'seo_title',
            'seo_keywords',
            'seo_description',
            'is_recommended',
            'status',
            'sort',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:100',
            'slug' => 'require|max:100|regex:^[a-z0-9\-]+$|unique:topics',
            'template' => 'max:100',
            'seo_title' => 'max:200',
            'seo_keywords' => 'max:255',
            'seo_description' => 'max:500',
        ])->message([
            'name.require' => '专题名称不能为空',
            'name.max' => '专题名称最多100个字符',
            'slug.require' => 'URL别名不能为空',
            'slug.max' => 'URL别名最多100个字符',
            'slug.regex' => 'URL别名只能包含小写字母、数字和连字符',
            'slug.unique' => 'URL别名已存在',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        // 设置默认值
        if (!isset($data['template'])) {
            $data['template'] = 'topic_default';
        }
        if (!isset($data['is_recommended'])) {
            $data['is_recommended'] = 0;
        }
        if (!isset($data['status'])) {
            $data['status'] = Topic::STATUS_ENABLED;
        }
        if (!isset($data['sort'])) {
            $data['sort'] = 0;
        }

        $topic = new Topic();
        $topic->save($data);

        return $this->success($topic, '创建成功');
    }

    /**
     * 更新专题
     */
    public function update(Request $request, $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        $data = $request->only([
            'name',
            'slug',
            'description',
            'cover_image',
            'template',
            'seo_title',
            'seo_keywords',
            'seo_description',
            'is_recommended',
            'status',
            'sort',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:100',
            'slug' => 'require|max:100|regex:^[a-z0-9\-]+$|unique:topics,slug,' . $id,
            'template' => 'max:100',
            'seo_title' => 'max:200',
            'seo_keywords' => 'max:255',
            'seo_description' => 'max:500',
        ])->message([
            'name.require' => '专题名称不能为空',
            'name.max' => '专题名称最多100个字符',
            'slug.require' => 'URL别名不能为空',
            'slug.max' => 'URL别名最多100个字符',
            'slug.regex' => 'URL别名只能包含小写字母、数字和连字符',
            'slug.unique' => 'URL别名已存在',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        $topic->save($data);

        return $this->success($topic, '更新成功');
    }

    /**
     * 删除专题
     */
    public function delete($id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        // 删除专题文章关联
        TopicArticle::where('topic_id', $id)->delete();

        // 软删除专题
        $topic->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 获取专题的文章列表
     */
    public function articles(Request $request, $id)
    {
        $page = $request->param('page', 1);
        $pageSize = $request->param('page_size', 10);

        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        // 获取关联的文章ID
        $topicArticles = TopicArticle::where('topic_id', $id)
            ->order('sort', 'asc')
            ->select();

        $articleIds = $topicArticles->column('article_id');

        if (empty($articleIds)) {
            return $this->success([
                'list' => [],
                'total' => 0,
                'page' => $page,
                'page_size' => $pageSize,
            ]);
        }

        // 查询文章详情
        $query = Article::whereIn('id', $articleIds)
            ->with(['category', 'user'])
            ->order('id', 'desc');

        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        // 附加排序和精选信息
        foreach ($list->items() as &$article) {
            $topicArticle = $topicArticles->where('article_id', $article->id)->first();
            $article->topic_sort = $topicArticle ? $topicArticle->sort : 0;
            $article->is_featured = $topicArticle ? $topicArticle->is_featured : 0;
        }

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 添加文章到专题
     */
    public function addArticle(Request $request, $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        $articleId = $request->param('article_id');
        $sort = $request->param('sort', 0);
        $isFeatured = $request->param('is_featured', 0);

        // 检查文章是否存在
        $article = Article::find($articleId);
        if (!$article) {
            return $this->error('文章不存在');
        }

        // 检查是否已关联
        $exists = TopicArticle::where('topic_id', $id)
            ->where('article_id', $articleId)
            ->find();

        if ($exists) {
            return $this->error('文章已在该专题中');
        }

        // 添加关联
        $topic->addArticle($articleId, $sort, $isFeatured);

        return $this->success(null, '添加成功');
    }

    /**
     * 从专题移除文章
     */
    public function removeArticle(Request $request, $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        $articleId = $request->param('article_id');

        // 移除关联
        $topic->removeArticle($articleId);

        return $this->success(null, '移除成功');
    }

    /**
     * 批量设置专题文章
     */
    public function setArticles(Request $request, $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        $articleIds = $request->param('article_ids', []);

        if (!is_array($articleIds)) {
            return $this->error('文章ID必须是数组');
        }

        // 批量设置
        $topic->setArticles($articleIds);

        return $this->success(null, '设置成功');
    }

    /**
     * 更新文章在专题中的排序
     */
    public function updateArticleSort(Request $request, $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        $articleId = $request->param('article_id');
        $sort = $request->param('sort', 0);

        TopicArticle::updateArticleSort($id, $articleId, $sort);

        return $this->success(null, '排序更新成功');
    }

    /**
     * 设置文章为精选
     */
    public function setArticleFeatured(Request $request, $id)
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return $this->error('专题不存在');
        }

        $articleId = $request->param('article_id');
        $isFeatured = $request->param('is_featured', 1);

        TopicArticle::setFeatured($id, $articleId, $isFeatured);

        return $this->success(null, '设置成功');
    }
}
