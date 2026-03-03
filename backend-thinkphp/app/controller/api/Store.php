<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\model\Store as StoreModel;
use app\model\Room;
use app\service\RedisService;
use think\facade\Db;

/**
 * 门店控制器
 */
class Store extends BaseController
{
    /**
     * 将相对路径转为完整URL
     */
    private function fullUrl($path)
    {
        if (empty($path)) return '';
        if (strpos($path, 'http') === 0) return $path;
        $scheme = $this->request->scheme();
        $host = $this->request->host();
        return $scheme . '://' . $host . $path;
    }

    /**
     * 获取门店列表
     */
    public function list()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $longitude = $this->request->param('longitude');
        $latitude = $this->request->param('latitude');

        $cacheKey = 'store:list:' . $tenantId;

        $list = RedisService::remember($cacheKey, 1800, function() use ($tenantId) {
            $query = StoreModel::where('tenant_id', $tenantId)
                ->where('status', 1);

            return $query->order('id', 'asc')->select()->toArray();
        });

        $list = $list ?: [];

        if ($longitude && $latitude) {
            foreach ($list as &$item) {
                if ($item['longitude'] && $item['latitude']) {
                    $item['distance'] = $this->getDistance(
                        (float)$latitude, (float)$longitude,
                        (float)$item['latitude'], (float)$item['longitude']
                    );
                } else {
                    $item['distance'] = null;
                }
            }
            usort($list, function($a, $b) {
                if ($a['distance'] === null) return 1;
                if ($b['distance'] === null) return -1;
                return $a['distance'] <=> $b['distance'];
            });
        }

        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    /**
     * 获取门店详情
     */
    public function detail()
    {
        $id = $this->request->param('id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $lat = $this->request->param('lat', '');
        $lon = $this->request->param('lon', '');

        $store = StoreModel::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->find();

        if (!$store) {
            return json(['code' => 1, 'msg' => '门店不存在']);
        }

        $data = $store->toArray();

        // 补充计算字段
        $data['store_id'] = $data['id'];
        $data['store_name'] = $data['name'];

        // 计算距离
        if ($lat && $lon && !empty($data['longitude']) && !empty($data['latitude'])) {
            $data['distance'] = round($this->getDistance(
                (float)$lat, (float)$lon,
                (float)$data['latitude'], (float)$data['longitude']
            ) / 1000, 2);
        }

        // 获取空闲房间数
        $freeRoomNum = Room::where('store_id', $id)->where('status', 1)->count();
        $data['freeRoomNum'] = $freeRoomNum;

        // 获取房间类别列表
        $roomClasses = Room::where('store_id', $id)
            ->where('status', 'in', [1, 2])
            ->distinct(true)->column('room_class');
        $data['room_class_list'] = $roomClasses;

        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 获取门店房间列表
     */
    public function rooms()
    {
        $storeId = $this->request->param('store_id') ?: $this->request->param('id');
        $type = $this->request->param('type');
        $roomClass = $this->request->param('room_class') ?? $this->request->param('roomClass');
        $tenantId = $this->request->tenantId ?? '88888888';

        if (!$storeId) {
            return json(['code' => 1, 'msg' => '缺少门店ID']);
        }

        $cacheKey = 'room:list:' . $tenantId . ':' . $storeId . ':' . ($type ?? 'all') . ':' . ($roomClass ?? 'all');

        // 先从数据库读取最新数据，检查是否有状态不一致
        $query = Room::where('store_id', $storeId);
        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }
        if ($roomClass !== null && $roomClass !== '' && is_numeric($roomClass)) {
            $query->where('room_class', (int)$roomClass);
        }
        $dbList = $query->order('sort', 'asc')->select()->toArray();
        
        // 检查是否有状态不一致的房间
        $hasInconsistency = false;
        foreach ($dbList as $room) {
            if ($room['status'] != 4 && $room['is_cleaning'] == 1) {
                $hasInconsistency = true;
                // 自动修复数据库
                Room::where('id', $room['id'])->update([
                    'is_cleaning' => 0,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                \think\facade\Log::warning('自动修复房间状态不一致', [
                    'room_id' => $room['id'],
                    'status' => $room['status'],
                    'is_cleaning_before' => 1,
                    'is_cleaning_after' => 0,
                ]);
            }
        }
        
        // 如果有不一致，清除缓存并重新读取
        if ($hasInconsistency) {
            RedisService::clearRoomCache((int)$storeId, $tenantId);
            // 重新读取数据库
            $query = Room::where('store_id', $storeId);
            if ($type !== null && $type !== '') {
                $query->where('type', $type);
            }
            if ($roomClass !== null && $roomClass !== '' && is_numeric($roomClass)) {
                $query->where('room_class', (int)$roomClass);
            }
            $list = $query->order('sort', 'asc')->select()->toArray();
        } else {
            // 没有不一致，使用缓存
            $list = RedisService::remember($cacheKey, 1800, function() use ($storeId, $type, $roomClass) {
                $query = Room::where('store_id', $storeId);
                if ($type !== null && $type !== '') {
                    $query->where('type', $type);
                }
                if ($roomClass !== null && $roomClass !== '' && is_numeric($roomClass)) {
                    $query->where('room_class', (int)$roomClass);
                }
                return $query->order('sort', 'asc')->select()->toArray();
            });
        }

        $list = $list ?: [];

        $roomIds = array_column($list, 'id');
        $activeOrders = [];
        $roomOrders = [];
        if (!empty($roomIds)) {
            $now = date('Y-m-d H:i:s');
            $orders = Db::name('order')
                ->whereIn('room_id', $roomIds)
                ->whereIn('status', [0, 1])
                ->where('end_time', '>', $now)
                ->field('room_id, start_time, end_time')
                ->select()
                ->toArray();
            
            foreach ($orders as $o) {
                $activeOrders[$o['room_id']] = true;
                if (!isset($roomOrders[$o['room_id']])) {
                    $roomOrders[$o['room_id']] = [];
                }
                $roomOrders[$o['room_id']][] = $o;
            }
        }
        
        // 查询保洁任务状态，用于区分"待清洁"和"清洁中"
        $cleaningTasks = [];
        if (!empty($roomIds)) {
            $tasks = Db::name('clear_task')
                ->whereIn('room_id', $roomIds)
                ->whereIn('status', [0, 1, 2])  // 0=待接单, 1=已接单, 2=已开始
                ->field('room_id, status')
                ->select()
                ->toArray();
            
            foreach ($tasks as $task) {
                $cleaningTasks[$task['room_id']] = (int)$task['status'];
            }
        }

        $todayStart = strtotime(date('Y-m-d'));
        $tomorrowStart = $todayStart + 86400;
        
        foreach ($list as &$item) {
            $item['room_id'] = $item['id'];
            $item['room_name'] = $item['name'];
            $images = $item['images'] ?? '';
            if ($images) {
                $decoded = json_decode($images, true);
                if (is_array($decoded)) {
                    $images = implode(',', array_filter($decoded));
                }
            }
            $item['imageUrls'] = $images;
            
            // is_cleaning 字段直接从数据库读取
            // status=4 时：is_cleaning=1 表示待清洁/清洁中，is_cleaning=0 表示已预约
            $item['is_cleaning'] = (bool)($item['is_cleaning'] ?? 0);
            
            // 添加 cleaning_status 字段，用于区分"待清洁"和"清洁中"
            // 0 = 待清洁（待接单或已接单）
            // 2 = 清洁中（保洁员已开始）
            if ($item['status'] == 4 && $item['is_cleaning']) {
                $taskStatus = $cleaningTasks[$item['id']] ?? 0;
                $item['cleaning_status'] = $taskStatus;  // 0/1=待清洁, 2=清洁中
            } else {
                $item['cleaning_status'] = 0;
            }
            
            $orders = $roomOrders[$item['id']] ?? [];
            $timeSlot = [];
            for ($day = 0; $day < 2; $day++) {
                $dayStart = $todayStart + $day * 86400;
                for ($h = 0; $h < 24; $h++) {
                    $slotStart = $dayStart + $h * 3600;
                    $slotEnd = $slotStart + 3600;
                    $disabled = false;
                    foreach ($orders as $o) {
                        $os = strtotime($o['start_time']);
                        $oe = strtotime($o['end_time']);
                        if ($slotStart < $oe && $slotEnd > $os) {
                            $disabled = true;
                            break;
                        }
                    }
                    $timeSlot[] = ['hour' => str_pad((string)$h, 2, '0', STR_PAD_LEFT), 'disable' => $disabled];
                }
            }
            $item['time_slot'] = $timeSlot;
            
            $orderTimeList = [];
            $timeText = '';
            foreach ($orders as $o) {
                $orderTimeList[] = [
                    'start_time' => $o['start_time'],
                    'end_time' => $o['end_time'],
                ];
            }
            if (!empty($orderTimeList)) {
                $first = $orderTimeList[0];
                $timeText = date('H:i', strtotime($first['start_time'])) . '-' . date('H:i', strtotime($first['end_time']));
            }
            $item['order_time_list'] = $orderTimeList;
            $item['timeText'] = $timeText;
        }
        unset($item);

        return json(['code' => 0, 'data' => $list]);
    }

    /**
     * 门店分页列表（路由: member/index/getStoreList）
     * 前端传参: pageNo, pageSize, cityName, lat, lon, name
     */
    public function page()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 10);
        $cityName = $this->request->param('cityName', '');
        $lat = $this->request->param('lat', '');
        $lon = $this->request->param('lon', '');
        $name = $this->request->param('name', '');

        $query = StoreModel::where('tenant_id', $tenantId)
            ->where('status', 1);

        if ($cityName && $cityName !== '选择城市') {
            $query->where('city', $cityName);
        }
        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        $total = $query->count();
        $list = $query->order('id', 'asc')
            ->page($pageNo, $pageSize)
            ->select()->toArray();

        // 补充计算字段
        foreach ($list as &$item) {
            $item['store_id'] = $item['id'];
            $item['store_name'] = $item['name'];
            $item['freeRoomNum'] = Room::where('store_id', $item['id'])->where('status', 1)->count();
            if ($lat && $lon && !empty($item['longitude']) && !empty($item['latitude'])) {
                $item['distance'] = round($this->getDistance(
                    (float)$lat, (float)$lon,
                    (float)$item['latitude'], (float)$item['longitude']
                ) / 1000, 2);
            } else {
                $item['distance'] = null;
            }
            // 评分和评价数
            $item['avg_score'] = (float)Db::name('review')->where('store_id', $item['id'])->where('status', 1)->avg('score');
            $item['review_count'] = (int)Db::name('review')->where('store_id', $item['id'])->where('status', 1)->count();
        }

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    /**
     * 获取Banner列表（路由: member/index/getBannerList）
     */
    public function bannerList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('banner')
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()->toArray();
        // 前端期望字段名为 imgUrl
        foreach ($list as &$item) {
            $item['imgUrl'] = $item['image'];
        }
        return json(['code' => 0, 'data' => $list]);
    }

    /**
     * 获取门店详情（路由: member/index/getStoreInfo/:id）
     */
    public function get()
    {
        return $this->detail();
    }

    /**
     * 获取房间列表（路由: member/index/getRoomInfoList）
     */
    public function roomList()
    {
        return $this->rooms();
    }

    /**
     * 获取城市列表（路由: member/index/getCityList）
     */
    public function cityList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $cities = StoreModel::where('tenant_id', $tenantId)
            ->where('status', 1)
            ->where('city', '<>', '')
            ->distinct(true)
            ->column('city');
        return json(['code' => 0, 'data' => $cities]);
    }

    /**
     * 根据经纬度获取城市名（路由: member/index/getCityByLocation）
     */
    public function getCityByLocation()
    {
        $lat = $this->request->param('lat');
        $lng = $this->request->param('lng');

        if (!$lat || !$lng) {
            return json(['code' => 1, 'msg' => '缺少经纬度参数']);
        }

        $key = 'TKUBZ-D24AF-GJ4JY-JDVM2-IBYKK-KEBCU';
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?location={$lat},{$lng}&key={$key}";

        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            if ($data && $data['status'] === 0) {
                $city = $data['result']['address_component']['city'] ?? '';
                $city = preg_replace('/市$/', '', $city);
                return json(['code' => 0, 'data' => ['city' => $city]]);
            }
        } catch (\Exception $e) {
            // 地图API调用失败
        }

        // 兜底：根据经纬度找最近的门店所在城市
        $tenantId = $this->request->tenantId ?? '88888888';
        $store = StoreModel::where('tenant_id', $tenantId)
            ->where('status', 1)
            ->where('city', '<>', '')
            ->where('latitude', '<>', '')
            ->where('longitude', '<>', '')
            ->order(Db::raw("ABS(latitude - {$lat}) + ABS(longitude - {$lng})"))
            ->find();
        $city = $store ? preg_replace('/市$/', '', $store['city'] ?? '') : '';

        return json(['code' => 0, 'data' => ['city' => $city]]);
    }

    public function getServiceInfo()
    {
        $userId = $this->request->userId ?? null;
        $tenantId = $this->request->tenantId ?? '88888888';
        return json(['code' => 0, 'data' => ['expire_time' => '', 'status' => 1]]);
    }

    public function roomGet()
    {
        $id = $this->request->param('id');
        $room = Room::find($id);
        return json(['code' => 0, 'data' => $room]);
    }

    public function roomCheck()
    {
        $roomId = $this->request->post('roomId');
        $startTime = $this->request->post('startTime');
        $endTime = $this->request->post('endTime');
        $room = Room::find($roomId);
        if (!$room) return json(['code' => 1, 'msg' => '房间不存在']);
        return json(['code' => 0, 'data' => ['available' => $room->status == 1]]);
    }

    // ========== 管理员功能 ==========

    public function getPageList()
    {
        $userId = $this->request->userId;
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);

        $list = StoreModel::where('tenant_id', $tenantId)
            ->page($pageNo, $pageSize)
            ->order('id', 'asc')
            ->select()->toArray();
        $total = StoreModel::where('tenant_id', $tenantId)->count();

        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function openStoreDoor($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $store = StoreModel::where('id', $id)->where('tenant_id', $tenantId)->find();
        if (!$store) return json(['code' => 1, 'msg' => '门店不存在']);
        // 调用开门服务
        try {
            $lockNo = $store->lock_no ?? '';
            if ($lockNo) {
                \app\service\DeviceService::control($lockNo, 'open');
            }
        } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '开门指令已发送']);
    }

    public function save()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $id = $this->request->param('id');
        $data = $this->request->param();
        $data['tenant_id'] = $tenantId;
        $data['update_time'] = date('Y-m-d H:i:s');
        unset($data['id']);

        if ($id) {
            StoreModel::where('id', $id)->where('tenant_id', $tenantId)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = StoreModel::insertGetId($data);
        }
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '保存成功']);
    }

    public function addLock()
    {
        $storeId = $this->request->param('store_id');
        $lockNo = $this->request->param('lock_no');
        $tenantId = $this->request->tenantId ?? '88888888';
        StoreModel::where('id', $storeId)->where('tenant_id', $tenantId)->update(['lock_no' => $lockNo, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function addDevice()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->param();
        $data['tenant_id'] = $tenantId;
        $data['store_id'] = $storeId;
        $data['create_time'] = date('Y-m-d H:i:s');
        unset($data['id']);
        $id = Db::name('device')->insertGetId($data);
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '添加成功']);
    }

    public function delDevice($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        Db::name('device')->where('id', $id)->where('tenant_id', $tenantId)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function getRoomList($id)
    {
        $list = Room::where('store_id', $id)->order('sort', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function getRoomInfoList()
    {
        $storeId = $this->request->param('id', $this->request->param('store_id'));
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 50);

        $query = Room::where('tenant_id', $this->request->tenantId ?? '88888888');
        if ($storeId) {
            $query = $query->where('store_id', $storeId);
        }
        $total = (clone $query)->count();
        $list = (clone $query)->page($pageNo, $pageSize)->order('sort', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function deleteRoomInfo($id)
    {
        Room::where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function disableRoom($id)
    {
        $room = Room::find($id);
        if (!$room) return json(['code' => 1, 'msg' => '房间不存在']);
        $newStatus = $room->status == 3 ? 1 : 3;
        Room::where('id', $id)->update(['status' => $newStatus, 'update_time' => date('Y-m-d H:i:s')]);
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    public function saveRoomInfo()
    {
        $data = $this->request->param();
        $data['update_time'] = date('Y-m-d H:i:s');
        $id = $data['id'] ?? null;
        unset($data['id']);
        if ($id) {
            Room::where('id', $id)->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['status'] = 1;
            $id = Room::insertGetId($data);
        }
        return json(['code' => 0, 'data' => ['id' => $id], 'msg' => '保存成功']);
    }

    public function updateRoomInfo() { return $this->saveRoomInfo(); }

    public function getDetail($id)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $store = StoreModel::where('id', $id)->where('tenant_id', $tenantId)->find();
        if (!$store) return json(['code' => 1, 'msg' => '门店不存在']);
        $data = $store->toArray();
        $data['roomCount'] = Room::where('store_id', $id)->count();
        $data['freeRoomNum'] = Room::where('store_id', $id)->where('status', 1)->count();
        return json(['code' => 0, 'data' => $data]);
    }

    public function getStatistics()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $today = date('Y-m-d');

        $todayIncome = Db::name('order')->where('store_id', $storeId)->where('tenant_id', $tenantId)
            ->whereIn('status', [1, 2])->whereDay('pay_time', $today)->sum('pay_amount') ?: 0;
        $todayOrder = Db::name('order')->where('store_id', $storeId)->where('tenant_id', $tenantId)
            ->whereIn('status', [1, 2])->whereDay('create_time', $today)->count();
        $totalIncome = Db::name('order')->where('store_id', $storeId)->where('tenant_id', $tenantId)
            ->whereIn('status', [1, 2])->sum('pay_amount') ?: 0;
        $totalOrder = Db::name('order')->where('store_id', $storeId)->where('tenant_id', $tenantId)
            ->whereIn('status', [1, 2])->count();

        return json(['code' => 0, 'data' => [
            'todayIncome' => $todayIncome, 'todayOrder' => $todayOrder,
            'totalIncome' => $totalIncome, 'totalOrder' => $totalOrder,
        ]]);
    }

    public function checkBall($id)
    {
        $room = Room::find($id);
        if (!$room) return json(['code' => 1, 'msg' => '房间不存在']);
        return json(['code' => 0, 'data' => ['ball_status' => $room->ball_status ?? 1]]);
    }

    public function getStoreSoundInfo($id)
    {
        $roomClass = (int)$this->request->param('room_class', 0);
        $store = StoreModel::find($id);
        if (!$store) {
            return json(['code' => 1, 'msg' => '门店不存在']);
        }
        $allConfig = json_decode($store->sound_config ?? '{}', true) ?: [];
        $config = $allConfig[(string)$roomClass] ?? [];
        return json(['code' => 0, 'data' => [
            'welcomeText'   => $config['welcomeText'] ?? '',
            'endText30'     => $config['endText30'] ?? '',
            'endText5'      => $config['endText5'] ?? '',
            'endText'       => $config['endText'] ?? '',
            'nightText'     => $config['nightText'] ?? '',
            'customizeText' => $config['customizeText'] ?? '',
        ]]);
    }


    public function saveStoreSoundInfo()
    {
        $storeId = $this->request->param('store_id');
        $roomClass = (string)$this->request->param('room_class', 0);
        $tenantId = $this->request->tenantId ?? '88888888';

        $store = StoreModel::where('id', $storeId)->where('tenant_id', $tenantId)->find();
        if (!$store) {
            return json(['code' => 1, 'msg' => '门店不存在']);
        }

        $allConfig = json_decode($store->sound_config ?? '{}', true) ?: [];
        $allConfig[$roomClass] = [
            'welcomeText'   => $this->request->param('welcomeText', ''),
            'endText30'     => $this->request->param('endText30', ''),
            'endText5'      => $this->request->param('endText5', ''),
            'endText'       => $this->request->param('endText', ''),
            'nightText'     => $this->request->param('nightText', ''),
            'customizeText' => $this->request->param('customizeText', ''),
        ];

        StoreModel::where('id', $storeId)->where('tenant_id', $tenantId)->update([
            'sound_config' => json_encode($allConfig, JSON_UNESCAPED_UNICODE),
            'update_time'  => date('Y-m-d H:i:s'),
        ]);

        return json(['code' => 0, 'msg' => '保存成功']);
    }


    public function getQrConfig()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $config = Db::name('config')->where('tenant_id', $tenantId)
            ->where('config_key', 'like', 'qr_%')->select()->toArray();
        $data = [];
        foreach ($config as $c) $data[$c['config_key']] = $c['config_value'];
        return json(['code' => 0, 'data' => $data]);
    }

    public function setQrConfig()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $params = $this->request->param();
        foreach ($params as $key => $value) {
            if (strpos($key, 'qr_') === 0) {
                $exists = Db::name('config')->where('tenant_id', $tenantId)->where('config_key', $key)->find();
                if ($exists) {
                    Db::name('config')->where('id', $exists['id'])->update(['config_value' => $value]);
                } else {
                    Db::name('config')->insert(['tenant_id' => $tenantId, 'config_key' => $key, 'config_value' => $value, 'create_time' => date('Y-m-d H:i:s')]);
                }
            }
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 重新生成门店+所有房间的小程序码
     */
    public function resetQrcode()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $storeId = (int)$this->request->param('store_id');
        if (!$storeId) {
            return json(['code' => 1, 'msg' => '缺少门店ID']);
        }
        $store = StoreModel::where('id', $storeId)->where('tenant_id', $tenantId)->find();
        if (!$store) {
            return json(['code' => 1, 'msg' => '门店不存在']);
        }
        try {
            $results = \app\service\QrcodeService::resetAllQrcode($tenantId, $storeId);
            return json(['code' => 0, 'msg' => '生成成功', 'data' => $results]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function getGroupPayAuthUrl()
    {
        $storeId = $this->request->param('store_id');
        $platform = $this->request->param('platform', 'meituan');
        $tenantId = $this->request->tenantId ?? '88888888';
        $url = Db::name('config')->where('tenant_id', $tenantId)
            ->where('config_key', $platform . '_auth_url')->value('config_value') ?: '';
        return json(['code' => 0, 'data' => ['url' => $url]]);
    }

    public function setDouyinId()
    {
        $storeId = $this->request->param('store_id');
        $douyinId = $this->request->param('douyin_id', '');
        $tenantId = $this->request->tenantId ?? '88888888';
        StoreModel::where('id', $storeId)->where('tenant_id', $tenantId)->update([
            'douyin_id' => $douyinId, 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function getStoreListByAdmin()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $userId = $this->request->userId;
        $list = StoreModel::where('tenant_id', $tenantId)->order('id', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function editMemberVip()
    {
        $userId = $this->request->param('user_id');
        $vipLevel = $this->request->param('vip_level', 0);
        $score = $this->request->param('score', 0);
        Db::name('user')->where('id', $userId)->update([
            'vip_level' => $vipLevel, 'score' => $score, 'update_time' => date('Y-m-d H:i:s')
        ]);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function addMemberVip()
    {
        return $this->editMemberVip();
    }

    public function getVipBlacklist()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        try {
            $query = Db::name('vip_blacklist')->alias('vb')
                ->leftJoin('user u', 'u.id = vb.user_id')
                ->where('vb.tenant_id', $tenantId);
            if ($storeId) $query->where('vb.store_id', $storeId);
            $total = (clone $query)->count();
            $list = $query->field('vb.id, vb.user_id, vb.store_id, vb.reason, vb.create_time as add_time, u.nickname, u.phone, u.avatar')
                ->page($pageNo, $pageSize)->order('vb.create_time', 'desc')->select()->toArray();
        } catch (\Exception $e) {
            $total = 0; $list = [];
        }
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function addBlackList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $phone = $this->request->param('phone');
        $storeId = $this->request->param('store_id');

        if (!$phone || strlen($phone) < 11) {
            return json(['code' => 1, 'msg' => '手机号格式错误']);
        }

        $user = Db::name('user')->where('phone', $phone)->where('tenant_id', $tenantId)->find();
        if (!$user) {
            return json(['code' => 1, 'msg' => '未找到该手机号对应的用户']);
        }

        $exists = Db::name('vip_blacklist')
            ->where('user_id', $user['id'])
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->find();
        if ($exists) {
            return json(['code' => 1, 'msg' => '该用户已在黑名单中']);
        }

        Db::name('vip_blacklist')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $user['id'],
            'store_id' => $storeId,
            'reason' => '小程序端添加',
            'create_time' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '添加成功']);
    }

    public function removeBlackList($id)
    {
        Db::name('vip_blacklist')->where('id', $id)->delete();
        return json(['code' => 0, 'msg' => '移除成功']);
    }

    public function getFaceBlacklistPage()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        try {
            $query = Db::name('face_blacklist')->where('tenant_id', $tenantId);
            if ($storeId) $query->where('store_id', $storeId);
            $total = (clone $query)->count();
            $list = $query->page($pageNo, $pageSize)->order('create_time', 'desc')->select()->toArray();
        } catch (\Exception $e) {
            $total = 0; $list = [];
        }
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function moveFaceById($id)
    {
        try { Db::name('face_blacklist')->where('id', $id)->delete(); } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '移除成功']);
    }

    public function getFaceRecordPage()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $pageNo = (int)$this->request->param('pageNo', 1);
        $pageSize = (int)$this->request->param('pageSize', 20);
        try {
            $query = Db::name('face_record')->where('tenant_id', $tenantId);
            if ($storeId) $query->where('store_id', $storeId);
            $total = (clone $query)->count();
            $list = $query->page($pageNo, $pageSize)->order('create_time', 'desc')->select()->toArray();
        } catch (\Exception $e) {
            $total = 0; $list = [];
        }
        return json(['code' => 0, 'data' => ['list' => $list, 'total' => $total]]);
    }

    public function moveFaceByRecord()
    {
        $recordId = $this->request->param('record_id');
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        try {
            $record = Db::name('face_record')->where('id', $recordId)->find();
            if ($record) {
                Db::name('face_blacklist')->insert([
                    'tenant_id' => $tenantId, 'store_id' => $storeId ?: ($record['store_id'] ?? 0),
                    'face_id' => $record['face_id'] ?? '', 'reason' => '从记录中移入黑名单',
                    'status' => 1, 'create_time' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '操作成功']);
    }

    public function getTemplateList()
    {
        $list = [
            ['template_key' => 'default', 'name' => '默认模板', 'color' => '#5AAB6E'],
            ['template_key' => 'blue',    'name' => '蓝色主题', 'color' => '#1890ff'],
            ['template_key' => 'orange',  'name' => '橙色主题', 'color' => '#fa8c16'],
            ['template_key' => 'purple',  'name' => '紫色主题', 'color' => '#722ed1'],
        ];
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function setTemplate()
    {
        $storeId = $this->request->param('store_id');
        $templateKey = $this->request->param('templateKey', '');
        $simpleModel = (int)$this->request->param('simple_model', 1);
        $tenantId = $this->request->tenantId ?? '88888888';

        $updateData = [
            'template_key' => $templateKey ?: 'default',
            'simple_model' => $simpleModel,
            'update_time'  => date('Y-m-d H:i:s'),
        ];

        // 标准模式时保存自定义按钮图片
        if ($simpleModel == 0) {
            $imgFields = ['btn_img', 'qh_img', 'tg_img', 'cz_img', 'open_img', 'wifi_img', 'kf_img'];
            foreach ($imgFields as $field) {
                $val = $this->request->param($field);
                if ($val !== null) {
                    $updateData[$field] = $val;
                }
            }
        }

        StoreModel::where('id', $storeId)->where('tenant_id', $tenantId)->update($updateData);
        return json(['code' => 0, 'msg' => '设置成功']);
    }

    public function getPushRule($storeId)
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        try {
            $rule = Db::name('push_rule')->where('store_id', $storeId)->where('tenant_id', $tenantId)->find();
        } catch (\Exception $e) {
            $rule = null;
        }
        return json(['code' => 0, 'data' => $rule ?: ['enabled' => 0]]);
    }

    public function savePushRule()
    {
        $storeId = $this->request->param('store_id');
        $tenantId = $this->request->tenantId ?? '88888888';
        $data = $this->request->param();
        $data['tenant_id'] = $tenantId;
        $data['store_id'] = $storeId;
        $data['update_time'] = date('Y-m-d H:i:s');
        unset($data['id']);
        try {
            $exists = Db::name('push_rule')->where('store_id', $storeId)->where('tenant_id', $tenantId)->find();
            if ($exists) {
                Db::name('push_rule')->where('id', $exists['id'])->update($data);
            } else {
                $data['create_time'] = date('Y-m-d H:i:s');
                Db::name('push_rule')->insert($data);
            }
        } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function getPrePayConfig()
    {
        $roomId = $this->request->param('roomId', $this->request->param('id'));
        $room = Room::find($roomId);
        return json(['code' => 0, 'data' => [
            'pre_pay_enabled' => $room->pre_pay_enabled ?? 0,
            'pre_pay_amount' => $room->pre_pay_amount ?? 0,
        ]]);
    }

    public function setPrePayConfig()
    {
        $roomId = $this->request->param('room_id');
        Room::where('id', $roomId)->update([
            'pre_pay_enabled' => $this->request->param('pre_pay_enabled', 0),
            'pre_pay_amount' => $this->request->param('pre_pay_amount', 0),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 计算两点间距离(米)
     */
    private function getDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = deg2rad($lng1) - deg2rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * 6378137;
        return round($s, 2);
    }
}
