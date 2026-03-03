<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use think\facade\Db;

class ProductOrder extends BaseController
{
    public function create()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->post('store_id');
        $roomId = $this->request->post('room_id');
        // 兼容前端两种参数名: productInfo 和 products
        $products = $this->request->post('products', []);
        if (empty($products)) {
            $products = $this->request->post('productInfo', []);
        }
        $mark = $this->request->post('mark', '');
        $payType = (int)$this->request->post('pay_type', 1);
        $pickupType = $this->request->post('pickup_type', 1);

        if (empty($products)) {
            return json(['code' => 1, 'msg' => '请选择商品']);
        }

        // 计算总金额，兼容 quantity 和 number 字段
        $totalAmount = 0;
        $productList = [];
        foreach ($products as $item) {
            $qty = $item['quantity'] ?? $item['number'] ?? 1;
            $price = $item['price'] ?? 0;
            // 如果有商品ID，从数据库获取最新价格
            if (!empty($item['id'])) {
                $product = Db::name('product')->find($item['id']);
                if ($product) {
                    $price = $product['price'];
                }
            }
            $totalAmount += $price * $qty;
            // 统一商品信息格式
            $productList[] = [
                'id' => $item['id'] ?? 0,
                'name' => $item['name'] ?? '',
                'image' => $item['image'] ?? '',
                'price' => $price,
                'number' => $qty,
                'spec' => $item['spec'] ?? ''
            ];
        }

        $orderNo = 'P' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // 默认未支付
        $status = 0;
        $payTime = null;

        // 先创建订单
        $orderId = Db::name('product_order')->insertGetId([
            'tenant_id' => $tenantId,
            'order_no' => $orderNo,
            'user_id' => $userId,
            'store_id' => $storeId,
            'room_id' => $roomId,
            'product_info' => json_encode($productList),
            'total_amount' => $totalAmount,
            'pay_amount' => $totalAmount,
            'status' => $status,
            'mark' => $mark,
            'pay_type' => $payType,
            'pay_time' => $payTime,
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        // 微信支付
        if ($payType == 1) {
            $user = User::find($userId);
            $openid = $user->openid ?? '';
            
            if (empty($openid)) {
                return json(['code' => 1, 'msg' => '用户未授权微信登录']);
            }
            
            $payService = new \app\service\PayService($tenantId);
            $payResult = $payService->createWechatOrder($orderNo, (float)$totalAmount, $openid, '商品购买');
            
            if ($payResult['code'] == 0 && isset($payResult['data'])) {
                return json([
                    'code' => 0,
                    'msg' => '订单创建成功',
                    'data' => [
                        'order_id' => $orderId,
                        'order_no' => $orderNo,
                        'pay_amount' => $totalAmount,
                        'payInfo' => $payResult['data']
                    ]
                ]);
            } else {
                return json(['code' => 1, 'msg' => $payResult['msg'] ?? '创建支付订单失败']);
            }
        }

        return json([
            'code' => 0, 
            'msg' => '订单创建成功',
            'data' => [
                'order_id' => $orderId, 
                'order_no' => $orderNo, 
                'pay_amount' => $totalAmount,
                'status' => $status
            ]
        ]);
    }

    public function list()
    {
        $userId = $this->request->userId;
        $status = $this->request->param('status');

        $query = Db::name('product_order')->where('user_id', $userId);
        if ($status !== null && $status !== '') $query->where('status', $status);

        $list = $query->order('id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function detail()
    {
        $userId = $this->request->userId;
        $id = $this->request->param('id');

        $order = Db::name('product_order')->where('id', $id)->where('user_id', $userId)->find();
        return json(['code' => 0, 'data' => $order]);
    }

    public function page()
    {
        $userId = $this->request->userId;
        $status = $this->request->param('status');
        $statusList = $this->request->param('statusList'); // 支持多状态查询，如 "0,1"
        $storeId = $this->request->param('store_id');
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 10);

        $query = Db::name('product_order')->alias('o')
            ->leftJoin('store s', 's.id = o.store_id')
            ->field('o.*, s.name as store_name')
            ->where('o.user_id', $userId);
        
        // 支持多状态查询
        if ($statusList !== null && $statusList !== '') {
            $statusArr = array_map('intval', explode(',', $statusList));
            $query->whereIn('o.status', $statusArr);
        } elseif ($status !== null && $status !== '') {
            $query->where('o.status', $status);
        }
        
        if ($storeId) {
            $query->where('o.store_id', $storeId);
        }

        $total = $query->count();
        $list = $query->order('o.id', 'desc')->page($pageNo, $pageSize)->select()->toArray();

        foreach ($list as &$item) {
            $item['order_id'] = $item['id'];
            $item['productInfoVoList'] = json_decode($item['product_info'] ?? '[]', true) ?: [];
            $item['products'] = $item['productInfoVoList']; // 兼容前端
        }

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function managePage()
    {
        $storeId = $this->request->param('store_id');
        $status = $this->request->param('status');
        $statusList = $this->request->param('statusList'); // 支持多状态查询
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 100);

        $query = Db::name('product_order')->alias('o')
            ->leftJoin('store s', 's.id = o.store_id')
            ->leftJoin('user u', 'u.id = o.user_id')
            ->field('o.*, s.name as store_name, u.nickname as userName, u.phone as userPhone');
        
        if ($storeId) $query->where('o.store_id', $storeId);
        
        // 支持多状态查询
        if ($statusList !== null && $statusList !== '') {
            $statusArr = array_map('intval', explode(',', $statusList));
            $query->whereIn('o.status', $statusArr);
        } elseif ($status !== null && $status !== '') {
            $query->where('o.status', $status);
        }

        $total = $query->count();
        $list = $query->order('o.id', 'desc')->page($pageNo, $pageSize)->select()->toArray();

        foreach ($list as &$item) {
            $item['order_id'] = $item['id'];
            $item['productInfoVoList'] = json_decode($item['product_info'] ?? '[]', true) ?: [];
            $item['products'] = $item['productInfoVoList']; // 兼容前端
        }

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function info()
    {
        $id = $this->request->param('id');
        $order = Db::name('product_order')->alias('o')
            ->leftJoin('user u', 'u.id = o.user_id')
            ->leftJoin('store s', 's.id = o.store_id')
            ->leftJoin('room r', 'r.id = o.room_id')
            ->field('o.*, o.id as order_id, u.nickname as userName, u.phone as userPhone, s.name as store_name, r.name as room_name')
            ->where('o.id', $id)
            ->find();
        if ($order) {
            $order['productInfoVoList'] = json_decode($order['product_info'] ?? '[]', true) ?: [];
            $order['totalPrice'] = $order['pay_amount'];
        }
        return json(['code' => 0, 'data' => $order]);
    }

    public function cancel()
    {
        $orderId = $this->request->post('order_id');
        $order = Db::name('product_order')->find($orderId);
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在']);
        if ($order['status'] >= 2) return json(['code' => 1, 'msg' => '订单状态不允许取消']);
        Db::name('product_order')->where('id', $orderId)->update(['status' => 3]);
        return json(['code' => 0, 'msg' => '取消成功']);
    }

    public function pay()
    {
        $orderId = $this->request->param('id');
        $payType = (int)($this->request->post('pay_type', 1));
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';

        $order = Db::name('product_order')->find($orderId);
        if (!$order || $order['status'] != 0) return json(['code' => 1, 'msg' => '订单不存在或已支付']);

        // 微信支付
        if ($payType == 1) {
            $user = User::find($userId);
            $openid = $user->openid ?? '';
            if (empty($openid)) {
                return json(['code' => 1, 'msg' => '请先完成微信授权']);
            }
            $payService = new \app\service\PayService($tenantId);
            $payResult = $payService->createWechatOrder($order['order_no'], (float)$order['pay_amount'], $openid, '商品购买');
            if ($payResult['code'] == 0 && isset($payResult['data'])) {
                $payData = $payResult['data'];
                return json(['code' => 0, 'data' => [
                    'timeStamp' => $payData['timeStamp'] ?? '',
                    'nonceStr' => $payData['nonceStr'] ?? '',
                    'pkg' => $payData['package'] ?? '',
                    'signType' => $payData['signType'] ?? '',
                    'paySign' => $payData['paySign'] ?? '',
                ]]);
            }
            return json($payResult);
        }

        // 余额支付
        if ($payType == 2) {
            $user = User::find($userId);
            $totalBalance = ($user->balance ?? 0) + ($user->gift_balance ?? 0);
            if (!$user || $totalBalance < $order['pay_amount']) return json(['code' => 1, 'msg' => '余额不足']);
            Db::startTrans();
            try {
                $remaining = (float)$order['pay_amount'];
                // 先扣主余额
                if ($user->balance > 0) {
                    $deduct = min((float)$user->balance, $remaining);
                    User::where('id', $userId)->dec('balance', $deduct)->update();
                    $remaining -= $deduct;
                }
                // 再扣赠送余额
                if ($remaining > 0 && ($user->gift_balance ?? 0) > 0) {
                    $deduct = min((float)$user->gift_balance, $remaining);
                    User::where('id', $userId)->dec('gift_balance', $deduct)->update();
                }
                Db::name('product_order')->where('id', $orderId)->update([
                    'status' => 1, 'pay_type' => 2, 'pay_time' => date('Y-m-d H:i:s')
                ]);
                Db::commit();
                return json(['code' => 0, 'msg' => '支付成功']);
            } catch (\Exception $e) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '支付失败']);
            }
        }

        return json(['code' => 1, 'msg' => '不支持的支付方式']);
    }

    public function finish()
    {
        $orderId = $this->request->param('id');
        Db::name('product_order')->where('id', $orderId)->update(['status' => 2]);
        return json(['code' => 0, 'msg' => '订单已完成']);
    }

    public function phone()
    {
        $orderId = $this->request->param('id');
        $order = Db::name('product_order')->find($orderId);
        if (!$order) return json(['code' => 1, 'msg' => '订单不存在']);
        $user = Db::name('user')->field('phone')->find($order['user_id']);
        return json(['code' => 0, 'data' => $user['phone'] ?? '']);
    }

    public function getstore()
    {
        $userId = $this->request->userId;
        $stores = Db::name('store')->where('status', 1)->field('id, name')->select()->toArray();
        return json(['code' => 0, 'data' => $stores]);
    }
}
