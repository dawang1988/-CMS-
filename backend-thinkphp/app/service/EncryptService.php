[APP]
ORDER_TIMEOUT = 15  # 超时时间（分钟）<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Log;
use app\service\LogService as StructuredLog;

class EncryptService
{
    private static $key;
    private static $method = 'AES-256-CBC';
    private static $ivLength;

    public static function init(): void
    {
        self::$key = env('app.encrypt_key', 'default_key_change_in_production');
        self::$ivLength = openssl_cipher_iv_length(self::$method);
        
        if (self::$key === 'default_key_change_in_production') {
            StructuredLog::warning('使用默认加密密钥，请修改.env中的app.encrypt_key');
        }
    }

    public static function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        self::init();
        
        $iv = openssl_random_pseudo_bytes(self::$ivLength);
        $encrypted = openssl_encrypt($data, self::$method, self::$key, 0, $iv);
        
        if ($encrypted === false) {
            StructuredLog::error('数据加密失败', [
                'data_length' => strlen($data),
                'error' => openssl_error_string(),
            ]);
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }

        self::init();
        
        $data = base64_decode($encrypted);
        if ($data === false) {
            StructuredLog::error('数据解密失败：base64解码失败');
            return '';
        }

        $iv = substr($data, 0, self::$ivLength);
        $encrypted = substr($data, self::$ivLength);
        
        $decrypted = openssl_decrypt($encrypted, self::$method, self::$key, 0, $iv);
        
        if ($decrypted === false) {
            StructuredLog::error('数据解密失败', [
                'encrypted_length' => strlen($encrypted),
                'error' => openssl_error_string(),
            ]);
            return '';
        }

        return $decrypted;
    }

    public static function encryptPhone(string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $encrypted = self::encrypt($phone);
        
        if (!empty($encrypted)) {
            StructuredLog::info('手机号加密', [
                'phone_prefix' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            ]);
        }
        
        return $encrypted;
    }

    public static function decryptPhone(string $encrypted): string
    {
        return self::decrypt($encrypted);
    }

    public static function encryptCardNo(string $cardNo): string
    {
        if (empty($cardNo)) {
            return '';
        }

        $encrypted = self::encrypt($cardNo);
        
        if (!empty($encrypted)) {
            StructuredLog::info('银行卡号加密', [
                'card_no_prefix' => substr($cardNo, 0, 4) . '****' . substr($cardNo, -4),
            ]);
        }
        
        return $encrypted;
    }

    public static function decryptCardNo(string $encrypted): string
    {
        return self::decrypt($encrypted);
    }

    public static function encryptIdCard(string $idCard): string
    {
        if (empty($idCard)) {
            return '';
        }

        $encrypted = self::encrypt($idCard);
        
        if (!empty($encrypted)) {
            StructuredLog::info('身份证号加密', [
                'id_card_prefix' => substr($idCard, 0, 6) . '********' . substr($idCard, -4),
            ]);
        }
        
        return $encrypted;
    }

    public static function decryptIdCard(string $encrypted): string
    {
        return self::decrypt($encrypted);
    }

    public static function maskPhone(string $phone): string
    {
        if (empty($phone) || strlen($phone) < 7) {
            return $phone;
        }
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    public static function maskIdCard(string $idCard): string
    {
        if (empty($idCard) || strlen($idCard) < 10) {
            return $idCard;
        }
        return substr($idCard, 0, 6) . '********' . substr($idCard, -4);
    }

    public static function maskCardNo(string $cardNo): string
    {
        if (empty($cardNo) || strlen($cardNo) < 8) {
            return $cardNo;
        }
        return substr($cardNo, 0, 4) . '****' . substr($cardNo, -4);
    }
}