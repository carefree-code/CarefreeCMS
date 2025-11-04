<?php

namespace app\service;

use app\model\FrontUser;
use app\model\MemberLevel;
use app\model\MemberLevelLog;
use think\facade\Db;
use think\facade\Log;

/**
 * 会员等级服务
 */
class MemberLevelService
{
    /**
     * 检查并升级单个用户等级
     *
     * @param int|FrontUser $user 用户ID或用户对象
     * @return array ['upgraded' => bool, 'old_level' => int, 'new_level' => int, 'message' => string]
     */
    public static function checkAndUpgradeUser($user): array
    {
        try {
            // 获取用户对象
            if (is_numeric($user)) {
                $user = FrontUser::find($user);
            }

            if (!$user) {
                return [
                    'upgraded' => false,
                    'old_level' => 0,
                    'new_level' => 0,
                    'message' => '用户不存在',
                ];
            }

            $oldLevel = $user->level;

            // 计算用户注册天数
            $regTime = strtotime($user->create_time);
            $days = floor((time() - $regTime) / 86400);

            // 计算应该达到的等级
            $targetLevel = MemberLevel::calculateUserLevel(
                $user->points,
                $user->article_count,
                $user->comment_count,
                $days
            );

            // 如果目标等级高于当前等级，则升级
            if ($targetLevel > $oldLevel) {
                Db::startTrans();
                try {
                    // 更新用户等级
                    $user->level = $targetLevel;
                    $user->save();

                    // 记录升级日志
                    MemberLevelLog::record(
                        $user->id,
                        $oldLevel,
                        $targetLevel,
                        'auto',
                        '满足升级条件自动升级'
                    );

                    // 发送升级通知
                    $levelConfig = MemberLevel::getByLevel($targetLevel);
                    if ($levelConfig) {
                        NotificationService::send(
                            $user->id,
                            'system',
                            '恭喜升级',
                            "恭喜您！您的等级已从 Lv.{$oldLevel} 升级到 Lv.{$targetLevel} ({$levelConfig->name})！",
                            ['link' => '/user/profile']
                        );
                    }

                    Db::commit();

                    return [
                        'upgraded' => true,
                        'old_level' => $oldLevel,
                        'new_level' => $targetLevel,
                        'message' => "用户 {$user->nickname} 从 Lv.{$oldLevel} 升级到 Lv.{$targetLevel}",
                    ];

                } catch (\Exception $e) {
                    Db::rollback();
                    throw $e;
                }
            }

            return [
                'upgraded' => false,
                'old_level' => $oldLevel,
                'new_level' => $oldLevel,
                'message' => '用户等级已是最高可达等级',
            ];

        } catch (\Exception $e) {
            Log::error('用户等级升级失败：' . $e->getMessage());
            return [
                'upgraded' => false,
                'old_level' => 0,
                'new_level' => 0,
                'message' => '升级失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 批量检查并升级用户等级
     *
     * @param int $limit 每次处理的用户数量
     * @return array ['total' => int, 'upgraded' => int, 'failed' => int, 'details' => array]
     */
    public static function batchCheckAndUpgrade(int $limit = 100): array
    {
        $result = [
            'total' => 0,
            'upgraded' => 0,
            'failed' => 0,
            'details' => [],
        ];

        try {
            // 获取所有活跃用户
            $users = FrontUser::where('status', 1)
                ->order('id', 'asc')
                ->limit($limit)
                ->select();

            $result['total'] = count($users);

            foreach ($users as $user) {
                $upgradeResult = self::checkAndUpgradeUser($user);

                if ($upgradeResult['upgraded']) {
                    $result['upgraded']++;
                    $result['details'][] = $upgradeResult['message'];
                } elseif (strpos($upgradeResult['message'], '失败') !== false) {
                    $result['failed']++;
                }
            }

        } catch (\Exception $e) {
            Log::error('批量升级用户等级失败：' . $e->getMessage());
            $result['failed'] = $result['total'];
        }

        return $result;
    }

    /**
     * 手动设置用户等级
     *
     * @param int $userId 用户ID
     * @param int $newLevel 新等级
     * @param int $operatorId 操作人ID
     * @param string|null $reason 原因
     * @return array
     */
    public static function manualSetLevel(int $userId, int $newLevel, int $operatorId, ?string $reason = null): array
    {
        try {
            $user = FrontUser::find($userId);
            if (!$user) {
                return ['success' => false, 'message' => '用户不存在'];
            }

            // 检查等级配置是否存在
            $levelConfig = MemberLevel::getByLevel($newLevel);
            if (!$levelConfig) {
                return ['success' => false, 'message' => '等级配置不存在'];
            }

            $oldLevel = $user->level;

            if ($oldLevel == $newLevel) {
                return ['success' => false, 'message' => '用户已是该等级'];
            }

            Db::startTrans();
            try {
                // 更新用户等级
                $user->level = $newLevel;
                $user->save();

                // 记录升级日志
                MemberLevelLog::record(
                    $userId,
                    $oldLevel,
                    $newLevel,
                    'manual',
                    $reason ?: '管理员手动调整等级',
                    $operatorId
                );

                // 发送通知
                $action = $newLevel > $oldLevel ? '升级' : '调整';
                NotificationService::send(
                    $userId,
                    'system',
                    "等级{$action}通知",
                    "您的等级已被{$action}为 Lv.{$newLevel} ({$levelConfig->name})",
                    ['link' => '/user/profile']
                );

                Db::commit();

                return [
                    'success' => true,
                    'message' => '等级设置成功',
                    'old_level' => $oldLevel,
                    'new_level' => $newLevel,
                ];

            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('手动设置用户等级失败：' . $e->getMessage());
            return ['success' => false, 'message' => '设置失败：' . $e->getMessage()];
        }
    }
}
