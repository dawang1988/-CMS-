<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Coupon extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 100);
        $type = $this->request->param('type', '');
        $roomClass = $this->request->param('room_class', '');
        $storeId = $this->request->param('store_id', '');

        $query = Db::name('coupon')->alias('c')
            ->leftJoin('store s', 'c.store_id = s.id')
            ->where('c.tenant_id', $tenantId);

        if ($type !== '') {
            $query->where('c.type', $type);
        }
        if ($roomClass !== '') {
            $query->where('c.room_class', $roomClass);
        }
        if ($storeId !== '') {
            $query->where('c.store_id', $storeId);
        }

        $total = (clone $query)->count();
        $list = $query->field('c.*, s.name as store_name')
            ->order('c.id desc')
            ->page($page, $pageSize)
            ->select()->toArray();

        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('coupon')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $allow = ['name','type','amount','min_amount','room_class','store_id','total','start_time','end_time','status'];
        $data = array_intersect_key($this->request->post(), array_flip($allow));
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['received'] = 0;
        if (!isset($data['status'])) $data['status'] = 1;
        $id = Db::name('coupon')->insertGetId($data);
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        if (!empty($data['id'])) {
            $id = $data['id']; unset($data['id']);
            Db::name('coupon')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('coupon')->insert($data);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('coupon')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('coupon')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

    /**
     * 赠送优惠券给用户
     */
    public function gift()
    {
        $couponId = $this->request->post('coupon_id');
        $userId = $this->request->post('user_id');
        $tenantId = $this->request->tenantId ?? '88888888';

        if (empty($couponId) || empty($userId)) {
            return json(['code' => 1, 'msg' => '参数不完整']);
        }

        $coupon = Db::name('coupon')->where('tenant_id', $tenantId)->where('id', $couponId)->find();
        if (!$coupon) {
            return json(['code' => 1, 'msg' => '优惠券不存在']);
        }

        $storeName = '';
        if (!empty($coupon['store_id'])) {
            $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $coupon['store_id'])->find();
            if ($store) $storeName = $store['name'];
        }

        Db::name('user_coupon')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'name' => $coupon['name'],
            'type' => $coupon['type'],
            'amount' => $coupon['amount'],
            'min_amount' => $coupon['min_amount'],
            'store_id' => $coupon['store_id'],
            'store_name' => $storeName,
            'room_type' => $coupon['room_class'],
            'status' => 0,
            'expire_time' => $coupon['end_time'] ? date('Y-m-d', strtotime($coupon['end_time'])) : null,
            'create_time' => date('Y-m-d H:i:s')
        ]);

        return json(['code' => 0, 'msg' => '赠送成功']);
    }

    /**
     * 发起优惠券活动
     */
    public function saveActivity()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $couponId = $this->request->post('coupon_id');
        $activeName = $this->request->post('active_name');
        $num = (int)$this->request->post('num', 0);
        $endTime = $this->request->post('end_time');

        if (empty($couponId) || empty($activeName) || $num <= 0 || empty($endTime)) {
            return json(['code' => 1, 'msg' => '请填写完整信息']);
        }

        // 查找已有活动
        $existing = Db::name('coupon_active')
            ->where('tenant_id', $tenantId)
            ->where('coupon_id', $couponId)
            ->where('status', 1)
            ->find();

        if ($existing) {
            Db::name('coupon_active')->where('tenant_id', $tenantId)->where('id', $existing['id'])->update([
                'active_name' => $activeName,
                'num' => $num,
                'end_time' => $endTime,
                'update_time' => date('Y-m-d H:i:s')
            ]);
        } else {
            Db::name('coupon_active')->insert([
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

        return json(['code' => 0, 'msg' => '活动已发起']);
    }

    /**
     * 获取优惠券活动信息
     */
    public function getActivity()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $couponId = $this->request->param('coupon_id');

        $activity = Db::name('coupon_active')
            ->where('tenant_id', $tenantId)
            ->where('coupon_id', $couponId)
            ->order('id desc')
            ->find();

        return json(['code' => 0, 'data' => $activity]);
    }

    /**
     * 结束优惠券活动
     */
    public function stopActivity()
    {
        $couponId = $this->request->post('coupon_id');
        $tenantId = $this->request->tenantId ?? '88888888';

        Db::name('coupon_active')
            ->where('tenant_id', $tenantId)
            ->where('coupon_id', $couponId)
            ->where('status', 1)
            ->update(['status' => 0, 'update_time' => date('Y-m-d H:i:s')]);

        return json(['code' => 0, 'msg' => '活动已结束']);
    }

    /**
     * 搜索用户（用于赠送优惠券）
     */
    public function searchUser()
    {
        $phone = $this->request->param('phone', '');
        $tenantId = $this->request->tenantId ?? '88888888';

        if (strlen($phone) < 4) {
            return json(['code' => 1, 'msg' => '请输入至少4位手机号']);
        }

        $list = Db::name('user')
            ->where('tenant_id', $tenantId)
            ->where('phone', 'like', '%' . $phone . '%')
            ->field('id, nickname, phone, avatar')
            ->limit(10)
            ->select()->toArray();

        return json(['code' => 0, 'data' => $list]);
    }

}
