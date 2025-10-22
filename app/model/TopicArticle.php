<?php
declare (strict_types = 1);

namespace app\model;

use think\model\Pivot;

/**
 * 专题-文章关联模型（中间表）
 */
class TopicArticle extends Pivot
{
    protected $name = 'topic_articles';

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 关联专题
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    /**
     * 关联文章
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * 获取文章在专题中的排序
     */
    public static function getArticleSort($topicId, $articleId)
    {
        $relation = self::where('topic_id', $topicId)
            ->where('article_id', $articleId)
            ->find();

        return $relation ? $relation->sort : 0;
    }

    /**
     * 更新文章在专题中的排序
     */
    public static function updateArticleSort($topicId, $articleId, $sort)
    {
        return self::where('topic_id', $topicId)
            ->where('article_id', $articleId)
            ->update(['sort' => $sort]);
    }

    /**
     * 设置文章为精选
     */
    public static function setFeatured($topicId, $articleId, $isFeatured = 1)
    {
        return self::where('topic_id', $topicId)
            ->where('article_id', $articleId)
            ->update(['is_featured' => $isFeatured]);
    }
}
