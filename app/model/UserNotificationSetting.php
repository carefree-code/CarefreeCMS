<?php

namespace app\model;

use think\Model;

/**
 * 用户消息设置模型
 */
class UserNotificationSetting extends Model
{
    protected $name = 'user_notification_settings';

    // 设置字段信息
    protected $schema = [
        'id'            => 'int',
        'user_id'       => 'int',
        'type'          => 'string',
        'site_enabled'  => 'int',
        'email_enabled' => 'int',
        'sms_enabled'   => 'int',
        'create_time'   => 'datetime',
        'update_time'   => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'user_id'       => 'integer',
        'site_enabled'  => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled'   => 'boolean',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(FrontUser::class, 'user_id');
    }

    /**
     * 获取用户的通知设置
     */
    public static function getUserSettings(int $userId): array
    {
        $settings = self::where('user_id', $userId)->select();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->type] = [
                'site_enabled'  => $setting->site_enabled,
                'email_enabled' => $setting->email_enabled,
                'sms_enabled'   => $setting->sms_enabled,
            ];
        }

        return $result;
    }

    /**
     * 获取或创建用户通知设置
     */
    public static function getOrCreate(int $userId, string $type): UserNotificationSetting
    {
        $setting = self::where('user_id', $userId)
            ->where('type', $type)
            ->find();

        if (!$setting) {
            $setting = self::create([
                'user_id'       => $userId,
                'type'          => $type,
                'site_enabled'  => 1,
                'email_enabled' => 1,
                'sms_enabled'   => 0,
            ]);
        }

        return $setting;
    }

    /**
     * 检查渠道是否启用
     */
    public static function isChannelEnabled(int $userId, string $type, string $channel): bool
    {
        $setting = self::where('user_id', $userId)
            ->where('type', $type)
            ->find();

        if (!$setting) {
            // 默认站内消息和邮件开启，短信关闭
            return in_array($channel, ['site', 'email']);
        }

        $field = $channel . '_enabled';
        return (bool) $setting->$field;
    }

    /**
     * 批量更新用户设置
     */
    public static function updateUserSettings(int $userId, array $settings): bool
    {
        foreach ($settings as $type => $channels) {
            $setting = self::getOrCreate($userId, $type);

            $updateData = [];
            if (isset($channels['site_enabled'])) {
                $updateData['site_enabled'] = $channels['site_enabled'];
            }
            if (isset($channels['email_enabled'])) {
                $updateData['email_enabled'] = $channels['email_enabled'];
            }
            if (isset($channels['sms_enabled'])) {
                $updateData['sms_enabled'] = $channels['sms_enabled'];
            }

            if (!empty($updateData)) {
                $setting->save($updateData);
            }
        }

        return true;
    }
}
