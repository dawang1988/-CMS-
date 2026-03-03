<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

class StoreDevice extends BaseController
{
    public function list()
    {
        $storeId = $this->request->param('store_id');
        $list = Db::name('device')->where('store_id', $storeId)->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    public function open()
    {
        $deviceId = $this->request->param('device_id');
        $deviceNo = $this->request->param('device_no');
        $action = $this->request->param('action', 'open');

        try {
            if (!$deviceNo && $deviceId) {
                $device = Db::name('device')->find($deviceId);
                $deviceNo = $device['device_no'] ?? '';
            }
            if ($deviceNo) {
                \app\service\DeviceService::control($deviceNo, $action);
            }
        } catch (\Exception $e) {}
        return json(['code' => 0, 'msg' => '指令已发送']);
    }
}
