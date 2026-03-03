<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog;
use app\service\DeviceService;
use think\facade\Db;

class Order extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $status = $this->request->param('status');
        $storeId = $this->request->param('store_id') ?: $this->request->param('storeId');
        
        $query = Db::name('order')->where('tenant_id', $tenantId);
        if ($status !== '' && $status !== null) $query->where('status', $status);
        if ($storeId) $query->where('store_id', $storeId);
        
        $total = $query->count();
        $list = Db::name('order')->where('tenant_id', $tenantId)
            ->when($status !== '' && $status !== null, function($q) use ($status) { $q->where('status', $status); })
            ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
            ->order('id', 'desc')->page($page, $pageSize)->select()->toArray();
        
        $userIds = array_unique(array_column($list, 'user_id'));
        $storeIds = array_unique(array_column($list, 'store_id'));
        $roomIds = array_unique(array_column($list, 'room_id'));
        $couponIds = array_unique(array_filter(array_column($list, 'coupon_id')));
        $users = $userIds ? Db::name('user')->where('tenant_id', $tenantId)->whereIn('id', $userIds)->column('*', 'id') : [];
        $stores = $storeIds ? Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('*', 'id') : [];
        $rooms = $roomIds ? Db::name('room')->where('tenant_id', $tenantId)->whereIn('id', $roomIds)->column('*', 'id') : [];
        // 批量查询优惠券信息
        $coupons = [];
        if ($couponIds) {
            $ucList = Db::name('user_coupon')->alias('uc')
                ->leftJoin('coupon c', 'uc.coupon_id = c.id')
                ->where('uc.tenant_id', $tenantId)
                ->whereIn('uc.id', $couponIds)
                ->field('uc.id, c.name, c.type, c.amount')
                ->select()->toArray();
            foreach ($ucList as $uc) {
                $coupons[$uc['id']] = $uc;
            }
        }
        foreach ($list as &$item) {
            $item['user'] = $users[$item['user_id']] ?? null;
            $item['store'] = $stores[$item['store_id']] ?? null;
            $item['room'] = $rooms[$item['room_id']] ?? null;
            $item['coupon'] = $coupons[$item['coupon_id'] ?? 0] ?? null;
        }
        
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $id = $this->request->param('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $order = Db::name('order')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($order) {
            $order['user'] = Db::name('user')->where('tenant_id', $tenantId)->where('id', $order['user_id'])->find();
            $order['store'] = Db::name('store')->where('tenant_id', $tenantId)->where('id', $order['store_id'])->find();
            $order['room'] = Db::name('room')->where('tenant_id', $tenantId)->where('id', $order['room_id'])->find();
            // 优惠券信息
            if (!empty($order['coupon_id'])) {
                $order['coupon'] = Db::name('user_coupon')->alias('uc')
                    ->leftJoin('coupon c', 'uc.coupon_id = c.id')
                    ->where('uc.id', $order['coupon_id'])
                    ->where('uc.tenant_id', $tenantId)
                    ->field('uc.id, uc.status as use_status, uc.use_time, c.name, c.type, c.amount, c.min_amount')
                    ->find();
            }
        }
        return json(['code' => 0, 'data' => $order]);
    }

    public function refund()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $order = Db::name('order')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }
        
        // 检查订单状态，已退款的不能重复退款
        if ($order['status'] == 4) {
            return json(['code' => 1, 'msg' => '订单已退款，请勿重复操作']);
        }
        
        $payAmount = $order['pay_amount'] ?? 0;
        $payType = $order['pay_type'] ?? 'balance'; // balance=余额支付, wechat=微信支付
        
        Db::startTrans();
        try {
            // 先执行退款操作
            $refundType = '';
            $refundRemark = '';
            $refundTransactionId = '';
            
            if ($payAmount > 0) {
                if ($payType === 'wechat' || $payType == 1) {
                    // 微信支付：先尝试微信退款
                    $refundResult = $this->wechatRefund($order);
                    if ($refundResult['success']) {
                        $refundType = 'wechat';
                        $refundTransactionId = $refundResult['refund_id'] ?? '';
                    } else {
                        // 微信退款失败，退回到门店余额
                        \app\service\StoreBalanceService::refund(
                            $order['user_id'],
                            $order['store_id'],
                            $payAmount,
                            $tenantId,
                            '微信退款失败，退回余额',
                            $id
                        );
                        $refundType = 'balance';
                        $refundRemark = '微信退款失败，已退至该门店余额';
                    }
                } else {
                    // 余额支付：退回到订单门店余额
                    \app\service\StoreBalanceService::refund(
                        $order['user_id'],
                        $order['store_id'],
                        $payAmount,
                        $tenantId,
                        '订单退款',
                        $id
                    );
                    $refundType = 'balance';
                }
            }
            
            // 处理团购券退款
            if (!empty($order['group_pay_no'])) {
                \app\service\GroupBuyService::refund($id, $tenantId);
            }
            
            // 退款操作完成后，再更新订单状态
            Db::name('order')->where('tenant_id', $tenantId)->where('id', $id)->update([
                'status' => 4, 
                'refund_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
                'refund_type' => $refundType ?: null,
                'refund_remark' => $refundRemark ?: null,
                'refund_transaction_id' => $refundTransactionId ?: null,
            ]);
            
            // 释放房间
            if ($order['room_id']) {
                Db::name('room')->where('tenant_id', $tenantId)->where('id', $order['room_id'])->where('status', 2)->update([
                    'status' => 1, 
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            }
            
            Db::commit();
            
            // 记录操作日志
            AdminLog::log(
                AdminLog::MODULE_ORDER,
                AdminLog::TYPE_UPDATE,
                "订单退款：{$order['order_no']}，金额：{$payAmount}元",
                ['order_no' => $order['order_no'], 'pay_amount' => $payAmount],
                $id
            );
            
            return json(['code' => 0, 'msg' => '退款成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '退款失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 微信退款（需要配置微信支付证书）
     */
    private function wechatRefund($order)
    {
        // 检查是否有微信支付交易号
        if (empty($order['transaction_id']) && empty($order['out_trade_no'])) {
            return ['success' => false, 'msg' => '缺少微信支付交易号'];
        }
        
        try {
            // 获取微信支付配置
            $config = config('wechat.payment', []);
            if (empty($config['mch_id']) || empty($config['key'])) {
                return ['success' => false, 'msg' => '微信支付未配置'];
            }
            
            // 构建退款请求参数
            $params = [
                'appid' => $config['app_id'] ?? '',
                'mch_id' => $config['mch_id'],
                'nonce_str' => md5(uniqid()),
                'out_trade_no' => $order['out_trade_no'] ?? $order['order_no'],
                'out_refund_no' => 'RF' . date('YmdHis') . rand(1000, 9999),
                'total_fee' => intval($order['pay_amount'] * 100),
                'refund_fee' => intval($order['pay_amount'] * 100),
            ];
            
            // 生成签名
            ksort($params);
            $signStr = '';
            foreach ($params as $k => $v) {
                if ($v !== '') $signStr .= "{$k}={$v}&";
            }
            $signStr .= "key={$config['key']}";
            $params['sign'] = strtoupper(md5($signStr));
            
            // 转换为XML
            $xml = '<xml>';
            foreach ($params as $k => $v) {
                $xml .= "<{$k}>{$v}</{$k}>";
            }
            $xml .= '</xml>';
            
            // 发送退款请求（需要证书）
            $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            // 设置证书路径
            $certPath = $config['cert_path'] ?? '';
            $keyPath = $config['key_path'] ?? '';
            if ($certPath && $keyPath && file_exists($certPath) && file_exists($keyPath)) {
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLKEY, $keyPath);
            } else {
                curl_close($ch);
                return ['success' => false, 'msg' => '微信支付证书未配置'];
            }
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            // 解析响应
            if ($response) {
                $result = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($result && (string)$result->return_code === 'SUCCESS' && (string)$result->result_code === 'SUCCESS') {
                    return [
                        'success' => true,
                        'refund_id' => (string)$result->refund_id
                    ];
                }
                return ['success' => false, 'msg' => (string)($result->err_code_des ?? '退款失败')];
            }
            
            return ['success' => false, 'msg' => '请求微信退款接口失败'];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function renew()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id') ?: $this->request->post('order_id');
        $addTime = (int)($this->request->post('add_time') ?: $this->request->post('duration', 60));
        $order = Db::name('order')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在']);
        $newEnd = date('Y-m-d H:i:s', strtotime($order['end_time']) + $addTime * 60);
        
        // 查找同房间下一个有效订单
        $nextOrder = Db::name('order')
            ->where('room_id', $order['room_id'])
            ->where('id', '<>', $id)
            ->whereIn('status', [0, 1])
            ->where('start_time', '>', $order['end_time'])
            ->where('tenant_id', $tenantId)
            ->order('start_time', 'asc')
            ->find();
        
        if ($nextOrder) {
            $gapMinutes = (strtotime($nextOrder['start_time']) - strtotime($order['end_time'])) / 60;
            // 间隔不足1小时，不允许续时
            if ($gapMinutes < 60) {
                $nextStart = date('H:i', strtotime($nextOrder['start_time']));
                return json(['code' => 1, 'msg' => "距下一个预约（{$nextStart}）不足1小时，无法续时"]);
            }
            // 续时后时间与下一个订单冲突
            if (strtotime($newEnd) > strtotime($nextOrder['start_time'])) {
                $maxEnd = date('H:i', strtotime($nextOrder['start_time']));
                return json(['code' => 1, 'msg' => "续时与后续预约冲突，最多可续到{$maxEnd}"]);
            }
        }
        
        Db::name('order')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'end_time' => $newEnd,
            'duration' => ($order['duration'] ?? 0) + $addTime,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_ORDER,
            AdminLog::TYPE_UPDATE,
            "订单续时：{$order['order_no']}，增加{$addTime}分钟",
            ['order_no' => $order['order_no'], 'add_time' => $addTime],
            $id
        );
        
        return json(['code' => 0, 'msg' => '续时成功']);
    }

    public function cancel()
    {
        $id = $this->request->param('id') ?: $this->request->post('id') ?: $this->request->post('order_id');
        return $this->doClose($id);
    }

    public function close()
    {
        $id = $this->request->param('id') ?: $this->request->post('id') ?: $this->request->post('order_id');
        return $this->doClose($id);
    }

    private function doClose($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        if (!$id) return json(['code' => 1, 'msg' => '缺少订单ID']);
        $order = Db::name('order')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在']);
        
        // 如果订单在使用中，先关闭设备
        if ($order['status'] == 1 && $order['room_id']) {
            $stopResult = DeviceService::stopRoom((int)$order['room_id'], $tenantId);
            if (!$stopResult) {
                \think\facade\Log::warning("关闭订单但设备关闭失败: order_id={$id}, room_id={$order['room_id']}");
            }
        }
        
        // 设备操作完成后再改状态
        Db::name('order')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 3, 'update_time' => date('Y-m-d H:i:s')]);
        // 释放房间
        if ($order['room_id']) {
            Db::name('room')->where('tenant_id', $tenantId)->where('id', $order['room_id'])->whereIn('status', [2, 4])->update(['status' => 1, 'update_time' => date('Y-m-d H:i:s')]);
        }
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_ORDER,
            AdminLog::TYPE_UPDATE,
            "关闭订单：{$order['order_no']}",
            ['order_no' => $order['order_no']],
            $id
        );
        
        return json(['code' => 0, 'msg' => '订单已关闭']);
    }

    public function availableRooms()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderId = $this->request->param('order_id');
        $storeId = $this->request->param('store_id');

        // 如果传了 order_id，根据订单找到门店
        $currentRoomId = null;
        if ($orderId) {
            $order = Db::name('order')->where('tenant_id', $tenantId)->where('id', $orderId)->find();
            if ($order) {
                $storeId = $order['store_id'];
                $currentRoomId = $order['room_id'];
            }
        }

        $query = Db::name('room')->where('tenant_id', $tenantId);
        if ($storeId) $query->where('store_id', $storeId);
        $rooms = $query->order('sort', 'asc')->select()->toArray();

        // 标记当前房间和可用状态
        $result = [];
        foreach ($rooms as $room) {
            $result[] = [
                'id' => $room['id'],
                'name' => $room['name'],
                'price' => $room['price'],
                'status' => $room['status'],
                'available' => $room['status'] == 1,
                'is_current' => $currentRoomId && $room['id'] == $currentRoomId,
            ];
        }

        return json(['code' => 0, 'data' => $result]);
    }

    public function changeRoom()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderId = $this->request->post('order_id') ?: $this->request->post('id');
        $newRoomId = $this->request->post('new_room_id') ?: $this->request->post('room_id');
        if (!$orderId || !$newRoomId) return json(['code' => 1, 'msg' => '参数不完整']);

        $order = Db::name('order')->where('tenant_id', $tenantId)->where('id', $orderId)->find();
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在']);

        $newRoom = Db::name('room')->where('tenant_id', $tenantId)->where('id', $newRoomId)->find();
        if (!$newRoom || $newRoom['status'] != 1) return json(['code' => 1, 'msg' => '目标房间不可用']);

        Db::startTrans();
        try {
            // 悲观锁锁定新房间，防止并发
            $lockedRoom = Db::name('room')->where('tenant_id', $tenantId)->where('id', $newRoomId)->lock(true)->find();
            if (!$lockedRoom || $lockedRoom['status'] != 1) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '目标房间已被占用，请选择其他房间']);
            }
            
            // 检查新房间是否有时间冲突的订单
            $conflictOrder = Db::name('order')
                ->where('tenant_id', $tenantId)
                ->where('room_id', $newRoomId)
                ->where('id', '<>', $orderId)
                ->whereIn('status', [0, 1])
                ->where('tenant_id', $tenantId)
                ->where('start_time', '<', $order['end_time'])
                ->where('end_time', '>', $order['start_time'])
                ->find();

            if ($conflictOrder) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '目标房间在该时段已有预约']);
            }
            
            // 占用新房间
            Db::name('room')->where('tenant_id', $tenantId)->where('id', $newRoomId)->update(['status' => 2, 'update_time' => date('Y-m-d H:i:s')]);
            // 更新订单
            Db::name('order')->where('tenant_id', $tenantId)->where('id', $orderId)->update(['room_id' => $newRoomId, 'update_time' => date('Y-m-d H:i:s')]);
            // 释放旧房间
            if ($order['room_id']) {
                Db::name('room')->where('tenant_id', $tenantId)->where('id', $order['room_id'])->update(['status' => 1, 'update_time' => date('Y-m-d H:i:s')]);
            }
            Db::commit();
            
            // 记录操作日志
            AdminLog::log(
                AdminLog::MODULE_ORDER,
                AdminLog::TYPE_UPDATE,
                "订单换房：{$order['order_no']}，从房间ID:{$order['room_id']}换至{$newRoomId}",
                ['order_no' => $order['order_no'], 'old_room_id' => $order['room_id'], 'new_room_id' => $newRoomId],
                $orderId
            );
            
            return json(['code' => 0, 'msg' => '换房成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '换房失败']);
        }
    }
}