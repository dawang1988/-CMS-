<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\service\SecurityService;

/**
 * 安全服务单元测试
 */
class SecurityServiceTest extends TestCase
{
    /**
     * 测试AES加密解密
     */
    public function testEncryptDecrypt(): void
    {
        $original = '测试数据 hello world 123';
        $encrypted = SecurityService::encrypt($original);

        // 加密后不应等于原文
        $this->assertNotEquals($original, $encrypted);

        // 解密后应等于原文
        $decrypted = SecurityService::decrypt($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    /**
     * 测试空字符串加密解密
     */
    public function testEncryptDecryptEmpty(): void
    {
        $encrypted = SecurityService::encrypt('');
        $decrypted = SecurityService::decrypt($encrypted);
        $this->assertEquals('', $decrypted);
    }

    /**
     * 测试签名生成与验证
     */
    public function testSignAndVerify(): void
    {
        $data = ['amount' => '100', 'order_no' => 'TEST001', 'user_id' => '1'];
        $secret = 'test_secret_key';

        $sign = SecurityService::sign($data, $secret);

        // 签名应为32位大写MD5
        $this->assertEquals(32, strlen($sign));
        $this->assertEquals(strtoupper($sign), $sign);

        // 验证签名
        $this->assertTrue(SecurityService::verifySign($data, $sign, $secret));

        // 篡改数据后验证应失败
        $data['amount'] = '200';
        $this->assertFalse(SecurityService::verifySign($data, $sign, $secret));
    }

    /**
     * 测试签名排序一致性
     */
    public function testSignOrderIndependent(): void
    {
        $data1 = ['b' => '2', 'a' => '1', 'c' => '3'];
        $data2 = ['a' => '1', 'c' => '3', 'b' => '2'];
        $secret = 'key123';

        $this->assertEquals(
            SecurityService::sign($data1, $secret),
            SecurityService::sign($data2, $secret)
        );
    }

    /**
     * 测试签名忽略空值和sign字段
     */
    public function testSignIgnoresEmptyAndSignField(): void
    {
        $data1 = ['a' => '1', 'b' => '', 'c' => null, 'sign' => 'old_sign'];
        $data2 = ['a' => '1'];
        $secret = 'key123';

        $this->assertEquals(
            SecurityService::sign($data1, $secret),
            SecurityService::sign($data2, $secret)
        );
    }

    /**
     * 测试密码哈希与验证
     */
    public function testPasswordHashAndVerify(): void
    {
        $password = 'MyP@ssw0rd!';
        $hash = SecurityService::hashPassword($password);

        // 哈希值不应等于原密码
        $this->assertNotEquals($password, $hash);

        // 验证正确密码
        $this->assertTrue(SecurityService::verifyPassword($password, $hash));

        // 验证错误密码
        $this->assertFalse(SecurityService::verifyPassword('wrong_password', $hash));
    }

    /**
     * 测试手机号脱敏
     */
    public function testMaskPhone(): void
    {
        $this->assertEquals('138****0001', SecurityService::maskPhone('13800000001'));
        // 非11位不脱敏
        $this->assertEquals('1234', SecurityService::maskPhone('1234'));
    }

    /**
     * 测试身份证脱敏
     */
    public function testMaskIdCard(): void
    {
        $this->assertEquals('1101**********1234', SecurityService::maskIdCard('110101199001011234'));
        // 短于8位不脱敏
        $this->assertEquals('1234567', SecurityService::maskIdCard('1234567'));
    }

    /**
     * 测试银行卡脱敏
     */
    public function testMaskBankCard(): void
    {
        $result = SecurityService::maskBankCard('6222021234567890');
        $this->assertStringStartsWith('6222', $result);
        $this->assertStringEndsWith('7890', $result);
        $this->assertStringContainsString('****', $result);
    }

    /**
     * 测试SQL注入检测
     */
    public function testCheckSqlInjection(): void
    {
        $this->assertTrue(SecurityService::checkSqlInjection("1' UNION SELECT * FROM users--"));
        $this->assertTrue(SecurityService::checkSqlInjection("'; DROP TABLE users;"));
        $this->assertTrue(SecurityService::checkSqlInjection("1 OR 1=1; DELETE FROM orders"));
        $this->assertFalse(SecurityService::checkSqlInjection('正常的搜索内容'));
        $this->assertFalse(SecurityService::checkSqlInjection('hello world 123'));
    }

    /**
     * 测试XSS过滤
     */
    public function testXssFilter(): void
    {
        $input = '<script>alert("xss")</script>';
        $filtered = SecurityService::xssFilter($input);

        $this->assertStringNotContainsString('<script>', $filtered);
        $this->assertStringContainsString('&lt;script&gt;', $filtered);

        // 正常文本不受影响
        $this->assertEquals('hello world', SecurityService::xssFilter('hello world'));
    }
}
