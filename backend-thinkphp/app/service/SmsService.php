<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Log;
use app\service\LogService as StructuredLog;

class SmsService
{
    protected $tenantId;
    protected $accessKey;
    protected $accessSecret;
    protected $signName;
    protected $templateCode;

    public function __construct(string $tenantId = '88888888')
    {
        $this->tenantId = $tenantId;
        $this->accessKey = CacheService::getConfig($tenantId, 'sms_access_key', '');
        $this->accessSecret = CacheService::getConfig($tenantId, 'sms_access_secret', '');
        $this->signName = CacheService::getConfig($tenantId, 'sms_sign_name', '');
        $this->templateCode = CacheService::getConfig($tenantId, 'sms_template_code', '');
    }

    public function sendCode(string $phone, string $code): bool
    {
        if (empty($this->accessKey) || empty($this->accessSecret)) {
            StructuredLog::warning('短信配置不完整', [
                'tenant_id' => $this->tenantId,
            ]);
            return false;
        }

        $params = [
            'PhoneNumbers' => $phone,
            'SignName' => $this->signName,
            'TemplateCode' => $this->templateCode,
            'TemplateParam' => json_encode(['code' => $code]),
        ];

        try {
            $result = $this->request($params);
            if ($result['Code'] === 'OK') {
                StructuredLog::info('短信发送成功', [
                    'phone' => $phone,
                    'template_code' => $this->templateCode,
                ]);
                return true;
            }
            StructuredLog::error('短信发送失败', [
                'phone' => $phone,
                'result' => $result,
            ]);
            return false;
        } catch (\Exception $e) {
            StructuredLog::error('短信发送异常', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 发送API请求
     */
    protected function request(array $params): array
    {
        $params['AccessKeyId'] = $this->accessKey;
        $params['Action'] = 'SendSms';
        $params['Format'] = 'JSON';
        $params['RegionId'] = 'cn-hangzhou';
        $params['SignatureMethod'] = 'HMAC-SHA1';
        $params['SignatureNonce'] = uniqid();
        $params['SignatureVersion'] = '1.0';
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        $params['Version'] = '2017-05-25';

        ksort($params);
        $queryString = http_build_query($params);
        $stringToSign = 'GET&' . urlencode('/') . '&' . urlencode($queryString);
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessSecret . '&', true));
        $params['Signature'] = $signature;

        $url = 'https://dysmsapi.aliyuncs.com/?' . http_build_query($params);
        $response = file_get_contents($url);
        
        return json_decode($response, true) ?: [];
    }
}
