<?php

namespace app\service;

use app\model\SmsConfig;
use app\model\SmsTemplate;
use app\model\SmsLog;
use think\facade\Log;

/**
 * 短信服务
 */
class SmsService
{
    /**
     * 发送短信
     *
     * @param string $phone 手机号
     * @param string $content 短信内容
     * @param array $options 其他选项
     * @return bool
     */
    public static function send(string $phone, string $content, array $options = []): bool
    {
        try {
            // 获取短信配置
            $config = isset($options['provider'])
                ? SmsConfig::getByProvider($options['provider'])
                : SmsConfig::getDefault();

            if (!$config) {
                Log::error('短信配置不存在');
                return false;
            }

            // 根据不同服务商发送短信
            $result = self::sendByProvider($config, $phone, $content, $options);

            // 记录日志
            SmsLog::record($phone, $content, [
                'code'          => $options['code'] ?? null,
                'template_code' => $options['template_code'] ?? null,
                'provider'      => $config->provider,
                'response'      => $result['message'] ?? '',
                'status'        => $result['success'] ? 1 : 0,
            ]);

            return $result['success'];

        } catch (\Exception $e) {
            Log::error('短信发送失败：' . $e->getMessage());

            // 记录失败日志
            SmsLog::record($phone, $content, [
                'code'          => $options['code'] ?? null,
                'template_code' => $options['template_code'] ?? null,
                'provider'      => $options['provider'] ?? null,
                'response'      => $e->getMessage(),
                'status'        => 0,
            ]);

            return false;
        }
    }

    /**
     * 使用模板发送短信
     *
     * @param string $phone 手机号
     * @param string $templateCode 模板代码
     * @param array $params 模板参数
     * @param array $options 其他选项
     * @return bool
     */
    public static function sendByTemplate(string $phone, string $templateCode, array $params = [], array $options = []): bool
    {
        $template = SmsTemplate::getByCode($templateCode);
        if (!$template) {
            Log::error('短信模板不存在', ['code' => $templateCode]);
            return false;
        }

        $content = $template->renderContent($params);

        $options['template_code'] = $templateCode;
        $options['template_id'] = $template->template_id;
        $options['provider'] = $template->provider;

        return self::send($phone, $content, $options);
    }

    /**
     * 根据服务商发送短信
     */
    protected static function sendByProvider(SmsConfig $config, string $phone, string $content, array $options = []): array
    {
        switch ($config->provider) {
            case 'aliyun':
                return self::sendByAliyun($config, $phone, $content, $options);

            case 'tencent':
                return self::sendByTencent($config, $phone, $content, $options);

            case 'yunpian':
                return self::sendByYunpian($config, $phone, $content, $options);

            default:
                // 开发环境使用模拟发送
                if (app()->isDebug()) {
                    return self::sendByMock($phone, $content, $options);
                }

                return [
                    'success' => false,
                    'message' => '不支持的短信服务商',
                ];
        }
    }

    /**
     * 阿里云短信发送
     */
    protected static function sendByAliyun(SmsConfig $config, string $phone, string $content, array $options = []): array
    {
        // TODO: 实现阿里云短信发送
        // 需要引入阿里云SDK: composer require alibabacloud/dysmsapi-20170525

        Log::info('阿里云短信发送', [
            'phone'   => $phone,
            'content' => $content,
        ]);

        return [
            'success' => true,
            'message' => '阿里云短信发送成功（未实现）',
        ];
    }

    /**
     * 腾讯云短信发送
     */
    protected static function sendByTencent(SmsConfig $config, string $phone, string $content, array $options = []): array
    {
        // TODO: 实现腾讯云短信发送
        // 需要引入腾讯云SDK: composer require tencentcloud/tencentcloud-sdk-php

        Log::info('腾讯云短信发送', [
            'phone'   => $phone,
            'content' => $content,
        ]);

        return [
            'success' => true,
            'message' => '腾讯云短信发送成功（未实现）',
        ];
    }

    /**
     * 云片短信发送
     */
    protected static function sendByYunpian(SmsConfig $config, string $phone, string $content, array $options = []): array
    {
        // TODO: 实现云片短信发送

        Log::info('云片短信发送', [
            'phone'   => $phone,
            'content' => $content,
        ]);

        return [
            'success' => true,
            'message' => '云片短信发送成功（未实现）',
        ];
    }

    /**
     * 模拟发送（开发环境）
     */
    protected static function sendByMock(string $phone, string $content, array $options = []): array
    {
        Log::info('【模拟短信】', [
            'phone'   => $phone,
            'content' => $content,
            'code'    => $options['code'] ?? null,
        ]);

        echo "\n==== 模拟短信发送 ====\n";
        echo "手机号: {$phone}\n";
        echo "内容: {$content}\n";
        if (isset($options['code'])) {
            echo "验证码: {$options['code']}\n";
        }
        echo "====================\n\n";

        return [
            'success' => true,
            'message' => '模拟发送成功',
        ];
    }

    /**
     * 检查发送频率限制
     */
    public static function checkRateLimit(string $phone, int $seconds = 60): bool
    {
        return SmsLog::checkRateLimit($phone, $seconds);
    }

    /**
     * 检查每日发送限制
     */
    public static function checkDailyLimit(string $phone, int $maxCount = 10): bool
    {
        $todayCount = SmsLog::getTodayCount($phone);
        return $todayCount < $maxCount;
    }
}
