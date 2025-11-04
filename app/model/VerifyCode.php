<?php

namespace app\model;

use think\Model;

/**
 * 验证码模型
 */
class VerifyCode extends Model
{
    protected $name = 'verify_codes';

    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'type'        => 'string',
        'account'     => 'string',
        'code'        => 'string',
        'scene'       => 'string',
        'status'      => 'int',
        'expire_time' => 'datetime',
        'use_time'    => 'datetime',
        'ip'          => 'string',
        'create_time' => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'status' => 'integer',
    ];

    // 追加属性
    protected $append = [
        'type_text',
        'scene_text',
        'status_text',
        'is_expired',
    ];

    /**
     * 类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $types = [
            'phone' => '手机',
            'email' => '邮箱',
        ];

        return $types[$data['type']] ?? '未知';
    }

    /**
     * 场景文本
     */
    public function getSceneTextAttr($value, $data)
    {
        $scenes = [
            'register' => '注册',
            'login'    => '登录',
            'reset'    => '重置密码',
            'bind'     => '绑定',
        ];

        return $scenes[$data['scene']] ?? '未知';
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $statuses = [
            0 => '未使用',
            1 => '已使用',
            2 => '已过期',
        ];

        return $statuses[$data['status']] ?? '未知';
    }

    /**
     * 是否已过期
     */
    public function getIsExpiredAttr($value, $data)
    {
        if ($data['status'] == 2) {
            return true;
        }

        return strtotime($data['expire_time']) < time();
    }

    /**
     * 生成验证码
     *
     * @param string $type 类型 phone/email
     * @param string $account 账号
     * @param string $scene 场景
     * @param int $length 验证码长度
     * @param int $expireMinutes 过期时间(分钟)
     * @return VerifyCode|null
     */
    public static function generate(string $type, string $account, string $scene, int $length = 6, int $expireMinutes = 5): ?VerifyCode
    {
        // 生成随机验证码
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= rand(0, 9);
        }

        // 设置过期时间
        $expireTime = date('Y-m-d H:i:s', time() + $expireMinutes * 60);

        // 将同账号同场景的旧验证码标记为过期
        self::where('type', $type)
            ->where('account', $account)
            ->where('scene', $scene)
            ->where('status', 0)
            ->update(['status' => 2]);

        // 创建新验证码
        return self::create([
            'type'        => $type,
            'account'     => $account,
            'code'        => $code,
            'scene'       => $scene,
            'status'      => 0,
            'expire_time' => $expireTime,
            'ip'          => request()->ip(),
        ]);
    }

    /**
     * 验证验证码
     *
     * @param string $type 类型
     * @param string $account 账号
     * @param string $code 验证码
     * @param string $scene 场景
     * @param bool $autoUse 是否自动标记为已使用
     * @return bool
     */
    public static function verify(string $type, string $account, string $code, string $scene, bool $autoUse = true): bool
    {
        $verifyCode = self::where('type', $type)
            ->where('account', $account)
            ->where('code', $code)
            ->where('scene', $scene)
            ->where('status', 0)
            ->order('create_time', 'desc')
            ->find();

        if (!$verifyCode) {
            return false;
        }

        // 检查是否过期
        if ($verifyCode->is_expired) {
            $verifyCode->status = 2;
            $verifyCode->save();
            return false;
        }

        // 标记为已使用
        if ($autoUse) {
            $verifyCode->status = 1;
            $verifyCode->use_time = date('Y-m-d H:i:s');
            $verifyCode->save();
        }

        return true;
    }

    /**
     * 检查验证码发送频率
     *
     * @param string $type 类型
     * @param string $account 账号
     * @param int $seconds 时间范围(秒)
     * @return bool true表示可以发送，false表示需要等待
     */
    public static function checkSendLimit(string $type, string $account, int $seconds = 60): bool
    {
        $lastCode = self::where('type', $type)
            ->where('account', $account)
            ->order('create_time', 'desc')
            ->find();

        if (!$lastCode) {
            return true;
        }

        $elapsed = time() - strtotime($lastCode->create_time);
        return $elapsed >= $seconds;
    }

    /**
     * 获取上次发送时间距今的秒数
     */
    public static function getLastSendElapsed(string $type, string $account): int
    {
        $lastCode = self::where('type', $type)
            ->where('account', $account)
            ->order('create_time', 'desc')
            ->find();

        if (!$lastCode) {
            return PHP_INT_MAX;
        }

        return time() - strtotime($lastCode->create_time);
    }

    /**
     * 清理过期验证码
     */
    public static function cleanExpired(): int
    {
        $now = date('Y-m-d H:i:s');

        return self::where('expire_time', '<', $now)
            ->where('status', 0)
            ->update(['status' => 2]);
    }

    /**
     * 删除旧验证码
     */
    public static function deleteOld(int $days = 7): int
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return self::where('create_time', '<', $date)->delete();
    }
}
