<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Permission extends BaseController
{
    /**
     * 管理员列表 — 前端发 POST JSON body
     */
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $input = json_decode($this->request->getContent(), true) ?: [];
        $storeId = $input['store_id'] ?? $this->request->param('store_id', '');
        $pageNo   = (int)($input['pageNo'] ?? $this->request->param('pageNo', 1));
        $pageSize = (int)($input['pageSize'] ?? $this->request->param('pageSize', 100));

        $list = [];
        $total = 0;

        try {
            // LEFT JOIN store_user，合并两个来源的 user_type
            // COALESCE: 优先取 store_user 的 user_type，否则取 user 表的
            $query = Db::name('user')->alias('u')
                ->leftJoin('ss_store_user su', 'u.id = su.user_id AND su.tenant_id = \'' . $tenantId . '\'')
                ->field('u.id, u.nickname, u.phone, u.avatar, COALESCE(su.store_id, u.store_id) as store_id, COALESCE(su.user_type, u.user_type) as user_type, su.name, su.permissions')
                ->where('u.tenant_id', $tenantId)
                ->where(function ($q) {
                    $q->whereOr('su.user_type', 'in', [12, 13, 14])
                      ->whereOr('u.user_type', 'in', [12, 13, 14]);
                });

            if ($storeId !== '' && $storeId !== null) {
                $query->where(function ($q) use ($storeId) {
                    $q->whereOr('su.store_id', $storeId)
                      ->whereOr('u.store_id', $storeId);
                });
            }

            $total = (clone $query)->count();
            $rows = $query->order('user_type asc, u.create_time desc')
                ->page($pageNo, $pageSize)
                ->select()
                ->toArray();
        } catch (\Exception $e) {
            // store_user 表不存在，仅查 user 表
            $where2 = [
                ['tenant_id', '=', $tenantId],
                ['user_type', 'in', [12, 13, 14]],
            ];
            if ($storeId !== '' && $storeId !== null) {
                $where2[] = ['store_id', '=', $storeId];
            }
            $total = Db::name('user')->where($where2)->count();
            $rows = Db::name('user')
                ->field('id, nickname, phone, avatar, store_id, user_type')
                ->where($where2)
                ->order('user_type asc, create_time desc')
                ->page($pageNo, $pageSize)
                ->select()
                ->toArray();
        }

        // 补充门店名称
        $storeIds = array_unique(array_filter(array_column($rows, 'store_id')));
        $storeMap = $storeIds ? Db::name('store')->where('tenant_id', $tenantId)->whereIn('id', $storeIds)->column('name', 'id') : [];

        foreach ($rows as &$row) {
            $row['store_name'] = $storeMap[$row['store_id']] ?? '';
            $row['phone'] = $row['phone'] ?? '';
            $row['name'] = $row['name'] ?? $row['nickname'] ?? '';
            $row['permissions'] = $row['permissions'] ?? '[]';
        }

        return json(['code' => 0, 'data' => ['list' => $rows, 'total' => $total]]);
    }

    /**
     * 保存权限 — 前端发 POST JSON body
     */
    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $input = json_decode($this->request->getContent(), true) ?: $this->request->post();

        $userId     = $input['user_id'] ?? 0;
        $storeId    = $input['store_id'] ?? 0;
        $userType   = (int)($input['user_type'] ?? 13);
        $permissions = $input['permissions'] ?? '[]';

        if (!$userId) {
            return json(['code' => 1, 'msg' => '缺少用户ID']);
        }

        try {
            // 优先写 store_user 表
            $exists = Db::name('store_user')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->find();

            if ($exists) {
                Db::name('store_user')->where('tenant_id', $tenantId)->where('id', $exists['id'])->update([
                    'store_id'    => $storeId,
                    'user_type'   => $userType,
                    'permissions' => $permissions,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            } else {
                Db::name('store_user')->insert([
                    'tenant_id'   => $tenantId,
                    'user_id'     => $userId,
                    'store_id'    => $storeId,
                    'user_type'   => $userType,
                    'permissions' => $permissions,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            // store_user 表不存在，回退到 user 表
        }

        // 同步更新 user 表的 user_type，确保前端个人中心能正确读取角色
        Db::name('user')->where('tenant_id', $tenantId)->where('id', $userId)->update([
            'user_type'   => $userType,
            'store_id'    => $storeId,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 移除管理员 — 前端发 {user_id: ...}
     */
    public function remove()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $input = json_decode($this->request->getContent(), true) ?: $this->request->post();
        $userId = $input['user_id'] ?? $input['id'] ?? 0;

        if (!$userId) {
            return json(['code' => 1, 'msg' => '缺少用户ID']);
        }

        try {
            Db::name('store_user')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'user_type'   => 0,
                    'permissions' => '[]',
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
        } catch (\Exception $e) {
            // fallback
        }

        // 同步 user 表
        Db::name('user')->where('tenant_id', $tenantId)->where('id', $userId)->update([
            'user_type'   => 0,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return json(['code' => 0, 'msg' => '已移除管理权限']);
    }

    /**
     * 搜索用户 — 查 user 表（小程序注册用户）
     */
    public function searchUser()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $keyword = $this->request->param('keyword', '');

        if (!$keyword) {
            return json(['code' => 0, 'data' => []]);
        }

        $list = Db::name('user')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($keyword) {
                $q->whereOr('nickname', 'like', "%{$keyword}%")
                  ->whereOr('phone', 'like', "%{$keyword}%");
            })
            ->field('id, nickname, phone, avatar, user_type')
            ->limit(20)
            ->select()
            ->toArray();

        return json(['code' => 0, 'data' => $list]);
    }

    /**
     * 门店列表 — 前端期望 [{key: name, value: id}]
     */
    public function storeList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $stores = Db::name('store')
            ->where('tenant_id', $tenantId)
            ->field('id, name')
            ->select()
            ->toArray();

        $result = [];
        foreach ($stores as $s) {
            $result[] = ['key' => $s['name'], 'value' => $s['id']];
        }

        return json(['code' => 0, 'data' => $result]);
    }
}
