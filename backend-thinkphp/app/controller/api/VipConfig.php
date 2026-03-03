<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 会员配置控制器
 */
class VipConfig extends BaseController
{
    /**
     * 获取会员配置列表
     */
    public function getList($id)
    {
        $storeId = $id;
        
        if (empty($storeId)) {
            return error('门店ID不能为空');
        }
        
        $list = Db::name('vip_config')
            ->where('store_id', $storeId)
            ->where('tenant_id', $this->request->tenantId)
            ->order('vip_level', 'asc')
            ->select()
            ->toArray();
        
        return success($list);
    }
    
    /**
     * 保存会员配置
     */
    public function save()
    {
        $vipId = $this->request->param('id');
        $storeId = $this->request->param('store_id');
        $vipName = $this->request->param('vip_name');
        $vipDiscount = $this->request->param('vip_discount');
        $score = $this->request->param('score');
        
        if (empty($storeId) || empty($vipName) || empty($vipDiscount) || empty($score)) {
            return error('参数不完整');
        }
        
        // 检查是否超过3个等级
        $count = Db::name('vip_config')
            ->where('store_id', $storeId)
            ->where('tenant_id', $this->request->tenantId)
            ->when($vipId, function ($query) use ($vipId) {
                $query->where('id', '<>', $vipId);
            })
            ->count();
        
        if ($count >= 3) {
            return error('最多只能设置3个会员等级');
        }
        
        $data = [
            'store_id' => $storeId,
            'tenant_id' => $this->request->tenantId,
            'vip_name' => $vipName,
            'vip_discount' => $vipDiscount,
            'score' => $score,
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        // 前端传了 vip_level 和 status 时使用
        $vipLevel = $this->request->param('vip_level');
        $status = $this->request->param('status');
        
        if ($vipId) {
            // 更新
            if ($vipLevel !== null) $data['vip_level'] = (int)$vipLevel;
            if ($status !== null) $data['status'] = (int)$status;
            Db::name('vip_config')->where('id', $vipId)->update($data);
        } else {
            // 新增
            $data['vip_level'] = $vipLevel ? (int)$vipLevel : (
                (Db::name('vip_config')
                    ->where('store_id', $storeId)
                    ->where('tenant_id', $this->request->tenantId)
                    ->max('vip_level') ?? 0) + 1
            );
            $data['status'] = $status !== null ? (int)$status : 1;
            $data['create_time'] = date('Y-m-d H:i:s');
            
            Db::name('vip_config')->insert($data);
        }
        
        return success([], '保存成功');
    }
    
    /**
     * 删除会员配置
     */
    public function delete($id)
    {
        if (empty($id)) {
            return error('ID不能为空');
        }
        
        Db::name('vip_config')
            ->where('id', $id)
            ->where('tenant_id', $this->request->tenantId)
            ->delete();
        
        return success([], '删除成功');
    }
}
