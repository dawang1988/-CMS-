<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class WechatNotifyService
{
    public static function sendPaySuccess($order): void
    {
        $tplId = self::getConfig($order->tenant_id ?? '88888888', 'wx_tpl_pay_ok');
        if (empty($tplId)) return;

        $user = Db::name('user')->where('id', $order->user_id)->find();
        if (!$user || empty($user['openid'])) return;

        $room = Db::name('room')->where('id', $order->room_id)->find();
        
        $data = [
            'character_string1' => ['value' => $order->order_no],
            'amount2' => ['value' => $order->pay_amount . '元'],
            'thing3' => ['value' => $room['name'] ?? '房间'],
            'time4' => ['value' => $order->start_time],
        ];

        $result = self::send($order->tenant_id ?? '88888888', $user['openid'], $tplId, $data, '/pages/order/detail?id=' . $order->id);
        
        if ($result) {
            StructuredLog::info('支付成功通知已发送', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
            ]);
        } else {
            StructuredLog::warning('支付成功通知发送失败', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
            ]);
        }
    }

    public static function sendOrderEnd($order): void
    {
        $tplId = self::getConfig($order->tenant_id ?? '88888888', 'wx_tpl_order_end');
        if (empty($tplId)) return;

        $user = Db::name('user')->where('id', $order->user_id)->find();
        if (!$user || empty($user['openid'])) return;

        $room = Db::name('room')->where('id', $order->room_id)->find();
        
        $data = [
            'character_string1' => ['value' => $order->order_no],
            'thing2' => ['value' => $room['name'] ?? '房间'],
            'time3' => ['value' => $order->end_time],
        ];

        $result = self::send($order->tenant_id ?? '88888888', $user['openid'], $tplId, $data, '/pages/order/detail?id=' . $order->id);
        
        if ($result) {
            StructuredLog::info('订单结束通知已发送', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
            ]);
        } else {
            StructuredLog::warning('订单结束通知发送失败', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
            ]);
        }
    }

    public static function sendExpireSoon($order): void
    {
        $tplId = self::getConfig($order->tenant_id ?? '88888888', 'wx_tpl_expire_soon');
        if (empty($tplId)) return;

        $user = Db::name('user')->where('id', $order->user_id)->find();
        if (!$user || empty($user['openid'])) return;

        $room = Db::name('room')->where('id', $order->room_id)->find();
        
        $data = [
            'thing1' => ['value' => $room['name'] ?? '房间'],
            'time2' => ['value' => $order->end_time],
            'thing3' => ['value' => '您的订单即将到期，请及时续费或结束使用'],
        ];

        $result = self::send($order->tenant_id ?? '88888888', $user['openid'], $tplId, $data, '/pages/order/detail?id=' . $order->id);
        
        if ($result) {
            StructuredLog::info('即将到期提醒已发送', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
            ]);
        } else {
            StructuredLog::warning('即将到期提醒发送失败', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
            ]);
        }
    }

    protected static function send(string $tenantId, string $openid, string $tplId, array $data, string $page = ''): bool
    {
        $appId = self::getConfig($tenantId, 'wx_appid');
        $appSecret = self::getConfig($tenantId, 'wx_app_secret');
        
        if (empty($appId) || empty($appSecret)) {
            StructuredLog::warning('微信通知发送失败', [
                'tenant_id' => $tenantId,
                'openid' => $openid,
                'reason' => '未配置微信 appid 或 secret',
            ]);
            return false;
        }

        $accessToken = self::getAccessToken($appId, $appSecret);
        if (empty($accessToken)) {
            StructuredLog::warning('微信通知发送失败', [
                'tenant_id' => $tenantId,
                'openid' => $openid,
                'reason' => '获取 access_token 失败',
            ]);
            return false;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        
        $postData = [
            'touser' => $openid,
            'template_id' => $tplId,
            'page' => $page,
            'data' => $data,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($result['errcode'] !== 0) {
            StructuredLog::error('发送订阅消息失败', [
                'tenant_id' => $tenantId,
                'openid' => $openid,
                'tpl_id' => $tplId,
                'errcode' => $result['errcode'],
                'errmsg' => $result['errmsg'] ?? '',
            ]);
            return false;
        }

        return true;
    }

    protected static function getConfig(string $tenantId, string $key)
    {
        return CacheService::getConfig($tenantId, $key);
    }

    protected static function getAccessToken(string $appId, string $appSecret): string
    {
        $cacheKey = "wx_access_token:{$appId}";
        $token = cache($cacheKey);
        
        if ($token) {
            return $token;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $response = file_get_contents($url);
        $result = json_decode($response, true);

        if (isset($result['access_token'])) {
            cache($cacheKey, $result['access_token'], 7000);
            StructuredLog::info('获取微信 access_token 成功', [
                'app_id' => $appId,
            ]);
            return $result['access_token'];
        }

        StructuredLog::error('获取微信 access_token 失败', [
            'app_id' => $appId,
            'result' => $result,
        ]);
        return '';
    }
}
