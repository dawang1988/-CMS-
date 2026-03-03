<?php

namespace app\constants;

class OrderStatus
{
    const PENDING = 0;
    const USING = 1;
    const COMPLETED = 2;
    const CANCELLED = 3;
    const REFUNDED = 4;

    public static function getStatusText($status)
    {
        return [
            self::PENDING => '待支付',
            self::USING => '使用中',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
            self::REFUNDED => '已退款',
        ][$status] ?? '未知';
    }
}

class PayType
{
    const WECHAT = 1;
    const BALANCE = 2;
    const YEEPAY = 3;
    const GROUP = 4;

    public static function getPayTypeText($type)
    {
        return [
            self::WECHAT => '微信支付',
            self::BALANCE => '余额支付',
            self::YEEPAY => '易宝支付',
            self::GROUP => '团购支付',
        ][$type] ?? '未知';
    }
}

class RoomStatus
{
    const DISABLED = 0;      // 禁用
    const FREE = 1;          // 空闲
    const USING = 2;         // 使用中（原 CLEANING，语义已修正）
    const MAINTENANCE = 3;   // 维护中（原 USING，语义已修正）
    const CLEANING = 4;      // 待清洁（原 RESERVED，语义已修正）

    public static function getStatusText($status)
    {
        return [
            self::DISABLED => '禁用',
            self::FREE => '空闲',
            self::USING => '使用中',
            self::MAINTENANCE => '维护中',
            self::CLEANING => '待清洁',
        ][$status] ?? '未知';
    }
}

class CouponType
{
    const DEDUCT = 1;
    const DISCOUNT = 2;
    const TIME = 3;

    public static function getTypeText($type)
    {
        return [
            self::DEDUCT => '抵扣券',
            self::DISCOUNT => '满减券',
            self::TIME => '加时券',
        ][$type] ?? '未知';
    }
}

class BalanceType
{
    const RECHARGE = 1;
    const CONSUME = 2;
    const REFUND = 3;
    const GIFT = 4;

    public static function getTypeText($type)
    {
        return [
            self::RECHARGE => '充值',
            self::CONSUME => '消费',
            self::REFUND => '退款',
            self::GIFT => '赠送',
        ][$type] ?? '未知';
    }
}

class RoomClass
{
    const MAHJONG = 0;
    const BILLIARDS = 1;
    const KTV = 2;

    public static function getClassText($class)
    {
        return [
            self::MAHJONG => '棋牌',
            self::BILLIARDS => '台球',
            self::KTV => 'KTV',
        ][$class] ?? '未知';
    }
}

class RoomType
{
    const SPECIAL = 0;
    const SMALL = 1;
    const MEDIUM = 2;
    const LARGE = 3;
    const LUXURY = 4;
    const BUSINESS = 5;
    const SNOOKER = 6;
    const CHINESE8 = 7;
    const AMERICAN = 8;

    public static function getTypeText($type)
    {
        return [
            self::SPECIAL => '特价房',
            self::SMALL => '小包',
            self::MEDIUM => '中包',
            self::LARGE => '大包',
            self::LUXURY => '豪包',
            self::BUSINESS => '商务房',
            self::SNOOKER => '斯诺克',
            self::CHINESE8 => '中式黑八',
            self::AMERICAN => '美式球桌',
        ][$type] ?? '未知';
    }
}

class HttpCode
{
    const SUCCESS = 200;
    const CREATED = 201;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const VALIDATION_ERROR = 422;
    const TOO_MANY_REQUESTS = 429;
    const SERVER_ERROR = 500;
}

class BusinessCode
{
    const SUCCESS = 0;
    const ERROR = 1;
    const UNAUTHORIZED = 401;
    const TOKEN_EXPIRED = 402;
    const INVALID_PARAMS = 400;
}
