<?php

namespace app\model;

use think\Model;

/**
 * 短信配置模型
 */
class SmsConfig extends Model
{
    protected $name = 'sms_config';

    // 设置字段信息
    protected $schema = [
        'id'            => 'int',
        'provider'      => 'string',
        'access_key'    => 'string',
        'access_secret' => 'string',
        'sign_name'     => 'string',
        'status'        => 'int',
        'is_default'    => 'int',
        'create_time'   => 'datetime',
        'update_time'   => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'status'     => 'boolean',
        'is_default' => 'boolean',
    ];

    // 追加属性
    protected $append = [
        'provider_text',
    ];

    /**
     * 服务商文本
     */
    public function getProviderTextAttr($value, $data)
    {
        $providers = [
            'aliyun'  => '阿里云',
            'tencent' => '腾讯云',
            'yunpian' => '云片',
        ];

        return $providers[$data['provider']] ?? '未知';
    }

    /**
     * 获取默认配置
     */
    public static function getDefault(): ?SmsConfig
    {
        return self::where('status', 1)
            ->where('is_default', 1)
            ->find();
    }

    /**
     * 获取指定服务商配置
     */
    public static function getByProvider(string $provider): ?SmsConfig
    {
        return self::where('provider', $provider)
            ->where('status', 1)
            ->find();
    }

    /**
     * 设置为默认
     */
    public function setAsDefault(): bool
    {
        // 取消其他默认配置
        self::where('id', '<>', $this->id)
            ->where('is_default', 1)
            ->update(['is_default' => 0]);

        $this->is_default = 1;
        return $this->save();
    }
}
