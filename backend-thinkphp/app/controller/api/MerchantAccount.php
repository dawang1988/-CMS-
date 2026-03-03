<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 商户账户控制器
 */
class MerchantAccount extends BaseController
{
    /**
     * 获取商户入驻信息
     */
    public function getApplyUrl()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $configs = Db::table('ss_config')
            ->where('tenant_id', $tenantId)
            ->where('config_key', 'like', 'merchant_%')
            ->select()->toArray();
        
        $data = [];
        foreach ($configs as $item) {
            $data[$item['config_key']] = $item['config_value'] ?? '';
        }
        
        return success([
            'applyUrl'     => $data['merchant_apply_url'] ?? '',
            'contactWx'    => $data['merchant_contact_wx'] ?? '',
            'contactPhone' => $data['merchant_contact_phone'] ?? '',
        ]);
    }
}
