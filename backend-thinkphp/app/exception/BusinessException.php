<?php
namespace app\exception;

use Exception;

/**
 * 业务异常基类
 */
class BusinessException extends Exception
{
    protected $code = 1;
    protected $statusCode = 200;

    public function __construct($message = '', $code = 1, $statusCode = 200)
    {
        parent::__construct($message, $code);
        $this->code = $code;
        $this->statusCode = $statusCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function render()
    {
        return json([
            'code' => $this->code,
            'msg' => $this->getMessage()
        ], $this->statusCode);
    }
}
