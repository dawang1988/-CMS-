<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use app\service\RedisService;
use think\facade\Cache;
use think\facade\Db;

class Member extends BaseController
{
    public function sendCode()
    {
        $mobile = $this->request->post('mobile');
        if (empty($mobile)) {
            return json(['code' => 1, 'msg' => '手机号不能为空']);
        }

        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return json(['code' => 1, 'msg' => '手机号格式不正确']);
        }

        // 发送频率限制：同一手机号60秒内只能发一次
        $rateLimitKey = 'sms_rate:' . $mobile;
        if (Cache::get($rateLimitKey)) {
            return json(['code' => 1, 'msg' => '验证码发送过于频繁，请60秒后再试']);
        }

        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        Cache::set('sms_code:' . $mobile, $code, 300);
        Cache::set($rateLimitKey, 1, 60);

        $smsService = new \app\service\SmsService($this->request->tenantId ?? '88888888');
        $smsService->sendCode($mobile, (string)$code);

        return json(['code' => 0, 'msg' => '验证码已发送']);
    }

    public function bindPhone()
    {
        $userId = $this->request->userId;
        $mobile = $this->request->post('mobile');
        $code = $this->request->post('code');

        $cachedCode = Cache::get('sms_code:' . $mobile);
        if ($cachedCode !== $code) {
            return json(['code' => 1, 'msg' => '验证码错误']);
        }

        User::where('id', $userId)->update(['phone' => $mobile]);
        Cache::delete('sms_code:' . $mobile);

        return json(['code' => 0, 'msg' => '绑定成功']);
    }

    public function profile()
    {
        $userId = $this->request->userId;
        $user = User::find($userId);

        return json(['code' => 0, 'data' => [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'balance' => $user->balance,
        ]]);
    }

    public function updateProfile()
    {
        $userId = $this->request->userId;
        $nickname = $this->request->post('nickname');
        $avatar = $this->request->post('avatar');

        $updateData = [];
        if ($nickname !== null) $updateData['nickname'] = $nickname;
        if ($avatar !== null) $updateData['avatar'] = $avatar;

        if (!empty($updateData)) {
            User::where('id', $userId)->update($updateData);
        }

        return json(['code' => 0, 'msg' => '更新成功']);
    }

    // ========== 路由别名方法 ==========

    public function login() { return $this->profile(); }

    public function info()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';

        $cacheKey = 'user:info:' . $tenantId . ':' . $userId;

        $data = RedisService::remember($cacheKey, 1800, function() use ($userId, $tenantId) {
            $user = User::find($userId);
            if (!$user) return null;

            $data = $user->toArray();

            try {
                $storeUser = Db::name('store_user')
                    ->where('user_id', $userId)
                    ->where('user_type', 'in', [12, 13, 14])
                    ->find();
                if ($storeUser) {
                    $data['user_type'] = $storeUser['user_type'];
                    if (!empty($storeUser['store_id'])) {
                        $data['store_id'] = $storeUser['store_id'];
                    }
                }
            } catch (\Exception $e) {
            }

            $storeId = $data['store_id'] ?? 0;
            $vipLevel = $data['vip_level'] ?? 0;
            if ($vipLevel > 0) {
                $vipConfig = Db::name('vip_config')
                    ->where('store_id', $storeId)
                    ->where('vip_level', $vipLevel)
                    ->where('status', 1)
                    ->find();
                $data['vip_discount'] = $vipConfig['vip_discount'] ?? 100;

                $nextVip = Db::name('vip_config')
                    ->where('store_id', $storeId)
                    ->where('vip_level', '>', $vipLevel)
                    ->where('status', 1)
                    ->order('vip_level', 'asc')
                    ->find();
                if ($nextVip) {
                    $data['next_vip_name'] = $nextVip['vip_name'];
                    $data['next_vip_score'] = $nextVip['score'];
                }
            } else {
                $data['vip_discount'] = 100;
            }

            $data['couponCount'] = Db::name('user_coupon')
                ->where('user_id', $userId)
                ->where('status', 0)
                ->count();

            return $data;
        });

        if (!$data) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        return json(['code' => 0, 'data' => $data]);
    }

    public function update() { return $this->updateProfile(); }

    public function updateMobile() { return $this->bindPhone(); }

    public function updateAvatar()
    {
        $userId = $this->request->userId;
        $avatar = $this->request->post('avatar');
        if ($avatar) User::where('id', $userId)->update(['avatar' => $avatar]);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function updateNickname()
    {
        $userId = $this->request->userId;
        $nickname = $this->request->post('nickname');
        if ($nickname) User::where('id', $userId)->update(['nickname' => $nickname]);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function getCouponPage()
    {
        $userId = $this->request->userId;
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 10);
        $status = $this->request->param('status');
        $roomId = $this->request->param('room_id');
        $nightLong = $this->request->param('nightLong');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $storeId = $this->request->param('store_id');

        $query = Db::name('user_coupon')->alias('uc')
            ->leftJoin('coupon c', 'uc.coupon_id = c.id')
            ->leftJoin('store s', 'c.store_id = s.id')
            ->where('uc.user_id', $userId)
            ->field('uc.id, uc.user_id, uc.coupon_id, uc.status, uc.use_time, uc.order_id, uc.create_time, c.name, c.type, c.amount, c.min_amount, c.end_time, c.store_id, s.name as store_name, c.room_class');
        if ($status !== null && $status !== '') $query->where('uc.status', $status);

        $total = (clone $query)->count();
        $list = $query->order('uc.id', 'desc')->page($pageNo, $pageSize)->select()->toArray();

        // 获取房间信息用于 room_class 匹配
        $roomClass = null;
        $orderAmount = 0;
        if ($roomId) {
            $room = Db::name('room')->where('id', $roomId)->find();
            if ($room) {
                $roomClass = $room['room_class'] ?? null;
                // 计算订单金额用于门槛判断
                if ($startTime && $endTime) {
                    $s = strtotime($startTime);
                    $e = strtotime($endTime);
                    if ($s && $e && $e > $s) {
                        $durationHours = ($e - $s) / 3600;
                        $orderAmount = (float)$room['price'] * $durationHours;
                    }
                }
            }
        }

        // enable 标记：综合判断是否可用
        foreach ($list as &$item) {
            $enable = ($item['status'] == 0);

            if ($enable) {
                // 检查优惠券是否过期
                if ($item['end_time'] && strtotime($item['end_time']) < time()) {
                    $enable = false;
                }
                // 检查门店限制
                if ($enable && $storeId && $item['store_id'] && $item['store_id'] != $storeId) {
                    $enable = false;
                }
                // 检查业态（room_class）限制：coupon.room_class 为 null 或 0 表示不限
                if ($enable && $roomClass !== null && $item['room_class'] && $item['room_class'] != $roomClass) {
                    $enable = false;
                }
                // 检查使用门槛
                if ($enable && $item['min_amount'] > 0) {
                    $couponType = (int)$item['type'];
                    if ($couponType == 2) {
                        // 满减券：订单金额需 >= min_amount
                        if ($orderAmount > 0 && $orderAmount < $item['min_amount']) {
                            $enable = false;
                        }
                    } elseif ($couponType == 1 || $couponType == 3) {
                        // 抵扣券/加时券：时长需 >= min_amount 小时
                        if ($startTime && $endTime) {
                            $durationHours = (strtotime($endTime) - strtotime($startTime)) / 3600;
                            if ($durationHours < $item['min_amount']) {
                                $enable = false;
                            }
                        }
                    }
                }
            }

            $item['enable'] = $enable;
        }
        unset($item);

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function getMoneyBillPage()
    {
        $userId = $this->request->userId;
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 10);
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');

        $query = Db::name('balance_log')->where('user_id', $userId);
        if ($startTime) $query->where('create_time', '>=', $startTime . ' 00:00:00');
        if ($endTime) $query->where('create_time', '<=', $endTime . ' 23:59:59');

        $total = (clone $query)->count();
        $list = $query->order('id', 'desc')->page($pageNo, $pageSize)->select()->toArray();

        // 返回数据库原始字段: type, amount, balance_before, balance_after, remark, create_time
        $user = User::find($userId);
        $balance = $user ? $user->balance : 0;

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total, 'balance' => $balance]]);
    }

    public function getGiftBalanceList()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        
        // 获取用户在各门店的余额
        $list = \app\service\StoreBalanceService::getAllStoreBalance((int)$userId, $tenantId);
        
        // 如果没有任何门店余额，返回空列表
        if (empty($list)) {
            return json(['code' => 0, 'data' => []]);
        }
        
        return json(['code' => 0, 'data' => $list]);
    }

    public function getStoreBalance()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('id') ?: $this->request->param('storeId');
        
        if (!$storeId) {
            return json(['code' => 1, 'msg' => '门店ID不能为空']);
        }
        
        // 获取用户在指定门店的余额
        $balanceData = \app\service\StoreBalanceService::getBalance((int)$userId, (int)$storeId, $tenantId);
        
        $data = [
            'balance' => $balanceData['balance'],
            'gift_balance' => $balanceData['gift_balance'],
            'total_balance' => $balanceData['total_balance'],
        ];

        // 查询用户VIP折扣
        $user = User::find($userId);
        if ($user && $user->vip_level > 0 && $storeId) {
            $vipConfig = Db::name('vip_config')
                ->where('store_id', 'in', [$storeId, 0])
                ->where('vip_level', $user->vip_level)
                ->where('status', 1)
                ->find();
            if ($vipConfig) {
                $data['vip_discount'] = (int)$vipConfig['vip_discount'];
                $data['vip_name'] = $vipConfig['vip_name'];
            }
        }

        return json(['code' => 0, 'data' => $data]);
    }

    public function preRechargeBalance()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->post('storeId') ?: $this->request->post('store_id');
        $amount = $this->request->post('amount');
        // 兼容前端传分的情况（price字段，单位分）
        if (!$amount) {
            $price = $this->request->post('price');
            if ($price && $price > 0) {
                $amount = $price / 100;
            }
        }

        if (!$amount || $amount <= 0) return json(['code' => 1, 'msg' => '请输入充值金额']);

        // 查询匹配的充值规则，获取赠送金额
        $giftAmount = 0;
        $rule = Db::name('discount_rule')
            ->where('store_id', $storeId)
            ->where('pay_money', $amount)
            ->where('status', 1)
            ->find();
        if ($rule) {
            $giftAmount = (float)($rule['gift_money'] ?? 0);
        }

        $user = User::find($userId);
        $payService = new \app\service\PayService($tenantId);
        $orderNo = 'RCH' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        Db::name('recharge_order')->insert([
            'tenant_id' => $tenantId, 'recharge_no' => $orderNo, 'user_id' => $userId,
            'store_id' => $storeId, 'amount' => $amount, 'gift_amount' => $giftAmount,
            'status' => 0, 'create_time' => date('Y-m-d H:i:s'),
        ]);

        $result = $payService->createWechatOrder($orderNo, (float)$amount, $user->openid ?? '', '余额充值');
        return json($result);
    }

    public function rechargeBalance()
    {
        return $this->preRechargeBalance();
    }
    
    /**
     * 取消充值订单
     */
    public function cancelRechargeOrder()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderNo = $this->request->post('order_no');
        
        if (!$orderNo) {
            return json(['code' => 1, 'msg' => '订单号不能为空']);
        }
        
        // 查询订单
        $order = Db::name('recharge_order')
            ->where('recharge_no', $orderNo)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->find();
        
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }
        
        // 只有待支付的订单才能取消
        if ($order['status'] != 0) {
            return json(['code' => 1, 'msg' => '订单状态不允许取消']);
        }
        
        // 更新订单状态为已取消
        Db::name('recharge_order')
            ->where('id', $order['id'])
            ->update([
                'status' => 2,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        
        return json(['code' => 0, 'msg' => '订单已取消']);
    }
    
    /**
     * 取消会员卡购买订单
     */
    public function cancelCardOrder()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $orderNo = $this->request->post('order_no');
        
        if (!$orderNo) {
            return json(['code' => 1, 'msg' => '订单号不能为空']);
        }
        
        // 查询订单
        $order = Db::name('card_order')
            ->where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->find();
        
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }
        
        // 只有待支付的订单才能取消
        if ($order['status'] != 0) {
            return json(['code' => 1, 'msg' => '订单状态不允许取消']);
        }
        
        // 更新订单状态为已取消
        Db::name('card_order')
            ->where('id', $order['id'])
            ->update([
                'status' => 2,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        
        return json(['code' => 0, 'msg' => '订单已取消']);
    }

    public function getFranchiseInfo()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $franchise = Db::name('config')->where('tenant_id', $tenantId)->where('config_key', 'franchise_phone')->value('config_value') ?: '';
        $apply = Db::name('franchise')->where('user_id', $userId)->find();
        return json(['code' => 0, 'data' => ['franchise' => $franchise, 'isCommit' => !empty($apply)]]);
    }

    public function saveFranchiseInfo()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';

        // 只允许白名单字段，防止任意字段注入
        $allowedFields = ['name', 'contact_phone', 'city', 'address', 'budget', 'experience', 'remark'];
        $data = [];
        foreach ($allowedFields as $field) {
            $value = $this->request->post($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        if (empty($data['name']) || empty($data['contact_phone'])) {
            return json(['code' => 1, 'msg' => '姓名和电话不能为空']);
        }

        $data['tenant_id'] = $tenantId;
        $data['user_id'] = $userId;
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('franchise')->insert($data);
        return json(['code' => 0, 'data' => true]);
    }

    public function getVipInfo()
    {
        $userId = $this->request->userId;
        $user = User::find($userId);
        return json(['code' => 0, 'data' => [
            'score' => $user->score ?? 0,
            'vip_level' => $user->vip_level ?? 0,
            'vip_name' => $user->vip_name ?? '普通会员',
        ]]);
    }
}
