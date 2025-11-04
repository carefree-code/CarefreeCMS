<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\SmsConfig;
use app\model\SmsTemplate;
use app\model\SmsLog;
use app\model\VerifyCode;
use think\Request;

/**
 * 后台短信管理控制器
 */
class SmsManage extends BaseController
{
    /**
     * 短信配置列表
     */
    public function configIndex(Request $request)
    {
        $list = SmsConfig::order('is_default', 'desc')
            ->order('id', 'asc')
            ->select();

        return Response::success($list);
    }

    /**
     * 短信配置详情
     */
    public function configRead($id)
    {
        $config = SmsConfig::find($id);

        if (!$config) {
            return Response::notFound('配置不存在');
        }

        return Response::success($config->toArray());
    }

    /**
     * 创建短信配置
     */
    public function configCreate(Request $request)
    {
        $data = $request->post();

        if (empty($data['provider']) || empty($data['access_key']) || empty($data['access_secret'])) {
            return Response::error('请填写完整信息');
        }

        try {
            $config = SmsConfig::create([
                'provider'      => $data['provider'],
                'access_key'    => $data['access_key'],
                'access_secret' => $data['access_secret'],
                'sign_name'     => $data['sign_name'] ?? '',
                'status'        => $data['status'] ?? 1,
                'is_default'    => $data['is_default'] ?? 0,
            ]);

            // 如果设置为默认，取消其他默认配置
            if ($config->is_default) {
                $config->setAsDefault();
            }

            return Response::success($config->toArray(), '创建成功');

        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新短信配置
     */
    public function configUpdate(Request $request, $id)
    {
        $config = SmsConfig::find($id);

        if (!$config) {
            return Response::notFound('配置不存在');
        }

        $data = $request->post();

        try {
            $allowFields = ['access_key', 'access_secret', 'sign_name', 'status', 'is_default'];
            $updateData = [];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $config->save($updateData);

            // 如果设置为默认，取消其他默认配置
            if (isset($data['is_default']) && $data['is_default']) {
                $config->setAsDefault();
            }

            return Response::success($config->toArray(), '更新成功');

        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除短信配置
     */
    public function configDelete($id)
    {
        $config = SmsConfig::find($id);

        if (!$config) {
            return Response::notFound('配置不存在');
        }

        if ($config->is_default) {
            return Response::error('不能删除默认配置');
        }

        try {
            $config->delete();
            return Response::success([], '删除成功');

        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 短信模板列表
     */
    public function templateIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $list = SmsTemplate::order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return Response::success($list);
    }

    /**
     * 短信模板详情
     */
    public function templateRead($id)
    {
        $template = SmsTemplate::find($id);

        if (!$template) {
            return Response::notFound('模板不存在');
        }

        return Response::success($template->toArray());
    }

    /**
     * 创建短信模板
     */
    public function templateCreate(Request $request)
    {
        $data = $request->post();

        if (empty($data['code']) || empty($data['name']) || empty($data['provider'])) {
            return Response::error('请填写完整信息');
        }

        // 检查代码是否重复
        if (SmsTemplate::where('code', $data['code'])->count() > 0) {
            return Response::error('模板代码已存在');
        }

        try {
            $template = SmsTemplate::create([
                'code'        => $data['code'],
                'name'        => $data['name'],
                'provider'    => $data['provider'],
                'template_id' => $data['template_id'] ?? '',
                'content'     => $data['content'] ?? '',
                'type'        => $data['type'] ?? 'verify',
                'status'      => $data['status'] ?? 1,
            ]);

            return Response::success($template->toArray(), '创建成功');

        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新短信模板
     */
    public function templateUpdate(Request $request, $id)
    {
        $template = SmsTemplate::find($id);

        if (!$template) {
            return Response::notFound('模板不存在');
        }

        $data = $request->post();

        try {
            $allowFields = ['name', 'template_id', 'content', 'type', 'status'];
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
     * 删除短信模板
     */
    public function templateDelete($id)
    {
        $template = SmsTemplate::find($id);

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
     * 短信发送日志
     */
    public function logIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $phone = $request->get('phone', '');
        $status = $request->get('status', '');

        $query = SmsLog::order('send_time', 'desc');

        if ($phone) {
            $query->where('phone', 'like', '%' . $phone . '%');
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
     * 短信发送统计
     */
    public function statistics(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $stats = SmsLog::getSuccessRate($startDate, $endDate);

        // 今日发送
        $todayCount = SmsLog::whereTime('send_time', 'today')->count();

        // 本周发送
        $weekCount = SmsLog::whereTime('send_time', 'week')->count();

        // 本月发送
        $monthCount = SmsLog::whereTime('send_time', 'month')->count();

        return Response::success([
            'success_rate' => $stats,
            'today_count'  => $todayCount,
            'week_count'   => $weekCount,
            'month_count'  => $monthCount,
        ]);
    }

    /**
     * 验证码列表
     */
    public function verifyCodeIndex(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $account = $request->get('account', '');
        $type = $request->get('type', '');

        $query = VerifyCode::order('create_time', 'desc');

        if ($account) {
            $query->where('account', 'like', '%' . $account . '%');
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
     * 清理过期验证码
     */
    public function cleanExpiredCodes(Request $request)
    {
        try {
            $count = VerifyCode::cleanExpired();

            return Response::success([
                'count' => $count,
            ], "已清理 {$count} 条过期验证码");

        } catch (\Exception $e) {
            return Response::error('清理失败：' . $e->getMessage());
        }
    }
}
