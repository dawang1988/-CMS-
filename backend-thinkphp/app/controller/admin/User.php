<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog;
use think\facade\Db;

class User extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $keyword = $this->request->param('keyword');
        $storeId = $this->request->param('store_id');
        
        $query = Db::name('user')->where('tenant_id', $tenantId);
        if ($keyword) $query->where('nickname|phone', 'like', "%{$keyword}%");
        if ($storeId) $query->where('store_id', $storeId);
        
        $total = $query->count();
        $list = Db::name('user')->where('tenant_id', $tenantId)
            ->when($keyword, function($q) use ($keyword) { $q->where('nickname|phone', 'like', "%{$keyword}%"); })
            ->when($storeId, function($q) use ($storeId) { $q->where('store_id', $storeId); })
            ->order('id', 'desc')->page($page, $pageSize)->select()->toArray();
        
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total, 'pageNo' => $page, 'pageSize' => $pageSize]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $user = Db::name('user')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }
        
        // phone → mobile 兼容（已废弃，直接用phone）

        // 获取余额记录（关联门店信息）
        $balanceLogs = [];
        try {
            $balanceLogs = Db::name('balance_log')
                ->alias('bl')
                ->leftJoin('store s', 'bl.store_id = s.id')
                ->leftJoin('order o', 'bl.order_id = o.id')
                ->where('bl.tenant_id', $tenantId)
                ->where('bl.user_id', $id)
                ->field('bl.*, s.name as store_name, o.order_no')
                ->order('bl.create_time', 'desc')
                ->limit(50)
                ->select()->toArray();
        } catch (\Exception $e) {}

        // 获取用户在各门店的余额
        $storeBalances = [];
        try {
            $storeBalances = \app\service\StoreBalanceService::getAllStoreBalance((int)$id, $tenantId);
        } catch (\Exception $e) {}

        return json(['code' => 0, 'data' => [
            'user' => $user, 
            'balanceLogs' => $balanceLogs,
            'storeBalances' => $storeBalances,
        ]]);
    }

    public function adjustBalance()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $userId = $this->request->post('user_id') ?: $this->request->post('id');
        $amount = (float)$this->request->post('amount');
        $remark = $this->request->post('remark', '管理员调整');
        $storeId = (int)$this->request->post('store_id', 0);
        
        if (!$userId) return json(['code' => 1, 'msg' => '缺少用户ID']);
        if ($amount == 0) return json(['code' => 1, 'msg' => '调整金额不能为0']);
        if (!$storeId) return json(['code' => 1, 'msg' => '请选择门店']);
        
        // 先检查用户是否存在
        $user = Db::name('user')->where('tenant_id', $tenantId)->where('id', $userId)->find();
        if (!$user) return json(['code' => 1, 'msg' => '用户不存在']);
        
        // 查询用户在该门店的余额
        $storeBalance = Db::name('user_store_balance')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('store_id', $storeId)
            ->find();
        
        $currentBalance = $storeBalance ? (float)$storeBalance['balance'] : 0;
        
        // 扣减时检查余额是否足够
        if ($amount < 0 && ($currentBalance + $amount) < 0) {
            return json(['code' => 1, 'msg' => '该门店余额不足，当前余额：' . $currentBalance]);
        }
        
        Db::startTrans();
        try {
            if ($amount > 0) {
                // 增加余额
                \app\service\StoreBalanceService::recharge(
                    $userId, 
                    $storeId, 
                    $amount, 
                    0, 
                    $tenantId, 
                    $remark
                );
            } else {
                // 减少余额
                $result = \app\service\StoreBalanceService::deduct(
                    $userId, 
                    $storeId, 
                    abs($amount), 
                    $tenantId, 
                    $remark
                );
                
                if (!$result['success']) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => $result['msg']]);
                }
            }
            
            Db::commit();
            
            // 记录操作日志
            AdminLog::log(
                AdminLog::MODULE_USER,
                $amount > 0 ? AdminLog::TYPE_UPDATE : AdminLog::TYPE_UPDATE,
                "调整用户余额：" . ($amount > 0 ? '+' : '') . $amount . '元',
                [
                    'user_id' => $userId,
                    'store_id' => $storeId,
                    'amount' => $amount,
                    'remark' => $remark,
                    'user_nickname' => $user['nickname'] ?? '',
                ],
                $userId
            );
            
            return json(['code' => 0, 'msg' => '调整成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '调整失败：' . $e->getMessage()]);
        }
    }

    public function updateStatus()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id') ?: $this->request->post('user_id');
        $status = $this->request->post('status');
        
        $user = Db::name('user')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }
        
        Db::name('user')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => $status]);
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_USER,
            AdminLog::TYPE_UPDATE,
            "修改用户状态：" . ($status == 1 ? '启用' : '禁用'),
            [
                'user_id' => $id,
                'status' => $status,
                'user_nickname' => $user['nickname'] ?? '',
            ],
            $id
        );
        
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function vipPage()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $name = $this->request->param('name', '');
        $cloumnName = $this->request->param('cloumnName', 'orderTime');
        $sortRule = strtoupper($this->request->param('sortRule', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $query = Db::name('user')->where('tenant_id', $tenantId);
        if ($name) {
            $query->where('nickname|phone', 'like', '%' . $name . '%');
        }

        $total = (clone $query)->count();
        $list = $query->page($pageNo, $pageSize)->order('create_time', 'desc')->select()->toArray();

        // 补充订单统计
        foreach ($list as &$item) {
            
            $item['orderCount'] = Db::name('order')
                ->where('user_id', $item['id'])
                ->where('tenant_id', $tenantId)
                ->whereIn('status', [1, 2])
                ->count();

            $lastOrder = Db::name('order')
                ->where('user_id', $item['id'])
                ->where('tenant_id', $tenantId)
                ->whereIn('status', [1, 2])
                ->order('create_time', 'desc')
                ->field('create_time')
                ->find();
            $item['lastOrderTime'] = $lastOrder ? substr($lastOrder['create_time'], 0, 10) : '';
        }

        // 按前端指定字段排序
        if ($cloumnName === 'orderTime') {
            usort($list, function($a, $b) use ($sortRule) {
                $cmp = strcmp($a['lastOrderTime'] ?: '0', $b['lastOrderTime'] ?: '0');
                return $sortRule === 'ASC' ? $cmp : -$cmp;
            });
        } elseif ($cloumnName === 'orderCount') {
            usort($list, function($a, $b) use ($sortRule) {
                $cmp = $a['orderCount'] - $b['orderCount'];
                return $sortRule === 'ASC' ? $cmp : -$cmp;
            });
        }

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total, 'pageNo' => $pageNo, 'pageSize' => $pageSize]]);
    }

    public function recharge()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $userId = $this->request->post('user_id') ?: $this->request->post('id');
        $amount = (float)($this->request->post('money') ?: $this->request->post('amount', 0));
        $storeId = (int)$this->request->post('store_id', 0);
        
        if (!$userId) return json(['code' => 1, 'msg' => '缺少用户ID']);
        if ($amount <= 0) return json(['code' => 1, 'msg' => '请输入有效金额']);
        if (!$storeId) return json(['code' => 1, 'msg' => '请选择门店']);
        
        // 检查用户是否存在
        $user = Db::name('user')->where('tenant_id', $tenantId)->where('id', $userId)->find();
        if (!$user) return json(['code' => 1, 'msg' => '用户不存在']);
        
        // 使用门店余额服务充值
        try {
            \app\service\StoreBalanceService::recharge(
                $userId, 
                $storeId, 
                $amount, 
                0, 
                $tenantId, 
                '管理员充值'
            );
            
            // 记录操作日志
            AdminLog::log(
                AdminLog::MODULE_USER,
                AdminLog::TYPE_CREATE,
                "给用户充值：+" . $amount . '元',
                [
                    'user_id' => $userId,
                    'store_id' => $storeId,
                    'amount' => $amount,
                    'user_nickname' => $user['nickname'] ?? '',
                ],
                $userId
            );
            
            return json(['code' => 0, 'msg' => '充值成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '充值失败：' . $e->getMessage()]);
        }
    }
}