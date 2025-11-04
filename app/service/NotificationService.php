<?php

namespace app\service;

use app\model\Notification;
use app\model\NotificationTemplate;
use app\model\UserNotificationSetting;
use app\model\FrontUser;
use think\facade\Log;

/**
 * 消息通知服务
 */
class NotificationService
{
    /**
     * 发送通知
     *
     * @param int $userId 接收用户ID
     * @param string $type 通知类型
     * @param string $title 标题
     * @param string $content 内容
     * @param array $options 其他选项
     * @return bool
     */
    public static function send(int $userId, string $type, string $title, string $content, array $options = []): bool
    {
        try {
            // 检查用户是否存在
            $user = FrontUser::find($userId);
            if (!$user) {
                Log::error("通知发送失败：用户不存在", ['user_id' => $userId]);
                return false;
            }

            // 获取用户的通知设置
            $siteEnabled = UserNotificationSetting::isChannelEnabled($userId, $type, 'site');
            $emailEnabled = UserNotificationSetting::isChannelEnabled($userId, $type, 'email');
            $smsEnabled = UserNotificationSetting::isChannelEnabled($userId, $type, 'sms');

            // 站内消息
            if ($siteEnabled) {
                self::sendSiteNotification($userId, $type, $title, $content, $options);
            }

            // 邮件通知
            if ($emailEnabled && !empty($user->email)) {
                self::sendEmailNotification($user->email, $title, $content, $options);
            }

            // 短信通知
            if ($smsEnabled && !empty($user->phone)) {
                self::sendSmsNotification($user->phone, $content, $options);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("通知发送失败：" . $e->getMessage(), [
                'user_id' => $userId,
                'type'    => $type,
                'error'   => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * 使用模板发送通知
     *
     * @param int $userId 接收用户ID
     * @param string $templateCode 模板代码
     * @param array $data 模板数据
     * @param array $options 其他选项
     * @return bool
     */
    public static function sendByTemplate(int $userId, string $templateCode, array $data = [], array $options = []): bool
    {
        $template = NotificationTemplate::getByCode($templateCode);
        if (!$template) {
            Log::error("通知模板不存在", ['code' => $templateCode]);
            return false;
        }

        $title = $template->renderTitle($data);
        $content = $template->renderContent($data);

        return self::send($userId, $template->type, $title, $content, $options);
    }

    /**
     * 批量发送通知
     *
     * @param array $userIds 用户ID数组
     * @param string $type 通知类型
     * @param string $title 标题
     * @param string $content 内容
     * @param array $options 其他选项
     * @return int 成功数量
     */
    public static function sendBatch(array $userIds, string $type, string $title, string $content, array $options = []): int
    {
        $successCount = 0;

        foreach ($userIds as $userId) {
            if (self::send($userId, $type, $title, $content, $options)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * 发送站内消息
     */
    protected static function sendSiteNotification(int $userId, string $type, string $title, string $content, array $options = []): bool
    {
        Notification::create([
            'user_id'      => $userId,
            'type'         => $type,
            'title'        => $title,
            'content'      => $content,
            'link'         => $options['link'] ?? null,
            'from_user_id' => $options['from_user_id'] ?? null,
            'related_type' => $options['related_type'] ?? null,
            'related_id'   => $options['related_id'] ?? null,
            'is_read'      => 0,
        ]);

        return true;
    }

    /**
     * 发送邮件通知
     */
    protected static function sendEmailNotification(string $email, string $title, string $content, array $options = []): bool
    {
        try {
            // TODO: 实现邮件发送功能
            // 这里可以使用 PHPMailer 或其他邮件发送库
            Log::info("邮件通知", [
                'email'   => $email,
                'title'   => $title,
                'content' => $content,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("邮件发送失败：" . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送短信通知
     */
    protected static function sendSmsNotification(string $phone, string $content, array $options = []): bool
    {
        try {
            // TODO: 实现短信发送功能
            // 这里将在后续开发短信服务时实现
            Log::info("短信通知", [
                'phone'   => $phone,
                'content' => $content,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("短信发送失败：" . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送系统通知给所有用户
     */
    public static function sendSystemNotificationToAll(string $title, string $content, array $options = []): int
    {
        $userIds = FrontUser::where('status', 1)->column('id');
        return self::sendBatch($userIds, 'system', $title, $content, $options);
    }

    /**
     * 发送评论回复通知
     */
    public static function sendCommentReplyNotification(int $userId, int $fromUserId, string $articleTitle, string $commentContent): bool
    {
        return self::sendByTemplate($userId, 'comment_reply', [
            'from_user'     => FrontUser::where('id', $fromUserId)->value('nickname'),
            'content'       => $commentContent,
            'article_title' => $articleTitle,
        ], [
            'from_user_id' => $fromUserId,
            'related_type' => 'comment',
        ]);
    }

    /**
     * 发送文章点赞通知
     */
    public static function sendArticleLikeNotification(int $userId, int $fromUserId, string $articleTitle, int $articleId): bool
    {
        return self::sendByTemplate($userId, 'article_like', [
            'from_user'     => FrontUser::where('id', $fromUserId)->value('nickname'),
            'article_title' => $articleTitle,
        ], [
            'from_user_id' => $fromUserId,
            'related_type' => 'article',
            'related_id'   => $articleId,
            'link'         => '/article/' . $articleId,
        ]);
    }

    /**
     * 发送新增粉丝通知
     */
    public static function sendNewFollowerNotification(int $userId, int $fromUserId): bool
    {
        return self::sendByTemplate($userId, 'new_follower', [
            'from_user' => FrontUser::where('id', $fromUserId)->value('nickname'),
        ], [
            'from_user_id' => $fromUserId,
            'related_type' => 'follow',
            'link'         => '/user/' . $fromUserId,
        ]);
    }

    /**
     * 发送文章审核通知
     */
    public static function sendArticleAuditNotification(int $userId, string $articleTitle, bool $passed, ?string $remark = null): bool
    {
        $result = $passed ? '通过' : '未通过';
        $content = $remark ? "，原因：{$remark}" : '';

        return self::sendByTemplate($userId, 'article_audit', [
            'article_title' => $articleTitle,
            'result'        => $result . $content,
        ], [
            'related_type' => 'article_audit',
        ]);
    }
}
