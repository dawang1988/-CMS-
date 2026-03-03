<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class CouponActive extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('coupon')
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->whereRaw('received < total')
            ->order('id', 'desc')
            ->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function getAdminByCouponId()
    {
        // 兼容 couponId 和 coupon_id 两种参数名
        $couponId = $this->request->param('coupon_id') ?: $this->request->param('couponId');
        $tenantId = $this->request->tenantId ?? '88888888';

        if (empty($couponId)) {
            return json(['code' => 1, 'msg' => '缺少优惠券ID']);
        }

        try {
            // 返回最新的活动记录（小程序端期望单条数据）
            $active = Db::name('coupon_active')
                ->where('tenant_id', $tenantId)
                ->where('coupon_id', $couponId)
                ->order('id', 'desc')
                ->find();

            return json(['code' => 0, 'data' => $active]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'data' => null]);
        }
    }

    public function saveAdminByCouponId()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $couponId = $this->request->param('coupon_id');
        $activeName = $this->request->param('active_name');
        $num = (int)$this->request->param('num', 0);
        $endTime = $this->request->param('end_time');

        if (empty($couponId) || empty($activeName) || $num <= 0 || empty($endTime)) {
            return json(['code' => 1, 'msg' => '请填写完整信息']);
        }

        try {
            // 查找已有活动
            $existing = Db::name('coupon_active')
                ->where('tenant_id', $tenantId)
                ->where('coupon_id', $couponId)
                ->where('status', 1)
                ->find();

            if ($existing) {
                Db::name('coupon_active')->where('id', $existing['id'])->update([
                    'active_name' => $activeName,
                    'num' => $num,
                    'end_time' => $endTime,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                $id = $existing['id'];
            } else {
                $id = Db::name('coupon_active')->insertGetId([
                    'tenant_id' => $tenantId,
                    'coupon_id' => $couponId,
                    'active_name' => $activeName,
                    'num' => $num,
                    'balance_num' => $num,
                    'end_time' => $endTime,
                    'status' => 1,
                    'create_time' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '保存成功']);
    }

    public function stopActive()
    {
        // 兼容 couponId 和 id 两种参数名
        $couponId = $this->request->param('couponId') ?: $this->request->param('coupon_id');
        $tenantId = $this->request->tenantId ?? '88888888';

        try {
            Db::name('coupon_active')
                ->where('tenant_id', $tenantId)
                ->where('coupon_id', $couponId)
                ->where('status', 1)
                ->update(['status' => 0, 'update_time' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '已停止']);
    }
}
