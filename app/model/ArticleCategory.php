<?php

namespace app\model;

use think\model\Pivot;

/**
 * 文章分类关联模型（支持主分类+副分类）
 */
class ArticleCategory extends Pivot
{
    protected $name = 'article_categories';

    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'article_id'  => 'integer',
        'category_id' => 'integer',
        'is_main'     => 'integer',
    ];

    /**
     * 关联文章
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
