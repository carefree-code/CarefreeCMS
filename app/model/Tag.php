<?php

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 标签模型
 */
class Tag extends Model
{
    use SoftDelete;

    protected $name = 'tags';

    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    protected $type = [
        'article_count' => 'integer',
        'sort'          => 'integer',
        'status'        => 'integer',
    ];

    /**
     * 关联文章（多对多）
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class, ArticleTag::class, 'article_id', 'tag_id');
    }

    /**
     * 搜索器：标签名称
     */
    public function searchNameAttr($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }
}
