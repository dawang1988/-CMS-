<?php
declare(strict_types=1);

namespace app\service;

/**
 * 安全服务
 */
class SecurityService
{
    /**
     * 生成Token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * 密码加密
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 验证密码
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * 签名验证
     */
    public static function verifySign(array $data, string $sign, string $key): bool
    {
        unset($data['sign']);
        ksort($data);
        $string = http_build_query($data) . '&key=' . $key;
        return strtoupper(md5($string)) === strtoupper($sign);
    }

    /**
     * 生成签名
     */
    public static function makeSign(array $data, string $key): string
    {
        unset($data['sign']);
        ksort($data);
        $string = http_build_query($data) . '&key=' . $key;
        return strtoupper(md5($string));
    }

    /**
     * XSS过滤
     */
    public static function xssFilter(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
