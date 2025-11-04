<?php

namespace app\model;

use think\Model;

/**
 * 短信发送日志模型
 */
class SmsLog extends Model
{
    protected $name = 'sms_logs';

    // 设置字段信息
    protected $schema = [
        'id'            => 'int',
        'phone'         => 'string',
        'code'          => 'string',
        'content'       => 'string',
        'template_code' => 'string',
        'provider'      => 'string',
        'response'      => 'string',
        'status'        => 'int',
        'ip'            => 'string',
        'send_time'     => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'send_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'status' => 'boolean',
    ];

    // 追加属性
    protected $append = [
        'status_text',
        'provider_text',
    ];

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] ? '成功' : '失败';
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
     * 记录发送日志
     */
    public static function record(string $phone, string $content, array $options = []): bool
    {
        $log = self::create([
            'phone'         => $phone,
            'code'          => $options['code'] ?? null,
            'content'       => $content,
            'template_code' => $options['template_code'] ?? null,
            'provider'      => $options['provider'] ?? null,
            'response'      => $options['response'] ?? null,
            'status'        => $options['status'] ?? 0,
            'ip'            => $options['ip'] ?? request()->ip(),
        ]);

        return $log ? true : false;
    }

    /**
     * 检查发送频率限制
     *
     * @param string $phone 手机号
     * @param int $seconds 时间范围(秒)
     * @param int $maxCount 最大次数
     * @return bool true表示未超限，false表示超限
     */
    public static function checkRateLimit(string $phone, int $seconds = 60, int $maxCount = 1): bool
    {
        $startTime = date('Y-m-d H:i:s', time() - $seconds);

        $count = self::where('phone', $phone)
            ->where('send_time', '>=', $startTime)
            ->count();

        return $count < $maxCount;
    }

    /**
     * 获取今日发送次数
     */
    public static function getTodayCount(string $phone): int
    {
        return self::where('phone', $phone)
            ->whereTime('send_time', 'today')
            ->count();
    }

    /**
     * 获取成功率统计
     */
    public static function getSuccessRate(string $startDate = null, string $endDate = null): array
    {
        $totalQuery = self::where('id', '>', 0);
        $successQuery = self::where('id', '>', 0);

        if ($startDate) {
            $totalQuery->where('send_time', '>=', $startDate);
            $successQuery->where('send_time', '>=', $startDate);
        }

        if ($endDate) {
            $totalQuery->where('send_time', '<=', $endDate);
            $successQuery->where('send_time', '<=', $endDate);
        }

        $total = $totalQuery->count();
        $success = $successQuery->where('status', 1)->count();

        $rate = $total > 0 ? round($success / $total * 100, 2) : 0;

        return [
            'total'        => $total,
            'success'      => $success,
            'failed'       => $total - $success,
            'success_rate' => $rate,
        ];
    }
}
