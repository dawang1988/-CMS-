<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\service\PayService;

/**
 * 支付服务单元测试
 */
class PayServiceTest extends TestCase
{
    /**
     * 测试微信签名生成（通过反射访问私有方法）
     */
    public function testMakeWechatSign(): void
    {
        $payService = new PayService('150');
        $method = new \ReflectionMethod($payService, 'makeWechatSign');
        $method->setAccessible(true);

        $data = [
            'appid' => 'wx1234567890',
            'mch_id' => '1234567890',
            'nonce_str' => 'abc123',
            'body' => '测试商品',
        ];
        $key = 'test_key_123';

        $sign = $method->invoke($payService, $data, $key);

        // 签名应为32位大写字符串
        $this->assertEquals(32, strlen($sign));
        $this->assertEquals(strtoupper($sign), $sign);

        // 相同参数应生成相同签名
        $sign2 = $method->invoke($payService, $data, $key);
        $this->assertEquals($sign, $sign2);

        // 不同参数应生成不同签名
        $data['body'] = '其他商品';
        $sign3 = $method->invoke($payService, $data, $key);
        $this->assertNotEquals($sign, $sign3);
    }

    /**
     * 测试XML转换
     */
    public function testArrayToXmlAndBack(): void
    {
        $payService = new PayService('150');

        $toXml = new \ReflectionMethod($payService, 'arrayToXml');
        $toXml->setAccessible(true);

        $toArray = new \ReflectionMethod($payService, 'xmlToArray');
        $toArray->setAccessible(true);

        $data = ['appid' => 'wx123', 'mch_id' => '456', 'body' => 'test'];
        $xml = $toXml->invoke($payService, $data);

        // XML应包含根标签
        $this->assertStringStartsWith('<xml>', $xml);
        $this->assertStringEndsWith('</xml>', $xml);

        // 转回数组应一致
        $result = $toArray->invoke($payService, $xml);
        $this->assertEquals($data['appid'], $result['appid']);
        $this->assertEquals($data['mch_id'], $result['mch_id']);
    }

    /**
     * 测试空XML解析
     */
    public function testXmlToArrayEmpty(): void
    {
        $payService = new PayService('150');
        $method = new \ReflectionMethod($payService, 'xmlToArray');
        $method->setAccessible(true);

        $result = $method->invoke($payService, '');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
