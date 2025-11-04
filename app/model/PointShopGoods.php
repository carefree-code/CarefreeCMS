<?php

namespace app\model;

use think\Model;

/**
 * 积分商城商品模型
 */
class PointShopGoods extends Model
{
    protected $name = 'point_shop_goods';

    // 设置字段信息
    protected $schema = [
        'id'             => 'int',
        'category_id'    => 'int',
        'name'           => 'string',
        'description'    => 'string',
        'images'         => 'string',
        'price'          => 'int',
        'stock'          => 'int',
        'sales'          => 'int',
        'limit_per_user' => 'int',
        'type'           => 'string',
        'virtual_content' => 'string',
        'level_required' => 'int',
        'vip_required'   => 'int',
        'status'         => 'int',
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
        'sort_order'     => 'int',
        'create_time'    => 'datetime',
        'update_time'    => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'category_id'    => 'integer',
        'price'          => 'integer',
        'stock'          => 'integer',
        'sales'          => 'integer',
        'limit_per_user' => 'integer',
        'level_required' => 'integer',
        'vip_required'   => 'boolean',
        'status'         => 'boolean',
        'sort_order'     => 'integer',
    ];

    // JSON字段
    protected $json = ['images'];

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'is_on_sale',
        'main_image',
    ];

    /**
     * 类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $types = [
            'virtual'  => '虚拟商品',
            'physical' => '实物商品',
        ];

        return $types[$data['type']] ?? '未知';
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] ? '上架' : '下架';
    }

    /**
     * 是否在售
     */
    public function getIsOnSaleAttr($value, $data)
    {
        if (!$data['status']) {
            return false;
        }

        $now = time();

        if (isset($data['start_time']) && $data['start_time'] && strtotime($data['start_time']) > $now) {
            return false;
        }

        if (isset($data['end_time']) && $data['end_time'] && strtotime($data['end_time']) < $now) {
            return false;
        }

        // 检查库存
        if ($data['stock'] != -1 && $data['stock'] <= 0) {
            return false;
        }

        return true;
    }

    /**
     * 主图
     */
    public function getMainImageAttr($value, $data)
    {
        if (empty($data['images'])) {
            return null;
        }

        $images = is_string($data['images']) ? json_decode($data['images'], true) : $data['images'];

        return $images[0] ?? null;
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(PointShopCategory::class, 'category_id');
    }

    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(PointShopOrder::class, 'goods_id');
    }

    /**
     * 检查用户是否可以兑换
     *
     * @param FrontUser $user
     * @return array ['can_exchange' => bool, 'message' => string]
     */
    public function canUserExchange(FrontUser $user): array
    {
        // 检查商品状态
        if (!$this->is_on_sale) {
            return ['can_exchange' => false, 'message' => '商品未上架或已下架'];
        }

        // 检查等级要求
        if ($this->level_required > 0 && $user->level < $this->level_required) {
            return ['can_exchange' => false, 'message' => "需要等级 {$this->level_required}"];
        }

        // 检查VIP要求
        if ($this->vip_required && !$user->is_vip) {
            return ['can_exchange' => false, 'message' => '需要VIP会员'];
        }

        // 检查积分
        if ($user->points < $this->price) {
            return ['can_exchange' => false, 'message' => '积分不足'];
        }

        // 检查库存
        if ($this->stock != -1 && $this->stock <= 0) {
            return ['can_exchange' => false, 'message' => '库存不足'];
        }

        // 检查个人限兑
        if ($this->limit_per_user > 0) {
            $userOrderCount = PointShopOrder::where('user_id', $user->id)
                ->where('goods_id', $this->id)
                ->whereIn('status', [0, 1, 2]) // 待发货、已发货、已完成
                ->count();

            if ($userOrderCount >= $this->limit_per_user) {
                return ['can_exchange' => false, 'message' => "每人限兑 {$this->limit_per_user} 次"];
            }
        }

        return ['can_exchange' => true, 'message' => ''];
    }

    /**
     * 减少库存
     */
    public function decreaseStock(int $quantity = 1): bool
    {
        if ($this->stock == -1) {
            return true; // 无限库存
        }

        if ($this->stock < $quantity) {
            return false;
        }

        $this->stock -= $quantity;
        return $this->save();
    }

    /**
     * 增加销量
     */
    public function increaseSales(int $quantity = 1): bool
    {
        $this->sales += $quantity;
        return $this->save();
    }
}
