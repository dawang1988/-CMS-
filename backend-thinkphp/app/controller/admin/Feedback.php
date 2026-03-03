<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Feedback extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';

        $page = (int)($this->request->param('pageNo') ?: $this->request->param('page', 1));
        $pageSize = (int)$this->request->param('pageSize', 20);
        $status = $this->request->param('status');
        $type = $this->request->param('type');
        $keyword = $this->request->param('keyword');

        $query = Db::name('feedback')->where('tenant_id', $tenantId);
        if ($status !== null && $status !== '') $query->where('status', $status);
        if ($type) $query->where('type', $type);
        if ($keyword) $query->where('content', 'like', '%' . $keyword . '%');

        $total = (clone $query)->count();
        $list = (clone $query)->order('id', 'desc')->page($page, $pageSize)->select()->toArray();

        // 补充用户信息，并映射为前端期望的camelCase字段
        $userIds = array_unique(array_filter(array_column($list, 'user_id')));
        $userMap = $userIds ? Db::name('user')->where('tenant_id', $tenantId)->whereIn('id', $userIds)->column('nickname', 'id') : [];

        foreach ($list as &$item) {
            $item['userName'] = $userMap[$item['user_id'] ?? 0] ?? '-';
            $item['createTime'] = $item['create_time'] ?? '';
            $item['replyTime'] = $item['reply_time'] ?? '';
        }

        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = Db::name('feedback')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if ($data) {
            $user = ($data['user_id'] ?? 0) ? Db::name('user')->where('tenant_id', $tenantId)->where('id', $data['user_id'])->find() : null;
            $data['userName'] = $user['nickname'] ?? '-';
            $data['createTime'] = $data['create_time'] ?? '';
            $data['replyTime'] = $data['reply_time'] ?? '';
        }
        return json(['code' => 0, 'data' => $data]);
    }

    public function reply()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        $reply = $this->request->post('reply');
        Db::name('feedback')->where('tenant_id', $tenantId)->where('id', $id)->update(['reply' => $reply, 'status' => 1, 'reply_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '回复成功']);
    }

    public function complete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        Db::name('feedback')->where('tenant_id', $tenantId)->where('id', $id)->update(['status' => 2]);
        return json(['code' => 0, 'msg' => '已完成']);
    }
}