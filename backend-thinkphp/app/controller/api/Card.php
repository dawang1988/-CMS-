<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class Card extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('card')->where('tenant_id', $tenantId)->where('status', 1)->order('sort', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function mine()
    {
        $userId = $this->request->userId;
        $list = Db::name('user_card')->alias('uc')
            ->leftJoin('card c', 'uc.card_id = c.id')
            ->where('uc.user_id', $userId)
            ->field('uc.id, uc.user_id, uc.card_id, uc.name, uc.type, uc.total_value, uc.remain_value as balance, uc.discount, uc.pay_amount, uc.expire_time, uc.status, uc.create_time')
            ->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function buy()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $cardId = $this->request->post('card_id');

        $card = Db::name('card')->where('id', $cardId)->where('status', 1)->find();
        if (!$card) {
            return json(['code' => 1, 'msg' => '会员卡不存在']);
        }

        $price = (float)$card['price'];
        if ($price <= 0) {
            return json(['code' => 1, 'msg' => '会员卡价格异常']);
        }

        // 生成订单号
        $orderNo = 'C' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // 创建会员卡购买订单（待支付状态）
        Db::name('card_order')->insert([
            'tenant_id' => $tenantId,
            'order_no' => $orderNo,
            'user_id' => $userId,
            'card_id' => $cardId,
            'amount' => $price,
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        // 调用微信支付
        $user = Db::name('user')->where('id', $userId)->find();
        $payService = new \app\service\PayService($tenantId);
        $result = $payService->createWechatOrder($orderNo, $price, $user['openid'] ?? '', '购买会员卡-' . $card['name']);

        if ($result['code'] == 0) {
            $payData = $result['data'];
            return json([
                'code' => 0,
                'data' => [
                    'order_no' => $orderNo,
                    'timeStamp' => $payData['timeStamp'] ?? '',
                    'nonceStr' => $payData['nonceStr'] ?? '',
                    'pkg' => $payData['package'] ?? '',
                    'signType' => $payData['signType'] ?? '',
                    'paySign' => $payData['paySign'] ?? '',
                ]
            ]);
        }

        return json($result);
    }

    public function checkEnabled()
    {
        $storeId = $this->request->param('store_id');
        if (!$storeId) {
            return json(['code' => 1, 'msg' => '缺少store_id参数']);
        }
        $store = Db::name('store')->field('card_enabled')->find($storeId);
        return json(['code' => 0, 'data' => ['enabled' => $store['card_enabled'] ?? 0]]);
    }

    public function getMyCardPage()
    {
        $userId = $this->request->userId;
        $storeId = $this->request->param('store_id');
        $list = Db::name('user_card')->alias('uc')
            ->leftJoin('card c', 'uc.card_id = c.id')
            ->where('uc.user_id', $userId)
            ->field('uc.id, uc.user_id, uc.card_id, uc.name, uc.type, uc.total_value, uc.remain_value, uc.discount, uc.pay_amount, uc.expire_time, uc.status, uc.create_time')
            ->order('uc.id', 'desc')->select()->toArray();

        $typeTexts = [1 => '次卡', 2 => '时长卡', 3 => '储值卡'];
        $statusTexts = [0 => '未激活', 1 => '正常', 2 => '已用完', 3 => '已过期'];

        foreach ($list as &$item) {
            $item['type_text'] = $typeTexts[$item['type']] ?? '未知';
            $item['status_text'] = $statusTexts[$item['status']] ?? '未知';
            // 折扣文本
            if ($item['discount'] && $item['discount'] < 1) {
                $item['discount_text'] = ($item['discount'] * 10) . '折';
            } else {
                $item['discount_text'] = '';
            }
            // 过期文本
            if ($item['expire_time']) {
                $expire = strtotime($item['expire_time']);
                $days = max(0, (int)ceil(($expire - time()) / 86400));
                $item['expire_text'] = $days > 0 ? $days . '天后过期' : '已过期';
            } else {
                $item['expire_text'] = '永久有效';
            }
            // 进度条
            if ($item['total_value'] > 0) {
                $item['progress'] = round(($item['remain_value'] / $item['total_value']) * 100);
            } else {
                $item['progress'] = 0;
            }
        }
        unset($item);

        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function getSaleCardPage()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $store = Db::name('store')->field('card_enabled')->find($storeId);
        $list = Db::name('card')->where('tenant_id', $tenantId)->where('status', 1)->order('sort', 'asc')->select()->toArray();

        $typeTexts = [1 => '次卡', 2 => '时长卡', 3 => '储值卡'];

        foreach ($list as &$item) {
            $item['type_text'] = $typeTexts[$item['type']] ?? '未知';
            // 折扣文本
            if ($item['discount'] && $item['discount'] < 1) {
                $item['discount_text'] = ($item['discount'] * 10) . '折';
            } else {
                $item['discount_text'] = '';
            }
            // 有效期文本
            if (isset($item['valid_days']) && $item['valid_days'] > 0) {
                $item['valid_text'] = $item['valid_days'] . '天';
            } else {
                $item['valid_text'] = '永久有效';
            }
        }
        unset($item);

        return json(['code' => 0, 'data' => ['list' => $list, 'card_enabled' => $store['card_enabled'] ?? 0]]);
    }

    public function getAvailableCards()
    {
        $userId = $this->request->userId;
        $storeId = $this->request->param('store_id');
        $amount = $this->request->param('amount', 0);
        $list = Db::name('user_card')->alias('uc')
            ->leftJoin('card c', 'uc.card_id = c.id')
            ->where('uc.user_id', $userId)->where('uc.status', 1)
            ->where('uc.remain_value', '>', 0)
            ->where(function($query) {
                $query->whereNull('uc.expire_time')->whereOr('uc.expire_time', '>', date('Y-m-d H:i:s'));
            })
            ->field('uc.id, uc.user_id, uc.card_id, uc.store_id, uc.name, uc.type, uc.total_value, uc.remain_value, uc.discount, uc.pay_amount, uc.expire_time, uc.status, uc.create_time')
            ->select()->toArray();

        // 计算过期文本和剩余时长
        foreach ($list as &$item) {
            if ($item['expire_time']) {
                $expire = strtotime($item['expire_time']);
                $days = max(0, (int)ceil(($expire - time()) / 86400));
                $item['expire_text'] = $days > 0 ? $days . '天后' : '已过期';
            } else {
                $item['expire_text'] = '永久';
            }
            // 时长卡：remain_value是分钟，转换为小时
            if ($item['type'] == 2) {
                $item['remain_hours'] = round($item['remain_value'] / 60, 1);
            }
        }
        unset($item);

        return json(['code' => 0, 'data' => $list]);
    }
}
