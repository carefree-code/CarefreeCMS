<?php

namespace app\listener;

use app\event\UserAction;
use app\service\MemberLevelService;
use think\facade\Log;

/**
 * 会员等级升级检查监听器
 */
class CheckMemberLevelUpgrade
{
    /**
     * 事件监听处理
     *
     * @param UserAction $event
     * @return void
     */
    public function handle(UserAction $event)
    {
        try {
            // 只在特定行为时检查等级升级
            $checkActions = ['article_created', 'comment_created', 'points_changed'];

            if (!in_array($event->actionType, $checkActions)) {
                return;
            }

            // 检查并升级用户等级
            $result = MemberLevelService::checkAndUpgradeUser($event->userId);

            // 如果升级成功，记录日志
            if ($result['upgraded']) {
                Log::info('用户等级自动升级', [
                    'user_id' => $event->userId,
                    'action_type' => $event->actionType,
                    'old_level' => $result['old_level'],
                    'new_level' => $result['new_level'],
                ]);
            }

        } catch (\Exception $e) {
            // 升级失败不影响主流程，只记录日志
            Log::error('会员等级检查失败', [
                'user_id' => $event->userId,
                'action_type' => $event->actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
