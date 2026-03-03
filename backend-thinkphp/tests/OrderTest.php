<?php
namespace tests;

use PHPUnit\Framework\TestCase;
use think\facade\Db;

class OrderTest extends TestCase
{
    protected $orderService;
    protected $testUserId;
    protected $testStoreId;
    protected $testRoomId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new \app\service\OrderService();

        Db::startTrans();
        $this->testUserId = Db::name('user')->insertGetId([
            'tenant_id' => 'test',
            'nickname' => '测试用户',
            'phone' => '13800138000',
            'balance' => 1000.00,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s')
        ]);

        $this->testStoreId = Db::name('store')->insertGetId([
            'tenant_id' => 'test',
            'name' => '测试门店',
            'address' => '测试地址',
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s')
        ]);

        $this->testRoomId = Db::name('room')->insertGetId([
            'tenant_id' => 'test',
            'store_id' => $this->testStoreId,
            'name' => '测试房间',
            'room_no' => '001',
            'price' => 50.00,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s')
        ]);
    }

    protected function tearDown(): void
    {
        Db::rollback();
        parent::tearDown();
    }

    public function testCreateOrderSuccess()
    {
        $orderData = [
            'user_id' => $this->testUserId,
            'store_id' => $this->testStoreId,
            'room_id' => $this->testRoomId,
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'duration' => 60,
            'price' => 50.00,
            'total_amount' => 50.00,
            'pay_type' => 2,
            'status' => 0
        ];

        $orderNo = $this->orderService->createOrder($orderData);

        $this->assertNotEmpty($orderNo);

        $order = Db::name('order')->where('order_no', $orderNo)->find();
        $this->assertEquals($this->testUserId, $order['user_id']);
        $this->assertEquals($this->testRoomId, $order['room_id']);
        $this->assertEquals(50.00, $order['total_amount']);
    }

    public function testCreateOrderWithCoupon()
    {
        $couponId = Db::name('coupon')->insertGetId([
            'tenant_id' => 'test',
            'name' => '测试优惠券',
            'type' => 1,
            'amount' => 10.00,
            'min_amount' => 50.00,
            'total' => 100,
            'received' => 0,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s')
        ]);

        Db::name('user_coupon')->insert([
            'tenant_id' => 'test',
            'user_id' => $this->testUserId,
            'coupon_id' => $couponId,
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s')
        ]);

        $orderData = [
            'user_id' => $this->testUserId,
            'store_id' => $this->testStoreId,
            'room_id' => $this->testRoomId,
            'coupon_id' => $couponId,
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'duration' => 60,
            'price' => 50.00,
            'total_amount' => 50.00,
            'discount_amount' => 10.00,
            'pay_amount' => 40.00,
            'pay_type' => 2,
            'status' => 0
        ];

        $orderNo = $this->orderService->createOrder($orderData);

        $order = Db::name('order')->where('order_no', $orderNo)->find();
        $this->assertEquals(40.00, $order['pay_amount']);
        $this->assertEquals(10.00, $order['discount_amount']);
    }

    public function testCreateOrderWithInsufficientBalance()
    {
        Db::name('user')->where('id', $this->testUserId)->update([
            'balance' => 10.00
        ]);

        $orderData = [
            'user_id' => $this->testUserId,
            'store_id' => $this->testStoreId,
            'room_id' => $this->testRoomId,
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'duration' => 60,
            'price' => 50.00,
            'total_amount' => 50.00,
            'pay_type' => 2,
            'status' => 0
        ];

        $this->expectException(\app\exception\BusinessException::class);
        $this->orderService->createOrder($orderData);
    }
}
