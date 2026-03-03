<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 优惠券控制器
 */
class Coupon extends BaseController
{
    /**
     * 可领取的优惠券列表
     */
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';

        $list = Db::name('coupon')
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->whereRaw('received < total')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    /**
     * 领取优惠券
     */
    public function receive()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $couponId = $this->request->post('coupon_id') ?: $this->request->param('coupon_id') ?: $this->request->param('id');

        $coupon = Db::name('coupon')
            ->where('id', $couponId)
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->find();

        if (!$coupon) {
            return json(['code' => 1, 'msg' => '优惠券不存在']);
        }

        if ($coupon['received'] >= $coupon['total']) {
            return json(['code' => 1, 'msg' => '优惠券已领完']);
        }

        if (strtotime($coupon['end_time']) < time()) {
            return json(['code' => 1, 'msg' => '优惠券已过期']);
        }

        // 检查是否已领取
        $exists = Db::name('user_coupon')
            ->where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->find();

        if ($exists) {
            return json(['code' => 1, 'msg' => '已领取过该优惠券']);
        }

        Db::startTrans();
        try {
            Db::name('user_coupon')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'coupon_id' => $couponId,
                'status' => 0,
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            Db::name('coupon')->where('id', $couponId)->inc('received')->update();

            Db::commit();
            return json(['code' => 0, 'msg' => '领取成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '领取失败']);
        }
    }

    /**
     * 我的优惠券
     */
    public function mine()
    {
        $userId = $this->request->userId;
        $status = $this->request->param('status');

        $query = Db::name('user_coupon')
            ->alias('uc')
            ->leftJoin('coupon c', 'uc.coupon_id = c.id')
            ->where('uc.user_id', $userId)
            ->field('uc.*, c.name, c.type, c.amount, c.min_amount, c.end_time as coupon_end_time');

        if ($status !== null && $status !== '') {
            $query->where('uc.status', $status);
        }

        $list = $query->order('uc.id', 'desc')->select()->toArray();

        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function groupList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 50);

        $list = Db::name('coupon')
            ->where('tenant_id', $tenantId)->where('status', 1)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->order('id', 'desc')->page($pageNo, $pageSize)->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function available()
    {
        $userId = $this->request->userId;
        $amount = $this->request->param('amount', 0);
        $storeId = $this->request->param('storeId') ?: $this->request->param('store_id');
        $roomClass = $this->request->param('room_class');

        $list = Db::name('user_coupon')->alias('uc')
            ->leftJoin('coupon c', 'uc.coupon_id = c.id')
            ->where('uc.user_id', $userId)->where('uc.status', 0)
            ->where('c.end_time', '>', date('Y-m-d H:i:s'))
            ->field('uc.*, c.name, c.type, c.amount, c.min_amount, c.end_time as coupon_end_time, c.store_id as coupon_store_id, c.room_class as coupon_room_class')
            ->select()->toArray();

        // 过滤满足门槛的
        $result = [];
        foreach ($list as $item) {
            if ($amount > 0 && $item['min_amount'] > $amount) continue;
            if ($storeId && $item['coupon_store_id'] && $item['coupon_store_id'] != $storeId) continue;
            if ($roomClass !== null && $roomClass !== '' && $item['coupon_room_class'] && $item['coupon_room_class'] != $roomClass) continue;
            $result[] = $item;
        }
        return json(['code' => 0, 'data' => ['list' => $result]]);
    }

    public function verify()
    {
        $code = $this->request->post('code');
        if (!$code) return json(['code' => 1, 'msg' => '请输入券码']);
        return json(['code' => 0, 'data' => ['valid' => true, 'code' => $code]]);
    }

    public function getById()
    {
        $couponId = $this->request->param('couponId') ?: $this->request->param('id');
        $coupon = Db::name('coupon')->find($couponId);
        if (!$coupon) return json(['code' => 1, 'msg' => '优惠券不存在']);
        return json(['code' => 0, 'data' => $coupon]);
    }
}
