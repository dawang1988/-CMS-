<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class ProductOrder extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 20);
        $status = $this->request->param('status');

        $where = [['o.tenant_id', '=', $tenantId]];
        if ($status !== '' && $status !== null) {
            $where[] = ['o.status', '=', $status];
        }

        $total = Db::name('product_order')->alias('o')->where($where)->count();

        $list = Db::name('product_order')->alias('o')
            ->leftJoin('user u', 'u.id = o.user_id')
            ->leftJoin('store s', 's.id = o.store_id')
            ->leftJoin('room r', 'r.id = o.room_id')
            ->field('o.*, o.id as order_id,
                     u.nickname as userName, u.phone as userPhone,
                     s.name as store_name,
                     r.name as room_name, r.room_class')
            ->where($where)
            ->order('o.id', 'desc')
            ->page($page, $pageSize)
            ->select()->toArray();

        // 解析 product_info JSON 为 productInfoVoList
        foreach ($list as &$item) {
            $item['productInfoVoList'] = [];
            if (!empty($item['product_info'])) {
                $parsed = json_decode($item['product_info'], true);
                if (is_array($parsed)) {
                    $item['productInfoVoList'] = $parsed;
                }
            }
        }
        unset($item);

        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $order = Db::name('product_order')->alias('o')
            ->leftJoin('user u', 'u.id = o.user_id')
            ->leftJoin('store s', 's.id = o.store_id')
            ->leftJoin('room r', 'r.id = o.room_id')
            ->field('o.*, o.id as order_id,
                     u.nickname as userName, u.phone as userPhone,
                     s.name as store_name,
                     r.name as room_name, r.room_class')
            ->where('o.tenant_id', $tenantId)
            ->where('o.id', $id)
            ->find();

        if ($order && !empty($order['product_info'])) {
            $parsed = json_decode($order['product_info'], true);
            $order['productInfoVoList'] = is_array($parsed) ? $parsed : [];
        } else if ($order) {
            $order['productInfoVoList'] = [];
        }

        return json(['code' => 0, 'data' => $order]);
    }

    public function finish()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id') ?: $this->request->post('id');
        Db::name('product_order')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 2, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '已完成']);
    }

    public function cancel()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('order_id') ?: $this->request->post('id');
        Db::name('product_order')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 3, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '已取消']);
    }

    public function refund()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('order_id') ?: $this->request->post('id');
        $order = Db::name('product_order')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }
        if ($order['status'] == 3) {
            return json(['code' => 1, 'msg' => '订单已取消/退款，不可重复操作']);
        }

        Db::startTrans();
        try {
            if ($order['pay_amount'] > 0) {
                Db::name('user')->where('tenant_id', $tenantId)->where('id', $order['user_id'])->inc('balance', $order['pay_amount'])->update();

                // 记录余额变动
                $user = Db::name('user')->where('tenant_id', $tenantId)->where('id', $order['user_id'])->find();
                Db::name('balance_log')->insert([
                    'tenant_id' => $tenantId,
                    'store_id' => $order['store_id'] ?? 0,
                    'user_id' => $order['user_id'],
                    'type' => 3,
                    'amount' => $order['pay_amount'],
                    'balance_before' => ($user['balance'] ?? 0) - $order['pay_amount'],
                    'balance_after' => $user['balance'] ?? 0,
                    'order_id' => $id,
                    'remark' => '商品订单退款',
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }
            Db::name('product_order')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 3, 'update_time' => date('Y-m-d H:i:s')]);
            Db::commit();
            return json(['code' => 0, 'msg' => '退款成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '退款失败']);
        }
    }
}
