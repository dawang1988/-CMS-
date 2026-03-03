<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Filesystem;

class Upload extends BaseController
{
    public function image()
    {
        return $this->upload();
    }

    public function upload()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请选择文件']);
        }

        $saveName = Filesystem::disk('public')->putFile('images', $file);
        
        // Windows 路径分隔符转换
        $saveName = str_replace('\\', '/', $saveName);
        
        // 返回完整 URL，自动适配当前域名
        $url = $this->request->domain() . '/storage/' . $saveName;

        return json(['code' => 0, 'data' => ['url' => $url]]);
    }
}
