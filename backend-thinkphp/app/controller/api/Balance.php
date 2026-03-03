<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use app\service\PayService;
use think\facade\Db;

/**
 * 余额控制器
 */
class Balance extends BaseController
{
    /**
     * 获取余额
     */
    public function get()
    {
        $userId = $this->request->userId;
        $user = User::find($userId);

        return json([
            'code' => 0,
            'data' => [
                'balance' => $user ? $user->balance : 0,
            ],
        ]);
    }

    /**
     * 充值套餐列表
     */
    public function packages()
    {
        $tenantId = $this->request->tenantId ?? '88888888';

        $list = Db::name('recharge_package')
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    /**
     * 充值
     */
    public function recharge()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';

        // 黑名单校验
        $blackMsg = \app\service\BlacklistService::check((int)$userId, $tenantId);
        if ($blackMsg) {
            return json(['code' => 1, 'msg' => $blackMsg]);
        }

        $packageId = $this->request->post('package_id');
        $payType = $this->request->post('pay_type', 1);

        $package = Db::name('recharge_package')
            ->where('id', $packageId)
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->find();

        if (!$package) {
            return json(['code' => 1, 'msg' => '套餐不存在']);
        }

        // 创建充值订单
        $orderNo = 'RCH' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        Db::name('recharge_order')->insert([
            'tenant_id' => $tenantId,
            'recharge_no' => $orderNo,
            'user_id' => $userId,
            'package_id' => $packageId,
            'amount' => $package['amount'],
            'gift_amount' => $package['gift_amount'],
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        // 微信支付
        if ($payType == 1) {
            $user = User::find($userId);
            $payService = new PayService($tenantId);
            $result = $payService->createWechatOrder(
                $orderNo,
                (float)$package['amount'],
                $user->openid ?? '',
                '余额充值'
            );
            if ($result['code'] == 0) {
                // 映射字段名以匹配前端期望
                $payData = $result['data'];
                return json([
                    'code' => 0,
                    'data' => [
                        'order_no' => $orderNo,
                        'timeStamp' => $payData['timeStamp'] ?? '',
                        'nonceStr' => $payData['nonceStr'] ?? '',
                        'pkg' => $payData['package'] ?? '', // 前端期望pkg而非package
                        'signType' => $payData['signType'] ?? '',
                        'paySign' => $payData['paySign'] ?? '',
                    ]
                ]);
            }
            return json($result);
        }

        return json(['code' => 1, 'msg' => '不支持的支付方式']);
    }

    /**
     * 余额记录
     */
    public function logs()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('pageSize', 20);

        // 关联门店和订单信息
        $query = Db::name('balance_log')
            ->alias('bl')
            ->leftJoin('store s', 'bl.store_id = s.id')
            ->leftJoin('order o', 'bl.order_id = o.id')
            ->where('bl.user_id', $userId)
            ->where('bl.tenant_id', $tenantId)
            ->field('bl.*, s.name as store_name, o.order_no');

        $total = $query->count();
        $list = $query->order('bl.id', 'desc')
            ->page((int)$page, (int)$pageSize)
            ->select()
            ->toArray();

        return json([
            'code' => 0,
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }

    public function info() { return $this->get(); }

    /**
     * 充值记录列表
     */
    public function rechargeList()
    {
        $userId = $this->request->userId;
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);

        $query = Db::name('recharge_order')->where('user_id', $userId);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    /**
     * 消费记录列表
     */
    public function consumeList()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);

        // 关联门店和订单信息
        $query = Db::name('balance_log')
            ->alias('bl')
            ->leftJoin('store s', 'bl.store_id = s.id')
            ->leftJoin('order o', 'bl.order_id = o.id')
            ->where('bl.user_id', $userId)
            ->where('bl.tenant_id', $tenantId)
            ->field('bl.*, s.name as store_name, o.order_no');
            
        $total = $query->count();
        $list = $query->order('bl.id', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function rechargeCallback()
    {
        // 复用 Pay 控制器的统一回调处理
        $pay = new \app\controller\api\Pay(app());
        return $pay->notify();
    }
}
