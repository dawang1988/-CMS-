<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\CacheService;
use think\facade\Db;

class System extends BaseController
{
    /**
     * 获取全部系统配置
     */
    public function config()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $config = [
            'app_name' => CacheService::getConfig($tenantId, 'app_name', '自助棋牌室'),
            'app_version' => CacheService::getConfig($tenantId, 'app_version', '1.0.0'),
            'contact_phone' => CacheService::getConfig($tenantId, 'contact_phone', ''),
            'service_time' => CacheService::getConfig($tenantId, 'service_time', '00:00-24:00'),
        ];

        return json(['code' => 0, 'data' => $config]);
    }

    /**
     * 获取单个系统配置项（路由: system/config/get?key=xxx）
     */
    public function getConfig()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $key = $this->request->param('key', '');
        
        if (empty($key)) {
            return json(['code' => 1, 'msg' => '缺少参数key']);
        }
        
        $value = CacheService::getConfig($tenantId, $key, '');
        
        return json(['code' => 0, 'data' => ['key' => $key, 'value' => $value]]);
    }

    /**
     * 获取系统信息（路由: member/index/getSysInfo）
     */
    public function getSysInfo()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        
        $config = [
            'app_name' => CacheService::getConfig($tenantId, 'app_name', '自助棋牌室'),
            'app_version' => CacheService::getConfig($tenantId, 'app_version', '1.0.0'),
            'contact_phone' => CacheService::getConfig($tenantId, 'contact_phone', ''),
            'service_time' => CacheService::getConfig($tenantId, 'service_time', '00:00-24:00'),
        ];

        return json(['code' => 0, 'data' => $config]);
    }

    /**
     * 获取Banner列表
     */
    public function banners()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $list = Db::name('banner')
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()->toArray();
        return json(['code' => 0, 'data' => ['list' => $list]]);
    }

    /**
     * 获取帮助列表（路由: system/help/list）
     */
    public function getHelpList()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $category = $this->request->param('category');

        $query = Db::name('help')->where('tenant_id', $tenantId)->where('status', 1);
        if ($category) $query->where('category', $category);

        $list = $query->order('sort', 'asc')->select()->toArray();
        return json(['code' => 0, 'data' => $list]);
    }

    /**
     * 获取帮助列表（旧方法名，保留兼容）
     */
    public function helps()
    {
        return $this->getHelpList();
    }

    /**
     * 获取帮助详情（路由: system/help/get?id=xxx）
     */
    public function getHelp()
    {
        $id = $this->request->param('id');
        $help = Db::name('help')->find($id);
        if ($help) {
            Db::name('help')->where('id', $id)->inc('view_count')->update();
        }
        return json(['code' => 0, 'data' => $help]);
    }

    /**
     * 获取帮助详情（旧方法名，保留兼容）
     */
    public function helpDetail()
    {
        return $this->getHelp();
    }

    /**
     * 提交意见反馈（路由: system/feedback/create）
     */
    public function createFeedback()
    {
        $tenantId = $this->request->tenantId ?? '88888888';
        $content = $this->request->param('content', '');
        $images = $this->request->param('images', '');
        $type = $this->request->param('type', 1);
        $contact = $this->request->param('contact', '');
        
        if (empty($content)) {
            return json(['code' => 1, 'msg' => '反馈内容不能为空']);
        }
        
        $data = [
            'tenant_id' => $tenantId,
            'type' => $type,
            'content' => $content,
            'images' => is_array($images) ? implode(',', $images) : $images,
            'contact' => $contact,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        
        if (!empty($this->request->userId)) {
            $data['user_id'] = $this->request->userId;
        }
        
        Db::name('feedback')->insert($data);
        
        return json(['code' => 0, 'msg' => '提交成功']);
    }
}
