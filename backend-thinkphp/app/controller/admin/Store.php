<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog;
use think\facade\Db;
use app\service\RedisService;

class Store extends BaseController
{
    /**
     * 清除门店列表缓存
     */
    private function clearStoreCache($tenantId)
    {
        $cacheKey = 'store:list:' . $tenantId;
        RedisService::delete($cacheKey);
    }
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        $query = Db::name('store')->where('tenant_id', $tenantId);
        $total = $query->count();
        $list = Db::name('store')->where('tenant_id', $tenantId)->order('id desc')->page($page, $pageSize)->select()->toArray();
        return json(['code' => 0, 'data' => ['data' => $list, 'list' => $list, 'total' => $total]]);
    }

    public function get()
    {
        $id = $this->request->param('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->find();
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 构建安全的门店数据（白名单方式）
     */
    private function buildStoreData($data)
    {
        $allowFields = [
            'name', 'city', 'address', 'phone', 'business_hours',
            'wifi_name', 'wifi_password', 'longitude', 'latitude', 'description',
            'status', 'tx_start_hour', 'tx_hour', 'clear_time',
            'delay_light', 'clear_open', 'order_door_open', 'clear_open_door', 'simple_model',
            'order_webhook', 'head_img', 'banner_img', 'env_images',
            'lock_no', 'sound_enabled', 'sound_volume', 'sound_start_time', 'sound_end_time',
            'notice', 'qr_code', 'dy_id', 'template_key',
            'btn_img', 'qh_img', 'tg_img', 'cz_img', 'open_img', 'wifi_img', 'kf_img'
        ];
        
        $saveData = [];
        foreach ($allowFields as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = $data[$field];
            }
        }
        return $saveData;
    }

    private function parseCoordinates(&$data)
    {
        if (!empty($data['longitude']) && strpos($data['longitude'], ',') !== false) {
            $parts = array_map('trim', explode(',', $data['longitude']));
            if (count($parts) === 2) {
                $data['latitude'] = $parts[0];
                $data['longitude'] = $parts[1];
            }
        }
        if (!empty($data['latitude']) && strpos($data['latitude'], ',') !== false) {
            $parts = array_map('trim', explode(',', $data['latitude']));
            if (count($parts) === 2 && (empty($data['longitude']) || strpos($data['longitude'], ',') !== false)) {
                $data['latitude'] = $parts[0];
                $data['longitude'] = $parts[1];
            }
        }
    }

    public function add()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        
        $this->parseCoordinates($data);
        
        // 验证必填字段
        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '门店名称不能为空']);
        }
        
        // 验证经纬度范围
        if (!empty($data['longitude'])) {
            $lng = floatval($data['longitude']);
            if ($lng < -180 || $lng > 180) {
                return json(['code' => 1, 'msg' => '经度范围应在-180到180之间']);
            }
        }
        if (!empty($data['latitude'])) {
            $lat = floatval($data['latitude']);
            if ($lat < -90 || $lat > 90) {
                return json(['code' => 1, 'msg' => '纬度范围应在-90到90之间']);
            }
        }
        
        // 验证通宵设置
        if (!empty($data['tx_start_hour'])) {
            $txStart = intval($data['tx_start_hour']);
            if ($txStart < 18 || $txStart > 23) {
                return json(['code' => 1, 'msg' => '通宵开始时间应在18-23点之间']);
            }
        }
        if (!empty($data['tx_hour'])) {
            $txHour = intval($data['tx_hour']);
            if ($txHour < 6 || $txHour > 14) {
                return json(['code' => 1, 'msg' => '通宵时长应在6-14小时之间']);
            }
        }
        
        // 使用白名单构建数据
        $saveData = $this->buildStoreData($data);
        $saveData['tenant_id'] = $tenantId;
        $saveData['create_time'] = date('Y-m-d H:i:s');
        $id = Db::name('store')->insertGetId($saveData);
        
        // 清除缓存
        $this->clearStoreCache($tenantId);
        
        // 记录操作日志
        AdminLog::log(
            AdminLog::MODULE_STORE,
            AdminLog::TYPE_CREATE,
            "创建门店：" . ($saveData['name'] ?? ''),
            [
                'store_id' => $id,
                'store_name' => $saveData['name'] ?? '',
                'city' => $saveData['city'] ?? '',
                'address' => $saveData['address'] ?? '',
            ],
            $id
        );
        
        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        
        $this->parseCoordinates($data);
        
        // 验证必填字段
        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '门店名称不能为空']);
        }
        
        // 验证经纬度范围
        if (!empty($data['longitude'])) {
            $lng = floatval($data['longitude']);
            if ($lng < -180 || $lng > 180) {
                return json(['code' => 1, 'msg' => '经度范围应在-180到180之间']);
            }
        }
        if (!empty($data['latitude'])) {
            $lat = floatval($data['latitude']);
            if ($lat < -90 || $lat > 90) {
                return json(['code' => 1, 'msg' => '纬度范围应在-90到90之间']);
            }
        }
        
        // 验证通宵设置
        if (!empty($data['tx_start_hour'])) {
            $txStart = intval($data['tx_start_hour']);
            if ($txStart < 18 || $txStart > 23) {
                return json(['code' => 1, 'msg' => '通宵开始时间应在18-23点之间']);
            }
        }
        if (!empty($data['tx_hour'])) {
            $txHour = intval($data['tx_hour']);
            if ($txHour < 6 || $txHour > 14) {
                return json(['code' => 1, 'msg' => '通宵时长应在6-14小时之间']);
            }
        }
        
        // 使用白名单构建数据
        $saveData = $this->buildStoreData($data);
        $saveData['tenant_id'] = $tenantId;
        $saveData['update_time'] = date('Y-m-d H:i:s');
        
        if (!empty($data['id'])) {
            $id = $data['id'];
            Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->update($saveData);
        } else {
            $saveData['create_time'] = date('Y-m-d H:i:s');
            Db::name('store')->insert($saveData);
        }
        
        // 清除缓存
        $this->clearStoreCache($tenantId);
        
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function update()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        
        $this->parseCoordinates($data);
        
        // 验证经纬度范围
        if (!empty($data['longitude'])) {
            $lng = floatval($data['longitude']);
            if ($lng < -180 || $lng > 180) {
                return json(['code' => 1, 'msg' => '经度范围应在-180到180之间']);
            }
        }
        if (!empty($data['latitude'])) {
            $lat = floatval($data['latitude']);
            if ($lat < -90 || $lat > 90) {
                return json(['code' => 1, 'msg' => '纬度范围应在-90到90之间']);
            }
        }
        
        // 使用白名单构建数据
        $saveData = $this->buildStoreData($data);
        $saveData['update_time'] = date('Y-m-d H:i:s');
        Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->update($saveData);
        
        // 清除缓存
        $this->clearStoreCache($tenantId);
        
        // 记录操作日志
        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->find();
        AdminLog::log(
            AdminLog::MODULE_STORE,
            AdminLog::TYPE_UPDATE,
            "修改门店信息：" . ($store['name'] ?? ''),
            [
                'store_id' => $id,
                'store_name' => $store['name'] ?? '',
                'changes' => array_keys($saveData),
            ],
            (int)$id
        );
        
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id');
        
        // 获取门店信息用于日志
        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->find();
        
        Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->delete();
        
        // 清除缓存
        $this->clearStoreCache($tenantId);
        
        // 记录操作日志
        if ($store) {
            AdminLog::log(
                AdminLog::MODULE_STORE,
                AdminLog::TYPE_DELETE,
                "删除门店：" . ($store['name'] ?? ''),
                [
                    'store_id' => $id,
                    'store_name' => $store['name'] ?? '',
                ],
                (int)$id
            );
        }
        
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function detail()
    {
        return $this->get();
    }

    public function getSoundConfig()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id') ?: $this->request->param('store_id');
        $roomClass = (string)$this->request->param('room_class', 0);
        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$store) {
            return json(['code' => 1, 'msg' => '门店不存在']);
        }
        $allConfig = json_decode($store['sound_config'] ?? '{}', true) ?: [];
        $config = $allConfig[$roomClass] ?? [];
        return json(['code' => 0, 'data' => [
            'welcomeText'   => $config['welcomeText'] ?? '',
            'endText30'     => $config['endText30'] ?? '',
            'endText5'      => $config['endText5'] ?? '',
            'endText'       => $config['endText'] ?? '',
            'nightText'     => $config['nightText'] ?? '',
            'customizeText' => $config['customizeText'] ?? '',
        ]]);
    }

    public function saveSoundConfig()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->post('id') ?: $this->request->post('store_id');
        $roomClass = (string)$this->request->post('room_class', 0);
        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->find();
        if (!$store) {
            return json(['code' => 1, 'msg' => '门店不存在']);
        }
        $allConfig = json_decode($store['sound_config'] ?? '{}', true) ?: [];
        $allConfig[$roomClass] = [
            'welcomeText'   => $this->request->post('welcomeText', ''),
            'endText30'     => $this->request->post('endText30', ''),
            'endText5'      => $this->request->post('endText5', ''),
            'endText'       => $this->request->post('endText', ''),
            'nightText'     => $this->request->post('nightText', ''),
            'customizeText' => $this->request->post('customizeText', ''),
        ];
        Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->update([
            'sound_config' => json_encode($allConfig, JSON_UNESCAPED_UNICODE),
            'update_time'  => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function testSound()
    {
        return json(['code' => 0, 'msg' => '测试播报已发送']);
    }

    public function getGroupAuthStatus()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('store_id') ?: $this->request->param('id');
        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $id)->field('meituan_auth,douyin_auth')->find();
        return json(['code' => 0, 'data' => $store ?: []]);
    }

    /**
     * 获取团购授权链接（后台管理用）
     */
    public function getGroupPayAuthUrl()
    {
        $storeId = $this->request->param('store_id');
        $groupPayType = $this->request->param('groupPayType', 1);
        $tenantId = $this->request->tenantId ?? '88888888';

        if (empty($storeId)) return json(['code' => 1, 'msg' => '请选择门店']);

        $store = Db::name('store')->where('tenant_id', $tenantId)->where('id', $storeId)->find();
        if (!$store) return json(['code' => 1, 'msg' => '门店不存在']);

        try {
            $configKey = $groupPayType == 2 ? 'douyin_' : 'meituan_';
            $appKey = Db::name('config')->where('tenant_id', $tenantId)->where('config_key', $configKey . 'app_key')->value('config_value') ?: '';
            $redirectUri = Db::name('config')->where('tenant_id', $tenantId)->where('config_key', $configKey . 'redirect_uri')->value('config_value') ?: '';

            if ($groupPayType == 2) {
                // 抖音授权
                $url = 'https://open.douyin.com/platform/oauth/connect?client_key=' . $appKey . '&response_type=code&scope=poi.product&redirect_uri=' . urlencode($redirectUri) . '&state=store_' . $storeId;
            } else {
                // 美团授权
                $url = 'https://openapi.meituan.com/oauth/authorize?app_id=' . $appKey . '&response_type=code&redirect_uri=' . urlencode($redirectUri) . '&state=store_' . $storeId . '&scope=tuangou';
            }

            return json(['code' => 0, 'data' => $url]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '获取授权链接失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 设置抖音门店ID（后台管理用）
     */
    public function setDouyinId()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = $this->request->param('store_id');
        $dyId = $this->request->param('dyId', $this->request->param('douyin_id', ''));

        if (empty($storeId)) return json(['code' => 1, 'msg' => '请选择门店']);

        Db::name('store')->where('tenant_id', $tenantId)->where('id', $storeId)->update([
            'dy_id' => $dyId,
            'update_time' => date('Y-m-d H:i:s')
        ]);

        return json(['code' => 0, 'msg' => '保存成功']);
    }
}
