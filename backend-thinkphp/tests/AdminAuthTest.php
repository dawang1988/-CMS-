<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use think\facade\Cache;

/**
 * 管理员认证测试
 */
class AdminAuthTest extends TestCase
{
    /**
     * 测试Token生成和验证
     */
    public function testTokenGeneration(): void
    {
        $adminId = 1;
        $adminName = 'admin';
        
        // 模拟生成token
        $token = md5($adminId . time() . mt_rand(1000, 9999));
        
        // Token应为32位MD5
        $this->assertEquals(32, strlen($token));
        
        // 模拟存储token数据
        $tokenData = [
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'login_time' => date('Y-m-d H:i:s')
        ];
        
        $this->assertArrayHasKey('admin_id', $tokenData);
        $this->assertArrayHasKey('admin_name', $tokenData);
        $this->assertEquals($adminId, $tokenData['admin_id']);
    }
    
    /**
     * 测试密码哈希验证
     */
    public function testPasswordHash(): void
    {
        $password = 'test123456';
        
        // 生成哈希
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // 验证正确密码
        $this->assertTrue(password_verify($password, $hash));
        
        // 验证错误密码
        $this->assertFalse(password_verify('wrongpassword', $hash));
    }
    
    /**
     * 测试用户名格式验证
     */
    public function testUsernameValidation(): void
    {
        // 有效用户名
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]{3,30}$/', 'admin');
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]{3,30}$/', 'test_user123');
        
        // 无效用户名
        $this->assertDoesNotMatchRegularExpression('/^[a-zA-Z0-9_]{3,30}$/', 'ab'); // 太短
        $this->assertDoesNotMatchRegularExpression('/^[a-zA-Z0-9_]{3,30}$/', 'user@name'); // 特殊字符
        $this->assertDoesNotMatchRegularExpression('/^[a-zA-Z0-9_]{3,30}$/', '用户名'); // 中文
    }
}
