<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Card extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 20);
        $storeId = $this->request->param('store_id') ?: $this->request->param('storeId');
        $type = $this->request->param('type');
        $status = $this->request->param('status');

        $query = Db::name('card')->where('tenant_id', $tenantId);
        if ($storeId === '0') {
            // 通用卡（无门店限制）
            $query->where(function($q) { $q->whereNull('store_id')->whereOr('store_id', 0)->whereOr('store_id', ''); });
        } elseif ($storeId) {
            $query->where('store_id', $storeId);
        }
        if ($type) $query->where('type', $type);
        if ($status !== null && $status !== '') $query->where('status', $status);

        $total = (clone $query)->count();
        $list = (clone $query)->order('sort asc')->page($page, $pageSize)->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('card')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $data['tenant_id'] = $tenantId;
        $data['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('card')->insertGetId($data);
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? null;
        
        // 验证必填字段
        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '会员卡名称不能为空']);
        }
        if (empty($data['type']) || !in_array($data['type'], [1, 2, 3])) {
            return json(['code' => 1, 'msg' => '请选择正确的会员卡类型']);
        }
        if (empty($data['value']) || $data['value'] <= 0) {
            return json(['code' => 1, 'msg' => '会员卡价值必须大于0']);
        }
        if (empty($data['price']) || $data['price'] <= 0) {
            return json(['code' => 1, 'msg' => '会员卡售价必须大于0']);
        }
        
        // 验证折扣范围
        if (isset($data['discount']) && $data['discount'] !== '' && $data['discount'] !== null) {
            if ($data['discount'] <= 0 || $data['discount'] > 1) {
                return json(['code' => 1, 'msg' => '折扣必须在0-1之间']);
            }
        }
        
        // 检查唯一性：同一门店不能有相同名称和类型的启用会员卡
        $storeId = $data['store_id'] ?? 0;
        $exists = Db::name('card')
            ->where('tenant_id', $tenantId)
            ->where('name', $data['name'])
            ->where('type', $data['type'])
            ->where('status', 1)
            ->when($storeId, function($query) use ($storeId) {
                $query->where(function($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                      ->whereOr('store_id', 0)
                      ->whereOr('store_id', null);
                });
            }, function($query) {
                $query->where(function($q) {
                    $q->where('store_id', 0)
                      ->whereOr('store_id', null);
                });
            })
            ->when($id, function($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        
        if ($exists) {
            return json(['code' => 1, 'msg' => '已存在相同名称和类型的启用会员卡，请修改名称或停用已有会员卡']);
        }
        
        $data['tenant_id'] = $tenantId;
        $data['update_time'] = date('Y-m-d H:i:s');
        
        if (!empty($data['id'])) {
            $id = $data['id']; unset($data['id']);
            Db::name('card')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            if (!isset($data['status'])) $data['status'] = 1;
            if (!isset($data['sort'])) $data['sort'] = 0;
            $id = Db::name('card')->insertGetId($data);
        }
        return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $id]]);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0; unset($data['id'], $data['tenant_id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('card')->where('tenant_id', $tenantId)->where('id', $id)->update($data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('card')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

    public function getStoreCardEnabled()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id') ?: $this->request->param('id');
        $store = Db::name('store')->where('tenant_id', $tenantId)->field('card_enabled')->where('id', $storeId)->find();
        $val = $store['card_enabled'] ?? 0;
        return json(['code' => 0, 'data' => ['enabled' => $val, 'card_enabled' => $val]]);
    }

    public function setStoreCardEnabled()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->post('store_id') ?: $this->request->post('id');
        $enabled = $this->request->post('card_enabled') ?? $this->request->post('enabled', 0);
        Db::name('store')->where('tenant_id', $tenantId)->where('id', $storeId)->update(['card_enabled' => $enabled]);
        return json(['code' => 0, 'msg' => $enabled ? '已开启会员卡功能' : '已关闭会员卡功能']);
    }

    public function userCardList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $userId = $this->request->param('user_id');
        $query = Db::name('user_card')->where('tenant_id', $tenantId);
        if ($userId) $query->where('user_id', $userId);
        $list = $query->order('id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list]]);
    }
}
