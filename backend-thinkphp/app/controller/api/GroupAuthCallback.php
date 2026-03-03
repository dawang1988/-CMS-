<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Store as StoreModel;
use think\facade\Db;

/**
 * 团购平台OAuth授权回调控制器
 * 处理美团、抖音等平台的授权回调
 */
class GroupAuthCallback extends BaseController
{
    /**
     * 美团授权回调
     */
    public function meituan()
    {
        $code = $this->request->param('code');
        $state = $this->request->param('state');
        
        if (empty($code)) {
            return $this->renderResult(false, '授权失败：未获取到授权码');
        }
        
        try {
            // 解析state获取门店信息
            $stateData = json_decode(base64_decode($state), true);
            if (empty($stateData['store_id']) || empty($stateData['tenant_id'])) {
                return $this->renderResult(false, '授权失败：参数错误');
            }
            
            $storeId = $stateData['store_id'];
            $tenantId = $stateData['tenant_id'];
            
            // 获取系统配置
            $config = Db::name('config')
                ->where('tenant_id', $tenantId)
                ->column('value', 'key');
            
            $appKey = $config['meituan_app_key'] ?? '';
            $appSecret = $config['meituan_app_secret'] ?? '';
            
            if (empty($appKey) || empty($appSecret)) {
                return $this->renderResult(false, '授权失败：系统未配置美团应用信息');
            }
            
            // 用code换取access_token
            $tokenUrl = 'https://open.meituan.com/oauth/token';
            $tokenData = [
                'app_key' => $appKey,
                'app_secret' => $appSecret,
                'code' => $code,
                'grant_type' => 'authorization_code'
            ];
            
            $response = $this->httpPost($tokenUrl, $tokenData);
            $result = json_decode($response, true);
            
            if (empty($result['access_token'])) {
                return $this->renderResult(false, '授权失败：' . ($result['error_description'] ?? '获取token失败'));
            }
            
            // 更新门店授权信息
            $store = StoreModel::find($storeId);
            if ($store) {
                $store->meituan_auth = 1;
                $store->meituan_access_token = $result['access_token'];
                $store->meituan_refresh_token = $result['refresh_token'] ?? '';
                // 美团token有效期通常为30天
                $store->meituan_expire = date('Y-m-d H:i:s', time() + ($result['expires_in'] ?? 2592000));
                $store->update_time = date('Y-m-d H:i:s');
                $store->save();
            }
            
            return $this->renderResult(true, '美团授权成功！您可以关闭此页面。');
            
        } catch (\Exception $e) {
            return $this->renderResult(false, '授权失败：' . $e->getMessage());
        }
    }
    
    /**
     * 抖音授权回调
     */
    public function douyin()
    {
        $code = $this->request->param('code');
        $state = $this->request->param('state');
        
        if (empty($code)) {
            return $this->renderResult(false, '授权失败：未获取到授权码');
        }
        
        try {
            // 解析state获取门店信息
            $stateData = json_decode(base64_decode($state), true);
            if (empty($stateData['store_id']) || empty($stateData['tenant_id'])) {
                return $this->renderResult(false, '授权失败：参数错误');
            }
            
            $storeId = $stateData['store_id'];
            $tenantId = $stateData['tenant_id'];
            
            // 获取系统配置
            $config = Db::name('config')
                ->where('tenant_id', $tenantId)
                ->column('value', 'key');
            
            $clientKey = $config['douyin_client_key'] ?? '';
            $clientSecret = $config['douyin_client_secret'] ?? '';
            
            if (empty($clientKey) || empty($clientSecret)) {
                return $this->renderResult(false, '授权失败：系统未配置抖音应用信息');
            }
            
            // 用code换取access_token
            $tokenUrl = 'https://open.douyin.com/oauth/access_token/';
            $tokenData = [
                'client_key' => $clientKey,
                'client_secret' => $clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code'
            ];
            
            $response = $this->httpPost($tokenUrl, $tokenData);
            $result = json_decode($response, true);
            
            if (empty($result['data']['access_token'])) {
                return $this->renderResult(false, '授权失败：' . ($result['data']['description'] ?? '获取token失败'));
            }
            
            $tokenInfo = $result['data'];
            
            // 更新门店授权信息
            $store = StoreModel::find($storeId);
            if ($store) {
                $store->douyin_auth = 1;
                $store->douyin_access_token = $tokenInfo['access_token'];
                $store->douyin_refresh_token = $tokenInfo['refresh_token'] ?? '';
                // 抖音token有效期通常为15天
                $store->douyin_expire = date('Y-m-d H:i:s', time() + ($tokenInfo['expires_in'] ?? 1296000));
                $store->update_time = date('Y-m-d H:i:s');
                $store->save();
            }
            
            return $this->renderResult(true, '抖音授权成功！请记得在小程序中设置抖音门店ID。您可以关闭此页面。');
            
        } catch (\Exception $e) {
            return $this->renderResult(false, '授权失败：' . $e->getMessage());
        }
    }
    
    /**
     * 渲染结果页面
     */
    private function renderResult(bool $success, string $message): string
    {
        $color = $success ? '#5AAB6E' : '#ff4d4f';
        $icon = $success ? '✓' : '✗';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权结果</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
        .card { background: #fff; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 400px; }
        .icon { font-size: 64px; color: {$color}; margin-bottom: 20px; }
        .message { font-size: 18px; color: #333; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{$icon}</div>
        <div class="message">{$message}</div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * HTTP POST请求
     */
    private function httpPost(string $url, array $data): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: '';
    }
}
