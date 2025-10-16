<?php

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 文章模型
 */
class Article extends Model
{
    use SoftDelete;

    protected $name = 'articles';

    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    protected $type = [
        'images'        => 'json',
        'category_id'   => 'integer',
        'user_id'       => 'integer',
        'view_count'    => 'integer',
        'like_count'    => 'integer',
        'comment_count' => 'integer',
        'is_top'        => 'integer',
        'is_recommend'  => 'integer',
        'is_hot'        => 'integer',
        'sort'          => 'integer',
        'status'        => 'integer',
    ];

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * 关联作者
     */
    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id', 'id');
    }

    /**
     * 关联标签（多对多）
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, ArticleTag::class, 'tag_id', 'article_id');
    }

    /**
     * 关联所有分类（主分类+副分类）
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, ArticleCategory::class, 'category_id', 'article_id')
            ->withField('is_main');
    }

    /**
     * 获取主分类
     */
    public function mainCategory()
    {
        return $this->hasOneThrough(
            Category::class,
            ArticleCategory::class,
            'article_id',
            'id',
            'id',
            'category_id'
        )->where('article_categories.is_main', 1);
    }

    /**
     * 获取副分类列表
     */
    public function subCategories()
    {
        return $this->belongsToMany(Category::class, ArticleCategory::class, 'category_id', 'article_id')
            ->where('article_categories.is_main', 0);
    }

    /**
     * 搜索器：标题
     */
    public function searchTitleAttr($query, $value)
    {
        $query->where('title', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：分类
     */
    public function searchCategoryIdAttr($query, $value)
    {
        $query->where('category_id', $value);
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 搜索器：是否置顶
     */
    public function searchIsTopAttr($query, $value)
    {
        $query->where('is_top', $value);
    }

    /**
     * 搜索器：是否推荐
     */
    public function searchIsRecommendAttr($query, $value)
    {
        $query->where('is_recommend', $value);
    }

    /**
     * 获取器：状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '草稿', 1 => '已发布', 2 => '待审核', 3 => '已下线'];
        return $status[$data['status']] ?? '未知';
    }
}
