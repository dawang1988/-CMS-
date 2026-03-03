<?php
declare(strict_types=1);

namespace app;

use think\App;
use think\Request;
use think\Response;
use think\Validate;
use think\exception\ValidateException;

/**
 * 控制器基类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var Request
     */
    protected $request;

    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
    }

    /**
     * 验证数据
     * @param array $data 数据
     * @param string|array $validate 验证器名或验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return bool
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false): bool
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 成功响应
     * @param mixed $data 数据
     * @param string $msg 消息
     * @return Response
     */
    protected function success($data = null, string $msg = 'success'): Response
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 失败响应
     * @param string $msg 消息
     * @param int $code 错误码
     * @return Response
     */
    protected function error(string $msg = 'error', int $code = 1): Response
    {
        return json(['code' => $code, 'msg' => $msg]);
    }
}
