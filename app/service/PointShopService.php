<?php

namespace app\service;

use app\model\PointShopGoods;
use app\model\PointShopOrder;
use app\model\FrontUser;

/**
 * 积分商城服务
 */
class PointShopService
{
    /**
     * 用户兑换商品
     *
     * @param int $userId 用户ID
     * @param int $goodsId 商品ID
     * @param int $quantity 数量
     * @param array $contactInfo 联系信息
     * @return array
     */
    public static function exchange(int $userId, int $goodsId, int $quantity, array $contactInfo = []): array
    {
        try {
            $user = FrontUser::find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户不存在',
                ];
            }

            $goods = PointShopGoods::find($goodsId);
            if (!$goods) {
                return [
                    'success' => false,
                    'message' => '商品不存在',
                ];
            }

            if ($quantity < 1) {
                return [
                    'success' => false,
                    'message' => '数量不能小于1',
                ];
            }

            // 创建订单
            $order = PointShopOrder::createOrder($user, $goods, $quantity, $contactInfo);

            // 如果是虚拟商品，直接发货并完成
            if ($goods->type == 'virtual') {
                $order->deliver('虚拟商品自动发货');
                $order->complete();
            }

            return [
                'success' => true,
                'message' => '兑换成功',
                'data'    => $order->toArray(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取用户可兑换商品列表
     *
     * @param int $userId
     * @param int $categoryId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getAvailableGoods(int $userId, int $categoryId = 0, int $page = 1, int $limit = 20): array
    {
        $user = FrontUser::find($userId);
        if (!$user) {
            return [
                'total' => 0,
                'list'  => [],
            ];
        }

        $query = PointShopGoods::where('status', 1);

        // 分类筛选
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        // 等级筛选
        $query->where(function ($q) use ($user) {
            $q->where('level_required', '<=', $user->level)
              ->whereOr('level_required', 0);
        });

        // VIP筛选
        if (!$user->is_vip) {
            $query->where('vip_required', 0);
        }

        // 时间筛选
        $now = date('Y-m-d H:i:s');
        $query->where(function ($q) use ($now) {
            $q->where('start_time', '<=', $now)
              ->whereOr('start_time', 'null');
        });
        $query->where(function ($q) use ($now) {
            $q->where('end_time', '>=', $now)
              ->whereOr('end_time', 'null');
        });

        // 库存筛选
        $query->where(function ($q) {
            $q->where('stock', '>', 0)
              ->whereOr('stock', -1);
        });

        // 排序
        $query->order('sort_order', 'asc')
              ->order('id', 'desc');

        // 分页
        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return $list->toArray();
    }

    /**
     * 获取用户兑换记录统计
     */
    public static function getUserStatistics(int $userId): array
    {
        $total = PointShopOrder::where('user_id', $userId)->count();
        $pending = PointShopOrder::where('user_id', $userId)->where('status', 0)->count();
        $delivered = PointShopOrder::where('user_id', $userId)->where('status', 1)->count();
        $completed = PointShopOrder::where('user_id', $userId)->where('status', 2)->count();
        $cancelled = PointShopOrder::where('user_id', $userId)->where('status', 3)->count();

        $totalPoints = PointShopOrder::where('user_id', $userId)
            ->whereIn('status', [0, 1, 2])
            ->sum('total_points');

        return [
            'total'        => $total,
            'pending'      => $pending,
            'delivered'    => $delivered,
            'completed'    => $completed,
            'cancelled'    => $cancelled,
            'total_points' => $totalPoints,
        ];
    }
}
