<?php
/**
 * 公共函数文件
 */

/**
 * 生成订单号
 * @param string $prefix 前缀
 * @return string
 */
function generateOrderNo(string $prefix = ''): string
{
    return $prefix . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 生成随机字符串
 * @param int $length 长度
 * @return string
 */
function generateRandomString(int $length = 16): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 格式化金额
 * @param float $amount 金额
 * @param int $decimals 小数位数
 * @return string
 */
function formatMoney(float $amount, int $decimals = 2): string
{
    return number_format($amount, $decimals, '.', '');
}

/**
 * 获取客户端IP
 * @return string
 */
function getClientIp(): string
{
    $ip = request()->ip();
    return $ip ?: '0.0.0.0';
}

/**
 * 返回成功响应
 * @param mixed $data 数据
 * @param string $msg 消息
 * @return \think\response\Json
 */
function success($data = [], string $msg = 'ok'): \think\response\Json
{
    return json(['code' => 0, 'data' => $data, 'msg' => $msg]);
}

/**
 * 返回错误响应
 * @param string $msg 错误消息
 * @param int $code 错误码
 * @return \think\response\Json
 */
function error(string $msg = '操作失败', int $code = 1): \think\response\Json
{
    return json(['code' => $code, 'msg' => $msg]);
}
