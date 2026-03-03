<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Order as OrderModel;
use app\model\Room;
use app\model\User;
use app\service\OrderService;
use app\service\PayService;
use app\service\DeviceService;
use app\service\CardService;
use app\service\CacheService;
use app\constants\OrderStatus;
use app\constants\PayType;
use app\constants\RoomStatus;
use app\constants\BalanceType;
use app\constants\CouponType;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 订单控制器
 */
class Order extends BaseController
{
    /**
     * 创建订单
     */
    public function create()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        
        // 黑名单校验
        $storeId = $this->request->post('store_id');
        $blackMsg = \app\service\BlacklistService::check((int)$userId, $tenantId, $storeId ? (int)$storeId : null);
        if ($blackMsg) {
            return json(['code' => 1, 'msg' => $blackMsg]);
        }

        $orderNo = $this->request->post('order_no');
        $roomId = $this->request->post('room_id');
        $startTime = $this->request->post('start_time');
        $endTime = $this->request->post('end_time');
        $couponId = $this->request->post('coupon_id');
        $userCardId = $this->request->post('user_card_id');
        $pkgId = $this->request->post('pkg_id');
        $payType = $this->request->post('pay_type', PayType::WECHAT);
        $groupPayNo = $this->request->post('group_pay_no');
        $preSubmit = $this->request->post('pre_submit', false);

        // ========== 场景1：已有订单号（微信支付在preOrder已创建订单） ==========
        if ($orderNo) {
            $order = OrderModel::where('order_no', $orderNo)->where('user_id', $userId)->find();
            if (!$order) {
                return json(['code' => 1, 'msg' => '订单不存在']);
            }
            if ($order->pay_status == 1) {
                return json(['code' => 0, 'msg' => '支付成功', 'data' => ['order_no' => $orderNo]]);
            }
            return json(['code' => 0, 'msg' => '预定成功', 'data' => ['order_no' => $orderNo]]);
        }

        // ========== 场景2：无订单号 — 余额支付/团购支付，原子化建单+支付 ==========
        $storeId = $this->request->post('store_id');

        $payMethodError = $this->checkPayMethod($tenantId, (int)$payType, (int)($storeId ?: 0));
        if ($groupPayNo) {
            $groupError = $this->checkPayMethod($tenantId, PayType::GROUP, (int)($storeId ?: 0));
            if ($groupError) return json(['code' => 1, 'msg' => $groupError]);
        } elseif ($payMethodError) {
            return json(['code' => 1, 'msg' => $payMethodError]);
        }

        Db::startTrans();
        try {
            // 使用悲观锁验证房间
            $room = Db::name('room')->where('id', $roomId)->lock(true)->find();
            if (!$room) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '房间不存在']);
            }

            // 校验房间状态
            $store = Db::name('store')->where('id', $room['store_id'])->find();
            $clearOpen = $store && $store['clear_open'] == 1;
            
            if ($room['status'] == RoomStatus::USING || $room['status'] == RoomStatus::DISABLED) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '该房间已停用，请选择其他房间']);
            }

            if ($room['status'] == RoomStatus::CLEANING || $room['status'] == RoomStatus::RESERVED) {
                $conflictOrder = Db::name('order')
                    ->where('room_id', $roomId)
                    ->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])
                    ->where(function($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                    })
                    ->find();
                if ($conflictOrder) {
                    Db::rollback();
                    $cs = date('H:i', strtotime($conflictOrder['start_time']));
                    $ce = date('H:i', strtotime($conflictOrder['end_time']));
                    return json(['code' => 1, 'msg' => "该时段已被预约（{$cs}-{$ce}），请选择其他时间"]);
                }
                if ($room['status'] == RoomStatus::RESERVED) {
                    $hasActive = Db::name('order')->where('room_id', $roomId)->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])->find();
                    if (!$hasActive && !$clearOpen) {
                        Db::rollback();
                        return json(['code' => 1, 'msg' => '该房间待清洁，请选择其他房间']);
                    }
                }
            }

            if (!in_array($room['status'], [RoomStatus::FREE, RoomStatus::CLEANING, RoomStatus::RESERVED])) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '该房间暂不可用，请选择其他房间']);
            }

            $storeId = $storeId ?: $room['store_id'];
            $start = strtotime($startTime);
            $end = strtotime($endTime);
            if (!$start || !$end || $end <= $start) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '时间参数不正确']);
            }
            $duration = ($end - $start) / 60;

            // 使用前端传来的preOrder计算结果
            $payAmount = (float)$this->request->post('pay_amount', 0);
            $totalAmount = (float)$this->request->post('total_amount', 0);
            $discountAmount = (float)$this->request->post('discount_amount', 0);
            $cardDeductAmount = (float)$this->request->post('card_deduct_amount', 0);

            // 如果前端没传金额，用房间价格重新算
            if ($totalAmount <= 0) {
                $totalAmount = $room['price'] * ($duration / 60);
                $payAmount = $totalAmount - $discountAmount;
                if ($payAmount < 0) $payAmount = 0;
            }

            // ---- 团购支付：在事务内再次验证券码（防止并发重复使用） ----
            if ($groupPayNo) {
                $usedLog = Db::name('group_verify_log')
                    ->where('group_pay_no', $groupPayNo)
                    ->where('status', 1)
                    ->lock(true)
                    ->find();
                if ($usedLog) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '该团购券码已被使用']);
                }
                $usedOrder = Db::name('order')
                    ->where('group_pay_no', $groupPayNo)
                    ->where('pay_status', 1)
                    ->lock(true)
                    ->find();
                if ($usedOrder) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '该团购券码已被使用']);
                }
                $payAmount = 0; // 团购免支付
            }

            // ---- 余额支付：先验证+扣款，再建单 ----
            if ($payType == 2 && $payAmount > 0) {
                // 使用门店余额服务扣款
                $deductResult = \app\service\StoreBalanceService::deduct(
                    $userId,
                    $storeId,
                    $payAmount,
                    $tenantId,
                    '订单支付'
                );
                
                if (!$deductResult['success']) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => $deductResult['msg']]);
                }
                
                $balanceDeduct = $deductResult['balance_deduct'];
                $giftDeduct = $deductResult['gift_deduct'];
            }

            $newOrderNo = date('YmdHis') . mt_rand(100000, 999999);
            $isReservation = strtotime($startTime) > time();
            
            // 余额支付/团购支付已完成，根据是否预约设置初始状态
            // 预约订单：PENDING（待消费），立即使用：PENDING（待开门后改为USING）
            $initialStatus = OrderStatus::PENDING;
            
            $orderData = [
                'tenant_id' => $tenantId,
                'order_no' => $newOrderNo,
                'user_id' => $userId,
                'store_id' => $storeId,
                'room_id' => $roomId,
                'order_type' => $pkgId ? 3 : ($groupPayNo ? 2 : ($preSubmit ? 4 : 1)),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $duration,
                'price' => $room['price'],
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'card_deduct_amount' => $cardDeductAmount,
                'pay_amount' => $payAmount,
                'pay_type' => $payType,
                'pay_status' => 1,
                'pay_time' => date('Y-m-d H:i:s'),
                'coupon_id' => $couponId ?: null,
                'user_card_id' => $userCardId ?: null,
                'pkg_id' => $pkgId ?: null,
                'group_pay_no' => $groupPayNo ?: null,
                'status' => $initialStatus,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ];
            $newOrder = OrderModel::create($orderData);

            // 扣减会员卡余额
            if ($userCardId && $cardDeductAmount > 0) {
                CardService::deduct((int)$userCardId, (int)$userId, (int)$newOrder->id, (float)$totalAmount, (float)$duration, (float)$cardDeductAmount);
            }
            if ($couponId) {
                Db::name('user_coupon')->where('id', $couponId)
                    ->where('user_id', $userId)->where('status', CouponType::UNUSED)
                    ->update(['status' => CouponType::USED, 'use_time' => date('Y-m-d H:i:s'), 'order_id' => $newOrder->id]);
            }

            if ($isReservation) {
                // 预约订单：保持 PENDING 状态，房间标记为已预约
                Room::where('id', $roomId)->update(['status' => RoomStatus::RESERVED]);
            } else {
                // 立即使用：尝试开门
                $doorOk = \app\service\DeviceService::openDoor((int)$roomId, $tenantId);
                if ($doorOk) {
                    OrderModel::where('id', $newOrder->id)->update(['status' => OrderStatus::USING]);
                    Room::where('id', $roomId)->update(['status' => RoomStatus::CLEANING]);
                    \app\service\DeviceService::startRoom((int)$roomId, $tenantId);
                } else {
                    // 开门失败，但订单已支付，保持 PENDING 状态，房间标记为已预约
                    Room::where('id', $roomId)->update(['status' => RoomStatus::RESERVED]);
                    \think\facade\Log::warning("支付成功但开门失败: order_no={$newOrderNo}");
                }
            }

            Db::commit();

            // 团购券核销记录（事务外，不影响订单）
            if ($groupPayNo) {
                \app\service\GroupBuyService::consume($groupPayNo, (int)$storeId, $tenantId, [
                    'group_coupon_id' => (int)$this->request->post('group_coupon_id', 0),
                    'title' => $this->request->post('group_title', ''),
                    'hours' => (float)$this->request->post('group_hours', 0),
                    'price' => (float)$this->request->post('group_price', 0),
                    'order_id' => $newOrder->id,
                    'order_no' => $newOrderNo,
                    'user_id' => $userId,
                    'platform' => $this->request->post('group_platform', ''),
                ]);
            }

            \think\facade\Log::info("原子化建单+支付成功: order_no={$newOrderNo}, pay_type={$payType}, amount={$payAmount}");
            return json(['code' => 0, 'msg' => '支付成功', 'data' => ['order_no' => $newOrderNo, 'order_id' => $newOrder->id]]);
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error("建单+支付失败: " . $e->getMessage());
            return json(['code' => 1, 'msg' => '支付失败，请稍后重试']);
        }
    }

    public function pay()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderId = $this->request->post('order_id') ?: $this->request->post('orderId');
        $payType = $this->request->post('pay_type') ?: $this->request->post('payType', PayType::WECHAT);

        \think\facade\Log::info("支付请求: user_id={$userId}, order_id={$orderId}, pay_type={$payType}");

        $order = OrderModel::where('id', $orderId)
            ->where('user_id', $userId)
            ->find();

        if (!$order) {
            \think\facade\Log::warning("支付失败: 订单不存在, order_id={$orderId}, user_id={$userId}");
            return json(['code' => 1, 'msg' => '订单不存在']);
        }
        if ($order->status != OrderStatus::PENDING) {
            \think\facade\Log::warning("支付失败: 订单状态不正确, order_id={$orderId}, status={$order->status}");
            return json(['code' => 1, 'msg' => '订单已支付或已取消']);
        }
        if ($order->pay_status == 1) {
            \think\facade\Log::warning("支付失败: 订单已支付, order_id={$orderId}");
            return json(['code' => 0, 'msg' => '订单已支付']);
        }

        $payMethodError = $this->checkPayMethod($tenantId, (int)$payType);
        if ($payMethodError) {
            return json(['code' => 1, 'msg' => $payMethodError]);
        }

        if ($payType == PayType::WECHAT) {
            $user = User::find($userId);
            $openid = $user ? ($user->openid ?? '') : '';
            
            if (empty($openid)) {
                \think\facade\Log::warning("支付失败: 用户openid为空, user_id={$userId}");
                return json(['code' => 1, 'msg' => '请先完成微信授权']);
            }

            $payService = new PayService($tenantId);
            $result = $payService->createWechatOrder(
                $order->order_no,
                (float)$order->pay_amount,
                $openid,
                '订单支付'
            );

            \think\facade\Log::info("微信支付创建: order_no={$order->order_no}, amount={$order->pay_amount}, result_code={$result['code']}");
            return json($result);
        }

        if ($payType == PayType::BALANCE) {
            // 检查用户在订单门店的余额
            $storeBalance = \app\service\StoreBalanceService::getBalance(
                $userId,
                $order->store_id,
                $tenantId
            );
            
            if ($storeBalance['total_balance'] < $order->pay_amount) {
                \think\facade\Log::warning("支付失败: 该门店余额不足, user_id={$userId}, store_id={$order->store_id}, balance={$storeBalance['total_balance']}, need={$order->pay_amount}");
                return json(['code' => 1, 'msg' => '该门店余额不足，请先充值']);
            }

            Db::startTrans();
            try {
                // 使用门店余额服务扣款
                $deductResult = \app\service\StoreBalanceService::deduct(
                    $userId,
                    $order->store_id,
                    $order->pay_amount,
                    $tenantId,
                    '订单支付',
                    $orderId
                );
                
                if (!$deductResult['success']) {
                    Db::rollback();
                    \think\facade\Log::warning("支付失败: {$deductResult['msg']}, user_id={$userId}");
                    return json(['code' => 1, 'msg' => $deductResult['msg']]);
                }
                
                $balanceDeduct = $deductResult['balance_deduct'];
                $giftDeduct = $deductResult['gift_deduct'];

                if ($order->user_card_id && $order->card_deduct_amount > 0) {
                    CardService::deduct(
                        (int)$order->user_card_id,
                        (int)$userId,
                        (int)$order->id,
                        (float)$order->total_amount,
                        (float)$order->duration,
                        (float)$order->card_deduct_amount
                    );
                }

                if ($order->coupon_id) {
                    Db::name('user_coupon')->where('id', $order->coupon_id)
                        ->where('user_id', $userId)
                        ->where('status', CouponType::UNUSED)
                        ->update([
                            'status' => CouponType::USED,
                            'use_time' => date('Y-m-d H:i:s'),
                            'order_id' => $orderId,
                        ]);
                }

                $isReservation = strtotime($order->start_time) > time();
                
                if ($isReservation) {
                    OrderModel::where('id', $orderId)->update([
                        'status' => OrderStatus::PENDING,
                        'pay_type' => PayType::BALANCE,
                        'pay_time' => date('Y-m-d H:i:s'),
                    ]);
                    Room::where('id', $order->room_id)->update(['status' => RoomStatus::RESERVED]);
                } else {
                    $doorOk = DeviceService::openDoor((int)$order->room_id, $tenantId);
                    if ($doorOk) {
                        OrderModel::where('id', $orderId)->update([
                            'status' => OrderStatus::USING,
                            'pay_type' => PayType::BALANCE,
                            'pay_time' => date('Y-m-d H:i:s'),
                        ]);
                        Room::where('id', $order->room_id)->update(['status' => RoomStatus::CLEANING]);
                        DeviceService::startRoom((int)$order->room_id, $tenantId);
                    } else {
                        OrderModel::where('id', $orderId)->update([
                            'status' => OrderStatus::PENDING,
                            'pay_type' => PayType::BALANCE,
                            'pay_time' => date('Y-m-d H:i:s'),
                        ]);
                        Room::where('id', $order->room_id)->update(['status' => RoomStatus::RESERVED]);
                        \think\facade\Log::warning("余额支付成功但开门失败，保持待消费: order_no={$order->order_no}");
                    }
                }

                Db::commit();

                \think\facade\Log::info("余额支付成功: order_id={$orderId}, order_no={$order->order_no}, amount={$order->pay_amount}, gift_deduct={$giftDeduct}, balance_deduct={$balanceDeduct}, is_reservation={$isReservation}");

                return json(['code' => 0, 'msg' => '支付成功']);
            } catch (\Exception $e) {
                Db::rollback();
                \think\facade\Log::error("余额支付异常: order_id={$orderId}, error=" . $e->getMessage());
                return json(['code' => 1, 'msg' => '支付失败，请稍后重试']);
            }
        }

        \think\facade\Log::warning("支付失败: 不支持的支付方式, pay_type={$payType}");
        return json(['code' => 1, 'msg' => '不支持的支付方式']);
    }

    public function list()
    {
        $userId = $this->request->userId;
        $status = $this->request->param('status');
        $page = $this->request->param('pageNo') ?: $this->request->param('page', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $orderColumn = $this->request->param('orderColumn');

        $query = OrderModel::where('user_id', $userId);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $list = $query->with(['store', 'room'])
            ->order('id', 'desc')
            ->page((int)$page, (int)$pageSize)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['order_id'] = $item['id'];
            $item['store_name'] = $item['store']['name'] ?? '';
            $item['room_name'] = $item['room']['name'] ?? '';
            $item['room_type'] = $item['room']['type'] ?? '';
            $item['room_class'] = $item['room']['room_class'] ?? 0;
            $item['address'] = $item['store']['address'] ?? '';
            $item['deposit'] = $item['room']['deposit'] ?? 0;
            unset($item['store'], $item['room']);
        }
        unset($item);

        return json([
            'code' => 0,
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }

    /**
     * 获取订单详情
     */
    public function detail()
    {
        $userId = $this->request->userId;
        $orderId = $this->request->param('id');

        $order = OrderModel::with(['store', 'room'])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->find();

        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }

        $data = $this->flattenOrder($order->toArray());
        return json(['code' => 0, 'data' => $data]);
    }

    public function cancel()
    {
        $userId = $this->request->userId;
        $orderId = $this->request->post('order_id') ?: $this->request->post('id') ?: $this->request->param('id');

        $order = OrderModel::where('id', $orderId)
            ->where('user_id', $userId)
            ->find();

        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }

        if (!in_array($order->status, [OrderStatus::PENDING])) {
            return json(['code' => 1, 'msg' => '订单状态不允许取消']);
        }

        $result = OrderService::cancel($orderId);

        return json($result ? ['code' => 0, 'msg' => '取消成功'] : ['code' => 1, 'msg' => '取消失败']);
    }

    public function openDoor()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderId = $this->request->post('order_id');

        $order = OrderModel::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', OrderStatus::USING)
            ->find();

        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在或状态不正确']);
        }

        $result = OrderService::earlyStart((int)$orderId, $tenantId);

        return json($result);
    }

    public function finish()
    {
        $userId = $this->request->userId;
        $orderId = $this->request->post('order_id');

        $order = OrderModel::where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', OrderStatus::USING)
            ->find();

        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在或状态不正确']);
        }

        $result = OrderService::complete($orderId);

        return json($result ? ['code' => 0, 'msg' => '结束成功'] : ['code' => 1, 'msg' => '结束失败']);
    }

    // ========== 路由别名方法（前端兼容） ==========

    public function save() { return $this->create(); }

    public function getOrderList() { return $this->list(); }

    public function getOrderInfo()
    {
        $userId = $this->request->userId;
        $id = $this->request->param('id');
        if ($id) return $this->detail();
        $order = OrderModel::with(['store', 'room'])
            ->where('user_id', $userId)
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])
            ->where('pay_status', 1)
            ->order('status', 'desc')
            ->order('id', 'desc')
            ->find();
        if (!$order) return json(['code' => 1, 'msg' => '暂无进行中的订单']);
        $data = $this->flattenOrder($order->toArray());
        $data['order_key'] = $data['order_no'] ?? $data['id'];
        $data['orderKey'] = $data['order_key'];
        return json(['code' => 0, 'data' => $data]);
    }


    public function getOrderInfoByNo()
    {
        $userId = $this->request->userId;
        $orderNo = $this->request->param('order_no') ?: $this->request->param('orderNo') ?: $this->request->param('order_key') ?: $this->request->param('orderKey');
        $order = OrderModel::with(['store', 'room'])->where('user_id', $userId)->where('order_no', $orderNo)->find();
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在']);
        $data = $this->flattenOrder($order->toArray());
        $data['order_key'] = $data['order_no'] ?? $data['id'];
        $data['orderKey'] = $data['order_key'];
        return json(['code' => 0, 'data' => $data]);
    }

    private function checkPayMethod(string $tenantId, int $payType, int $storeId = 0, bool $isWxPay = false): ?string
    {
        if ($payType == PayType::WECHAT || $isWxPay) {
            $enabled = CacheService::getConfig($tenantId, 'wx_enabled', '0');
            if ($enabled !== '1' && $enabled !== 1) {
                return '微信支付未启用，请联系店家';
            }
        }
        if ($payType == PayType::BALANCE) {
            $enabled = CacheService::getConfig($tenantId, 'balance_pay_enabled', '0');
            if ($enabled !== '1' && $enabled !== 1) {
                return '余额支付未启用，请联系店家';
            }
        }
        if ($payType == PayType::GROUP) {
            $enabled = CacheService::getConfig($tenantId, 'group_pay_enabled', '0');
            if ($enabled !== '1' && $enabled !== 1) {
                return '团购支付未启用，请联系店家';
            }
            if ($storeId > 0) {
                $store = Db::name('store')->where('id', $storeId)->find();
                $meituanOk = $store && !empty($store['meituan_auth']) && !empty($store['meituan_access_token'])
                    && (empty($store['meituan_expire']) || strtotime($store['meituan_expire']) > time());
                $douyinOk = $store && !empty($store['douyin_auth']) && !empty($store['douyin_access_token'])
                    && (empty($store['douyin_expire']) || strtotime($store['douyin_expire']) > time());
                if (!$meituanOk && !$douyinOk) {
                    return '该门店未授权团购平台，请联系店家';
                }
            }
        }
        return null;
    }

    private function flattenOrder(array $item): array
    {
        $item['order_id'] = $item['id'];
        $item['store_name'] = $item['store']['name'] ?? '';
        $item['address'] = $item['store']['address'] ?? '';
        $item['phone'] = $item['store']['phone'] ?? '';
        $item['wifi_name'] = $item['store']['wifi_name'] ?? '';
        $item['wifi_password'] = $item['store']['wifi_password'] ?? '';
        $item['latitude'] = $item['store']['latitude'] ?? '';
        $item['longitude'] = $item['store']['longitude'] ?? '';
        $item['room_name'] = $item['room']['name'] ?? '';
        $item['room_type'] = $item['room']['type'] ?? '';
        $item['room_class'] = $item['room']['room_class'] ?? 0;
        $item['room_price'] = $item['room']['price'] ?? 0;
        $item['work_price'] = $item['room']['work_price'] ?? 0;
        $item['deposit'] = $item['room']['deposit'] ?? 0;
        unset($item['store'], $item['room']);
        if (!empty($item['coupon_id'])) {
            $coupon = Db::name('user_coupon')->alias('uc')
                ->leftJoin('coupon c', 'uc.coupon_id = c.id')
                ->where('uc.id', $item['coupon_id'])
                ->field('c.name as coupon_name, c.type as coupon_type, c.amount as coupon_amount')
                ->find();
            if ($coupon) {
                $item['coupon_name'] = $coupon['coupon_name'];
                $item['coupon_type'] = (int)$coupon['coupon_type'];
                $item['coupon_amount'] = $coupon['coupon_amount'];
            }
        }
        return $item;
    }

    private function preRenewOrder($userId, $orderId, $endTime, $payType, $pkgId, $couponId)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $userCardId = $this->request->post('user_card_id');
        
        $order = OrderModel::where('id', $orderId)->where('user_id', $userId)->where('status', OrderStatus::USING)->find();
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在或状态不正确']);

        $newEnd = strtotime($endTime);
        $oldEnd = strtotime($order->end_time);
        if ($newEnd <= $oldEnd) return json(['code' => 1, 'msg' => '续费时间必须大于当前结束时间']);

        $nextOrder = Db::name('order')
            ->where('room_id', $order->room_id)
            ->where('id', '<>', $orderId)
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])
            ->where('start_time', '>', $order->end_time)
            ->order('start_time', 'asc')
            ->find();
        if ($nextOrder) {
            $gapMinutes = (strtotime($nextOrder['start_time']) - $oldEnd) / 60;
            if ($gapMinutes < 60) {
                $nextStart = date('H:i', strtotime($nextOrder['start_time']));
                return json(['code' => 1, 'msg' => "距下一个预约（{$nextStart}）不足1小时，无法续费"]);
            }
            if ($newEnd > strtotime($nextOrder['start_time'])) {
                $maxEnd = date('H:i', strtotime($nextOrder['start_time']));
                return json(['code' => 1, 'msg' => "续费时间与后续预约冲突，最多可续到{$maxEnd}"]);
            }
        }

        $addMinutes = ($newEnd - $oldEnd) / 60;
        
        if ($pkgId) {
            $pkg = Db::name('package')->where('id', $pkgId)->find();
            $addAmount = $pkg ? (float)$pkg['price'] : $order->price * ($addMinutes / 60);
        } else {
            $addAmount = $order->price * ($addMinutes / 60);
        }

        $cardDeductAmount = 0;
        $cardDiscountAmount = 0;
        $renewPayAmount = $addAmount;

        if ($userCardId) {
            $cardResult = CardService::calculateDeduct((int)$userCardId, $renewPayAmount, $addMinutes);
            if (!$cardResult['error']) {
                $cardDiscountAmount = $cardResult['discount_amount'];
                $cardDeductAmount = $cardResult['deduct_amount'];
                $renewPayAmount = $renewPayAmount - $cardDiscountAmount - $cardDeductAmount;
                if ($renewPayAmount < 0) $renewPayAmount = 0;
            }
        }

        $result = [
            'total_amount' => round($addAmount, 2),
            'discount_amount' => round($cardDiscountAmount, 2),
            'card_deduct_amount' => round($cardDeductAmount, 2),
            'card_discount_amount' => round($cardDiscountAmount, 2),
            'pay_amount' => round($renewPayAmount, 2),
            'deposit' => 0,
            'duration' => $addMinutes,
            'price' => $order->price,
            'order_no' => $order->order_no . 'R' . date('His'),
        ];

        if ($payType == PayType::WECHAT && $renewPayAmount > 0) {
            $user = User::find($userId);
            $payService = new PayService($tenantId);
            $renewOrderNo = $order->order_no . 'R' . date('His');
            $payResult = $payService->createWechatOrder($renewOrderNo, (float)$renewPayAmount, $user->openid ?? '', '订单续费');
            if ($payResult['code'] == 0) {
                $payData = $payResult['data'];
                $result['timeStamp'] = $payData['timeStamp'] ?? '';
                $result['nonceStr'] = $payData['nonceStr'] ?? '';
                $result['pkg'] = $payData['package'] ?? '';
                $result['signType'] = $payData['signType'] ?? '';
                $result['paySign'] = $payData['paySign'] ?? '';
                $result['order_no'] = $renewOrderNo;
            } else {
                return json($payResult);
            }
        }

        return json(['code' => 0, 'data' => $result]);
    }

    public function renew()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderId = $this->request->post('order_id') ?: $this->request->post('orderId');
        $endTime = $this->request->post('end_time') ?: $this->request->post('endTime');
        $payType = $this->request->post('pay_type') ?: $this->request->post('payType', PayType::WECHAT);
        $userCardId = $this->request->post('user_card_id');

        $order = OrderModel::where('id', $orderId)->where('user_id', $userId)->where('status', OrderStatus::USING)->find();
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在或状态不正确']);

        $payMethodError = $this->checkPayMethod($tenantId, (int)$payType);
        if ($payMethodError) {
            return json(['code' => 1, 'msg' => $payMethodError]);
        }

        $newEnd = strtotime($endTime);
        $oldEnd = strtotime($order->end_time);
        if ($newEnd <= $oldEnd) return json(['code' => 1, 'msg' => '续费时间必须大于当前结束时间']);

        $nextOrder = Db::name('order')
            ->where('room_id', $order->room_id)
            ->where('id', '<>', $orderId)
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])
            ->where('start_time', '>', $order->end_time)
            ->order('start_time', 'asc')
            ->find();
        if ($nextOrder) {
            $gapMinutes = (strtotime($nextOrder['start_time']) - $oldEnd) / 60;
            if ($gapMinutes < 60) {
                $nextStart = date('H:i', strtotime($nextOrder['start_time']));
                return json(['code' => 1, 'msg' => "距下一个预约（{$nextStart}）不足1小时，无法续费"]);
            }
            if ($newEnd > strtotime($nextOrder['start_time'])) {
                $maxEnd = date('H:i', strtotime($nextOrder['start_time']));
                return json(['code' => 1, 'msg' => "续费时间与后续预约冲突，最多可续到{$maxEnd}"]);
            }
        }

        $addMinutes = ($newEnd - $oldEnd) / 60;
        $addAmount = $order->price * ($addMinutes / 60);
        $cardDeductAmount = 0;
        $cardDiscountAmount = 0;
        $renewPayAmount = $addAmount;

        if ($userCardId) {
            $cardResult = CardService::calculateDeduct((int)$userCardId, $renewPayAmount, $addMinutes);
            if (!$cardResult['error']) {
                $cardDiscountAmount = $cardResult['discount_amount'];
                $cardDeductAmount = $cardResult['deduct_amount'];
                $renewPayAmount = $renewPayAmount - $cardDiscountAmount - $cardDeductAmount;
                if ($renewPayAmount < 0) $renewPayAmount = 0;
            }
        }

        if ($payType == PayType::BALANCE) {
            // 检查用户在订单门店的余额
            $storeBalance = \app\service\StoreBalanceService::getBalance(
                $userId,
                $order->store_id,
                $tenantId
            );
            
            if ($storeBalance['total_balance'] < $renewPayAmount) {
                return json(['code' => 1, 'msg' => '该门店余额不足，请先充值']);
            }
            
            Db::startTrans();
            try {
                if ($userCardId && ($cardDeductAmount + $cardDiscountAmount) > 0) {
                    CardService::deduct(
                        (int)$userCardId,
                        (int)$userId,
                        (int)$orderId,
                        (float)$addAmount,
                        (float)$addMinutes,
                        (float)$cardDeductAmount
                    );
                }

                // 使用门店余额服务扣款
                $deductResult = \app\service\StoreBalanceService::deduct(
                    $userId,
                    $order->store_id,
                    $renewPayAmount,
                    $tenantId,
                    '订单续费',
                    $orderId
                );
                
                if (!$deductResult['success']) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => $deductResult['msg']]);
                }
                
                $balanceDeduct = $deductResult['balance_deduct'];
                $giftDeduct = $deductResult['gift_deduct'];
                
                OrderModel::where('id', $orderId)->update([
                    'end_time' => $endTime,
                    'pay_amount' => $order->pay_amount + $renewPayAmount,
                    'card_deduct_amount' => ($order->card_deduct_amount ?? 0) + $cardDeductAmount,
                    'discount_amount' => ($order->discount_amount ?? 0) + $cardDiscountAmount,
                ]);
                
                \think\facade\Log::info("续费余额支付: order_id={$orderId}, amount={$renewPayAmount}, card_deduct={$cardDeductAmount}, gift_deduct={$giftDeduct}, balance_deduct={$balanceDeduct}");
                
                Db::commit();
                return json(['code' => 0, 'msg' => '续费成功']);
            } catch (\Exception $e) {
                Db::rollback();
                \think\facade\Log::error("续费余额支付失败: order_id={$orderId}, error=" . $e->getMessage());
                return json(['code' => 1, 'msg' => '续费失败']);
            }
        }

        $user = User::find($userId);
        $payService = new PayService($tenantId);
        $result = $payService->createWechatOrder($order->order_no . 'R', (float)$renewPayAmount, $user->openid ?? '', '订单续费');
        if ($result['code'] == 0) {
            $payData = $result['data'];
            return json([
                'code' => 0,
                'data' => [
                    'order_no' => $order->order_no . 'R',
                    'pay_amount' => $renewPayAmount,
                    'card_deduct_amount' => round($cardDeductAmount, 2),
                    'card_discount_amount' => round($cardDiscountAmount, 2),
                    'timeStamp' => $payData['timeStamp'] ?? '',
                    'nonceStr' => $payData['nonceStr'] ?? '',
                    'pkg' => $payData['package'] ?? '',
                    'signType' => $payData['signType'] ?? '',
                    'paySign' => $payData['paySign'] ?? '',
                ]
            ]);
        }
        return json($result);
    }

    public function openRoomDoor()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderKey = $this->request->param('orderKey');

        if ($orderKey) {
            $order = OrderModel::where('user_id', $userId)->where('order_no', $orderKey)->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])->find();
        } else {
            $order = OrderModel::where('user_id', $userId)->where('status', OrderStatus::USING)->order('id', 'desc')->find();
        }
        if (!$order) return json(['code' => 1, 'msg' => '暂无进行中的订单']);

        $room = Room::find($order->room_id);
        $deviceConfig = null;
        if ($room && !empty($room->device_config)) {
            $deviceConfig = is_string($room->device_config) 
                ? json_decode($room->device_config, true) 
                : $room->device_config;
        }
        if (!$deviceConfig) {
            $deviceConfig = ['lock' => true, 'light' => true, 'ac' => true, 'mahjong' => false];
        }

        $store = \app\model\Store::find($order->store_id);
        $doorAlwaysOpen = $store && $store->order_door_open == 1;

        $isFirstStart = ($order->status == OrderStatus::PENDING);
        if ($isFirstStart) {
            $now = time();
            $originalStart = strtotime($order->start_time);
            $originalEnd = strtotime($order->end_time);
            
            $duration = $originalEnd - $originalStart;
            $newStartTime = date('Y-m-d H:i:s', $now);
            $newEndTime = date('Y-m-d H:i:s', $now + $duration);
            
            $conflictOrder = Db::name('order')
                ->where('room_id', $order->room_id)
                ->where('id', '<>', $order->id)
                ->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])
                ->where('start_time', '<', $newEndTime)
                ->where('end_time', '>', $newStartTime)
                ->find();
            if ($conflictOrder) {
                $conflictEnd = date('H:i', strtotime($conflictOrder['end_time']));
                return json(['code' => 1, 'msg' => "该房间有订单正在使用中，预计{$conflictEnd}结束，请稍后再开门"]);
            }
            
            $hasDevice = Db::name('device')
                ->where('room_id', $order->room_id)
                ->where('tenant_id', $tenantId)
                ->find();
            if (!$hasDevice) {
                return json(['code' => 1, 'msg' => '该房间未绑定门锁设备，请联系店家']);
            }

            $result = DeviceService::openDoor((int)$order->room_id, $tenantId);
            if (!$result) {
                return json(['code' => 1, 'msg' => '开门指令发送失败，请检查设备网络或重试']);
            }

            OrderModel::where('id', $order->id)->update([
                'status' => OrderStatus::USING, 
                'start_time' => $newStartTime,
                'end_time' => $newEndTime
            ]);
            Room::where('id', $order->room_id)->update(['status' => RoomStatus::CLEANING]);
            DeviceService::startRoom((int)$order->room_id, $tenantId, $deviceConfig, $doorAlwaysOpen);
            
            Log::info("提前开门: order_id={$order->id}, 原开始={$order->start_time}, 新开始={$newStartTime}, 新结束={$newEndTime}");
            return json(['code' => 0, 'msg' => '开门成功']);
        }

        $hasDevice = Db::name('device')
            ->where('room_id', $order->room_id)
            ->where('tenant_id', $tenantId)
            ->find();
        if (!$hasDevice) {
            return json(['code' => 1, 'msg' => '该房间未绑定门锁设备，请联系店家']);
        }

        $result = DeviceService::openDoor((int)$order->room_id, $tenantId);

        return json($result ? ['code' => 0, 'msg' => '开门成功'] : ['code' => 1, 'msg' => '开门指令发送失败，请检查设备网络或重试']);
    }

    public function openStoreDoor()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderKey = $this->request->param('orderKey');

        if ($orderKey) {
            $order = OrderModel::where('user_id', $userId)->where('order_no', $orderKey)->whereIn('status', [OrderStatus::PENDING, OrderStatus::USING])->find();
        } else {
            $order = OrderModel::where('user_id', $userId)->where('status', OrderStatus::USING)->order('id', 'desc')->find();
        }
        if (!$order) return json(['code' => 1, 'msg' => '暂无进行中的订单']);
        $result = DeviceService::openStoreDoor((int)$order->store_id, $tenantId);
        return json($result ? ['code' => 0, 'msg' => '开门成功'] : ['code' => 1, 'msg' => '门店大门设备未响应，请重试或联系店家']);
    }

    public function openLockOnly() { return $this->openRoomDoor(); }
    public function getLockPwd() { return json(['code' => 0, 'data' => ['pwd' => '']]); }
    public function controlKT() { return json(['code' => 0, 'msg' => '操作成功']); }
    public function controlDevice() { return json(['code' => 0, 'msg' => '操作成功']); }

    public function startOrder()
    {
        $userId = $this->request->userId;
        $orderId = $this->request->param('id');
        $order = OrderModel::where('id', $orderId)->where('user_id', $userId)->find();
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在']);
        
        $conflictOrder = Db::name('order')
            ->where('room_id', $order->room_id)
            ->where('id', '<>', $orderId)
            ->where('status', OrderStatus::USING)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->find();
        if ($conflictOrder) {
            $conflictEnd = date('H:i', strtotime($conflictOrder['end_time']));
            return json(['code' => 1, 'msg' => "该房间有订单正在使用中，预计{$conflictEnd}结束，请稍后"]);
        }
        
        OrderModel::where('id', $orderId)->update(['status' => OrderStatus::USING, 'start_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '开始成功']);
    }

    public function closeOrder()
        {
            $userId = $this->request->userId;
            $orderId = (int)$this->request->param('id');

            $order = OrderModel::where('id', $orderId)->where('user_id', $userId)->where('status', OrderStatus::USING)->find();
            if (!$order) {
                return json(['code' => 1, 'msg' => '订单不存在或状态不正确']);
            }

            $result = OrderService::complete($orderId);
            return json($result ? ['code' => 0, 'msg' => '结束成功'] : ['code' => 1, 'msg' => '结束失败']);
        }


    public function getOrderByRoomId()
    {
        $userId = $this->request->userId;
        $roomId = $this->request->param('id');
        $order = OrderModel::with(['store', 'room'])->where('user_id', $userId)->where('room_id', $roomId)->where('status', OrderStatus::USING)->order('id', 'desc')->find();
        if (!$order) return json(['code' => 1, 'msg' => '暂无进行中的订单']);
        $data = $order->toArray();
        $data['orderKey'] = $data['order_no'] ?? $data['id'];
        return json(['code' => 0, 'data' => $data]);
    }

    public function getDiscountRules()
    {
        $storeId = $this->request->param('id');
        $list = Db::name('discount_rule')->where('store_id', $storeId)->where('status', 1)->order('id', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    public function preGroupNo()
    {
        $code = $this->request->post('code');
        $storeId = (int)$this->request->post('store_id', 0);
        $tenantId = $this->request->tenantId ?? '88888888';

        if (!$code) return json(['code' => 1, 'msg' => '请输入团购券码']);

        // 检查团购支付是否启用
        $grpEnabled = CacheService::getConfig($tenantId, 'group_pay_enabled', '0');
        if ($grpEnabled !== '1' && $grpEnabled !== 1) {
            return json(['code' => 1, 'msg' => '团购支付未启用，请联系店家']);
        }

        $result = \app\service\GroupBuyService::verify($code, $storeId, $tenantId);
        return json($result);
    }

    /**
     * 获取可用支付方式状态
     */
    public function getPayMethods()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = (int)($this->request->param('store_id') ?: $this->request->param('storeId', 0));

        // 微信支付：开关
        $wxEnabled = CacheService::getConfig($tenantId, 'wx_enabled', '0');
        $wxOn = $wxEnabled === '1' || $wxEnabled === 1;
        // 微信支付配置是否完整（appid + mchid + mchkey）
        $wxConfigOk = false;
        if ($wxOn) {
            $appid = CacheService::getConfig($tenantId, 'wx_appid', '');
            $mchId = CacheService::getConfig($tenantId, 'wx_mch_id', '');
            $mchKey = CacheService::getConfig($tenantId, 'wx_mch_key', '');
            $wxConfigOk = !empty($appid) && !empty($mchId) && !empty($mchKey);
        }

        // 余额支付：开关
        $balEnabled = CacheService::getConfig($tenantId, 'balance_pay_enabled', '0');
        $balOn = $balEnabled === '1' || $balEnabled === 1;

        // 团购支付：开关 + 门店平台授权
        $grpEnabled = CacheService::getConfig($tenantId, 'group_pay_enabled', '0');
        $grpOn = $grpEnabled === '1' || $grpEnabled === 1;
        $grpPlatformOk = false;
        if ($grpOn && $storeId > 0) {
            $store = Db::name('store')->where('id', $storeId)->find();
            if ($store) {
                $meituanOk = !empty($store['meituan_auth']) && !empty($store['meituan_access_token'])
                    && (empty($store['meituan_expire']) || strtotime($store['meituan_expire']) > time());
                $douyinOk = !empty($store['douyin_auth']) && !empty($store['douyin_access_token'])
                    && (empty($store['douyin_expire']) || strtotime($store['douyin_expire']) > time());
                $grpPlatformOk = $meituanOk || $douyinOk;
            }
        }

        return json([
            'code' => 0,
            'data' => [
                'wechat' => ['enabled' => $wxOn, 'config_ok' => $wxConfigOk],
                'balance' => ['enabled' => $balOn],
                'group' => ['enabled' => $grpOn, 'platform_ok' => $grpPlatformOk],
            ],
        ]);
    }


    public function search()
    {
        $userId = $this->request->userId;
        $keyword = $this->request->param('keyword', '');

        // 转义 LIKE 通配符，防止 LIKE 注入
        $keyword = str_replace(['%', '_'], ['\%', '\_'], $keyword);
        if (empty($keyword)) {
            return json(['code' => 0, 'data' => ['list' => []]]);
        }

        $list = OrderModel::where('user_id', $userId)->where('order_no', 'like', '%' . $keyword . '%')->order('id', 'desc')->limit(20)->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function preOrder()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';

        // 黑名单校验
        $blackMsg = \app\service\BlacklistService::check((int)$userId, $tenantId);
        if ($blackMsg) {
            return json(['code' => 1, 'msg' => $blackMsg]);
        }

        $roomId = $this->request->post('room_id');
        $startTime = $this->request->post('start_time');
        $endTime = $this->request->post('end_time');
        $couponId = $this->request->post('coupon_id');
        $userCardId = $this->request->post('user_card_id');
        $pkgId = $this->request->post('pkg_id');
        $payType = $this->request->post('pay_type', 1);
        $preSubmit = $this->request->post('pre_submit', false);
        $wxPay = $this->request->post('wx_pay', false);
        $groupPayNo = $this->request->post('group_pay_no');
        $nightLong = $this->request->post('night_long', false);
        $timeIndex = $this->request->post('time_index', 0);
        $orderId = $this->request->post('order_id') ?: $this->request->post('orderId');

        // 续费场景：有order_id参数
        if ($orderId) {
            return $this->preRenewOrder($userId, $orderId, $endTime, $payType, $pkgId, $couponId);
        }

        // ========== 支付方式配置校验（仅在真正提交支付时校验，纯价格计算不拦截） ==========
        $tenantId = $this->request->tenantId ?? '88888888';
        if ($wxPay) {
            $payMethodError = $this->checkPayMethod($tenantId, (int)$payType, 0, true);
            if ($payMethodError) {
                return json(['code' => 1, 'msg' => $payMethodError]);
            }
        }

        $room = Room::find($roomId);
        if (!$room) return json(['code' => 1, 'msg' => '房间不存在']);

        // 校验房间状态
        $store = \app\model\Store::find($room->store_id);
        $clearOpen = $store && $store->clear_open == 1;  // 待清洁允许预订
        
        // 状态0（禁用）或状态3（维护中）不允许预订
        if ($room->status == 3 || $room->status == 0) {
            return json(['code' => 1, 'msg' => '该房间已停用，请选择其他房间']);
        }
        
        // 状态2（使用中）或状态4（已预约/待清洁）需要检查时间段冲突
        if ($room->status == 2 || $room->status == 4) {
            // 检查是否有时间段冲突的订单
            $conflictOrder = Db::name('order')
                ->where('room_id', $roomId)
                ->whereIn('status', [0, 1])  // 待支付或使用中
                ->where(function($query) use ($startTime, $endTime) {
                    // 时间段有重叠
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                })
                ->find();
                
            if ($conflictOrder) {
                $conflictStart = date('H:i', strtotime($conflictOrder['start_time']));
                $conflictEnd = date('H:i', strtotime($conflictOrder['end_time']));
                return json(['code' => 1, 'msg' => "该时段已被预约（{$conflictStart}-{$conflictEnd}），请选择其他时间"]);
            }
            
            // 状态4且没有冲突订单，检查是否是待清洁状态
            if ($room->status == 4) {
                $hasActiveOrder = Db::name('order')
                    ->where('room_id', $roomId)
                    ->whereIn('status', [0, 1])
                    ->find();
                // 没有活跃订单说明是待清洁状态
                if (!$hasActiveOrder && !$clearOpen) {
                    return json(['code' => 1, 'msg' => '该房间待清洁，请选择其他房间']);
                }
            }
        }
        
        if ($room->status != 1 && $room->status != 2 && $room->status != 4) {
            return json(['code' => 1, 'msg' => '该房间暂不可用，请选择其他房间']);
        }

        $start = strtotime($startTime);
        $end = strtotime($endTime);
        if (!$start || !$end || $end <= $start) {
            return json(['code' => 1, 'msg' => '时间参数不正确']);
        }
        $duration = ($end - $start) / 60;

        // 套餐价格和验证
        if ($pkgId) {
            $pkg = Db::name('package')->where('id', $pkgId)->find();
            if (!$pkg) {
                return json(['code' => 1, 'msg' => '套餐不存在']);
            }
            
            // 验证套餐状态
            if ($pkg['status'] != 1) {
                return json(['code' => 1, 'msg' => '该套餐已下架']);
            }
            
            // 验证套餐适用门店
            if (!empty($pkg['store_id']) && $pkg['store_id'] != $room->store_id) {
                return json(['code' => 1, 'msg' => '该套餐不适用于当前门店']);
            }
            
            // 验证套餐适用房间类别
            if (isset($pkg['room_class']) && !empty($pkg['room_class']) && $pkg['room_class'] != $room->room_class) {
                return json(['code' => 1, 'msg' => '该套餐不适用于当前房间类型']);
            }
            
            // 验证订单时长是否符合套餐设置（允许2分钟误差，因为前端可能有计算误差）
            $pkgDuration = (int)$pkg['duration'];  // 套餐时长（分钟）
            if (abs($duration - $pkgDuration) > 2) {
                $pkgHours = round($pkgDuration / 60, 1);
                return json(['code' => 1, 'msg' => "该套餐时长为 {$pkgHours} 小时，请重新选择"]);
            }
            
            // 验证可用星期
            if (!empty($pkg['enable_week'])) {
                $enableWeek = is_string($pkg['enable_week']) ? json_decode($pkg['enable_week'], true) : $pkg['enable_week'];
                if (is_array($enableWeek) && !empty($enableWeek)) {
                    $orderWeek = (int)date('w', $start);  // 0=周日, 1=周一, ..., 6=周六
                    if (!in_array($orderWeek, $enableWeek)) {
                        $weekNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
                        $enableWeekNames = array_map(function($w) use ($weekNames) {
                            return $weekNames[$w];
                        }, $enableWeek);
                        return json(['code' => 1, 'msg' => '该套餐仅在' . implode('、', $enableWeekNames) . '可用']);
                    }
                }
            }
            
            // 验证可用时段
            if (!empty($pkg['enable_time'])) {
                $enableTime = is_string($pkg['enable_time']) ? json_decode($pkg['enable_time'], true) : $pkg['enable_time'];
                if (is_array($enableTime) && isset($enableTime['start']) && isset($enableTime['end'])) {
                    $startHour = (int)date('H', $start);
                    $startMinute = (int)date('i', $start);
                    $startTimeMinutes = $startHour * 60 + $startMinute;
                    
                    $enableStartParts = explode(':', $enableTime['start']);
                    $enableStartMinutes = (int)$enableStartParts[0] * 60 + (int)($enableStartParts[1] ?? 0);
                    
                    $enableEndParts = explode(':', $enableTime['end']);
                    $enableEndMinutes = (int)$enableEndParts[0] * 60 + (int)($enableEndParts[1] ?? 0);
                    
                    if ($startTimeMinutes < $enableStartMinutes || $startTimeMinutes > $enableEndMinutes) {
                        return json(['code' => 1, 'msg' => "该套餐仅在 {$enableTime['start']} - {$enableTime['end']} 可用"]);
                    }
                }
            }
            
            // 验证余额购买（如果 balance_buy = 0，则不允许余额支付）
            if (isset($pkg['balance_buy']) && $pkg['balance_buy'] == 0 && $payType == 2) {
                return json(['code' => 1, 'msg' => '该套餐不支持余额支付，请使用微信支付']);
            }
            
            $totalAmount = (float)$pkg['price'];
        } elseif ($timeIndex >= 9991 && $timeIndex <= 9994) {
            // 包场价格
            $priceMap = [9991 => 'morning_price', 9992 => 'afternoon_price', 9993 => 'night_price', 9994 => 'tx_price'];
            $field = $priceMap[$timeIndex] ?? null;
            $totalAmount = ($field && $room->$field > 0) ? (float)$room->$field : $room->price * ($duration / 60);
        } else {
            $totalAmount = $room->price * ($duration / 60);
        }

        $discountAmount = 0;
        $couponExtendMinutes = 0; // 加时券延长的分钟数
        $cardDeductAmount = 0;
        $cardDiscountAmount = 0;
        $payAmount = $totalAmount;

        // 优惠券折扣计算
        if ($couponId) {
            $userCoupon = Db::name('user_coupon')->alias('uc')
                ->leftJoin('coupon c', 'uc.coupon_id = c.id')
                ->where('uc.id', $couponId)
                ->where('uc.user_id', $userId)
                ->where('uc.status', 0)
                ->field('uc.*, c.type as coupon_type, c.amount as coupon_amount, c.min_amount, c.store_id as coupon_store_id, c.room_class as coupon_room_class, c.end_time as coupon_end_time')
                ->find();

            if ($userCoupon && strtotime($userCoupon['coupon_end_time']) > time()) {
                $couponType = (int)$userCoupon['coupon_type'];
                $couponAmount = (float)$userCoupon['coupon_amount'];
                $minAmount = (float)$userCoupon['min_amount'];
                $durationHours = $duration / 60;

                if ($couponType == 2) {
                    // 满减券：订单金额 >= min_amount 时，减 coupon_amount 元
                    if ($totalAmount >= $minAmount) {
                        $discountAmount = min($couponAmount, $totalAmount);
                    }
                } elseif ($couponType == 1) {
                    // 抵扣券（时长抵扣）：时长 >= min_amount 小时时，抵扣 coupon_amount 小时的费用
                    if ($durationHours >= $minAmount) {
                        $hourPrice = $duration > 0 ? ($totalAmount / $durationHours) : 0;
                        $discountAmount = min($couponAmount * $hourPrice, $totalAmount);
                    }
                } elseif ($couponType == 3) {
                    // 加时券：免费延长 coupon_amount 小时，不减金额
                    if ($durationHours >= $minAmount) {
                        $couponExtendMinutes = (int)($couponAmount * 60);
                    }
                }
            }
        }

        $payAmount = $totalAmount - $discountAmount;

        // 会员卡抵扣计算（含折扣）
        if ($userCardId) {
            $cardResult = CardService::calculateDeduct((int)$userCardId, $payAmount, $duration);
            if ($cardResult['error']) {
                return json(['code' => 1, 'msg' => $cardResult['error']]);
            }
            $cardDiscountAmount = $cardResult['discount_amount']; // 折扣减免
            $cardDeductAmount = $cardResult['deduct_amount'];     // 卡余额抵扣
            $payAmount = $payAmount - $cardDiscountAmount - $cardDeductAmount;
        }

        // 押金
        $deposit = (float)($room->deposit ?? 0);
        if ($preSubmit && $room->pre_pay_enable) {
            $payAmount = (float)($room->pre_pay_amount ?? 0);
            $deposit = $payAmount;
        }

        // 确保支付金额不为负
        if ($payAmount < 0) $payAmount = 0;

        // 团购免支付
        if ($groupPayNo) {
            $payAmount = 0;
        }

        // 加上押金
        if ($deposit > 0 && !$preSubmit) {
            $payAmount += $deposit;
        }

        // 加时券：延长结束时间
        if ($couponExtendMinutes > 0) {
            $end = $end + $couponExtendMinutes * 60;
            $endTime = date('Y-m-d H:i:s', $end);
            $duration = ($end - $start) / 60;
        }

        $result = [
            'total_amount' => round($totalAmount, 2),
            'discount_amount' => round($discountAmount + ($cardDiscountAmount ?? 0), 2),
            'card_deduct_amount' => round($cardDeductAmount, 2),
            'card_discount_amount' => round($cardDiscountAmount ?? 0, 2),
            'pay_amount' => round($payAmount, 2),
            'deposit' => round($deposit, 2),
            'duration' => $duration,
            'price' => $room->price,
            'end_time' => $endTime,
            'coupon_extend_minutes' => $couponExtendMinutes,
        ];

        // 只有微信支付才需要在preOrder阶段创建订单（微信需要order_no调起支付）
        // 余额支付、团购支付不在此创建订单，由create()方法原子化处理（支付+建单一体）
        $needCreateOrder = $wxPay && $payAmount > 0;
        
        // 免支付订单（金额为0）也在此创建并直接标记已支付
        $isFreeOrder = $payAmount == 0;
        
        if ($needCreateOrder || $isFreeOrder) {
            $tenantId = $this->request->tenantId ?? '88888888';
            
            // 使用事务和悲观锁防止并发下单
            Db::startTrans();
            try {
                // 幂等性检查：查找用户在同一房间、同一时间段的待支付订单（放在事务内）
                $existingOrder = OrderModel::where('user_id', $userId)
                    ->where('room_id', $roomId)
                    ->where('start_time', $startTime)
                    ->where('end_time', $endTime)
                    ->where('status', 0)
                    ->where('create_time', '>', date('Y-m-d H:i:s', time() - 300))
                    ->lock(true) // 加锁防止并发
                    ->find();
                
                if ($existingOrder) {
                    // 复用已存在的订单
                    $orderNo = $existingOrder->order_no;
                    Db::commit();
                } else {
                    // 再次检查房间状态（使用悲观锁）
                    $roomCheck = Db::name('room')->where('id', $roomId)->lock(true)->find();
                    
                    // 允许空闲(1)、使用中(2)、已预约/待清洁(4)的房间下单，但需检查时间冲突
                    $canOrder = false;
                    if ($roomCheck && $roomCheck['status'] == 1) {
                        $canOrder = true;
                    } elseif ($roomCheck && ($roomCheck['status'] == 2 || $roomCheck['status'] == 4)) {
                        // 检查时间段是否冲突
                        $conflictOrder = Db::name('order')
                            ->where('room_id', $roomId)
                            ->whereIn('status', [0, 1])
                            ->where(function($query) use ($startTime, $endTime) {
                                $query->where('start_time', '<', $endTime)
                                      ->where('end_time', '>', $startTime);
                            })
                            ->find();
                        if (!$conflictOrder) {
                            // 状态4且无活跃订单时是待清洁，需要检查clearOpen
                            if ($roomCheck['status'] == 4) {
                                $hasActive = Db::name('order')
                                    ->where('room_id', $roomId)
                                    ->whereIn('status', [0, 1])
                                    ->find();
                                $canOrder = $hasActive || $clearOpen;
                            } else {
                                $canOrder = true;
                            }
                        }
                    }
                    
                    if (!$canOrder) {
                        Db::rollback();
                        return json(['code' => 1, 'msg' => '该房间已被其他用户预订，请选择其他房间']);
                    }
                    
                    // 创建新订单
                    $orderNo = date('YmdHis') . mt_rand(100000, 999999);
                    $orderData = [
                        'tenant_id' => $tenantId,
                        'order_no' => $orderNo,
                        'user_id' => $userId,
                        'store_id' => $room->store_id,
                        'room_id' => $roomId,
                        'order_type' => $pkgId ? 3 : ($groupPayNo ? 2 : ($preSubmit ? 4 : 1)),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'duration' => $duration,
                        'price' => $room->price,
                        'total_amount' => $totalAmount,
                        'discount_amount' => $discountAmount + ($cardDiscountAmount ?? 0),
                        'coupon_discount_amount' => $discountAmount,
                        'card_deduct_amount' => $cardDeductAmount,
                        'pay_amount' => $payAmount,
                        'pay_type' => $payType,
                        'coupon_id' => $couponId ?: null,
                        'user_card_id' => $userCardId ?: null,
                        'pkg_id' => $pkgId ?: null,
                        'group_pay_no' => $groupPayNo ?: null,
                        'status' => 0,
                        'pay_status' => $isFreeOrder ? 1 : 0,
                        'pay_time' => $isFreeOrder ? date('Y-m-d H:i:s') : null,
                    ];
                    OrderModel::create($orderData);
                    
                    // 免支付订单直接处理开门逻辑
                    if ($isFreeOrder) {
                        // 扣减会员卡余额
                        if ($userCardId && $cardDeductAmount > 0) {
                            CardService::deduct((int)$userCardId, (int)$userId, 0, (float)$totalAmount, (float)$duration, (float)$cardDeductAmount);
                        }
                        // 标记优惠券为已使用
                        if ($couponId) {
                            Db::name('user_coupon')->where('id', $couponId)
                                ->where('user_id', $userId)->where('status', 0)
                                ->update(['status' => 1, 'use_time' => date('Y-m-d H:i:s')]);
                        }
                        $isReservation = strtotime($startTime) > time();
                        if ($isReservation) {
                            Db::name('room')->where('id', $roomId)->update(['status' => 4, 'update_time' => date('Y-m-d H:i:s')]);
                        } else {
                            $doorOk = \app\service\DeviceService::openDoor((int)$roomId, $tenantId);
                            if ($doorOk) {
                                // 更新订单为使用中
                                OrderModel::where('order_no', $orderNo)->update(['status' => 1]);
                                Db::name('room')->where('id', $roomId)->update(['status' => 2, 'update_time' => date('Y-m-d H:i:s')]);
                                \app\service\DeviceService::startRoom((int)$roomId, $tenantId);
                            } else {
                                Db::name('room')->where('id', $roomId)->update(['status' => 4, 'update_time' => date('Y-m-d H:i:s')]);
                            }
                        }
                    } else {
                        // 微信支付：锁定房间状态为已预约
                        Db::name('room')->where('id', $roomId)->update(['status' => 4, 'update_time' => date('Y-m-d H:i:s')]);
                    }
                    
                    Db::commit();
                }
            } catch (\Exception $e) {
                Db::rollback();
                \think\facade\Log::error("preOrder创建订单失败: " . $e->getMessage());
                return json(['code' => 1, 'msg' => '下单失败，请重试']);
            }
            
            $result['order_no'] = $orderNo;
            
            // 微信支付需要生成支付参数
            if ($needCreateOrder) {
                $user = User::find($userId);
                $payService = new PayService($tenantId);
                $payResult = $payService->createWechatOrder($orderNo, (float)$payAmount, $user->openid ?? '', '台球订单');
                if ($payResult['code'] == 0) {
                    $payData = $payResult['data'];
                    $result['timeStamp'] = $payData['timeStamp'] ?? '';
                    $result['nonceStr'] = $payData['nonceStr'] ?? '';
                    $result['pkg'] = $payData['package'] ?? '';
                    $result['signType'] = $payData['signType'] ?? '';
                    $result['paySign'] = $payData['paySign'] ?? '';
                } else {
                    return json($payResult);
                }
            }
        }

        // 余额支付、团购支付：返回价格信息，不创建订单
        // 前端拿到价格后调用 /member/order/save 原子化完成 建单+支付
        $result['start_time'] = $startTime;
        $result['end_time'] = $endTime;
        $result['room_id'] = $roomId;
        $result['store_id'] = $room->store_id;
        $result['coupon_id'] = $couponId;
        $result['user_card_id'] = $userCardId;
        $result['pkg_id'] = $pkgId;
        $result['group_pay_no'] = $groupPayNo;

        return json(['code' => 0, 'data' => $result]);
    }

    public function lockOrder()
    {
        $userId = $this->request->userId;
        $roomId = $this->request->post('room_id');
        $startTime = $this->request->post('start_time');
        $endTime = $this->request->post('end_time');

        if (empty($roomId) || empty($startTime) || empty($endTime)) {
            return json(['code' => 1, 'msg' => '参数不完整']);
        }

        // 使用缓存锁定房间时间段，防止并发预订
        $lockKey = 'room_lock:' . $roomId;
        $lockValue = $userId . ':' . time();

        $existingLock = Cache::get($lockKey);
        if ($existingLock && !str_starts_with($existingLock, $userId . ':')) {
            return json(['code' => 1, 'msg' => '房间正在被其他用户预订中，请稍后再试']);
        }

        // 从数据库获取订单超时时间配置，锁定时间与订单超时保持一致
        $config = Db::name('config')
            ->where('config_key', 'order_pay_timeout')
            ->value('config_value');
        $timeout = $config ? (int)$config : 15;
        $lockSeconds = $timeout * 60;

        Cache::set($lockKey, $lockValue, $lockSeconds);
        \think\facade\Log::info("房间锁定: room_id={$roomId}, user_id={$userId}, timeout={$timeout}分钟");

        return json(['code' => 0, 'msg' => '锁定成功', 'data' => ['expire' => $lockSeconds]]);
    }

    public function changeRoom()
    {
        $userId = $this->request->userId;
        $orderId = $this->request->param('orderId') ?: $this->request->post('order_id');
        $newRoomId = $this->request->param('roomId') ?: $this->request->post('room_id');

        \think\facade\Log::info("换房请求: user_id={$userId}, order_id={$orderId}, new_room_id={$newRoomId}");

        $order = OrderModel::where('id', $orderId)->where('user_id', $userId)->where('status', 1)->find();
        if (!$order) {
            \think\facade\Log::warning("换房失败: 订单不存在或状态不正确, order_id={$orderId}");
            return json(['code' => 1, 'msg' => '订单不存在或状态不正确']);
        }

        $newRoom = Room::find($newRoomId);
        if (!$newRoom) {
            return json(['code' => 1, 'msg' => '目标房间不存在']);
        }
        if ($newRoom->status != 1) {
            return json(['code' => 1, 'msg' => '目标房间已被占用']);
        }

        Db::startTrans();
        try {
            $oldRoomId = $order->room_id;
            Room::where('id', $oldRoomId)->update(['status' => 1]);
            Room::where('id', $newRoomId)->update(['status' => 2]);
            OrderModel::where('id', $orderId)->update(['room_id' => $newRoomId]);
            Db::commit();
            \think\facade\Log::info("换房成功: order_id={$orderId}, old_room={$oldRoomId}, new_room={$newRoomId}");
            return json(['code' => 0, 'msg' => '换房成功']);
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error("换房异常: order_id={$orderId}, error=" . $e->getMessage());
            return json(['code' => 1, 'msg' => '换房失败，请稍后重试']);
        }
    }

    public function getRoomInfo()
    {
        $roomId = $this->request->param('id') ?: $this->request->param('room_id');
        $room = Room::find($roomId);
        if (!$room) return json(['code' => 1, 'msg' => '房间不存在']);

        $data = $room->toArray();
        
        // 图片字段处理（前端使用imageUrls，期望逗号分隔格式）
        $images = $data['images'] ?? '';
        if ($images) {
            $decoded = json_decode($images, true);
            if (is_array($decoded)) {
                $images = implode(',', array_filter($decoded));
            }
        }
        $data['imageUrls'] = $images;
        $data['image_urls'] = $images;

        // 获取该房间当前已预订的时间段
        $now = date('Y-m-d H:i:s');
        $orders = Db::name('order')
            ->where('room_id', $roomId)
            ->where('status', 'in', [0, 1])
            ->where('end_time', '>', $now)
            ->field('start_time, end_time')
            ->select()
            ->toArray();

        $orderTimeList = [];
        foreach ($orders as $o) {
            $orderTimeList[] = [
                'start_time' => $o['start_time'],
                'end_time' => $o['end_time'],
            ];
        }
        $data['order_time_list'] = $orderTimeList;

        // 时间段占用情况（今天+明天共48小时）
        $timeSlot = [];
        $todayStart = strtotime(date('Y-m-d'));
        for ($day = 0; $day < 2; $day++) {
            $dayStart = $todayStart + $day * 86400;
            for ($h = 0; $h < 24; $h++) {
                $slotStart = $dayStart + $h * 3600;
                $slotEnd = $slotStart + 3600;
                $disabled = false;
                foreach ($orders as $o) {
                    $os = strtotime($o['start_time']);
                    $oe = strtotime($o['end_time']);
                    if ($slotStart < $oe && $slotEnd > $os) {
                        $disabled = true;
                        break;
                    }
                }
                $timeSlot[] = ['hour' => str_pad((string)$h, 2, '0', STR_PAD_LEFT), 'disable' => $disabled];
            }
        }
        $data['time_slot'] = $timeSlot;
        $data['min_hour'] = $room->min_hour ?? 1;

        // 门店信息
        $store = Db::name('store')->where('id', $room->store_id)->find();
        if ($store) {
            $data['store_name'] = $store['name'] ?? '';
        }

        // 时间选择列表（今天和明天）
        $data['time_select_lists'] = [
            ['name' => '今天', 'select_list' => []],
            ['name' => '明天', 'select_list' => []],
        ];

        return json(['code' => 0, 'data' => $data]);
    }

    public function managerSubmitOrder() { return $this->create(); }
}
