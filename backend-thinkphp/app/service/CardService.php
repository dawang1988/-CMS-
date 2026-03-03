<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class CardService
{
    public static function calculateDeduct(int $userCardId, float $totalAmount, float $duration): array
    {
        $userCard = Db::name('user_card')->where('id', $userCardId)->find();
        if (!$userCard) {
            StructuredLog::warning('会员卡不存在', [
                'user_card_id' => $userCardId,
            ]);
            return ['deduct_amount' => 0, 'discount' => 1, 'discount_amount' => 0, 'card_info' => null, 'error' => '会员卡不存在'];
        }
        if ($userCard['status'] != 1) {
            StructuredLog::warning('会员卡状态异常', [
                'user_card_id' => $userCardId,
                'status' => $userCard['status'],
            ]);
            return ['deduct_amount' => 0, 'discount' => 1, 'discount_amount' => 0, 'card_info' => null, 'error' => '会员卡状态异常'];
        }
        if ($userCard['expire_time'] && strtotime($userCard['expire_time']) < time()) {
            StructuredLog::warning('会员卡已过期', [
                'user_card_id' => $userCardId,
                'expire_time' => $userCard['expire_time'],
            ]);
            return ['deduct_amount' => 0, 'discount' => 1, 'discount_amount' => 0, 'card_info' => null, 'error' => '会员卡已过期'];
        }
        if ($userCard['remain_value'] <= 0) {
            StructuredLog::warning('会员卡余额不足', [
                'user_card_id' => $userCardId,
                'remain_value' => $userCard['remain_value'],
            ]);
            return ['deduct_amount' => 0, 'discount' => 1, 'discount_amount' => 0, 'card_info' => null, 'error' => '会员卡余额不足'];
        }

        $discount = (float)($userCard['discount'] ?? 1);
        if ($discount <= 0 || $discount > 1) {
            $discount = 1;
        }

        $discountedAmount = $totalAmount * $discount;
        $cardDiscountAmount = round($totalAmount - $discountedAmount, 2);

        $deductAmount = 0;
        $type = (int)$userCard['type'];

        switch ($type) {
            case 1:
                if ($userCard['remain_value'] >= 1) {
                    $deductAmount = $discountedAmount;
                }
                break;

            case 2:
                $remainMinutes = (float)$userCard['remain_value'];
                if ($remainMinutes >= $duration) {
                    $deductAmount = $discountedAmount;
                } else {
                    $deductAmount = $discountedAmount * ($remainMinutes / $duration);
                }
                break;

            case 3:
                $remainAmount = (float)$userCard['remain_value'];
                $deductAmount = min($remainAmount, $discountedAmount);
                break;
        }

        StructuredLog::info('会员卡抵扣计算', [
            'user_card_id' => $userCardId,
            'total_amount' => $totalAmount,
            'duration' => $duration,
            'discount' => $discount,
            'deduct_amount' => round($deductAmount, 2),
            'card_type' => $type,
        ]);

        return [
            'deduct_amount' => round($deductAmount, 2),
            'discount' => $discount,
            'discount_amount' => $cardDiscountAmount,
            'card_info' => $userCard,
            'error' => null
        ];
    }

    public static function deduct(int $userCardId, int $userId, ?int $orderId, float $totalAmount, float $duration, float $deductAmount): bool
    {
        $userCard = Db::name('user_card')->where('id', $userCardId)->lock(true)->find();
        if (!$userCard || $userCard['status'] != 1) {
            StructuredLog::warning('会员卡扣减失败', [
                'user_card_id' => $userCardId,
                'reason' => '会员卡不存在或状态异常',
            ]);
            return false;
        }

        $type = (int)$userCard['type'];
        $beforeValue = (float)$userCard['remain_value'];
        $changeValue = 0;

        switch ($type) {
            case 1:
                if ($beforeValue < 1) return false;
                $changeValue = 1;
                break;

            case 2:
                $changeValue = min($beforeValue, $duration);
                break;

            case 3:
                $changeValue = min($beforeValue, $deductAmount);
                break;
        }

        $afterValue = $beforeValue - $changeValue;

        $updateData = ['remain_value' => $afterValue];
        if ($afterValue <= 0) {
            $updateData['status'] = 2;
        }
        Db::name('user_card')->where('id', $userCardId)->update($updateData);

        Db::name('card_log')->insert([
            'tenant_id' => $userCard['tenant_id'],
            'user_card_id' => $userCardId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'type' => 2,
            'change_value' => -$changeValue,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
            'remark' => '订单使用',
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        // 更新会员卡模板使用统计
        if ($userCard['card_id']) {
            Db::name('card')
                ->where('id', $userCard['card_id'])
                ->inc('use_count')
                ->update();
        }
        
        StructuredLog::info('会员卡扣减', [
            'card_id' => $userCardId,
            'type' => $type,
            'change_value' => $changeValue,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
            'order_id' => $orderId,
        ]);
        return true;
    }
}
