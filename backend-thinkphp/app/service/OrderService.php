<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\Order;
use app\model\Room;
use app\service\LogService as StructuredLog;

class OrderService
{
    public static function create(array $data): array
    {
        $userSuffix = str_pad((string)($data['user_id'] % 10000), 4, '0', STR_PAD_LEFT);
        $orderNo = 'O' . date('YmdHis') . $userSuffix . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $order = new Order();
        $order->save([
            'tenant_id' => $data['tenant_id'] ?? '88888888',
            'order_no' => $orderNo,
            'user_id' => $data['user_id'],
            'store_id' => $data['store_id'],
            'room_id' => $data['room_id'],
            'order_type' => $data['order_type'] ?? 1,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration' => $data['duration'] ?? 0,
            'price' => $data['price'] ?? 0,
            'total_amount' => $data['total_amount'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'card_deduct_amount' => $data['card_deduct_amount'] ?? 0,
            'pay_amount' => $data['pay_amount'],
            'coupon_id' => $data['coupon_id'] ?? null,
            'user_card_id' => $data['user_card_id'] ?? null,
            'remark' => $data['remark'] ?? '',
            'status' => 0,
        ]);

        StructuredLog::info('订单创建', [
            'order_no' => $orderNo,
            'user_id' => $data['user_id'],
            'room_id' => $data['room_id'],
            'amount' => $data['pay_amount'],
            'duration' => $data['duration'] ?? 0,
        ]);
        return ['code' => 0, 'data' => ['order_id' => $order->id, 'order_no' => $orderNo]];
    }

    public static function paySuccess(string $orderNo, int $payType = 1): bool
    {
        $order = Order::where('order_no', $orderNo)->find();
        if (!$order || $order->status != 0) {
            StructuredLog::warning('支付回调失败', [
                'order_no' => $orderNo,
                'reason' => '订单不存在或状态不正确',
            ]);
            return false;
        }

        Db::startTrans();
        try {
            $startTime = strtotime($order->start_time);
            $now = time();
            $isReservation = $startTime > $now;
            
            $order->pay_type = $payType;
            $order->pay_time = date('Y-m-d H:i:s');
            $order->pay_status = 1;

            if ($order->user_card_id && $order->card_deduct_amount > 0) {
                CardService::deduct(
                    (int)$order->user_card_id,
                    (int)$order->user_id,
                    (int)$order->id,
                    (float)$order->total_amount,
                    (float)$order->duration,
                    (float)$order->card_deduct_amount
                );
            }

            if ($order->coupon_id) {
                Db::name('user_coupon')->where('id', $order->coupon_id)
                    ->where('user_id', $order->user_id)
                    ->where('status', 0)
                    ->update([
                        'status' => 1,
                        'use_time' => date('Y-m-d H:i:s'),
                        'order_id' => $order->id,
                    ]);
            }

            if (!$isReservation) {
                // 立即使用：直接设置订单为"使用中"，开始计时
                $order->status = 1;
                Room::where('id', $order->room_id)->update([
                    'status' => 2,
                    'is_cleaning' => 0  // 清除待清洁标记
                ]);
                
                // 尝试开门（开门失败不影响订单状态）
                $doorResult = DeviceService::openDoor((int)$order->room_id, $order->tenant_id ?? '88888888');
                if ($doorResult) {
                    DeviceService::startRoom((int)$order->room_id, $order->tenant_id ?? '88888888');
                    StructuredLog::info('支付成功立即开始（开门成功）', [
                        'order_no' => $orderNo,
                        'start_time' => $order->start_time,
                        'room_id' => $order->room_id,
                    ]);
                } else {
                    StructuredLog::warning('支付成功立即开始（开门失败，用户可手动开门）', [
                        'order_no' => $orderNo,
                        'room_id' => $order->room_id,
                    ]);
                }
            } else {
                // 预约订单：保持"待消费"状态，等待定时任务处理
                $order->status = 0;
                Room::where('id', $order->room_id)->update([
                    'status' => 4,
                    'is_cleaning' => 0  // 已预约状态，不是待清洁
                ]);
                StructuredLog::info('预约订单支付成功', [
                    'order_no' => $orderNo,
                    'start_time' => $order->start_time,
                    'room_id' => $order->room_id,
                ]);
            }

            $order->save();

            // 清除房间列表缓存，确保小程序显示最新状态
            RedisService::clearRoomCache((int)$order->store_id, $order->tenant_id ?? '88888888');

            Db::commit();
            StructuredLog::info('支付成功处理完成', [
                'order_no' => $orderNo,
                'pay_type' => $payType,
                'room_id' => $order->room_id,
                'is_reservation' => $isReservation,
            ]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            StructuredLog::error('订单支付处理失败', [
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function complete(int $orderId): bool
    {
        $order = Order::find($orderId);
        if (!$order || $order->status != 1) {
            StructuredLog::warning('订单完成失败', [
                'order_id' => $orderId,
                'reason' => '订单不存在或状态不正确',
            ]);
            return false;
        }

        Db::startTrans();
        try {
            $store = Db::name('store')->where('id', $order->store_id)->find();
            $lightDelay = 0;
            if ($store && $store['delay_light'] == 1) {
                $lightDelay = 300;
            }

            $stopResult = DeviceService::stopRoom((int)$order->room_id, $order->tenant_id ?? '88888888', $lightDelay);
            if (!$stopResult) {
                StructuredLog::warning('订单完成但关闭设备失败', [
                    'order_id' => $orderId,
                    'room_id' => $order->room_id,
                ]);
            }

            $order->status = 2;
            $order->end_time = date('Y-m-d H:i:s');
            $order->save();

            // 订单完成后，房间状态设置为待清洁
            Room::where('id', $order->room_id)->update([
                'status' => 4,
                'is_cleaning' => 1  // 标记为待清洁状态
            ]);

            $storeName = Db::name('store')->where('id', $order->store_id)->value('name') ?: '';
            $roomName = Db::name('room')->where('id', $order->room_id)->value('name') ?: '';
            $existTask = Db::name('clear_task')->where('order_no', $order->order_no)->find();
            if (!$existTask) {
                Db::name('clear_task')->insert([
                    'tenant_id' => $order->tenant_id ?? '88888888',
                    'store_id' => $order->store_id,
                    'room_id' => $order->room_id,
                    'order_no' => $order->order_no,
                    'user_id' => $order->user_id,
                    'status' => 0,
                    'store_name' => $storeName,
                    'room_name' => $roomName,
                    'order_end_time' => $order->end_time,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }

            // 清除房间列表缓存，确保小程序显示最新状态
            RedisService::clearRoomCache((int)$order->store_id, $order->tenant_id ?? '88888888');

            Db::commit();
            StructuredLog::info('订单完成', [
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'room_id' => $order->room_id,
                'light_delay' => $lightDelay,
            ]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            StructuredLog::error('订单完成处理失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function earlyStart(int $orderId, string $tenantId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['code' => 1, 'msg' => '订单不存在'];
        }
        
        // 只有待消费的订单才能提前开门
        if ($order->status != 0 || $order->pay_status != 1) {
            return ['code' => 1, 'msg' => '订单状态不正确'];
        }

        $now = time();
        $originalStart = strtotime($order->start_time);
        $originalEnd = strtotime($order->end_time);
        
        // 如果已经到了开始时间，直接开门即可
        if ($originalStart <= $now) {
            $conflictOrder = Db::name('order')
                ->where('room_id', $order->room_id)
                ->where('id', '<>', $orderId)
                ->where('status', 1)
                ->where('end_time', '>', date('Y-m-d H:i:s'))
                ->find();
            if ($conflictOrder) {
                $conflictEnd = date('H:i', strtotime($conflictOrder['end_time']));
                return ['code' => 1, 'msg' => "该房间有订单正在使用中，预计{$conflictEnd}结束，请稍后再开门"];
            }
            
            // 更新订单状态为使用中
            Order::where('id', $orderId)->update(['status' => 1]);
            Room::where('id', $order->room_id)->update(['status' => 2]);
            
            // 尝试开门（失败不影响订单状态）
            $doorResult = DeviceService::openDoor((int)$order->room_id, $tenantId);
            if ($doorResult) {
                DeviceService::startRoom((int)$order->room_id, $tenantId);
                return ['code' => 0, 'msg' => '开门成功，订单已开始'];
            } else {
                return ['code' => 0, 'msg' => '订单已开始，但开门失败，请重试或联系客服'];
            }
        }

        // 提前开门：需要调整订单时间
        $duration = $originalEnd - $originalStart;
        $newStartTime = date('Y-m-d H:i:s', $now);
        $newEndTime = date('Y-m-d H:i:s', $now + $duration);

        $conflictOrder = Db::name('order')
            ->where('room_id', $order->room_id)
            ->where('id', '<>', $orderId)
            ->whereIn('status', [0, 1])
            ->where('start_time', '<', $newEndTime)
            ->where('end_time', '>', $newStartTime)
            ->find();
        if ($conflictOrder) {
            $conflictEnd = date('H:i', strtotime($conflictOrder['end_time']));
            return ['code' => 1, 'msg' => "该房间有订单正在使用中，预计{$conflictEnd}结束，请稍后再开门"];
        }

        Db::startTrans();
        try {
            // 更新订单时间和状态
            $order->start_time = $newStartTime;
            $order->end_time = $newEndTime;
            $order->status = 1;
            $order->save();

            Room::where('id', $order->room_id)->update(['status' => 2]);
            
            // 尝试开门（失败不影响订单状态）
            $doorResult = DeviceService::openDoor((int)$order->room_id, $tenantId);
            if ($doorResult) {
                DeviceService::startRoom((int)$order->room_id, $tenantId);
            }

            // 清除房间列表缓存，确保小程序显示最新状态
            RedisService::clearRoomCache((int)$order->store_id, $tenantId);

            Db::commit();
            StructuredLog::info('提前开门', [
                'order_id' => $orderId,
                'old_start_time' => $order->getOrigin('start_time'),
                'new_start_time' => $newStartTime,
                'new_end_time' => $newEndTime,
                'door_result' => $doorResult,
            ]);
            
            if ($doorResult) {
                return [
                    'code' => 0, 
                    'msg' => '开门成功，订单时间已更新',
                    'data' => [
                        'start_time' => $newStartTime,
                        'end_time' => $newEndTime
                    ]
                ];
            } else {
                return [
                    'code' => 0, 
                    'msg' => '订单已开始，但开门失败，请重试或联系客服',
                    'data' => [
                        'start_time' => $newStartTime,
                        'end_time' => $newEndTime
                    ]
                ];
            }
        } catch (\Exception $e) {
            Db::rollback();
            StructuredLog::error('提前开门失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return ['code' => 1, 'msg' => '操作失败：' . $e->getMessage()];
        }
    }

    /**
     * 房间清洁完成
     */
    public static function cleaningComplete(int $roomId): bool
    {
        return Room::where('id', $roomId)->where('status', 4)->update(['status' => 1]) > 0;
    }

    public static function cancel(int $orderId): bool
    {
        $order = Order::find($orderId);
        if (!$order || !in_array($order->status, [0, 1])) {
            StructuredLog::warning('订单取消失败', [
                'order_id' => $orderId,
                'reason' => '订单不存在或状态不允许取消',
            ]);
            return false;
        }

        Db::startTrans();
        try {
            $oldStatus = $order->status;
            
            if ($oldStatus == 1) {
                $stopResult = DeviceService::stopRoom((int)$order->room_id, $order->tenant_id ?? '88888888');
                if (!$stopResult) {
                    StructuredLog::warning('订单取消但关闭设备失败', [
                        'order_id' => $orderId,
                        'room_id' => $order->room_id,
                    ]);
                }
            }
            
            if ($order->user_card_id && $order->card_deduct_amount > 0 && $order->pay_status == 1) {
                $userCard = Db::name('user_card')->where('id', $order->user_card_id)->lock(true)->find();
                if ($userCard) {
                    $type = (int)$userCard['type'];
                    $beforeValue = (float)$userCard['remain_value'];
                    $refundValue = 0;
                    switch ($type) {
                        case 1: $refundValue = 1; break;
                        case 2: $refundValue = min((float)$order->duration, (float)$userCard['total_value'] - $beforeValue); break;
                        case 3: $refundValue = (float)$order->card_deduct_amount; break;
                    }
                    if ($refundValue > 0) {
                        $afterValue = $beforeValue + $refundValue;
                        $updateData = ['remain_value' => $afterValue];
                        if ($userCard['status'] == 2) {
                            $updateData['status'] = 1;
                        }
                        Db::name('user_card')->where('id', $order->user_card_id)->update($updateData);
                        Db::name('card_log')->insert([
                            'tenant_id' => $userCard['tenant_id'],
                            'user_card_id' => (int)$order->user_card_id,
                            'user_id' => (int)$order->user_id,
                            'order_id' => $orderId,
                            'type' => 3,
                            'change_value' => $refundValue,
                            'before_value' => $beforeValue,
                            'after_value' => $afterValue,
                            'remark' => '订单取消退还',
                            'create_time' => date('Y-m-d H:i:s'),
                        ]);
                        StructuredLog::info('会员卡退还', [
                            'card_id' => $order->user_card_id,
                            'type' => $type,
                            'refund_value' => $refundValue,
                            'before_value' => $beforeValue,
                            'after_value' => $afterValue,
                        ]);
                    }
                }
            }

            $order->status = 3;
            $order->save();

            $room = Room::find($order->room_id);
            if ($room && in_array($room->status, [2, 4])) {
                Room::where('id', $order->room_id)->update([
                    'status' => 1,
                    'is_cleaning' => 0  // 清除待清洁标记
                ]);
            }

            // 清除房间列表缓存，确保小程序显示最新状态
            RedisService::clearRoomCache((int)$order->store_id, $order->tenant_id ?? '88888888');

            Db::commit();
            StructuredLog::info('订单取消', [
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'old_status' => $oldStatus,
            ]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            StructuredLog::error('订单取消失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
