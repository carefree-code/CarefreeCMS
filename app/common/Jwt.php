<?php

namespace app\common;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

/**
 * JWT工具类
 */
class Jwt
{
    /**
     * 生成JWT Token
     * @param array $payload 载荷数据
     * @return string
     * @throws \Exception
     */
    public static function generate(array $payload): string
    {
        $key = self::getSecretKey();
        $expire = (int)(env('jwt.expire') ?: env('JWT_EXPIRE') ?: 7200);

        $token = [
            'iss' => 'cms_system',  // 签发者
            'aud' => 'cms_user',    // 接收者
            'iat' => time(),        // 签发时间
            'nbf' => time(),        // 生效时间
            'exp' => time() + $expire,  // 过期时间
            'data' => $payload      // 自定义数据
        ];

        return FirebaseJWT::encode($token, $key, 'HS256');
    }

    /**
     * 验证JWT Token
     * @param string $token
     * @return array|false 返回解析后的数据或false
     */
    public static function verify(string $token)
    {
        $key = self::getSecretKey();

        try {
            $decoded = FirebaseJWT::decode($token, new Key($key, 'HS256'));
            return (array) $decoded->data;
        } catch (ExpiredException $e) {
            // Token已过期
            return false;
        } catch (SignatureInvalidException $e) {
            // 签名验证失败
            return false;
        } catch (BeforeValidException $e) {
            // Token尚未生效
            return false;
        } catch (\Exception $e) {
            // 其他异常
            return false;
        }
    }

    /**
     * 刷新Token
     * @param string $token 旧token
     * @return string|false 返回新token或false
     */
    public static function refresh(string $token)
    {
        $data = self::verify($token);
        if ($data === false) {
            return false;
        }

        return self::generate($data);
    }

    /**
     * 检查Token是否即将过期（剩余时间少于30分钟）
     * @param string $token
     * @return bool
     */
    public static function shouldRefresh(string $token): bool
    {
        $key = self::getSecretKey();

        try {
            $decoded = FirebaseJWT::decode($token, new Key($key, 'HS256'));
            $exp = $decoded->exp;
            $now = time();

            // 如果剩余时间少于30分钟（1800秒），返回true
            return ($exp - $now) < 1800;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取JWT密钥（强制从环境变量读取，确保安全）
     * @return string
     * @throws \Exception
     */
    private static function getSecretKey(): string
    {
        // ThinkPHP 8 环境变量读取：尝试多种格式
        $key = env('jwt.secret') ?: env('JWT_SECRET') ?: env('jwt.JWT_SECRET');

        // 调试信息（开发环境）
        if (empty($key) && env('APP_DEBUG')) {
            error_log('JWT_SECRET 读取失败，env值：' . var_export([
                'jwt.secret' => env('jwt.secret'),
                'JWT_SECRET' => env('JWT_SECRET'),
                'jwt.JWT_SECRET' => env('jwt.JWT_SECRET'),
            ], true));
        }

        // 检查密钥是否已配置
        if (empty($key)) {
            throw new \Exception('JWT_SECRET 未配置，请检查 .env 文件并清除配置缓存：php think clear');
        }

        // 检查密钥强度（至少16个字符）
        if (strlen($key) < 16) {
            throw new \Exception('JWT_SECRET 密钥强度不足，建议至少32位随机字符串。生成方法：openssl rand -base64 32');
        }

        // 警告：不允许使用示例密钥
        $weakKeys = [
            'your_jwt_secret_key_here',
            'cms_jwt_secret_key_2024',
            'simple_key',
            'test_key'
        ];

        if (in_array($key, $weakKeys)) {
            throw new \Exception('检测到弱 JWT 密钥，请修改为强随机密钥。生成方法：openssl rand -base64 32');
        }

        return $key;
    }
}
