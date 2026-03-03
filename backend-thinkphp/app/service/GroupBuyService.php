<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\service\LogService as StructuredLog;

class GroupBuyService
{
    /**
     * 验证团购券码
     * 
     * @param string $code 券码
     * @param int $storeId 门店ID
     * @param string $tenantId 租户ID
     * @return array 验证结果
     */
    public static function verify(string $code, int $storeId, string $tenantId = '88888888'): array
    {
        // 1. 基础格式验证
        $formatCheck = self::validateCodeFormat($code);
        if ($formatCheck['code'] !== 0) {
            StructuredLog::warning('团购券码格式错误', [
                'code' => $code,
                'store_id' => $storeId,
                'reason' => $formatCheck['msg'],
            ]);
            return $formatCheck;
        }

        // 2. 使用数据库锁防止并发重复核销
        Db::startTrans();
        try {
            // 加锁查询已使用记录
            $usedLog = Db::name('group_verify_log')
                ->where('group_pay_no', $code)
                ->where('status', 1)
                ->lock(true)
                ->find();
            
            if ($usedLog) {
                Db::rollback();
                StructuredLog::warning('团购券码已被使用', [
                    'code' => $code,
                    'store_id' => $storeId,
                    'verify_log_id' => $usedLog['id'],
                    'used_time' => $usedLog['create_time'],
                ]);
                return ['code' => 1, 'msg' => '该团购券码已被使用'];
            }

            // 加锁查询订单使用记录
            $usedOrder = Db::name('order')
                ->where('group_pay_no', $code)
                ->where('pay_status', 1)
                ->lock(true)
                ->find();
            
            if ($usedOrder) {
                Db::rollback();
                StructuredLog::warning('团购券码已在订单中使用', [
                    'code' => $code,
                    'store_id' => $storeId,
                    'order_id' => $usedOrder['id'],
                    'order_no' => $usedOrder['order_no'],
                ]);
                return ['code' => 1, 'msg' => '该团购券码已被使用'];
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            StructuredLog::error('团购券验证查询异常', [
                'code' => $code,
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);
            return ['code' => 1, 'msg' => '验证失败，请稍后重试'];
        }

        // 3. 获取门店信息
        $store = Db::name('store')->where('id', $storeId)->find();
        if (!$store) {
            return ['code' => 1, 'msg' => '门店不存在'];
        }

        // 4. 调用平台API验证
        $platformResult = null;
        
        // 优先验证美团
        if (self::checkPlatformAuth($store, 'meituan')) {
            $platformResult = self::verifyMeituan($code, $store, $tenantId);
        }
        
        // 如果美团验证失败，尝试抖音
        if (!$platformResult && self::checkPlatformAuth($store, 'douyin')) {
            $platformResult = self::verifyDouyin($code, $store, $tenantId);
        }

        // 5. 返回验证结果
        if ($platformResult) {
            if ($platformResult['code'] === 0) {
                StructuredLog::info('团购券验证成功', [
                    'code' => $code,
                    'store_id' => $storeId,
                    'platform' => $platformResult['data']['platform'] ?? 'unknown',
                    'title' => $platformResult['data']['title'] ?? '',
                ]);
                return $platformResult;
            }
            
            StructuredLog::warning('团购券验证失败', [
                'code' => $code,
                'store_id' => $storeId,
                'msg' => $platformResult['msg'],
            ]);
            return ['code' => 1, 'msg' => $platformResult['msg'] ?? '团购券码验证失败，请稍后重试'];
        }

        StructuredLog::warning('门店未开通团购验券功能', [
            'store_id' => $storeId,
            'tenant_id' => $tenantId,
        ]);
        return ['code' => 1, 'msg' => '该门店未开通团购验券功能，请联系店家'];
    }

    /**
     * 验证券码格式
     * 
     * @param string $code 券码
     * @return array 验证结果
     */
    private static function validateCodeFormat(string $code): array
    {
        if (empty($code)) {
            return ['code' => 1, 'msg' => '券码不能为空'];
        }

        $code = trim($code);
        $length = mb_strlen($code);

        // 基础长度验证
        if ($length < 6) {
            return ['code' => 1, 'msg' => '请输入正确的团购券码（至少6位）'];
        }

        if ($length > 100) {
            return ['code' => 1, 'msg' => '券码长度超出限制'];
        }

        // 检查是否包含非法字符
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $code)) {
            return ['code' => 1, 'msg' => '券码格式不正确，只能包含字母、数字、横线和下划线'];
        }

        return ['code' => 0, 'msg' => '格式验证通过'];
    }

    /**
     * 检查平台授权状态
     * 
     * @param array $store 门店信息
     * @param string $platform 平台名称
     * @return bool 是否已授权且未过期
     */
    private static function checkPlatformAuth(array $store, string $platform): bool
    {
        $authField = $platform . '_auth';
        $tokenField = $platform . '_access_token';
        $expireField = $platform . '_expire';

        // 检查是否已授权
        if (empty($store[$authField]) || empty($store[$tokenField])) {
            return false;
        }

        // 检查Token是否过期
        if (!empty($store[$expireField])) {
            $expireTime = strtotime($store[$expireField]);
            $now = time();
            
            // Token已过期
            if ($expireTime <= $now) {
                StructuredLog::warning('平台授权已过期', [
                    'store_id' => $store['id'],
                    'platform' => $platform,
                    'expire_time' => $store[$expireField],
                ]);
                return false;
            }
            
            // Token即将过期(7天内),记录警告
            if ($expireTime - $now < 7 * 86400) {
                StructuredLog::warning('平台授权即将过期', [
                    'store_id' => $store['id'],
                    'platform' => $platform,
                    'expire_time' => $store[$expireField],
                    'days_left' => floor(($expireTime - $now) / 86400),
                ]);
            }
        }

        return true;
    }
    /**
     * 美团平台验券
     * 
     * @param string $code 券码
     * @param array $store 门店信息
     * @param string $tenantId 租户ID
     * @return array|null 验证结果
     */
    private static function verifyMeituan(string $code, array $store, string $tenantId): ?array
    {
        $startTime = microtime(true);
        
        try {
            $config = Db::name('config')
                ->where('tenant_id', $tenantId)
                ->whereIn('config_key', ['meituan_app_key', 'meituan_app_secret'])
                ->column('config_value', 'config_key');

            $appKey = $config['meituan_app_key'] ?? '';
            if (empty($appKey)) {
                StructuredLog::warning('美团配置缺失', [
                    'store_id' => $store['id'],
                    'tenant_id' => $tenantId,
                ]);
                return null;
            }

            $accessToken = $store['meituan_access_token'];
            $shopId = $store['meituan_shop_id'] ?? '';

            $url = 'https://open.meituan.com/api/v1/tuangou/receipt/prepare';
            $params = [
                'app_key' => $appKey,
                'access_token' => $accessToken,
                'receipt_code' => $code,
            ];
            if ($shopId) {
                $params['shop_id'] = $shopId;
            }

            $response = self::httpGet($url . '?' . http_build_query($params));
            $result = json_decode($response, true);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            StructuredLog::info('美团验券API调用', [
                'code' => $code,
                'store_id' => $store['id'],
                'duration_ms' => $duration,
                'response_length' => strlen($response),
                'success' => !empty($result),
            ]);

            if (!$result) {
                StructuredLog::error('美团验券接口无响应', [
                    'code' => $code,
                    'store_id' => $store['id'],
                    'url' => $url,
                    'response' => $response,
                ]);
                return ['code' => 1, 'msg' => '美团验券接口无响应，请稍后重试'];
            }

            $error = $result['error'] ?? $result;
            $errorCode = $error['code'] ?? ($result['code'] ?? -1);

            if ($errorCode === 0 && !empty($result['data'])) {
                $data = $result['data'];
                $hours = 2;
                $groupCouponId = 0;
                $price = (float)($data['deal_price'] ?? 0);
                
                $localCoupon = Db::name('group_coupon')
                    ->where('store_id', $store['id'])
                    ->where('platform', 'meituan')
                    ->where('status', 1)
                    ->find();
                
                if ($localCoupon) {
                    $hours = (float)($localCoupon['hours'] ?? 2);
                    $groupCouponId = (int)$localCoupon['id'];
                    // 如果本地配置了价格,使用本地价格
                    if (!empty($localCoupon['price'])) {
                        $price = (float)$localCoupon['price'];
                    }
                }

                return [
                    'code' => 0,
                    'data' => [
                        'code' => $code,
                        'valid' => true,
                        'hours' => $hours >= 99 ? 99 : (int)$hours,
                        'title' => $data['coupon_name'] ?? $data['deal_title'] ?? '美团团购券',
                        'group_coupon_id' => $groupCouponId,
                        'platform' => 'meituan',
                        'price' => $price,
                        'verify_source' => 'meituan_api',
                        'platform_data' => $data,
                    ],
                ];
            }

            $msg = $error['msg'] ?? $result['msg'] ?? '美团验券失败';
            
            StructuredLog::warning('美团验券失败', [
                'code' => $code,
                'store_id' => $store['id'],
                'error_code' => $errorCode,
                'error_msg' => $msg,
            ]);
            
            if (stripos($msg, '已验') !== false || stripos($msg, '已使用') !== false) {
                return ['code' => 1, 'msg' => '该美团券码已被核销', 'invalid' => true];
            }
            if (stripos($msg, '不存在') !== false || stripos($msg, '无效') !== false) {
                return ['code' => 1, 'msg' => '美团券码无效', 'invalid' => true];
            }

            return ['code' => 1, 'msg' => $msg];
        } catch (\Exception $e) {
            StructuredLog::error('美团验券异常', [
                'code' => $code,
                'store_id' => $store['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['code' => 1, 'msg' => '美团验券失败，请稍后重试'];
        }
    }

    /**
     * 抖音平台验券
     * 
     * @param string $code 券码
     * @param array $store 门店信息
     * @param string $tenantId 租户ID
     * @return array|null 验证结果
     */
    private static function verifyDouyin(string $code, array $store, string $tenantId): ?array
    {
        $startTime = microtime(true);
        
        try {
            $config = Db::name('config')
                ->where('tenant_id', $tenantId)
                ->whereIn('config_key', ['douyin_client_key', 'douyin_client_secret'])
                ->column('config_value', 'config_key');

            $clientKey = $config['douyin_client_key'] ?? '';
            if (empty($clientKey)) {
                StructuredLog::warning('抖音配置缺失', [
                    'store_id' => $store['id'],
                    'tenant_id' => $tenantId,
                ]);
                return null;
            }

            $accessToken = $store['douyin_access_token'];
            $accountId = $store['douyin_id'] ?? '';

            $url = 'https://open.douyin.com/goodlife/v1/fulfilment/certificate/prepare/';
            $headers = [
                'Content-Type: application/json',
                'access-token: ' . $accessToken,
            ];
            $body = json_encode([
                'encrypted_data' => $code,
                'code' => $code,
            ]);
            if ($accountId) {
                $bodyArr = json_decode($body, true);
                $bodyArr['account_id'] = $accountId;
                $body = json_encode($bodyArr);
            }

            $response = self::httpPostJson($url, $body, $headers);
            $result = json_decode($response, true);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            StructuredLog::info('抖音验券API调用', [
                'code' => $code,
                'store_id' => $store['id'],
                'duration_ms' => $duration,
                'response_length' => strlen($response),
                'success' => !empty($result),
            ]);

            if (!$result) {
                StructuredLog::error('抖音验券接口无响应', [
                    'code' => $code,
                    'store_id' => $store['id'],
                    'url' => $url,
                    'response' => $response,
                ]);
                return ['code' => 1, 'msg' => '抖音验券接口无响应，请稍后重试'];
            }

            $extra = $result['extra'] ?? [];
            $errNo = $extra['error_code'] ?? ($result['err_no'] ?? -1);

            if ($errNo === 0 && !empty($result['data'])) {
                $data = $result['data'];
                $hours = 2;
                $groupCouponId = 0;
                $price = (float)($data['original_amount'] ?? 0) / 100;
                
                $localCoupon = Db::name('group_coupon')
                    ->where('store_id', $store['id'])
                    ->where('platform', 'douyin')
                    ->where('status', 1)
                    ->find();
                
                if ($localCoupon) {
                    $hours = (float)($localCoupon['hours'] ?? 2);
                    $groupCouponId = (int)$localCoupon['id'];
                    // 如果本地配置了价格,使用本地价格
                    if (!empty($localCoupon['price'])) {
                        $price = (float)$localCoupon['price'];
                    }
                }

                return [
                    'code' => 0,
                    'data' => [
                        'code' => $code,
                        'valid' => true,
                        'hours' => $hours >= 99 ? 99 : (int)$hours,
                        'title' => $data['sku_name'] ?? $data['title'] ?? '抖音团购券',
                        'group_coupon_id' => $groupCouponId,
                        'platform' => 'douyin',
                        'price' => $price,
                        'verify_source' => 'douyin_api',
                        'platform_data' => $data,
                    ],
                ];
            }

            $msg = $extra['description'] ?? $result['err_tips'] ?? '抖音验券失败';
            
            StructuredLog::warning('抖音验券失败', [
                'code' => $code,
                'store_id' => $store['id'],
                'error_code' => $errNo,
                'error_msg' => $msg,
            ]);
            
            if (stripos($msg, '已核销') !== false || stripos($msg, '已使用') !== false) {
                return ['code' => 1, 'msg' => '该抖音券码已被核销', 'invalid' => true];
            }
            if (stripos($msg, '不存在') !== false || stripos($msg, '无效') !== false) {
                return ['code' => 1, 'msg' => '抖音券码无效', 'invalid' => true];
            }

            return ['code' => 1, 'msg' => $msg];
        } catch (\Exception $e) {
            StructuredLog::error('抖音验券异常', [
                'code' => $code,
                'store_id' => $store['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['code' => 1, 'msg' => '抖音验券失败，请稍后重试'];
        }
    }

    /**
     * 核销团购券
     * 
     * @param string $code 券码
     * @param int $storeId 门店ID
     * @param string $tenantId 租户ID
     * @param array $extra 额外信息
     * @return bool 是否成功
     */
    public static function consume(string $code, int $storeId, string $tenantId, array $extra = []): bool
    {
        Db::startTrans();
        try {
            // 1. 记录核销日志
            Db::name('group_verify_log')->insert([
                'tenant_id' => $tenantId,
                'store_id' => $storeId,
                'group_coupon_id' => $extra['group_coupon_id'] ?? 0,
                'group_pay_no' => $code,
                'platform' => $extra['platform'] ?? '',
                'title' => $extra['title'] ?? '',
                'hours' => $extra['hours'] ?? 0,
                'order_id' => $extra['order_id'] ?? null,
                'order_no' => $extra['order_no'] ?? '',
                'user_id' => $extra['user_id'] ?? null,
                'verify_type' => $extra['verify_type'] ?? 1,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            // 2. 更新团购券配置统计
            $groupCouponId = $extra['group_coupon_id'] ?? 0;
            if ($groupCouponId > 0) {
                $price = $extra['price'] ?? 0;
                Db::name('group_coupon')
                    ->where('id', $groupCouponId)
                    ->inc('use_count', 1)
                    ->inc('use_amount', $price)
                    ->update([
                        'last_use_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
            }

            Db::commit();

            StructuredLog::info('团购券核销成功', [
                'code' => $code,
                'store_id' => $storeId,
                'order_no' => $extra['order_no'] ?? '',
                'platform' => $extra['platform'] ?? '',
                'group_coupon_id' => $groupCouponId,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            
            StructuredLog::error('团购券核销失败', [
                'code' => $code,
                'store_id' => $storeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * 退款处理 - 更新核销记录状态
     * 
     * @param int $orderId 订单ID
     * @param string $tenantId 租户ID
     * @return bool 是否成功
     */
    public static function refund(int $orderId, string $tenantId): bool
    {
        Db::startTrans();
        try {
            // 1. 查找核销记录
            $verifyLog = Db::name('group_verify_log')
                ->where('order_id', $orderId)
                ->where('tenant_id', $tenantId)
                ->where('status', 1)
                ->find();

            if (!$verifyLog) {
                Db::commit();
                return true; // 没有核销记录,直接返回成功
            }

            // 2. 更新核销记录状态为已退款
            Db::name('group_verify_log')
                ->where('id', $verifyLog['id'])
                ->update([
                    'status' => 2, // 2=已退款
                    'refund_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);

            // 3. 更新团购券配置统计(减少使用次数和金额)
            $groupCouponId = $verifyLog['group_coupon_id'] ?? 0;
            if ($groupCouponId > 0) {
                $groupCoupon = Db::name('group_coupon')->where('id', $groupCouponId)->find();
                if ($groupCoupon) {
                    $price = (float)($groupCoupon['price'] ?? 0);
                    Db::name('group_coupon')
                        ->where('id', $groupCouponId)
                        ->dec('use_count', 1)
                        ->dec('use_amount', $price)
                        ->update([
                            'update_time' => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            Db::commit();

            StructuredLog::info('团购券退款处理成功', [
                'order_id' => $orderId,
                'verify_log_id' => $verifyLog['id'],
                'group_pay_no' => $verifyLog['group_pay_no'],
                'group_coupon_id' => $groupCouponId,
            ]);

            return true;
        } catch (\Exception $e) {
            Db::rollback();
            
            StructuredLog::error('团购券退款处理失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    private static function httpGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: '';
    }

    private static function httpPostJson(string $url, string $body, array $headers = []): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: '';
    }
}
