<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

class Config extends BaseController
{
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('config')->where('tenant_id', $tenantId)->select()->toArray();
        // Frontend expects camelCase: configKey, configValue, remark
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id'          => $item['id'] ?? null,
                'configKey'   => $item['config_key'] ?? '',
                'configValue' => $item['config_value'] ?? '',
                'remark'      => $item['description'] ?? $item['remark'] ?? '',
                'createTime'  => $item['create_time'] ?? '',
                'updateTime'  => $item['update_time'] ?? '',
            ];
        }
        return json(['code' => 0, 'data' => $result]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        // Support both JSON body and form data
        $input = $this->request->getContent();
        $data = json_decode($input, true);
        if (!$data) {
            $data = $this->request->post();
        }

        $key = $data['configKey'] ?? $data['config_key'] ?? '';
        $value = $data['configValue'] ?? $data['config_value'] ?? '';
        $remark = $data['remark'] ?? '';
        $isDelete = $data['_delete'] ?? false;

        if (!$key) {
            return json(['code' => 1, 'msg' => '配置键不能为空']);
        }

        // Handle delete
        if ($isDelete) {
            Db::name('config')->where('tenant_id', $tenantId)->where('config_key', $key)->delete();
            return json(['code' => 0, 'msg' => '删除成功']);
        }

        $exists = Db::name('config')->where('tenant_id', $tenantId)->where('config_key', $key)->find();
        if ($exists) {
            $updateData = ['config_value' => $value, 'update_time' => date('Y-m-d H:i:s')];
            if ($remark) {
                $updateData['description'] = $remark;
            }
            Db::name('config')->where('tenant_id', $tenantId)->where('id', $exists['id'])->update($updateData);
        } else {
            Db::name('config')->insert([
                'tenant_id'    => $tenantId,
                'config_key'   => $key,
                'config_value' => $value,
                'description'  => $remark,
                'create_time'  => date('Y-m-d H:i:s'),
            ]);
        }
        // 清除缓存
        \app\service\CacheService::clearConfig($tenantId, $key);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function payment()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        // 返回所有支付相关配置
        $prefixes = ['wx_', 'ali_', 'sms_', 'balance_', 'order_', 'wechat_', 'pay_', 'group_', 'meituan_', 'douyin_'];
        $allConfigs = Db::name('config')->where('tenant_id', $tenantId)->select()->toArray();
        $result = [];
        foreach ($allConfigs as $item) {
            $key = $item['config_key'] ?? '';
            foreach ($prefixes as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $result[$key] = $item['config_value'] ?? '';
                    break;
                }
            }
        }
        return json(['code' => 0, 'data' => $result]);
    }

    public function savePayment()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $input = $this->request->getContent();
        $data = json_decode($input, true);
        if (!$data) {
            $data = $this->request->post();
        }
        unset($data['tenantId'], $data['tenant_id']);

        // 验证配置
        $validation = $this->validatePaymentConfig($data);
        if ($validation['code'] !== 0) {
            return json($validation);
        }

        // 记录修改前的值（用于日志）
        $oldValues = [];
        foreach ($data as $key => $value) {
            $old = Db::name('config')
                ->where('tenant_id', $tenantId)
                ->where('config_key', $key)
                ->value('config_value');
            if ($old !== null) {
                $oldValues[$key] = $old;
            }
        }

        // 保存配置
        foreach ($data as $key => $value) {
            $exists = Db::name('config')->where('tenant_id', $tenantId)->where('config_key', $key)->find();
            if ($exists) {
                Db::name('config')->where('tenant_id', $tenantId)->where('id', $exists['id'])->update(['config_value' => $value, 'update_time' => date('Y-m-d H:i:s')]);
            } else {
                Db::name('config')->insert(['tenant_id' => $tenantId, 'config_key' => $key, 'config_value' => $value, 'create_time' => date('Y-m-d H:i:s')]);
            }
            // 清除该配置项的缓存，使新值立即生效
            \app\service\CacheService::clearConfig($tenantId, $key);
        }

        // 记录操作日志
        $this->logConfigChange($tenantId, 'payment', $oldValues, $data);

        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 验证支付配置
     */
    private function validatePaymentConfig($data)
    {
        // 微信支付必填项
        if (isset($data['wx_enabled']) && $data['wx_enabled'] === '1') {
            if (empty($data['wx_appid'])) {
                return ['code' => 1, 'msg' => '微信支付已启用，AppID不能为空'];
            }
            if (empty($data['wx_mch_id'])) {
                return ['code' => 1, 'msg' => '微信支付已启用，商户号不能为空'];
            }
            if (empty($data['wx_mch_key'])) {
                return ['code' => 1, 'msg' => '微信支付已启用，API密钥不能为空'];
            }
            // 验证格式
            if (!preg_match('/^wx[a-zA-Z0-9]{16}$/', $data['wx_appid'])) {
                return ['code' => 1, 'msg' => 'AppID格式不正确，应为wx开头的18位字符'];
            }
            if (strlen($data['wx_mch_key']) !== 32) {
                return ['code' => 1, 'msg' => 'API密钥应为32位字符'];
            }
        }

        // 支付宝必填项
        if (isset($data['ali_enabled']) && $data['ali_enabled'] === '1') {
            if (empty($data['ali_appid'])) {
                return ['code' => 1, 'msg' => '支付宝已启用，AppID不能为空'];
            }
            if (empty($data['ali_private_key'])) {
                return ['code' => 1, 'msg' => '支付宝已启用，应用私钥不能为空'];
            }
            if (empty($data['ali_public_key'])) {
                return ['code' => 1, 'msg' => '支付宝已启用，支付宝公钥不能为空'];
            }
        }

        // 短信服务必填项
        if (isset($data['sms_enabled']) && $data['sms_enabled'] === '1') {
            if (empty($data['sms_access_key'])) {
                return ['code' => 1, 'msg' => '短信服务已启用，AccessKey不能为空'];
            }
            if (empty($data['sms_access_secret'])) {
                return ['code' => 1, 'msg' => '短信服务已启用，Secret不能为空'];
            }
            if (empty($data['sms_sign_name'])) {
                return ['code' => 1, 'msg' => '短信服务已启用，短信签名不能为空'];
            }
            if (empty($data['sms_template_code'])) {
                return ['code' => 1, 'msg' => '短信服务已启用，模板编号不能为空'];
            }
        }

        // 订单超时时间验证
        if (isset($data['order_pay_timeout'])) {
            $timeout = intval($data['order_pay_timeout']);
            if ($timeout < 1 || $timeout > 60) {
                return ['code' => 1, 'msg' => '订单超时时间应在1-60分钟之间'];
            }
        }

        return ['code' => 0];
    }

    /**
     * 记录配置修改日志
     */
    private function logConfigChange($tenantId, $type, $oldValues, $newValues)
    {
        try {
            $adminId = $this->request->adminId ?? 0;
            $changes = [];
            
            foreach ($newValues as $key => $newValue) {
                $oldValue = $oldValues[$key] ?? '';
                if ($oldValue !== $newValue) {
                    // 敏感信息遮蔽
                    $displayOld = $this->maskSensitiveValue($key, $oldValue);
                    $displayNew = $this->maskSensitiveValue($key, $newValue);
                    $changes[] = "{$key}: {$displayOld} → {$displayNew}";
                }
            }

            if (!empty($changes)) {
                Db::name('admin_log')->insert([
                    'tenant_id' => $tenantId,
                    'admin_id' => $adminId,
                    'module' => 'config',
                    'action' => 'update_' . $type,
                    'content' => '修改' . $type . '配置: ' . implode('; ', $changes),
                    'ip' => $this->request->ip(),
                    'create_time' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            // 日志记录失败不影响主流程
        }
    }

    /**
     * 遮蔽敏感值
     */
    private function maskSensitiveValue($key, $value)
    {
        $sensitiveKeys = ['secret', 'key', 'password', 'private', 'cert', 'pem'];
        foreach ($sensitiveKeys as $sensitive) {
            if (stripos($key, $sensitive) !== false) {
                if (strlen($value) > 4) {
                    return substr($value, 0, 2) . '****' . substr($value, -2);
                }
                return '****';
            }
        }
        return $value;
    }

    /**
     * 测试微信支付配置
     */
    public function testWechat()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        
        // 获取配置
        $config = [];
        $keys = ['wx_appid', 'wx_app_secret', 'wx_mch_id', 'wx_mch_key'];
        foreach ($keys as $key) {
            $value = Db::name('config')
                ->where('tenant_id', $tenantId)
                ->where('config_key', $key)
                ->value('config_value');
            $config[$key] = $value ?? '';
        }

        // 验证必填项
        if (empty($config['wx_appid']) || empty($config['wx_mch_id'])) {
            return json(['code' => 1, 'msg' => '请先配置微信支付参数']);
        }

        // 测试获取access_token（验证AppID和Secret）
        if (!empty($config['wx_app_secret'])) {
            try {
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$config['wx_appid']}&secret={$config['wx_app_secret']}";
                $result = file_get_contents($url);
                $data = json_decode($result, true);
                
                if (isset($data['access_token'])) {
                    return json(['code' => 0, 'msg' => '微信支付配置测试成功', 'data' => ['appid' => $config['wx_appid']]]);
                } else {
                    return json(['code' => 1, 'msg' => '配置错误: ' . ($data['errmsg'] ?? '未知错误')]);
                }
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => '测试失败: ' . $e->getMessage()]);
            }
        }

        return json(['code' => 0, 'msg' => '配置格式正确（未测试连接）']);
    }

    /**
     * 测试支付宝配置
     */
    public function testAlipay()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        
        // 获取配置
        $appid = Db::name('config')
            ->where('tenant_id', $tenantId)
            ->where('config_key', 'ali_appid')
            ->value('config_value');

        if (empty($appid)) {
            return json(['code' => 1, 'msg' => '请先配置支付宝AppID']);
        }

        // 验证AppID格式
        if (!preg_match('/^20\d{14}$/', $appid)) {
            return json(['code' => 1, 'msg' => 'AppID格式不正确']);
        }

        return json(['code' => 0, 'msg' => '支付宝配置格式正确', 'data' => ['appid' => $appid]]);
    }
}
