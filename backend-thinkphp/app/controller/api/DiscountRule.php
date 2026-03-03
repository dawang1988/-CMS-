<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 折扣规则控制器
 */
class DiscountRule extends BaseController
{
    /**
     * 获取折扣规则分页列表
     */
    public function getPage()
    {
        $pageNo = $this->request->param('pageNo', 1);
        $pageSize = $this->request->param('pageSize', 10);
        $storeId = $this->request->param('store_id');
        
        $query = Db::name('discount_rule')
            ->alias('d')
            ->leftJoin('ss_store s', 'd.store_id = s.id')
            ->where('d.tenant_id', $this->request->tenantId);
        
        if (!empty($storeId)) {
            $query->where('d.store_id', $storeId);
        }
        
        $total = $query->count();
        
        $list = $query
            ->field('d.*, s.name as store_name')
            ->order('d.id', 'desc')
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();
        
        return success([
            'list' => $list,
            'total' => $total,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize
        ]);
    }
    
    /**
     * 获取折扣规则详情
     */
    public function getDetail($id)
    {
        if (empty($id)) {
            return error('ID不能为空');
        }
        
        $item = Db::name('discount_rule')
            ->where('id', $id)
            ->where('tenant_id', $this->request->tenantId)
            ->find();
        
        if (empty($item)) {
            return error('规则不存在');
        }
        
        return success($item);
    }
    
    /**
     * 保存折扣规则
     */
    public function save()
    {
        $discountId = $this->request->param('id');
        $storeId = $this->request->param('store_id');
        $payMoney = $this->request->param('pay_money');
        $giftMoney = $this->request->param('gift_money', '0');
        $expriceTime = $this->request->param('end_time');
        $status = $this->request->param('status');
        
        if (empty($storeId) || $payMoney === null || $payMoney === '' || empty($expriceTime)) {
            return error('参数不完整');
        }
        
        $data = [
            'store_id' => $storeId,
            'tenant_id' => $this->request->tenantId,
            'pay_money' => $payMoney,
            'gift_money' => $giftMoney,
            'end_time' => $expriceTime,
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        if ($status !== null && $status !== '') {
            $data['status'] = (int)$status;
        }
        
        if ($discountId) {
            Db::name('discount_rule')->where('id', $discountId)->update($data);
        } else {
            if (!isset($data['status'])) {
                $data['status'] = 1;
            }
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('discount_rule')->insert($data);
        }
        
        return success([], '保存成功');
    }
    
    /**
     * 切换折扣规则状态
     */
    public function changeStatus($id)
    {
        if (empty($id)) {
            return error('ID不能为空');
        }
        
        $item = Db::name('discount_rule')
            ->where('id', $id)
            ->where('tenant_id', $this->request->tenantId)
            ->find();
        
        if (empty($item)) {
            return error('规则不存在');
        }
        
        $newStatus = $item['status'] == 1 ? 0 : 1;
        
        Db::name('discount_rule')
            ->where('id', $id)
            ->update([
                'status' => $newStatus,
                'update_time' => date('Y-m-d H:i:s')
            ]);
        
        return success([], '操作成功');
    }
    
    /**
     * 删除折扣规则
     */
    public function delete($id)
    {
        if (empty($id)) {
            return error('ID不能为空');
        }
        
        $item = Db::name('discount_rule')
            ->where('id', $id)
            ->where('tenant_id', $this->request->tenantId)
            ->find();
        
        if (empty($item)) {
            return error('规则不存在');
        }
        
        Db::name('discount_rule')->where('id', $id)->delete();
        
        return success([], '删除成功');
    }
}
