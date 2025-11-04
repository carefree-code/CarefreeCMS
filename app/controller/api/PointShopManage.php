<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\PointShopCategory;
use app\model\PointShopGoods;
use app\model\PointShopOrder;
use think\Request;

/**
 * 后台积分商城管理控制器
 */
class PointShopManage extends BaseController
{
    // ==================== 分类管理 ====================

    /**
     * 分类列表
     */
    public function categoryIndex(Request $request)
    {
        $list = PointShopCategory::order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select();

        return Response::success($list);
    }

    /**
     * 创建分类
     */
    public function categoryCreate(Request $request)
    {
        $data = $request->post();

        if (empty($data['name'])) {
            return Response::error('请输入分类名称');
        }

        try {
            $category = PointShopCategory::create([
                'name'       => $data['name'],
                'icon'       => $data['icon'] ?? '',
                'sort_order' => $data['sort_order'] ?? 0,
                'status'     => $data['status'] ?? 1,
            ]);

            return Response::success($category->toArray(), '创建成功');

        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新分类
     */
    public function categoryUpdate(Request $request, $id)
    {
        $category = PointShopCategory::find($id);

        if (!$category) {
            return Response::notFound('分类不存在');
        }

        $data = $request->post();

        try {
            $allowFields = ['name', 'icon', 'sort_order', 'status'];
            $updateData = [];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $category->save($updateData);

            return Response::success($category->toArray(), '更新成功');

        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除分类
     */
    public function categoryDelete($id)
    {
        $category = PointShopCategory::find($id);

        if (!$category) {
            return Response::notFound('分类不存在');
        }

        // 检查是否有商品
        $goodsCount = PointShopGoods::where('category_id', $id)->count();
        if ($goodsCount > 0) {
            return Response::error('该分类下还有商品，无法删除');
        }

        try {
            $category->delete();
            return Response::success([], '删除成功');

        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    // ==================== 商品管理 ====================

    /**
     * 商品列表
     */
    public function goodsIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $categoryId = $request->get('category_id', '');
        $keyword = $request->get('keyword', '');
        $status = $request->get('status', '');

        $query = PointShopGoods::order('id', 'desc');

        if ($categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 商品详情
     */
    public function goodsRead($id)
    {
        $goods = PointShopGoods::find($id);

        if (!$goods) {
            return Response::notFound('商品不存在');
        }

        return Response::success($goods->toArray());
    }

    /**
     * 创建商品
     */
    public function goodsCreate(Request $request)
    {
        $data = $request->post();

        if (empty($data['name']) || !isset($data['price'])) {
            return Response::error('请填写完整信息');
        }

        try {
            $goods = PointShopGoods::create([
                'category_id'    => $data['category_id'] ?? 0,
                'name'           => $data['name'],
                'description'    => $data['description'] ?? '',
                'images'         => $data['images'] ?? [],
                'price'          => $data['price'],
                'stock'          => $data['stock'] ?? -1,
                'limit_per_user' => $data['limit_per_user'] ?? -1,
                'type'           => $data['type'] ?? 'virtual',
                'virtual_content' => $data['virtual_content'] ?? '',
                'level_required' => $data['level_required'] ?? 0,
                'vip_required'   => $data['vip_required'] ?? 0,
                'status'         => $data['status'] ?? 1,
                'start_time'     => $data['start_time'] ?? null,
                'end_time'       => $data['end_time'] ?? null,
                'sort_order'     => $data['sort_order'] ?? 0,
            ]);

            return Response::success($goods->toArray(), '创建成功');

        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新商品
     */
    public function goodsUpdate(Request $request, $id)
    {
        $goods = PointShopGoods::find($id);

        if (!$goods) {
            return Response::notFound('商品不存在');
        }

        $data = $request->post();

        try {
            $allowFields = [
                'category_id', 'name', 'description', 'images', 'price',
                'stock', 'limit_per_user', 'type', 'virtual_content',
                'level_required', 'vip_required', 'status', 'start_time',
                'end_time', 'sort_order'
            ];

            $updateData = [];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $goods->save($updateData);

            return Response::success($goods->toArray(), '更新成功');

        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除商品
     */
    public function goodsDelete($id)
    {
        $goods = PointShopGoods::find($id);

        if (!$goods) {
            return Response::notFound('商品不存在');
        }

        try {
            $goods->delete();
            return Response::success([], '删除成功');

        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    // ==================== 订单管理 ====================

    /**
     * 订单列表
     */
    public function orderIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $orderNo = $request->get('order_no', '');
        $status = $request->get('status', '');

        $query = PointShopOrder::order('create_time', 'desc');

        if ($orderNo) {
            $query->where('order_no', 'like', '%' . $orderNo . '%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $list = $query->with(['user', 'goods'])->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 订单详情
     */
    public function orderRead($id)
    {
        $order = PointShopOrder::with(['user', 'goods'])->find($id);

        if (!$order) {
            return Response::notFound('订单不存在');
        }

        return Response::success($order->toArray());
    }

    /**
     * 发货
     */
    public function orderDeliver(Request $request, $id)
    {
        $order = PointShopOrder::find($id);

        if (!$order) {
            return Response::notFound('订单不存在');
        }

        $adminRemark = $request->post('admin_remark', '');

        try {
            $order->deliver($adminRemark);
            return Response::success([], '发货成功');

        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * 完成订单
     */
    public function orderComplete($id)
    {
        $order = PointShopOrder::find($id);

        if (!$order) {
            return Response::notFound('订单不存在');
        }

        try {
            $order->complete();
            return Response::success([], '订单已完成');

        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * 取消订单
     */
    public function orderCancel(Request $request, $id)
    {
        $order = PointShopOrder::find($id);

        if (!$order) {
            return Response::notFound('订单不存在');
        }

        $reason = $request->post('reason', '');

        try {
            $order->cancel($reason);
            return Response::success([], '订单已取消');

        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * 统计信息
     */
    public function statistics(Request $request)
    {
        $totalGoods = PointShopGoods::count();
        $onSaleGoods = PointShopGoods::where('status', 1)->count();

        $totalOrders = PointShopOrder::count();
        $pendingOrders = PointShopOrder::where('status', 0)->count();
        $deliveredOrders = PointShopOrder::where('status', 1)->count();
        $completedOrders = PointShopOrder::where('status', 2)->count();

        $todayOrders = PointShopOrder::whereTime('create_time', 'today')->count();
        $weekOrders = PointShopOrder::whereTime('create_time', 'week')->count();
        $monthOrders = PointShopOrder::whereTime('create_time', 'month')->count();

        $totalPoints = PointShopOrder::whereIn('status', [0, 1, 2])->sum('total_points');

        return Response::success([
            'goods'   => [
                'total'   => $totalGoods,
                'on_sale' => $onSaleGoods,
            ],
            'orders'  => [
                'total'     => $totalOrders,
                'pending'   => $pendingOrders,
                'delivered' => $deliveredOrders,
                'completed' => $completedOrders,
                'today'     => $todayOrders,
                'week'      => $weekOrders,
                'month'     => $monthOrders,
            ],
            'points'  => [
                'total_consumed' => $totalPoints,
            ],
        ]);
    }
}
