<?php

namespace app\model;

use think\Model;

/**
 * 文章属性模型
 */
class ArticleFlag extends Model
{
    protected $name = 'article_flags';

    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'flag_value'  => 'string',
        'is_show'     => 'int',
        'sort_order'  => 'int',
        'status'      => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取所有启用的属性
     */
    public static function getAllEnabled()
    {
        return self::where('status', 1)
            ->order('sort_order', 'asc')
            ->select();
    }

    /**
     * 获取显示的属性
     */
    public static function getDisplayFlags()
    {
        return self::where('status', 1)
            ->where('is_show', 1)
            ->order('sort_order', 'asc')
            ->select();
    }
}
