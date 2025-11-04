<?php

namespace app\model;

use think\Model;

/**
 * 短信模板模型
 */
class SmsTemplate extends Model
{
    protected $name = 'sms_templates';

    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'code'        => 'string',
        'name'        => 'string',
        'provider'    => 'string',
        'template_id' => 'string',
        'content'     => 'string',
        'type'        => 'string',
        'status'      => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'status' => 'boolean',
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'provider_text',
    ];

    /**
     * 类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $types = [
            'verify' => '验证码',
            'login'  => '登录通知',
            'reset'  => '密码重置',
        ];

        return $types[$data['type']] ?? '未知';
    }

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
     * 根据模板代码获取模板
     */
    public static function getByCode(string $code): ?SmsTemplate
    {
        return self::where('code', $code)
            ->where('status', 1)
            ->find();
    }

    /**
     * 根据类型获取模板
     */
    public static function getByType(string $type, ?string $provider = null): ?SmsTemplate
    {
        $query = self::where('type', $type)
            ->where('status', 1);

        if ($provider) {
            $query->where('provider', $provider);
        }

        return $query->find();
    }

    /**
     * 渲染模板内容
     */
    public function renderContent(array $data): string
    {
        $content = $this->content;
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }
}
