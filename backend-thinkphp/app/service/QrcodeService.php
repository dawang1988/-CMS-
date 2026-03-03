<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;
use think\facade\Db;
use app\service\LogService as StructuredLog;

class QrcodeService
{
    private static function getAccessToken(string $tenantId): string
    {
        $appId = CacheService::getConfig($tenantId, 'wx_appid', '');
        $appSecret = CacheService::getConfig($tenantId, 'wx_app_secret', '');
        if (empty($appId) || empty($appSecret)) {
            StructuredLog::error('未配置微信小程序 appid 或 secret', [
                'tenant_id' => $tenantId,
            ]);
            throw new \Exception('未配置微信小程序 appid 或 secret');
        }

        $cacheKey = 'wx_access_token:' . $appId;
        $token = Cache::get($cacheKey);
        if ($token) return $token;

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $resp = @file_get_contents($url);
        $data = json_decode($resp ?: '{}', true);
        if (!empty($data['access_token'])) {
            Cache::set($cacheKey, $data['access_token'], ($data['expires_in'] ?? 7200) - 200);
            StructuredLog::info('获取微信access_token成功', [
                'tenant_id' => $tenantId,
                'app_id' => $appId,
            ]);
            return $data['access_token'];
        }
        StructuredLog::error('获取微信access_token失败', [
            'tenant_id' => $tenantId,
            'app_id' => $appId,
            'error' => $data['errmsg'] ?? '未知错误',
        ]);
        throw new \Exception('获取微信access_token失败: ' . ($data['errmsg'] ?? '未知错误'));
    }

    private static function generateWxacode(string $tenantId, string $scene, string $page = '', int $width = 430): string
    {
        $accessToken = self::getAccessToken($tenantId);
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";

        $postData = ['scene' => $scene, 'width' => $width];
        if ($page) {
            $postData['page'] = $page;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            StructuredLog::error('请求微信接口失败', [
                'tenant_id' => $tenantId,
                'scene' => $scene,
                'page' => $page,
                'http_code' => $httpCode,
            ]);
            throw new \Exception('请求微信接口失败');
        }

        $json = @json_decode($response, true);
        if ($json && isset($json['errcode']) && $json['errcode'] !== 0) {
            StructuredLog::error('生成小程序码失败', [
                'tenant_id' => $tenantId,
                'scene' => $scene,
                'page' => $page,
                'errcode' => $json['errcode'],
                'errmsg' => $json['errmsg'] ?? '未知错误',
            ]);
            throw new \Exception('生成小程序码失败: ' . ($json['errmsg'] ?? '未知错误') . ' (code:' . $json['errcode'] . ')');
        }

        return $response;
    }

    private static function saveImage(string $imageData, string $subDir, string $filename): string
    {
        $dir = app()->getRootPath() . 'public/storage/qrcode/' . $subDir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filePath = $dir . '/' . $filename . '.png';
        file_put_contents($filePath, $imageData);

        return '/storage/qrcode/' . $subDir . '/' . $filename . '.png';
    }

    /**
     * 生成门店小程序码
     */
    public static function generateStoreQrcode(string $tenantId, int $storeId): string
    {
        $scene = 'sid=' . $storeId;
        // 门店首页
        $page = 'pages/index/index';
        $imageData = self::generateWxacode($tenantId, $scene, $page);
        $relativePath = self::saveImage($imageData, 'store', 'store_' . $storeId);

        // 更新门店记录
        Db::name('store')->where('id', $storeId)->update([
            'qr_code' => $relativePath,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $relativePath;
    }

    /**
     * 生成房间小程序码（扫码直接进入该房间下单页）
     */
    public static function generateRoomQrcode(string $tenantId, int $storeId, int $roomId): string
    {
        $scene = 'sid=' . $storeId . '&rid=' . $roomId;
        $page = 'pages/index/index';
        $imageData = self::generateWxacode($tenantId, $scene, $page);
        $relativePath = self::saveImage($imageData, 'room', 'room_' . $roomId);

        return $relativePath;
    }

    /**
     * 批量重新生成门店 + 所有房间的小程序码
     */
    public static function resetAllQrcode(string $tenantId, int $storeId): array
    {
        $results = ['store' => '', 'rooms' => []];

        // 1. 生成门店码
        $results['store'] = self::generateStoreQrcode($tenantId, $storeId);

        // 2. 生成所有房间码
        $rooms = Db::name('room')->where('store_id', $storeId)
            ->where('tenant_id', $tenantId)->column('id');
        foreach ($rooms as $roomId) {
            $results['rooms'][$roomId] = self::generateRoomQrcode($tenantId, $storeId, $roomId);
        }

        return $results;
    }
}
