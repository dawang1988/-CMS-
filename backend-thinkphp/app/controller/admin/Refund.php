<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog;
use think\facade\Db;

class Refund extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $status = $this->request->param('status');
        $keyword = $this->request->param('keyword');
        $query = Db::name('refund')->alias('rf')
            ->leftJoin('user u', 'u.id = rf.user_id')
            ->leftJoin('store s', 's.id = rf.store_id')
            ->leftJoin('order o', 'o.id = rf.order_id')
            ->field('rf.*, u.nickname as user_name, u.phone, s.name as store_name, o.order_no, o.pay_amount as order_amount')
            ->where('rf.tenant_id', $tenantId);
        if ($storeId) $query->where('rf.store_id', $storeId);
        if ($status !== '' && $status !== null) $query->where('rf.status', $status);
        if ($keyword) $query->where('o.order_no|u.phone', 'like', "%{$keyword}%");
        $list = $query->order('rf.id', 'desc')->select()->toArray();
        // 兼容前端字段: refund_amount
        foreach ($list as &$item) {
            if (!isset($item['refund_amount'])) $item['refund_amount'] = $item['amount'] ?? 0;
        }
        unset($item);
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list]]);
    }

    public function detail()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $item = Db::name('refund')->alias('rf')
            ->leftJoin('user u', 'u.id = rf.user_id')
            ->leftJoin('store s', 's.id = rf.store_id')
            ->leftJoin('order o', 'o.id = rf.order_id')
            ->field('rf.*, u.nickname as user_name, u.phone, s.name as store_name, o.order_no, o.pay_amount as order_amount')
            ->where('rf.tenant_id', $tenantId)
            ->where('rf.id', $id)
            ->find();
        if ($item && !isset($item['refund_amount'])) $item['refund_amount'] = $item['amount'] ?? 0;
        return json(['code' => 0, 'data' => $item]);
    }

    public function approve()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $refund = Db::name('refund')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($refund) {
            Db::name('refund')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 1, 'audit_time' => date('Y-m-d H:i:s')]);
            if ($refund['amount'] > 0) {
                // 退款到订单所属门店的余额
                \app\service\StoreBalanceService::refund(
                    $refund['user_id'],
                    $refund['store_id'],
                    $refund['amount'],
                    $tenantId,
                    '退款审核通过',
                    $refund['order_id']
                );
            }
            
            // 记录操作日志
            $order = Db::name('order')->where('id', $refund['order_id'])->find();
            AdminLog::log(
                AdminLog::MODULE_REFUND,
                AdminLog::TYPE_UPDATE,
                "审核通过退款申请：¥" . $refund['amount'],
                [
                    'refund_id' => $id,
                    'order_id' => $refund['order_id'],
                    'order_no' => $order['order_no'] ?? '',
                    'amount' => $refund['amount'],
                    'user_id' => $refund['user_id'],
                ],
                (int)$id
            );
        }
        return json(['code' => 0, 'msg' => '已通过']);
    }

    public function reject()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $reason = $this->request->post('reason', '');
        
        $refund = Db::name('refund')->where('tenant_id', $tenantId)->where('id', $id)->find();
        
        Db::name('refund')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 2, 'reject_reason' => $reason, 'audit_time' => date('Y-m-d H:i:s')]);
        
        // 记录操作日志
        if ($refund) {
            $order = Db::name('order')->where('id', $refund['order_id'])->find();
            AdminLog::log(
                AdminLog::MODULE_REFUND,
                AdminLog::TYPE_UPDATE,
                "拒绝退款申请：¥" . $refund['amount'],
                [
                    'refund_id' => $id,
                    'order_id' => $refund['order_id'],
                    'order_no' => $order['order_no'] ?? '',
                    'amount' => $refund['amount'],
                    'reason' => $reason,
                ],
                (int)$id
            );
        }
        
        return json(['code' => 0, 'msg' => '已拒绝']);
    }
}