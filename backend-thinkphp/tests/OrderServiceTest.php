<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\service\OrderService;
use think\facade\Db;

/**
 * 订单服务单元测试
 */
class OrderServiceTest extends TestCase
{
    /**
     * 测试生成订单号
     */
    public function testGenerateOrderNo(): void
    {
        $orderNo1 = OrderService::generateOrderNo();
        $orderNo2 = OrderService::generateOrderNo();
        
        // 订单号长度应为18位
        $this->assertEquals(18, strlen($orderNo1));
        
        // 两次生成的订单号应不同
        $this->assertNotEquals($orderNo1, $orderNo2);
        
        // 订单号应以当前日期开头
        $this->assertStringStartsWith(date('Ymd'), $orderNo1);
    }
    
    /**
     * 测试计算订单金额 - 小时开台
     */
    public function testCalculateAmountHourly(): void
    {
        $room = ['price' => 50, 'deposit' => 100, 'pre_pay_amount' => 0];
        $startTime = '2024-01-01 10:00';
        $endTime = '2024-01-01 13:00';
        
        $result = OrderService::calculateAmount($room, $startTime, $endTime);
        
        $this->assertEquals(180, $result['duration']); // 3小时 = 180分钟
        $this->assertEquals(3, $result['hours']);
        $this->assertEquals(150, $result['total_amount']); // 50 * 3
        $this->assertEquals(1, $result['order_type']); // 小时开台
    }
    
    /**
     * 测试计算订单金额 - 押金开台
     */
    public function testCalculateAmountDeposit(): void
    {
        $room = ['price' => 50, 'deposit' => 100, 'pre_pay_amount' => 200];
        $startTime = '2024-01-01 10:00';
        
        $result = OrderService::calculateAmount($room, $startTime, null, null, true);
        
        $this->assertEquals(4, $result['order_type']); // 押金开台
        $this->assertEquals(200, $result['total_amount']); // pre_pay_amount
        $this->assertNotEmpty($result['end_time']); // 应自动计算结束时间
    }
    
    /**
     * 测试格式化订单详情
     */
    public function testFormatOrderDetail(): void
    {
        $order = [
            'id' => 1,
            'order_no' => 'TEST123456',
            'status' => '1',
            'pay_type' => '2',
            'pay_amount' => '100.00',
            'start_time' => '2024-01-01 10:00:00',
            'end_time' => '2024-01-01 13:00:00',
            'duration' => 180,
            'store_id' => 1,
            'room_id' => 1
        ];
        
        $store = [
            'name' => '测试门店',
            'address' => '测试地址',
            'latitude' => 39.9,
            'longitude' => 116.4
        ];
        
        $room = [
            'name' => '测试房间',
            'price' => 50,
            'room_type' => 1
        ];
        
        $result = OrderService::formatOrderDetail($order, $store, $room);
        
        // 验证类型转换
        $this->assertIsInt($result['status']);
        $this->assertEquals(1, $result['status']);
        $this->assertIsInt($result['pay_type']);
        $this->assertEquals(2, $result['pay_type']);
        
        // 验证驼峰命名兼容
        $this->assertEquals($result['order_no'], $result['orderNo']);
        $this->assertEquals($result['store_name'], $result['storeName']);
        $this->assertEquals($result['room_name'], $result['roomName']);
        
        // 验证订单时长计算
        $this->assertEquals(3, $result['order_hour']);
    }
    
    /**
     * 测试订单详情格式化 - 空关联数据
     */
    public function testFormatOrderDetailWithNullRelations(): void
    {
        $order = [
            'id' => 1,
            'order_no' => 'TEST123456',
            'status' => 0,
            'pay_type' => 1,
            'pay_amount' => 0,
            'store_id' => 1,
            'room_id' => 1
        ];
        
        $result = OrderService::formatOrderDetail($order, null, null);
        
        // 应有默认值
        $this->assertEquals('', $result['store_name']);
        $this->assertEquals('', $result['room_name']);
        $this->assertEquals(0, $result['order_hour']);
    }
}
