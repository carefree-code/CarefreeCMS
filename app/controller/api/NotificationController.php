<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\middleware\AuthMiddleware;
use app\model\Notification;
use app\model\UserNotificationSetting;
use app\service\NotificationService;
use think\Request;

/**
 * 消息通知控制器
 */
class NotificationController extends BaseController
{
    protected $middleware = [
        AuthMiddleware::class => ['except' => []],
    ];

    /**
     * 获取通知列表
     */
    public function index(Request $request)
    {
        $userId = $request->userId;
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $type = $request->get('type', '');
        $isRead = $request->get('is_read', '');

        $query = Notification::where('user_id', $userId);

        // 筛选条件
        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($isRead !== '') {
            $query->where('is_read', $isRead);
        }

        // 排序
        $query->order('create_time', 'desc');

        // 分页
        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 获取未读数量
     */
    public function unreadCount(Request $request)
    {
        $userId = $request->userId;
        $type = $request->get('type', null);

        $count = Notification::getUnreadCount($userId, $type);

        // 按类型分组统计
        $countByType = Notification::where('user_id', $userId)
            ->where('is_read', 0)
            ->field('type, count(*) as count')
            ->group('type')
            ->select()
            ->toArray();

        $typeCount = [];
        foreach ($countByType as $item) {
            $typeCount[$item['type']] = $item['count'];
        }

        return Response::success([
            'total'     => $count,
            'by_type'   => $typeCount,
        ]);
    }

    /**
     * 获取通知详情
     */
    public function read(Request $request, $id)
    {
        $userId = $request->userId;

        $notification = Notification::where('user_id', $userId)
            ->find($id);

        if (!$notification) {
            return Response::notFound('通知不存在');
        }

        // 自动标记为已读
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return Response::success($notification->toArray());
    }

    /**
     * 标记为已读
     */
    public function markAsRead(Request $request)
    {
        $userId = $request->userId;
        $ids = $request->post('ids', []);

        if (empty($ids)) {
            return Response::error('请选择要标记的通知');
        }

        $count = Notification::markMultipleAsRead($ids, $userId);

        return Response::success([
            'count' => $count,
        ], '已标记为已读');
    }

    /**
     * 全部标记为已读
     */
    public function markAllAsRead(Request $request)
    {
        $userId = $request->userId;

        $count = Notification::markAllAsRead($userId);

        return Response::success([
            'count' => $count,
        ], '已全部标记为已读');
    }

    /**
     * 删除通知
     */
    public function delete(Request $request, $id)
    {
        $userId = $request->userId;

        $notification = Notification::where('user_id', $userId)
            ->find($id);

        if (!$notification) {
            return Response::notFound('通知不存在');
        }

        $notification->delete();

        return Response::success([], '删除成功');
    }

    /**
     * 批量删除通知
     */
    public function batchDelete(Request $request)
    {
        $userId = $request->userId;
        $ids = $request->post('ids', []);

        if (empty($ids)) {
            return Response::error('请选择要删除的通知');
        }

        $count = Notification::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->delete();

        return Response::success([
            'count' => $count,
        ], '删除成功');
    }

    /**
     * 清空已读通知
     */
    public function clearRead(Request $request)
    {
        $userId = $request->userId;

        $count = Notification::where('user_id', $userId)
            ->where('is_read', 1)
            ->delete();

        return Response::success([
            'count' => $count,
        ], '已清空已读通知');
    }

    /**
     * 获取通知设置
     */
    public function getSettings(Request $request)
    {
        $userId = $request->userId;

        $settings = UserNotificationSetting::getUserSettings($userId);

        // 如果用户没有设置，返回默认值
        if (empty($settings)) {
            $defaultTypes = ['system', 'comment', 'like', 'follow', 'reply', 'audit', 'order'];
            foreach ($defaultTypes as $type) {
                $settings[$type] = [
                    'site_enabled'  => true,
                    'email_enabled' => true,
                    'sms_enabled'   => false,
                ];
            }
        }

        return Response::success($settings);
    }

    /**
     * 更新通知设置
     */
    public function updateSettings(Request $request)
    {
        $userId = $request->userId;
        $settings = $request->post('settings', []);

        if (empty($settings)) {
            return Response::error('请提供通知设置');
        }

        UserNotificationSetting::updateUserSettings($userId, $settings);

        return Response::success([], '设置已保存');
    }

    /**
     * 测试发送通知（仅用于开发测试）
     */
    public function testSend(Request $request)
    {
        // 仅在开发环境启用
        if (app()->isDebug() === false) {
            return Response::error('该功能仅在开发环境可用');
        }

        $userId = $request->userId;
        $type = $request->post('type', 'system');
        $title = $request->post('title', '测试通知');
        $content = $request->post('content', '这是一条测试通知');

        $result = NotificationService::send($userId, $type, $title, $content);

        if ($result) {
            return Response::success([], '测试通知发送成功');
        } else {
            return Response::error('测试通知发送失败');
        }
    }
}
