<?php

namespace app\service;

use app\model\VerifyCode;

/**
 * 验证码服务
 */
class VerifyCodeService
{
    /**
     * 发送手机验证码
     *
     * @param string $phone 手机号
     * @param string $scene 场景 register/login/reset/bind
     * @param int $length 验证码长度
     * @param int $expireMinutes 过期时间(分钟)
     * @return array
     */
    public static function sendPhoneCode(string $phone, string $scene, int $length = 6, int $expireMinutes = 5): array
    {
        // 验证手机号格式
        if (!self::validatePhone($phone)) {
            return [
                'success' => false,
                'message' => '手机号格式不正确',
            ];
        }

        // 检查发送频率（60秒内只能发送一次）
        if (!VerifyCode::checkSendLimit('phone', $phone, 60)) {
            $elapsed = VerifyCode::getLastSendElapsed('phone', $phone);
            $wait = 60 - $elapsed;
            return [
                'success' => false,
                'message' => "请等待 {$wait} 秒后再试",
            ];
        }

        // 检查短信发送频率
        if (!SmsService::checkRateLimit($phone, 60)) {
            return [
                'success' => false,
                'message' => '发送过于频繁，请稍后再试',
            ];
        }

        // 检查每日发送限制（每天最多10条）
        if (!SmsService::checkDailyLimit($phone, 10)) {
            return [
                'success' => false,
                'message' => '今日发送次数已达上限',
            ];
        }

        try {
            // 生成验证码
            $verifyCode = VerifyCode::generate('phone', $phone, $scene, $length, $expireMinutes);

            if (!$verifyCode) {
                return [
                    'success' => false,
                    'message' => '验证码生成失败',
                ];
            }

            // 发送短信
            $content = "您的验证码是：{$verifyCode->code}，{$expireMinutes}分钟内有效。";

            $result = SmsService::send($phone, $content, [
                'code'  => $verifyCode->code,
                'scene' => $scene,
            ]);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => '短信发送失败',
                ];
            }

            return [
                'success' => true,
                'message' => '验证码发送成功',
                'data'    => [
                    'expire_time' => $verifyCode->expire_time,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '验证码发送失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 发送邮箱验证码
     *
     * @param string $email 邮箱
     * @param string $scene 场景
     * @param int $length 验证码长度
     * @param int $expireMinutes 过期时间(分钟)
     * @return array
     */
    public static function sendEmailCode(string $email, string $scene, int $length = 6, int $expireMinutes = 5): array
    {
        // 验证邮箱格式
        if (!self::validateEmail($email)) {
            return [
                'success' => false,
                'message' => '邮箱格式不正确',
            ];
        }

        // 检查发送频率
        if (!VerifyCode::checkSendLimit('email', $email, 60)) {
            $elapsed = VerifyCode::getLastSendElapsed('email', $email);
            $wait = 60 - $elapsed;
            return [
                'success' => false,
                'message' => "请等待 {$wait} 秒后再试",
            ];
        }

        try {
            // 生成验证码
            $verifyCode = VerifyCode::generate('email', $email, $scene, $length, $expireMinutes);

            if (!$verifyCode) {
                return [
                    'success' => false,
                    'message' => '验证码生成失败',
                ];
            }

            // TODO: 发送邮件
            // 这里应该调用邮件发送服务
            // EmailService::send($email, '验证码', "您的验证码是：{$verifyCode->code}");

            return [
                'success' => true,
                'message' => '验证码发送成功',
                'data'    => [
                    'expire_time' => $verifyCode->expire_time,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '验证码发送失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 验证手机验证码
     *
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $scene 场景
     * @param bool $autoUse 是否自动标记为已使用
     * @return bool
     */
    public static function verifyPhoneCode(string $phone, string $code, string $scene, bool $autoUse = true): bool
    {
        return VerifyCode::verify('phone', $phone, $code, $scene, $autoUse);
    }

    /**
     * 验证邮箱验证码
     *
     * @param string $email 邮箱
     * @param string $code 验证码
     * @param string $scene 场景
     * @param bool $autoUse 是否自动标记为已使用
     * @return bool
     */
    public static function verifyEmailCode(string $email, string $code, string $scene, bool $autoUse = true): bool
    {
        return VerifyCode::verify('email', $email, $code, $scene, $autoUse);
    }

    /**
     * 验证手机号格式
     */
    protected static function validatePhone(string $phone): bool
    {
        return preg_match('/^1[3-9]\d{9}$/', $phone) === 1;
    }

    /**
     * 验证邮箱格式
     */
    protected static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 清理过期验证码
     */
    public static function cleanExpired(): int
    {
        return VerifyCode::cleanExpired();
    }

    /**
     * 删除旧验证码
     */
    public static function deleteOld(int $days = 7): int
    {
        return VerifyCode::deleteOld($days);
    }
}
