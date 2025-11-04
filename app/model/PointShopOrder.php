<?php

namespace app\model;

use think\Model;

/**
 * 积分商城订单模型
 */
class PointShopOrder extends Model
{
    protected $name = 'point_shop_orders';

    // 设置字段信息
    protected $schema = [
        'id'              => 'int',
        'order_no'        => 'string',
        'user_id'         => 'int',
        'goods_id'        => 'int',
        'goods_name'      => 'string',
        'goods_image'     => 'string',
        'points'          => 'int',
        'quantity'        => 'int',
        'total_points'    => 'int',
        'status'          => 'int',
        'contact_name'    => 'string',
        'contact_phone'   => 'string',
        'contact_address' => 'string',
        'remark'          => 'string',
        'admin_remark'    => 'string',
        'virtual_content' => 'string',
        'deliver_time'    => 'datetime',
        'complete_time'   => 'datetime',
        'cancel_time'     => 'datetime',
        'create_time'     => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'user_id'      => 'integer',
        'goods_id'     => 'integer',
        'points'       => 'integer',
        'quantity'     => 'integer',
        'total_points' => 'integer',
        'status'       => 'integer',
    ];

    // 追加属性
    protected $append = [
        'status_text',
        'status_color',
    ];

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $statuses = [
            0 => '待发货',
            1 => '已发货',
            2 => '已完成',
            3 => '已取消',
        ];

        return $statuses[$data['status']] ?? '未知';
    }

    /**
     * 状态颜色
     */
    public function getStatusColorAttr($value, $data)
    {
        $colors = [
            0 => 'warning',
            1 => 'info',
            2 => 'success',
            3 => 'default',
        ];

        return $colors[$data['status']] ?? 'default';
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 关联商品
     */
    public function goods()
    {
        return $this->belongsTo(PointShopGoods::class, 'goods_id');
    }

    /**
     * 生成订单号
     */
    public static function generateOrderNo(): string
    {
        return 'PS' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * 创建订单
     *
     * @param FrontUser $user
     * @param PointShopGoods $goods
     * @param int $quantity
     * @param array $contactInfo
     * @return PointShopOrder|null
     */
    public static function createOrder(FrontUser $user, PointShopGoods $goods, int $quantity, array $contactInfo = []): ?PointShopOrder
    {
        // 检查用户是否可以兑换
        $check = $goods->canUserExchange($user);
        if (!$check['can_exchange']) {
            throw new \Exception($check['message']);
        }

        // 计算总积分
        $totalPoints = $goods->price * $quantity;

        // 再次检查积分是否足够
        if ($user->points < $totalPoints) {
            throw new \Exception('积分不足');
        }

        // 开始事务
        \think\facade\Db::startTrans();

        try {
            // 创建订单
            $order = self::create([
                'order_no'        => self::generateOrderNo(),
                'user_id'         => $user->id,
                'goods_id'        => $goods->id,
                'goods_name'      => $goods->name,
                'goods_image'     => $goods->main_image,
                'points'          => $goods->price,
                'quantity'        => $quantity,
                'total_points'    => $totalPoints,
                'status'          => 0,
                'contact_name'    => $contactInfo['name'] ?? null,
                'contact_phone'   => $contactInfo['phone'] ?? null,
                'contact_address' => $contactInfo['address'] ?? null,
                'remark'          => $contactInfo['remark'] ?? null,
                'virtual_content' => $goods->type == 'virtual' ? $goods->virtual_content : null,
            ]);

            // 扣除积分
            $user->deductPoints($totalPoints, 'point_shop', "兑换商品：{$goods->name}", 'order', $order->id);

            // 减少库存
            $goods->decreaseStock($quantity);

            // 增加销量
            $goods->increaseSales($quantity);

            // 提交事务
            \think\facade\Db::commit();

            return $order;

        } catch (\Exception $e) {
            \think\facade\Db::rollback();
            throw $e;
        }
    }

    /**
     * 发货
     */
    public function deliver(string $adminRemark = ''): bool
    {
        if ($this->status != 0) {
            throw new \Exception('订单状态不正确');
        }

        $this->status = 1;
        $this->deliver_time = date('Y-m-d H:i:s');
        if ($adminRemark) {
            $this->admin_remark = $adminRemark;
        }

        // 发送通知
        if ($this->save()) {
            \app\service\NotificationService::send(
                $this->user_id,
                'order',
                '订单已发货',
                "您的积分兑换订单（{$this->order_no}）已发货",
                [
                    'related_type' => 'point_shop_order',
                    'related_id'   => $this->id,
                    'link'         => '/point-shop/order/' . $this->id,
                ]
            );

            return true;
        }

        return false;
    }

    /**
     * 完成订单
     */
    public function complete(): bool
    {
        if ($this->status != 1) {
            throw new \Exception('订单状态不正确');
        }

        $this->status = 2;
        $this->complete_time = date('Y-m-d H:i:s');

        return $this->save();
    }

    /**
     * 取消订单
     */
    public function cancel(string $reason = ''): bool
    {
        if ($this->status != 0) {
            throw new \Exception('只能取消待发货订单');
        }

        \think\facade\Db::startTrans();

        try {
            // 退还积分
            $user = FrontUser::find($this->user_id);
            $user->addPoints($this->total_points, 'point_shop_refund', "取消订单退还积分：{$this->order_no}", 'order', $this->id);

            // 恢复库存
            $goods = PointShopGoods::find($this->goods_id);
            if ($goods && $goods->stock != -1) {
                $goods->stock += $this->quantity;
                $goods->save();
            }

            // 减少销量
            if ($goods) {
                $goods->sales -= $this->quantity;
                $goods->save();
            }

            // 更新订单状态
            $this->status = 3;
            $this->cancel_time = date('Y-m-d H:i:s');
            if ($reason) {
                $this->admin_remark = $reason;
            }
            $this->save();

            \think\facade\Db::commit();

            return true;

        } catch (\Exception $e) {
            \think\facade\Db::rollback();
            throw $e;
        }
    }
}
