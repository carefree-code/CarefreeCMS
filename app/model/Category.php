<?php

namespace app\model;

use think\Model;

/**
 * 分类模型
 */
class Category extends Model
{
    protected $name = 'categories';

    protected $autoWriteTimestamp = true;

    protected $type = [
        'parent_id' => 'integer',
        'sort'      => 'integer',
        'status'    => 'integer',
    ];

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 关联子分类
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * 关联文章
     */
    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    /**
     * 搜索器：分类名称
     */
    public function searchNameAttr($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：父分类
     */
    public function searchParentIdAttr($query, $value)
    {
        $query->where('parent_id', $value);
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }
}
