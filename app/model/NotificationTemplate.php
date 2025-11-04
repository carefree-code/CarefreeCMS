<?php

namespace app\model;

use think\Model;

/**
 * 消息模板模型
 */
class NotificationTemplate extends Model
{
    protected $name = 'notification_templates';

    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'code'        => 'string',
        'name'        => 'string',
        'type'        => 'string',
        'title'       => 'string',
        'content'     => 'string',
        'channels'    => 'string',
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
        'channels_array',
    ];

    /**
     * 渠道数组
     */
    public function getChannelsArrayAttr($value, $data)
    {
        if (empty($data['channels'])) {
            return [];
        }

        return explode(',', $data['channels']);
    }

    /**
     * 根据模板代码获取模板
     */
    public static function getByCode(string $code): ?NotificationTemplate
    {
        return self::where('code', $code)
            ->where('status', 1)
            ->find();
    }

    /**
     * 渲染模板内容
     *
     * @param string $template 模板内容
     * @param array $data 替换数据
     * @return string
     */
    public static function render(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    /**
     * 渲染标题
     */
    public function renderTitle(array $data): string
    {
        return self::render($this->title, $data);
    }

    /**
     * 渲染内容
     */
    public function renderContent(array $data): string
    {
        return self::render($this->content, $data);
    }

    /**
     * 检查渠道是否启用
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels_array);
    }
}
