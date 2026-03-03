<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;
use app\service\CacheService;

/**
 * 认证控制器
 */
class Auth extends BaseController
{
    /**
     * 微信登录
     */
    public function wxLogin()
    {
        $code = $this->request->post('code');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => 'code不能为空']);
        }

        $tenantId = $this->request->tenantId ?? '88888888';
        $appId = CacheService::getConfig($tenantId, 'wx_appid');
        $appSecret = CacheService::getConfig($tenantId, 'wx_app_secret');

        if (empty($appId) || empty($appSecret)) {
            return json(['code' => 1, 'msg' => '微信配置不完整']);
        }

        // 获取openid
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
        $response = file_get_contents($url);
        $result = json_decode($response, true);

        if (empty($result['openid'])) {
            return json(['code' => 1, 'msg' => '登录失败']);
        }

        $openid = $result['openid'];

        // 查找或创建用户
        $user = Db::name('user')
            ->where('tenant_id', $tenantId)
            ->where('openid', $openid)
            ->find();

        if (!$user) {
            $randomNick = '棋友' . substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6);
            $userId = Db::name('user')->insertGetId([
                'tenant_id' => $tenantId,
                'openid' => $openid,
                'unionid' => $result['unionid'] ?? null,
                'nickname' => $randomNick,
                'avatar' => '/static/img/default-avatar.png',
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            $user = Db::name('user')->find($userId);
        } elseif (empty($user['nickname'])) {
            $randomNick = '棋友' . substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6);
            Db::name('user')->where('id', $user['id'])->update(['nickname' => $randomNick]);
            $user['nickname'] = $randomNick;
        }

        // 生成token
        $token = bin2hex(random_bytes(32));
        Cache::set('user_token:' . $token, [
            'user_id' => $user['id'],
            'tenant_id' => $tenantId,
            'user_type' => 11,
        ], 86400 * 7);

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'token' => $token,
                'access_token' => $token,
                'accessToken' => $token,
                'user' => [
                    'id' => $user['id'],
                    'nickname' => $user['nickname'] ?? '',
                    'avatar' => $user['avatar'] ?? '',
                    'phone' => $user['phone'] ?? '',
                    'balance' => $user['balance'] ?? 0,
                ],
            ],
        ]);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $userId = $this->request->userId;
        $user = Db::name('user')->where('id', $userId)->find();

        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        return json([
            'code' => 0,
            'data' => [
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'phone' => $user['phone'],
                'balance' => $user['balance'],
            ],
        ]);
    }

    /**
     * 更新用户信息
     */
    public function updateUserInfo()
    {
        $userId = $this->request->userId;
        $nickname = $this->request->post('nickname');
        $avatar = $this->request->post('avatar');

        $updateData = [];
        if ($nickname !== null) {
            $updateData['nickname'] = $nickname;
        }
        if ($avatar !== null) {
            $updateData['avatar'] = $avatar;
        }

        if (empty($updateData)) {
            return json(['code' => 1, 'msg' => '没有要更新的数据']);
        }

        Db::name('user')->where('id', $userId)->update($updateData);

        return json(['code' => 0, 'msg' => '更新成功']);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $token = $this->request->header('token');
        if ($token) {
            Cache::delete('user_token:' . $token);
        }
        return json(['code' => 0, 'msg' => '退出成功']);
    }

    /**
     * 微信小程序登录（前端路由: weixin-mini-app-login）
     * 支持code登录 + 手机号获取
     */
    public function weixinMiniAppLogin()
    {
        $code = $this->request->post('code') ?: $this->request->post('loginCode', '');
        $phoneCode = $this->request->post('phoneCode', '');

        if (empty($code)) {
            return json(['code' => 1, 'msg' => 'code不能为空']);
        }

        $tenantId = $this->request->tenantId ?? '88888888';
        $appId = CacheService::getConfig($tenantId, 'wx_appid');
        $appSecret = CacheService::getConfig($tenantId, 'wx_app_secret');

        // 开发模式：未配置微信appid时，使用固定的测试openid，避免每次登录创建新用户
        $devMode = empty($appId) || empty($appSecret);
        $openid = '';
        $result = [];

        if ($devMode) {
            // 使用固定的测试 openid，这样每次登录都是同一个用户
            $openid = 'dev_test_user_001';
        } else {
            // 正式模式：调用微信接口获取openid
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
            $response = @file_get_contents($url);
            $result = json_decode($response ?: '{}', true);

            if (empty($result['openid'])) {
                return json(['code' => 1, 'msg' => '微信登录失败: ' . ($result['errmsg'] ?? '未知错误')]);
            }
            $openid = $result['openid'];
        }

        // 查找或创建用户
        $user = Db::name('user')
            ->where('tenant_id', $tenantId)
            ->where('openid', $openid)
            ->find();

        if (!$user) {
            // 随机生成昵称：棋友 + 6位随机字符
            $randomNick = '棋友' . substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6);
            $userId = Db::name('user')->insertGetId([
                'tenant_id' => $tenantId,
                'openid' => $openid,
                'unionid' => $result['unionid'] ?? null,
                'nickname' => $randomNick,
                'avatar' => '/static/img/default-avatar.png',
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            $user = Db::name('user')->find($userId);
        } elseif (empty($user['nickname'])) {
            // 已有用户但没昵称，补一个
            $randomNick = '棋友' . substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6);
            Db::name('user')->where('id', $user['id'])->update(['nickname' => $randomNick]);
            $user['nickname'] = $randomNick;
        }

        // 如果有phoneCode，获取手机号（仅正式模式）
        if (!$devMode && $phoneCode && empty($user['phone'])) {
            try {
                $accessToken = $this->getWxAccessToken($appId, $appSecret);
                if ($accessToken) {
                    $phoneUrl = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$accessToken}";
                    $phoneResp = $this->httpPost($phoneUrl, json_encode(['code' => $phoneCode]));
                    $phoneResult = json_decode($phoneResp ?: '{}', true);
                    if (!empty($phoneResult['phone_info']['phoneNumber'])) {
                        Db::name('user')->where('id', $user['id'])->update([
                            'phone' => $phoneResult['phone_info']['phoneNumber'],
                            'update_time' => date('Y-m-d H:i:s'),
                        ]);
                        $user['phone'] = $phoneResult['phone_info']['phoneNumber'];
                    }
                }
            } catch (\Exception $e) {
                // 手机号获取失败不影响登录
            }
        }

        // 生成token
        $token = bin2hex(random_bytes(32));
        Cache::set('user_token:' . $token, [
            'user_id' => $user['id'],
            'tenant_id' => $tenantId,
            'user_type' => $user['user_type'] ?? 11,
        ], 86400 * 7);

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'token' => $token,
                'access_token' => $token,
                'accessToken' => $token,
                'userId' => $user['id'],
                'user' => [
                    'id' => $user['id'],
                    'nickname' => $user['nickname'] ?? '',
                    'avatar' => $user['avatar'] ?? '',
                    'phone' => $user['phone'] ?? '',
                    'balance' => $user['balance'] ?? 0,
                ],
            ],
        ]);
    }

    /**
     * 通过code登录（wxLoginByCode 别名）
     */
    public function wxLoginByCode()
    {
        return $this->weixinMiniAppLogin();
    }

    /**
     * 账号密码登录
     */
    public function login()
    {
        $mobile = $this->request->post('mobile', '');
        $password = $this->request->post('password', '');
        $code = $this->request->post('code', '');

        // 如果有code，走微信登录
        if ($code) {
            return $this->weixinMiniAppLogin();
        }

        if (empty($mobile) || empty($password)) {
            return json(['code' => 1, 'msg' => '手机号和密码不能为空']);
        }

        $tenantId = $this->request->tenantId ?? '88888888';
        $user = Db::name('user')
            ->where('tenant_id', $tenantId)
            ->where('phone', $mobile)
            ->find();

        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        if (!empty($user['password']) && !password_verify($password, $user['password'])) {
            return json(['code' => 1, 'msg' => '密码错误']);
        }

        $token = bin2hex(random_bytes(32));
        Cache::set('user_token:' . $token, [
            'user_id' => $user['id'],
            'tenant_id' => $tenantId,
            'user_type' => $user['user_type'] ?? 11,
        ], 86400 * 7);

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'token' => $token,
                'access_token' => $token,
                'accessToken' => $token,
                'userId' => $user['id'],
                'user' => [
                    'id' => $user['id'],
                    'nickname' => $user['nickname'] ?? '',
                    'avatar' => $user['avatar'] ?? '',
                    'phone' => $user['phone'] ?? '',
                    'balance' => $user['balance'] ?? 0,
                ],
            ],
        ]);
    }

    /**
     * 获取微信access_token
     */
    private function getWxAccessToken(string $appId, string $appSecret): string
    {
        $cacheKey = 'wx_access_token:' . $appId;
        $token = Cache::get($cacheKey);
        if ($token) return $token;

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $resp = @file_get_contents($url);
        $data = json_decode($resp ?: '{}', true);
        if (!empty($data['access_token'])) {
            Cache::set($cacheKey, $data['access_token'], ($data['expires_in'] ?? 7200) - 200);
            return $data['access_token'];
        }
        return '';
    }

    /**
     * HTTP POST请求
     */
    private function httpPost(string $url, string $data): string
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $data,
                'timeout' => 10,
            ]
        ];
        $context = stream_context_create($opts);
        return @file_get_contents($url, false, $context) ?: '';
    }

}
