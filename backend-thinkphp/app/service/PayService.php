<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Log;
use app\service\LogService as StructuredLog;

class PayService
{
    protected $tenantId;
    protected $appId;
    protected $mchId;
    protected $mchKey;

    public function __construct(string $tenantId = '88888888')
    {
        $this->tenantId = $tenantId;
        $this->appId = CacheService::getConfig($tenantId, 'wx_appid', '');
        $this->mchId = CacheService::getConfig($tenantId, 'wx_mch_id', '');
        $this->mchKey = CacheService::getConfig($tenantId, 'wx_mch_key', '');
    }

    public function createWechatOrder(string $orderNo, float $amount, string $openid, string $body = '订单支付'): array
    {
        if (empty($this->appId) || empty($this->mchId) || empty($this->mchKey)) {
            StructuredLog::warning('支付配置不完整', [
                'tenant_id' => $this->tenantId,
            ]);
            return ['code' => 1, 'msg' => '支付配置不完整'];
        }

        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->generateNonceStr(),
            'sign_type' => 'HMAC-SHA256',
            'body' => $body,
            'out_trade_no' => $orderNo,
            'total_fee' => (int)($amount * 100),
            'spbill_create_ip' => request()->ip(),
            'notify_url' => request()->domain() . '/app-api/pay/notify',
            'trade_type' => 'JSAPI',
            'openid' => $openid,
        ];

        $params['sign'] = $this->makeSign($params);

        $xml = $this->arrayToXml($params);
        $response = $this->httpPost('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        $result = $this->xmlToArray($response);

        if ($result['return_code'] !== 'SUCCESS' || $result['result_code'] !== 'SUCCESS') {
            StructuredLog::error('微信支付下单失败', [
                'order_no' => $orderNo,
                'amount' => $amount,
                'result' => $result,
            ]);
            return ['code' => 1, 'msg' => $result['return_msg'] ?? '支付下单失败'];
        }

        StructuredLog::info('微信支付下单成功', [
            'order_no' => $orderNo,
            'amount' => $amount,
            'prepay_id' => $result['prepay_id'],
        ]);

        $payParams = [
            'appId' => $this->appId,
            'timeStamp' => (string)time(),
            'nonceStr' => $this->generateNonceStr(),
            'package' => 'prepay_id=' . $result['prepay_id'],
            'signType' => 'HMAC-SHA256',
        ];
        $payParams['paySign'] = $this->makeSign($payParams);

        return ['code' => 0, 'data' => $payParams];
    }

    public function wechatCallback(array $data): bool
    {
        if (empty($data['sign'])) {
            StructuredLog::warning('微信回调签名缺失', [
                'data' => $data,
            ]);
            return false;
        }

        $sign = $data['sign'];
        unset($data['sign']);

        $isValid = $sign === $this->makeSign($data);
        
        if (!$isValid) {
            StructuredLog::warning('微信回调签名验证失败', [
                'expected' => $this->makeSign($data),
                'received' => $sign,
            ]);
        }

        return $isValid;
    }

    /**
     * 生成签名（HMAC-SHA256）
     */
    protected function makeSign(array $params): string
    {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $key !== 'sign') {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string .= 'key=' . $this->mchKey;
        return strtoupper(hash_hmac('sha256', $string, $this->mchKey));
    }

    /**
     * 生成随机字符串
     */
    protected function generateNonceStr(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * 数组转XML
     */
    protected function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $key => $value) {
            $xml .= "<{$key}><![CDATA[{$value}]]></{$key}>";
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * XML转数组（安全解析，禁用外部实体）
     */
    protected function xmlToArray(string $xml): array
    {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);

        $result = [];
        if ($dom->documentElement) {
            foreach ($dom->documentElement->childNodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE) {
                    $result[$node->nodeName] = $node->textContent;
                }
            }
        }
        return $result;
    }

    protected function httpPost(string $url, string $data): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            StructuredLog::error('HTTP请求失败', [
                'url' => $url,
                'error' => curl_error($ch),
            ]);
        }
        curl_close($ch);
        return $response ?: '';
    }
}
