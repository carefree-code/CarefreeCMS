<?php

namespace app\model;

use think\Model;

/**
 * 积分商城分类模型
 */
class PointShopCategory extends Model
{
    protected $name = 'point_shop_categories';

    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'icon'        => 'string',
        'sort_order'  => 'int',
        'status'      => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'status'     => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 关联商品
     */
    public function goods()
    {
        return $this->hasMany(PointShopGoods::class, 'category_id');
    }

    /**
     * 获取启用的分类
     */
    public static function getEnabled(): array
    {
        return self::where('status', 1)
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取分类商品数量
     */
    public function getGoodsCountAttr($value, $data)
    {
        return PointShopGoods::where('category_id', $data['id'])
            ->where('status', 1)
            ->count();
    }
}
