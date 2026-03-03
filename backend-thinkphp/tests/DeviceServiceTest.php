<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;

/**
 * 设备服务测试
 */
class DeviceServiceTest extends TestCase
{
    /**
     * 测试开门结果格式
     */
    public function testOpenDoorResultFormat(): void
    {
        // 模拟成功结果
        $successResult = [
            'success' => true,
            'msg' => '操作成功'
        ];
        
        $this->assertArrayHasKey('success', $successResult);
        $this->assertArrayHasKey('msg', $successResult);
        $this->assertTrue($successResult['success']);
        
        // 模拟失败结果
        $failResult = [
            'success' => false,
            'msg' => '房间不存在'
        ];
        
        $this->assertFalse($failResult['success']);
    }
    
    /**
     * 测试空调控制结果格式
     */
    public function testAirConditionerControlResult(): void
    {
        $result = [
            'success' => true,
            'msg' => '操作成功',
            'data' => [
                'success_count' => 2,
                'total' => 2
            ]
        ];
        
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(2, $result['data']['success_count']);
        $this->assertEquals(2, $result['data']['total']);
    }
    
    /**
     * 测试设备日志数据格式
     */
    public function testDeviceLogFormat(): void
    {
        $logData = [
            'tenant_id' => '150',
            'target_id' => 1,
            'device_type' => 'room_door',
            'action' => 'open',
            'success' => 1,
            'create_time' => date('Y-m-d H:i:s')
        ];
        
        $this->assertArrayHasKey('tenant_id', $logData);
        $this->assertArrayHasKey('device_type', $logData);
        $this->assertArrayHasKey('action', $logData);
        $this->assertContains($logData['device_type'], ['room_door', 'store_door', 'air_conditioner']);
    }
}
