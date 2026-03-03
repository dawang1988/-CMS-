<?php
namespace tests;

use PHPUnit\Framework\TestCase;

class ConstantsTest extends TestCase
{
    public function testOrderStatusConstants()
    {
        $this->assertEquals(0, \app\constants\OrderStatus::PENDING);
        $this->assertEquals(1, \app\constants\OrderStatus::USING);
        $this->assertEquals(2, \app\constants\OrderStatus::COMPLETED);
        $this->assertEquals(3, \app\constants\OrderStatus::CANCELLED);
        $this->assertEquals(4, \app\constants\OrderStatus::REFUNDED);
    }

    public function testOrderStatusText()
    {
        $this->assertEquals('待支付', \app\constants\OrderStatus::getStatusText(0));
        $this->assertEquals('使用中', \app\constants\OrderStatus::getStatusText(1));
        $this->assertEquals('已完成', \app\constants\OrderStatus::getStatusText(2));
        $this->assertEquals('已取消', \app\constants\OrderStatus::getStatusText(3));
        $this->assertEquals('已退款', \app\constants\OrderStatus::getStatusText(4));
    }

    public function testPayTypeConstants()
    {
        $this->assertEquals(1, \app\constants\PayType::WECHAT);
        $this->assertEquals(2, \app\constants\PayType::BALANCE);
        $this->assertEquals(3, \app\constants\PayType::YEEPAY);
        $this->assertEquals(4, \app\constants\PayType::GROUP);
    }

    public function testRoomStatusConstants()
    {
        $this->assertEquals(0, \app\constants\RoomStatus::DISABLED);
        $this->assertEquals(1, \app\constants\RoomStatus::FREE);
        $this->assertEquals(2, \app\constants\RoomStatus::CLEANING);
        $this->assertEquals(3, \app\constants\RoomStatus::USING);
        $this->assertEquals(4, \app\constants\RoomStatus::RESERVED);
    }

    public function testCouponTypeConstants()
    {
        $this->assertEquals(1, \app\constants\CouponType::DEDUCT);
        $this->assertEquals(2, \app\constants\CouponType::DISCOUNT);
        $this->assertEquals(3, \app\constants\CouponType::TIME);
    }

    public function testBalanceTypeConstants()
    {
        $this->assertEquals(1, \app\constants\BalanceType::RECHARGE);
        $this->assertEquals(2, \app\constants\BalanceType::CONSUME);
        $this->assertEquals(3, \app\constants\BalanceType::REFUND);
        $this->assertEquals(4, \app\constants\BalanceType::GIFT);
    }

    public function testRoomClassConstants()
    {
        $this->assertEquals(0, \app\constants\RoomClass::MAHJONG);
        $this->assertEquals(1, \app\constants\RoomClass::BILLIARDS);
        $this->assertEquals(2, \app\constants\RoomClass::KTV);
    }

    public function testRoomTypeConstants()
    {
        $this->assertEquals(0, \app\constants\RoomType::SPECIAL);
        $this->assertEquals(1, \app\constants\RoomType::SMALL);
        $this->assertEquals(2, \app\constants\RoomType::MEDIUM);
        $this->assertEquals(3, \app\constants\RoomType::LARGE);
        $this->assertEquals(4, \app\constants\RoomType::LUXURY);
    }
}
