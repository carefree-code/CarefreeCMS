<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 专题模型
 */
class Topic extends Model
{
    use SoftDelete;

    protected $name = 'topics';
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    /**
     * 关联文章（多对多）
     */
    public function articles()
    {
        return $this->belongsToMany(
            Article::class,
            TopicArticle::class,
            'article_id',
            'topic_id'
        )->withField(['id', 'title', 'summary', 'cover_image', 'view_count', 'create_time'])
         ->order('topic_articles.sort', 'asc');
    }

    /**
     * 获取专题的文章列表（带分页）
     */
    public function getArticlesWithPagination($page = 1, $pageSize = 10)
    {
        return $this->articles()
            ->where('articles.status', Article::STATUS_PUBLISHED)
            ->where('articles.deleted_at', null)
            ->page($page, $pageSize)
            ->select();
    }

    /**
     * 添加文章到专题
     */
    public function addArticle($articleId, $sort = 0, $isFeatured = 0)
    {
        $topicArticle = new TopicArticle();
        $topicArticle->topic_id = $this->id;
        $topicArticle->article_id = $articleId;
        $topicArticle->sort = $sort;
        $topicArticle->is_featured = $isFeatured;
        $topicArticle->save();

        // 更新专题文章数量
        $this->updateArticleCount();
    }

    /**
     * 从专题移除文章
     */
    public function removeArticle($articleId)
    {
        TopicArticle::where('topic_id', $this->id)
            ->where('article_id', $articleId)
            ->delete();

        // 更新专题文章数量
        $this->updateArticleCount();
    }

    /**
     * 批量设置专题文章
     */
    public function setArticles($articleIds)
    {
        // 删除现有关联
        TopicArticle::where('topic_id', $this->id)->delete();

        // 添加新关联
        foreach ($articleIds as $index => $articleId) {
            $this->addArticle($articleId, $index);
        }
    }

    /**
     * 更新文章数量
     */
    public function updateArticleCount()
    {
        $count = TopicArticle::where('topic_id', $this->id)->count();
        $this->article_count = $count;
        $this->save();
    }

    /**
     * 增加浏览次数
     */
    public function incrementViewCount()
    {
        $this->view_count++;
        $this->save();
    }

    /**
     * 获取所有状态
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 0;
        $list = self::getStatusList();
        return $list[$status] ?? '未知';
    }

    /**
     * URL别名修改器 - 自动转小写，替换空格为连字符
     */
    public function setSlugAttr($value)
    {
        return strtolower(str_replace(' ', '-', trim($value)));
    }
}
