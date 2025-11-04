<?php

namespace app\model;

use think\Model;

/**
 * 会员等级升级日志模型
 */
class MemberLevelLog extends Model
{
    protected $name = 'member_level_logs';

    protected $autoWriteTimestamp = 'create_time';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'user_id' => 'integer',
        'old_level' => 'integer',
        'new_level' => 'integer',
        'operator_id' => 'integer',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 关联操作人
     */
    public function operator()
    {
        return $this->belongsTo(\app\model\AdminUser::class, 'operator_id');
    }

    /**
     * 记录等级变更
     *
     * @param int $userId 用户ID
     * @param int $oldLevel 原等级
     * @param int $newLevel 新等级
     * @param string $upgradeType 升级类型：auto自动 manual手动
     * @param string|null $reason 升级原因
     * @param int|null $operatorId 操作人ID
     * @return bool
     */
    public static function record(
        int $userId,
        int $oldLevel,
        int $newLevel,
        string $upgradeType = 'auto',
        ?string $reason = null,
        ?int $operatorId = null
    ): bool {
        $log = self::create([
            'user_id' => $userId,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'upgrade_type' => $upgradeType,
            'reason' => $reason,
            'operator_id' => $operatorId,
        ]);

        return $log ? true : false;
    }

    /**
     * 获取用户升级历史
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function getUserHistory(int $userId, int $limit = 10): array
    {
        return self::where('user_id', $userId)
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
