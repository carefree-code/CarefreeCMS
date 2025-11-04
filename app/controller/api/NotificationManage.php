<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\NotificationTemplate;
use app\service\NotificationService;
use think\Request;

/**
 * 后台消息通知管理控制器
 */
class NotificationManage extends BaseController
{
    /**
     * 模板列表
     */
    public function templateIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $keyword = $request->get('keyword', '');
        $type = $request->get('type', '');

        $query = NotificationTemplate::order('id', 'desc');

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->whereOr('code', 'like', '%' . $keyword . '%');
            });
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 模板详情
     */
    public function templateRead($id)
    {
        $template = NotificationTemplate::find($id);

        if (!$template) {
            return Response::notFound('模板不存在');
        }

        return Response::success($template->toArray());
    }

    /**
     * 创建模板
     */
    public function templateCreate(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['code']) || empty($data['name']) || empty($data['type'])) {
            return Response::error('请填写完整信息');
        }

        // 检查代码是否重复
        if (NotificationTemplate::where('code', $data['code'])->count() > 0) {
            return Response::error('模板代码已存在');
        }

        try {
            $template = NotificationTemplate::create([
                'code'     => $data['code'],
                'name'     => $data['name'],
                'type'     => $data['type'],
                'title'    => $data['title'] ?? '',
                'content'  => $data['content'] ?? '',
                'channels' => $data['channels'] ?? 'site',
                'status'   => $data['status'] ?? 1,
            ]);

            return Response::success($template->toArray(), '创建成功');

        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新模板
     */
    public function templateUpdate(Request $request, $id)
    {
        $template = NotificationTemplate::find($id);

        if (!$template) {
            return Response::notFound('模板不存在');
        }

        $data = $request->post();

        // 如果修改了代码，检查是否重复
        if (isset($data['code']) && $data['code'] !== $template->code) {
            if (NotificationTemplate::where('code', $data['code'])->count() > 0) {
                return Response::error('模板代码已存在');
            }
        }

        try {
            $allowFields = ['name', 'type', 'title', 'content', 'channels', 'status'];
            $updateData = [];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $template->save($updateData);

            return Response::success($template->toArray(), '更新成功');

        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除模板
     */
    public function templateDelete($id)
    {
        $template = NotificationTemplate::find($id);

        if (!$template) {
            return Response::notFound('模板不存在');
        }

        try {
            $template->delete();
            return Response::success([], '删除成功');

        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 发送系统通知给所有用户
     */
    public function sendSystemNotification(Request $request)
    {
        $title = $request->post('title', '');
        $content = $request->post('content', '');

        if (empty($title) || empty($content)) {
            return Response::error('请填写标题和内容');
        }

        try {
            $count = NotificationService::sendSystemNotificationToAll($title, $content);

            return Response::success([
                'count' => $count,
            ], "系统通知已发送给 {$count} 个用户");

        } catch (\Exception $e) {
            return Response::error('发送失败：' . $e->getMessage());
        }
    }

    /**
     * 发送通知给指定用户
     */
    public function sendToUser(Request $request)
    {
        $userIds = $request->post('user_ids', []);
        $type = $request->post('type', 'system');
        $title = $request->post('title', '');
        $content = $request->post('content', '');

        if (empty($userIds)) {
            return Response::error('请选择用户');
        }

        if (empty($title) || empty($content)) {
            return Response::error('请填写标题和内容');
        }

        try {
            $count = NotificationService::sendBatch($userIds, $type, $title, $content);

            return Response::success([
                'count' => $count,
            ], "通知已发送给 {$count} 个用户");

        } catch (\Exception $e) {
            return Response::error('发送失败：' . $e->getMessage());
        }
    }

    /**
     * 通知记录列表
     */
    public function notificationIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $keyword = $request->get('keyword', '');
        $type = $request->get('type', '');
        $is_read = $request->get('is_read', '');

        $query = \app\model\Notification::with(['user'])
            ->order('id', 'desc');

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                  ->whereOr('content', 'like', '%' . $keyword . '%');
            });
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($is_read !== '') {
            $query->where('is_read', $is_read);
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return Response::success($list);
    }

    /**
     * 清理旧通知
     */
    public function cleanOldNotifications(Request $request)
    {
        $days = $request->post('days', 30);

        if ($days < 7) {
            return Response::error('保留天数不能少于7天');
        }

        try {
            $count = \app\model\Notification::deleteOldNotifications($days);

            return Response::success([
                'count' => $count,
            ], "已清理 {$count} 条旧通知");

        } catch (\Exception $e) {
            return Response::error('清理失败：' . $e->getMessage());
        }
    }
}
