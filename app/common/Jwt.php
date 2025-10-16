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
     */
    public static function generate(array $payload): string
    {
        $key = env('JWT_SECRET', 'cms_jwt_secret_key_2024');
        $expire = env('JWT_EXPIRE', 7200);

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
        $key = env('JWT_SECRET', 'cms_jwt_secret_key_2024');

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
        $key = env('JWT_SECRET', 'cms_jwt_secret_key_2024');

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
}
