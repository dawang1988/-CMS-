<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use app\model\UserStoreBalance;

/**
 * 门店余额服务
 */
class StoreBalanceService
{
    /**
     * 获取用户在指定门店的余额
     * 
     * @param int $userId 用户ID
     * @param int $storeId 门店ID
     * @param string $tenantId 租户ID
     * @return array ['balance' => 主余额, 'gift_balance' => 赠送余额, 'total_balance' => 总余额]
     */
    public static function getBalance(int $userId, int $storeId, string $tenantId = '88888888'): array
    {
        $storeBalance = Db::name('user_store_balance')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('store_id', $storeId)
            ->find();

        if (!$storeBalance) {
            return [
                'balance' => 0,
                'gift_balance' => 0,
                'total_balance' => 0,
            ];
        }

        return [
            'balance' => (float)$storeBalance['balance'],
            'gift_balance' => (float)$storeBalance['gift_balance'],
            'total_balance' => (float)$storeBalance['balance'] + (float)$storeBalance['gift_balance'],
        ];
    }

    /**
     * 获取用户在所有门店的余额
     * 
     * @param int $userId 用户ID
     * @param string $tenantId 租户ID
     * @return array
     */
    public static function getAllStoreBalance(int $userId, string $tenantId = '88888888'): array
    {
        $list = Db::name('user_store_balance')
            ->alias('usb')
            ->leftJoin('store s', 'usb.store_id = s.id')
            ->where('usb.tenant_id', $tenantId)
            ->where('usb.user_id', $userId)
            ->field('usb.*, s.name as store_name')
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['total_balance'] = (float)$item['balance'] + (float)$item['gift_balance'];
        }

        return $list;
    }

    /**
     * 充值（增加余额）
     * 
     * @param int $userId 用户ID
     * @param int $storeId 门店ID
     * @param float $amount 充值金额
     * @param float $giftAmount 赠送金额
     * @param string $tenantId 租户ID
     * @param string $remark 备注
     * @return bool
     */
    public static function recharge(
        int $userId, 
        int $storeId, 
        float $amount, 
        float $giftAmount = 0, 
        string $tenantId = '88888888',
        string $remark = '充值'
    ): bool {
        Db::startTrans();
        try {
            // 查询或创建门店余额记录
            $storeBalance = Db::name('user_store_balance')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('store_id', $storeId)
                ->lock(true)
                ->find();

            $balanceBefore = $storeBalance ? (float)$storeBalance['balance'] : 0;
            $giftBalanceBefore = $storeBalance ? (float)$storeBalance['gift_balance'] : 0;

            if (!$storeBalance) {
                // 首次充值，创建记录
                Db::name('user_store_balance')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                    'balance' => $amount,
                    'gift_balance' => $giftAmount,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            } else {
                // 增加余额
                if ($amount > 0) {
                    Db::name('user_store_balance')
                        ->where('id', $storeBalance['id'])
                        ->inc('balance', $amount)
                        ->update();
                }
                if ($giftAmount > 0) {
                    Db::name('user_store_balance')
                        ->where('id', $storeBalance['id'])
                        ->inc('gift_balance', $giftAmount)
                        ->update();
                }
            }

            // 记录余额日志 - 主余额
            if ($amount > 0) {
                Db::name('balance_log')->insert([
                    'tenant_id' => $tenantId,
                    'store_id' => $storeId,
                    'user_id' => $userId,
                    'type' => 1, // 充值
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceBefore + $amount,
                    'remark' => $remark,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }

            // 记录余额日志 - 赠送余额
            if ($giftAmount > 0) {
                Db::name('balance_log')->insert([
                    'tenant_id' => $tenantId,
                    'store_id' => $storeId,
                    'user_id' => $userId,
                    'type' => 4, // 赠送
                    'amount' => $giftAmount,
                    'balance_before' => $giftBalanceBefore,
                    'balance_after' => $giftBalanceBefore + $giftAmount,
                    'remark' => $remark . '（赠送）',
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 扣款（减少余额）
     * 优先扣除主余额，不足时扣除赠送余额
     * 
     * @param int $userId 用户ID
     * @param int $storeId 门店ID
     * @param float $amount 扣款金额
     * @param string $tenantId 租户ID
     * @param string $remark 备注
     * @param int|null $orderId 订单ID
     * @return array ['success' => bool, 'balance_deduct' => 主余额扣除, 'gift_deduct' => 赠送余额扣除, 'msg' => 错误信息]
     */
    public static function deduct(
        int $userId, 
        int $storeId, 
        float $amount, 
        string $tenantId = '88888888',
        string $remark = '消费',
        ?int $orderId = null
    ): array {
        Db::startTrans();
        try {
            // 查询门店余额（加锁）
            $storeBalance = Db::name('user_store_balance')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('store_id', $storeId)
                ->lock(true)
                ->find();

            if (!$storeBalance) {
                Db::rollback();
                return [
                    'success' => false,
                    'msg' => '您在该门店没有余额',
                    'balance_deduct' => 0,
                    'gift_deduct' => 0,
                ];
            }

            $balance = (float)$storeBalance['balance'];
            $giftBalance = (float)$storeBalance['gift_balance'];
            $totalBalance = $balance + $giftBalance;

            if ($totalBalance < $amount) {
                Db::rollback();
                return [
                    'success' => false,
                    'msg' => '该门店余额不足',
                    'balance_deduct' => 0,
                    'gift_deduct' => 0,
                ];
            }

            // 计算扣款金额（优先扣主余额）
            $balanceDeduct = 0;
            $giftDeduct = 0;
            $remaining = $amount;

            if ($balance > 0) {
                $balanceDeduct = min($balance, $remaining);
                $remaining -= $balanceDeduct;
            }

            if ($remaining > 0 && $giftBalance > 0) {
                $giftDeduct = min($giftBalance, $remaining);
            }

            // 扣除主余额
            if ($balanceDeduct > 0) {
                Db::name('user_store_balance')
                    ->where('id', $storeBalance['id'])
                    ->dec('balance', $balanceDeduct)
                    ->update();

                // 记录日志
                Db::name('balance_log')->insert([
                    'tenant_id' => $tenantId,
                    'store_id' => $storeId,
                    'user_id' => $userId,
                    'type' => 2, // 消费
                    'amount' => -$balanceDeduct,
                    'balance_before' => $balance,
                    'balance_after' => $balance - $balanceDeduct,
                    'order_id' => $orderId,
                    'remark' => $remark,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }

            // 扣除赠送余额
            if ($giftDeduct > 0) {
                Db::name('user_store_balance')
                    ->where('id', $storeBalance['id'])
                    ->dec('gift_balance', $giftDeduct)
                    ->update();

                // 记录日志
                Db::name('balance_log')->insert([
                    'tenant_id' => $tenantId,
                    'store_id' => $storeId,
                    'user_id' => $userId,
                    'type' => 2, // 消费
                    'amount' => -$giftDeduct,
                    'balance_before' => $giftBalance,
                    'balance_after' => $giftBalance - $giftDeduct,
                    'order_id' => $orderId,
                    'remark' => $remark . '（赠送余额）',
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }

            Db::commit();
            return [
                'success' => true,
                'balance_deduct' => $balanceDeduct,
                'gift_deduct' => $giftDeduct,
                'msg' => '扣款成功',
            ];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 退款（增加余额）
     * 
     * @param int $userId 用户ID
     * @param int $storeId 门店ID
     * @param float $amount 退款金额
     * @param string $tenantId 租户ID
     * @param string $remark 备注
     * @param int|null $orderId 订单ID
     * @return bool
     */
    public static function refund(
        int $userId, 
        int $storeId, 
        float $amount, 
        string $tenantId = '88888888',
        string $remark = '退款',
        ?int $orderId = null
    ): bool {
        Db::startTrans();
        try {
            // 查询或创建门店余额记录
            $storeBalance = Db::name('user_store_balance')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('store_id', $storeId)
                ->lock(true)
                ->find();

            $balanceBefore = $storeBalance ? (float)$storeBalance['balance'] : 0;

            if (!$storeBalance) {
                // 创建记录
                Db::name('user_store_balance')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'store_id' => $storeId,
                    'balance' => $amount,
                    'gift_balance' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            } else {
                // 增加余额
                Db::name('user_store_balance')
                    ->where('id', $storeBalance['id'])
                    ->inc('balance', $amount)
                    ->update();
            }

            // 记录余额日志
            Db::name('balance_log')->insert([
                'tenant_id' => $tenantId,
                'store_id' => $storeId,
                'user_id' => $userId,
                'type' => 3, // 退款
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'order_id' => $orderId,
                'remark' => $remark,
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
}
