<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\middleware\AuthMiddleware;
use app\model\PointShopCategory;
use app\model\PointShopGoods;
use app\model\PointShopOrder;
use app\model\FrontUser;
use app\service\PointShopService;
use think\Request;

/**
 * 前台积分商城控制器
 */
class PointShop extends BaseController
{
    protected $middleware = [
        AuthMiddleware::class => ['except' => ['categoryList', 'goodsList', 'goodsDetail']],
    ];

    /**
     * 分类列表
     */
    public function categoryList(Request $request)
    {
        $list = PointShopCategory::getEnabled();

        return Response::success($list);
    }

    /**
     * 商品列表
     */
    public function goodsList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $categoryId = $request->get('category_id', 0);
        $type = $request->get('type', '');

        $query = PointShopGoods::where('status', 1);

        // 分类筛选
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        // 类型筛选
        if ($type) {
            $query->where('type', $type);
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

        return Response::success($list);
    }

    /**
     * 商品详情
     */
    public function goodsDetail(Request $request, $id)
    {
        $goods = PointShopGoods::find($id);

        if (!$goods) {
            return Response::notFound('商品不存在');
        }

        // 如果用户已登录，检查是否可以兑换
        if ($request->has('userId')) {
            $user = FrontUser::find($request->userId);
            if ($user) {
                $check = $goods->canUserExchange($user);
                $goods->can_exchange = $check['can_exchange'];
                $goods->exchange_message = $check['message'];
            }
        }

        return Response::success($goods->toArray());
    }

    /**
     * 兑换商品
     */
    public function exchange(Request $request)
    {
        $userId = $request->userId;
        $goodsId = $request->post('goods_id', 0);
        $quantity = $request->post('quantity', 1);

        // 联系信息（实物商品需要）
        $contactInfo = [
            'name'    => $request->post('contact_name', ''),
            'phone'   => $request->post('contact_phone', ''),
            'address' => $request->post('contact_address', ''),
            'remark'  => $request->post('remark', ''),
        ];

        $result = PointShopService::exchange($userId, $goodsId, $quantity, $contactInfo);

        if ($result['success']) {
            return Response::success($result['data'], $result['message']);
        } else {
            return Response::error($result['message']);
        }
    }

    /**
     * 我的兑换订单
     */
    public function myOrders(Request $request)
    {
        $userId = $request->userId;
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $status = $request->get('status', '');

        $query = PointShopOrder::where('user_id', $userId);

        if ($status !== '') {
            $query->where('status', $status);
        }

        $query->order('create_time', 'desc');

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 订单详情
     */
    public function orderDetail(Request $request, $id)
    {
        $userId = $request->userId;

        $order = PointShopOrder::where('user_id', $userId)
            ->find($id);

        if (!$order) {
            return Response::notFound('订单不存在');
        }

        return Response::success($order->toArray());
    }

    /**
     * 取消订单
     */
    public function cancelOrder(Request $request, $id)
    {
        $userId = $request->userId;

        $order = PointShopOrder::where('user_id', $userId)
            ->find($id);

        if (!$order) {
            return Response::notFound('订单不存在');
        }

        try {
            $order->cancel();
            return Response::success([], '订单已取消');

        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * 我的兑换统计
     */
    public function myStatistics(Request $request)
    {
        $userId = $request->userId;

        $stats = PointShopService::getUserStatistics($userId);

        return Response::success($stats);
    }
}
