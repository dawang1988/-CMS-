<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\PayService;
use app\service\OrderService;
use app\service\WechatNotifyService;
use think\facade\Db;
use think\facade\Log;

/**
 * 支付控制器
 */
class Pay extends BaseController
{
    /**
     * 微信支付回调
     */
    public function notify()
    {
        $xml = file_get_contents('php://input');
        $data = $this->xmlToArray($xml);

        Log::info('微信支付回调', $data);

        if ($data['return_code'] !== 'SUCCESS') {
            return $this->returnWechatXml('FAIL', '通信失败');
        }

        $orderNo = $data['out_trade_no'] ?? '';

        // 根据订单号前缀区分：R开头为充值订单，C开头为会员卡订单，P开头为商品订单，其他为普通订单
        if (str_starts_with($orderNo, 'R')) {
            return $this->handleRechargeNotify($orderNo, $data);
        }
        if (str_starts_with($orderNo, 'C')) {
            return $this->handleCardNotify($orderNo, $data);
        }
        if (str_starts_with($orderNo, 'P')) {
            return $this->handleProductOrderNotify($orderNo, $data);
        }

        return $this->handleOrderNotify($orderNo, $data);
    }

    /**
     * 处理会员卡购买支付回调
     */
    private function handleCardNotify(string $orderNo, array $data)
    {
        $cardOrder = Db::name('card_order')->where('order_no', $orderNo)->find();

        if (!$cardOrder) {
            return $this->returnWechatXml('FAIL', '会员卡订单不存在');
        }

        if ($cardOrder['status'] != 0) {
            return $this->returnWechatXml('SUCCESS', 'OK');
        }

        // 验证签名
        $payService = new PayService($cardOrder['tenant_id'] ?? '88888888');
        if (!$payService->wechatCallback($data)) {
            return $this->returnWechatXml('FAIL', '签名验证失败');
        }

        $card = Db::name('card')->where('id', $cardOrder['card_id'])->find();
        if (!$card) {
            return $this->returnWechatXml('FAIL', '会员卡不存在');
        }

        Db::startTrans();
        try {
            // 创建用户会员卡
            $userCardId = Db::name('user_card')->insertGetId([
                'tenant_id' => $cardOrder['tenant_id'],
                'user_id' => $cardOrder['user_id'],
                'card_id' => $cardOrder['card_id'],
                'store_id' => $card['store_id'] ?? null,
                'name' => $card['name'],
                'type' => $card['type'],
                'total_value' => $card['value'],
                'remain_value' => $card['value'],
                'discount' => $card['discount'] ?? 1,
                'pay_amount' => $cardOrder['amount'],
                'expire_time' => ($card['valid_days'] ?? 0) > 0 ? date('Y-m-d H:i:s', strtotime('+' . $card['valid_days'] . ' days')) : null,
                'status' => 1,
                'buy_time' => date('Y-m-d H:i:s'),
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            // 记录购买日志
            Db::name('card_log')->insert([
                'tenant_id' => $cardOrder['tenant_id'],
                'user_card_id' => $userCardId,
                'user_id' => $cardOrder['user_id'],
                'order_id' => null,
                'type' => 1, // 1=购买
                'change_value' => $card['value'],
                'before_value' => 0,
                'after_value' => $card['value'],
                'remark' => '购买会员卡',
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            // 更新订单状态
            Db::name('card_order')->where('id', $cardOrder['id'])->update([
                'status' => 1,
                'pay_time' => date('Y-m-d H:i:s'),
            ]);
            
            // 更新会员卡统计
            Db::name('card')
                ->where('id', $cardOrder['card_id'])
                ->inc('sale_count')
                ->inc('sale_amount', $cardOrder['amount'])
                ->update([
                    'last_sale_time' => date('Y-m-d H:i:s')
                ]);

            Db::commit();
            Log::info("会员卡购买成功: 用户{$cardOrder['user_id']}, 会员卡{$card['name']}");
            return $this->returnWechatXml('SUCCESS', 'OK');
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('会员卡购买回调处理失败: ' . $e->getMessage());
            return $this->returnWechatXml('FAIL', '处理失败');
        }
    }

    /**
     * 处理普通订单支付回调
     */
    private function handleOrderNotify(string $orderNo, array $data)
    {
        $order = Db::name('order')->where('order_no', $orderNo)->find();

        if (!$order) {
            return $this->returnWechatXml('FAIL', '订单不存在');
        }

        if ($order['status'] != 0) {
            return $this->returnWechatXml('SUCCESS', 'OK');
        }

        // 验证签名
        $payService = new PayService($order['tenant_id'] ?? '88888888');
        if (!$payService->wechatCallback($data)) {
            return $this->returnWechatXml('FAIL', '签名验证失败');
        }

        // 处理支付成功
        $result = OrderService::paySuccess($orderNo, 1);

        if ($result) {
            try {
                $orderModel = \app\model\Order::find($order['id']);
                WechatNotifyService::sendPaySuccess($orderModel);
            } catch (\Exception $e) {
                Log::error('发送支付通知失败: ' . $e->getMessage());
            }
            return $this->returnWechatXml('SUCCESS', 'OK');
        }

        return $this->returnWechatXml('FAIL', '处理失败');
    }

    /**
     * 处理充值订单支付回调
     */
    private function handleRechargeNotify(string $orderNo, array $data)
    {
        $rechargeOrder = Db::name('recharge_order')->where('recharge_no', $orderNo)->find();

        if (!$rechargeOrder) {
            return $this->returnWechatXml('FAIL', '充值订单不存在');
        }

        // 已处理过，直接返回成功（防止重复回调）
        if ($rechargeOrder['status'] != 0) {
            return $this->returnWechatXml('SUCCESS', 'OK');
        }

        // 验证签名
        $payService = new PayService($rechargeOrder['tenant_id'] ?? '88888888');
        if (!$payService->wechatCallback($data)) {
            return $this->returnWechatXml('FAIL', '签名验证失败');
        }

        $userId = $rechargeOrder['user_id'];
        $amount = (float)$rechargeOrder['amount'];
        $giftAmount = (float)($rechargeOrder['gift_amount'] ?? 0);
        $storeId = (int)($rechargeOrder['store_id'] ?? 0);
        $tenantId = $rechargeOrder['tenant_id'] ?? '88888888';

        try {
            // 使用门店余额服务充值
            \app\service\StoreBalanceService::recharge(
                $userId,
                $storeId,
                $amount,
                $giftAmount,
                $tenantId,
                '在线充值'
            );

            // 更新充值订单状态
            Db::name('recharge_order')->where('id', $rechargeOrder['id'])->update([
                'status' => 1,
                'pay_time' => date('Y-m-d H:i:s'),
            ]);
            
            // 更新充值规则使用统计
            if ($giftAmount > 0) {
                // 查找匹配的充值规则
                $rule = Db::name('discount_rule')
                    ->where('tenant_id', $tenantId)
                    ->where('store_id', $storeId)
                    ->where('pay_money', $amount)
                    ->where('status', 1)
                    ->find();
                
                if ($rule) {
                    // 更新使用统计
                    Db::name('discount_rule')
                        ->where('id', $rule['id'])
                        ->inc('use_count')
                        ->inc('use_amount', $amount)
                        ->update([
                            'last_use_time' => date('Y-m-d H:i:s')
                        ]);
                }
            }

            Log::info("充值成功: 用户{$userId}, 门店{$storeId}, 充值{$amount}(余额), 赠送{$giftAmount}(赠送余额)");
            return $this->returnWechatXml('SUCCESS', 'OK');
        } catch (\Exception $e) {
            Log::error('充值回调处理失败: ' . $e->getMessage());
            return $this->returnWechatXml('FAIL', '处理失败');
        }
    }

    /**
     * 处理商品订单支付回调
     */
    private function handleProductOrderNotify(string $orderNo, array $data)
    {
        $order = Db::name('product_order')->where('order_no', $orderNo)->find();
        if (!$order) {
            return $this->returnWechatXml('FAIL', '商品订单不存在');
        }
        if ($order['status'] != 0) {
            return $this->returnWechatXml('SUCCESS', 'OK');
        }

        $payService = new PayService($order['tenant_id'] ?? '88888888');
        if (!$payService->wechatCallback($data)) {
            return $this->returnWechatXml('FAIL', '签名验证失败');
        }

        Db::name('product_order')->where('id', $order['id'])->update([
            'status' => 1,
            'pay_type' => 1,
            'pay_time' => date('Y-m-d H:i:s'),
        ]);

        Log::info("商品订单支付成功: order_no={$orderNo}");
        return $this->returnWechatXml('SUCCESS', 'OK');
    }

    /**
     * 微信支付回调（路由别名）
     */
    public function wechatCallback()
    {
        return $this->notify();
    }

    private function returnWechatXml(string $code, string $msg): string
    {
        return "<xml><return_code><![CDATA[{$code}]]></return_code><return_msg><![CDATA[{$msg}]]></return_msg></xml>";
    }

    private function xmlToArray(string $xml): array
    {
        // PHP 8.0+ 安全的 XML 解析方式
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);

        $result = [];
        if ($dom->documentElement) {
            foreach ($dom->documentElement->childNodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE) {
                    $result[$node->nodeName] = $node->textContent;
                }
            }
        }
        return $result;
    }
}
