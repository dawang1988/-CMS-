<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class Game extends BaseController
{
    /**
     * 处理头像URL，确保返回完整可用的URL
     */
    private function formatAvatar(string $avatar): string
    {
        if (empty($avatar)) {
            return '';
        }
        if (str_starts_with($avatar, 'http')) {
            return $avatar;
        }
        $host = $this->request->scheme() . '://' . $this->request->host();
        return $host . (str_starts_with($avatar, '/') ? '' : '/') . $avatar;
    }

    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');

        $query = Db::name('game')->where('tenant_id', $tenantId)->where('status', 0);
        if ($storeId) $query->where('store_id', $storeId);

        $list = $query->order('id', 'desc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function create()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';

        $storeId = $this->request->post('store_id');
        $startTime = $this->request->post('start_time');
        $endTime = $this->request->post('end_time');
        $maxPlayers = (int)$this->request->post('maxPlayers', 4);
        
        // 验证必填字段
        if (!$storeId) {
            return json(['code' => 1, 'msg' => '请选择门店']);
        }
        if (!$startTime || !$endTime) {
            return json(['code' => 1, 'msg' => '请选择开始和结束时间']);
        }
        
        // 验证最大人数
        if ($maxPlayers < 2 || $maxPlayers > 20) {
            return json(['code' => 1, 'msg' => '最大人数必须在2-20之间']);
        }
        
        // 验证时间
        $startTimestamp = strtotime($startTime);
        $endTimestamp = strtotime($endTime);
        
        if ($startTimestamp < time() - 300) { // 允许5分钟误差
            return json(['code' => 1, 'msg' => '开始时间不能早于当前时间']);
        }
        if ($endTimestamp <= $startTimestamp) {
            return json(['code' => 1, 'msg' => '结束时间必须晚于开始时间']);
        }
        
        // 检查用户是否有进行中的拼场
        $exists = Db::name('game')
            ->where('user_id', $userId)
            ->where('status', 'in', [0, 1])
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->find();
        
        if ($exists) {
            return json(['code' => 1, 'msg' => '您已有进行中的拼场，请先完成或解散']);
        }

        $data = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'store_id' => $storeId,
            'room_id' => $this->request->post('room_id'),
            'title' => $this->request->post('title', ''),
            'game_type' => $this->request->post('gameType', ''),
            'max_players' => $maxPlayers,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'remark' => $this->request->post('remark', ''),
            'current_players' => 1,
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
        ];

        try {
            $gameId = Db::name('game')->insertGetId($data);

            Db::name('game_user')->insert([
                'tenant_id' => $tenantId,
                'game_id' => $gameId,
                'user_id' => $userId,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            return json(['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $gameId]]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '创建失败: ' . $e->getMessage()]);
        }
    }

    public function join()
    {
        $userId = $this->request->userId;
        $gameId = $this->request->param('id') ?: $this->request->post('game_id') ?: $this->request->post('gameId');

        Db::startTrans();
        try {
            // 加锁查询拼场信息
            $game = Db::name('game')->where('id', $gameId)->lock(true)->find();
            if (!$game) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '拼场不存在']);
            }

            $exists = Db::name('game_user')->where('game_id', $gameId)->where('user_id', $userId)->find();

            if ($exists) {
                // 已加入 → 退出
                Db::name('game_user')->where('game_id', $gameId)->where('user_id', $userId)->delete();
                Db::name('game')->where('id', $gameId)->dec('current_players')->update();

                // 房主退出则解散
                if ($game['user_id'] == $userId) {
                    Db::name('game')->where('id', $gameId)->update(['status' => 4]);
                } else {
                    // 非房主退出，如果之前已满员则恢复为招募中
                    if ($game['status'] == 1) {
                        Db::name('game')->where('id', $gameId)->update(['status' => 0]);
                    }
                }

                Db::commit();
                return json(['code' => 0, 'msg' => '退出成功']);
            } else {
                // 未加入 → 加入
                if ($game['status'] != 0) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '该拼场不在招募中']);
                }
                if ($game['current_players'] >= $game['max_players']) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '人数已满']);
                }

                Db::name('game_user')->insert([
                    'tenant_id' => $game['tenant_id'],
                    'game_id' => $gameId,
                    'user_id' => $userId,
                    'status' => 1,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                Db::name('game')->where('id', $gameId)->inc('current_players')->update();

                // 重新查询检查是否满员
                $game = Db::name('game')->where('id', $gameId)->find();
                if ($game['current_players'] >= $game['max_players']) {
                    Db::name('game')->where('id', $gameId)->update(['status' => 1]);
                }

                Db::commit();
                return json(['code' => 0, 'msg' => '加入成功']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    public function quit()
    {
        $userId = $this->request->userId;
        $gameId = $this->request->post('game_id');

        Db::startTrans();
        try {
            Db::name('game_user')->where('game_id', $gameId)->where('user_id', $userId)->delete();
            Db::name('game')->where('id', $gameId)->dec('current_players')->update();
            Db::commit();
            return json(['code' => 0, 'msg' => '退出成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '退出失败']);
        }
    }

    // ========== 路由别名方法 ==========

    public function getGamePage()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $status = $this->request->param('status');
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 10);

        $query = Db::name('game')->alias('g')
            ->leftJoin('store s', 'g.store_id = s.id')
            ->leftJoin('room r', 'g.room_id = r.id')
            ->where('g.tenant_id', $tenantId);
        if ($storeId) $query->where('g.store_id', $storeId);
        if ($status !== '' && $status !== null) $query->where('g.status', $status);

        $total = $query->count();
        $list = $query->field('g.*, s.name as store_name, s.address, s.latitude, s.longitude, r.name as room_name')
            ->order('g.id', 'desc')->page($pageNo, $pageSize)->select()->toArray();

        foreach ($list as &$item) {
            $item['gameId'] = $item['id'];
            $users = Db::name('game_user')->alias('gu')
                ->leftJoin('user u', 'gu.user_id = u.id')
                ->where('gu.game_id', $item['id'])
                ->field('gu.user_id, u.nickname, u.avatar')
                ->select()->toArray();
            // 处理头像URL
            foreach ($users as &$u) {
                $u['avatar'] = $this->formatAvatar($u['avatar'] ?? '');
            }
            $item['playUserList'] = $users;
            $item['playUserIds'] = array_map('intval', array_column($users, 'user_id'));
            // 规则描述：游戏类型 + 最大人数
            $item['ruleDesc'] = ($item['game_type'] ?: '自由拼场') . ' ' . $item['max_players'] . '人局';
        }

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function save() { return $this->create(); }

    public function deleteUser()
    {
        $userId = $this->request->userId;
        $gameId = $this->request->param('id') ?: $this->request->post('game_id');
        $targetUserId = $this->request->param('userId') ?: $this->request->post('user_id');
        
        // 查询拼场信息
        $game = Db::name('game')->where('id', $gameId)->find();
        if (!$game) {
            return json(['code' => 1, 'msg' => '拼场不存在']);
        }
        
        // 验证房主权限
        if ($game['user_id'] != $userId) {
            return json(['code' => 1, 'msg' => '只有房主可以移除成员']);
        }
        
        // 不能移除自己
        if ($targetUserId == $userId) {
            return json(['code' => 1, 'msg' => '不能移除自己，请使用退出功能']);
        }
        
        // 验证目标用户是否在拼场中
        $targetUser = Db::name('game_user')
            ->where('game_id', $gameId)
            ->where('user_id', $targetUserId)
            ->find();
        
        if (!$targetUser) {
            return json(['code' => 1, 'msg' => '该用户不在拼场中']);
        }

        Db::startTrans();
        try {
            Db::name('game_user')->where('game_id', $gameId)->where('user_id', $targetUserId)->delete();
            Db::name('game')->where('id', $gameId)->dec('current_players')->update();
            
            // 如果之前已满员，移除后恢复为招募中
            if ($game['status'] == 1) {
                Db::name('game')->where('id', $gameId)->update(['status' => 0]);
            }
            
            Db::commit();
            return json(['code' => 0, 'msg' => '移除成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '移除失败: ' . $e->getMessage()]);
        }
    }

    public function sendMessage()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $gameId = $this->request->post('game_id');
        $content = $this->request->post('content');

        if (!$gameId || !$content) {
            return json(['code' => 1, 'msg' => '参数不完整']);
        }
        
        // 验证消息内容
        $content = trim($content);
        if (empty($content)) {
            return json(['code' => 1, 'msg' => '消息内容不能为空']);
        }
        if (mb_strlen($content) > 500) {
            return json(['code' => 1, 'msg' => '消息内容不能超过500字']);
        }

        // 验证用户是否为该拼场参与者
        $isMember = Db::name('game_user')->where('game_id', $gameId)->where('user_id', $userId)->find();
        if (!$isMember) {
            return json(['code' => 1, 'msg' => '您不是该拼场的参与者']);
        }
        
        // 简单的敏感词过滤（可以扩展为更完善的敏感词库）
        $sensitiveWords = ['fuck', 'shit', 'damn', '傻逼', '操', '妈的', '草泥马'];
        foreach ($sensitiveWords as $word) {
            if (stripos($content, $word) !== false) {
                $content = str_ireplace($word, str_repeat('*', mb_strlen($word)), $content);
            }
        }

        Db::name('game_message')->insert([
            'tenant_id' => $tenantId,
            'game_id' => $gameId,
            'user_id' => $userId,
            'content' => $content,
            'create_time' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '发送成功']);
    }

    public function getMessages()
    {
        $userId = $this->request->userId;
        $gameId = $this->request->param('game_id');
        $lastId = $this->request->param('last_id', 0);

        $query = Db::name('game_message')->alias('gm')
            ->leftJoin('user u', 'gm.user_id = u.id')
            ->where('gm.game_id', $gameId)
            ->field('gm.*, u.nickname, u.avatar');
        if ($lastId) $query->where('gm.id', '>', $lastId);

        $list = $query->order('gm.id', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'current_user_id' => $userId]]);
    }

    /**
     * 房主编辑拼场
     */
    public function update()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $gameId = $this->request->post('game_id') ?: $this->request->post('id');

        // 查询拼场信息
        $game = Db::name('game')->where('id', $gameId)->where('tenant_id', $tenantId)->find();
        if (!$game) {
            return json(['code' => 1, 'msg' => '拼场不存在']);
        }

        // 验证房主权限
        if ($game['user_id'] != $userId) {
            return json(['code' => 1, 'msg' => '只有房主可以编辑拼场']);
        }

        // 只能编辑招募中的拼场
        if ($game['status'] != 0) {
            return json(['code' => 1, 'msg' => '只能编辑招募中的拼场']);
        }

        // 获取更新数据
        $updateData = [];

        if ($this->request->has('title', 'post')) {
            $updateData['title'] = $this->request->post('title');
        }
        if ($this->request->has('game_type', 'post') || $this->request->has('gameType', 'post')) {
            $updateData['game_type'] = $this->request->post('game_type') ?: $this->request->post('gameType');
        }
        if ($this->request->has('max_players', 'post') || $this->request->has('maxPlayers', 'post')) {
            $maxPlayers = (int)($this->request->post('max_players') ?: $this->request->post('maxPlayers'));
            // 验证最大人数
            if ($maxPlayers < 2 || $maxPlayers > 20) {
                return json(['code' => 1, 'msg' => '最大人数必须在2-20之间']);
            }
            // 最大人数不能小于当前人数
            if ($maxPlayers < $game['current_players']) {
                return json(['code' => 1, 'msg' => '最大人数不能小于当前人数']);
            }
            $updateData['max_players'] = $maxPlayers;
        }
        if ($this->request->has('start_time', 'post')) {
            $startTime = $this->request->post('start_time');
            if (strtotime($startTime) < time() - 300) {
                return json(['code' => 1, 'msg' => '开始时间不能早于当前时间']);
            }
            $updateData['start_time'] = $startTime;
        }
        if ($this->request->has('end_time', 'post')) {
            $endTime = $this->request->post('end_time');
            $startTime = $updateData['start_time'] ?? $game['start_time'];
            if (strtotime($endTime) <= strtotime($startTime)) {
                return json(['code' => 1, 'msg' => '结束时间必须晚于开始时间']);
            }
            $updateData['end_time'] = $endTime;
        }
        if ($this->request->has('remark', 'post')) {
            $updateData['remark'] = $this->request->post('remark');
        }

        if (empty($updateData)) {
            return json(['code' => 1, 'msg' => '没有需要更新的数据']);
        }

        $updateData['update_time'] = date('Y-m-d H:i:s');

        try {
            Db::name('game')->where('id', $gameId)->where('tenant_id', $tenantId)->update($updateData);
            return json(['code' => 0, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '更新失败: ' . $e->getMessage()]);
        }
    }

}
