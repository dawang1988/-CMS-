<?php
// API路由配置
use think\facade\Route;

// 健康检查路由（无需认证和限流）
Route::group('app-api', function () {
    Route::get('health', 'api.Health/index');
    Route::get('health/detail', 'api.Health/detail');
    Route::get('health/ready', 'api.Health/ready');
    Route::get('health/live', 'api.Health/live');
})->middleware([\app\middleware\Cors::class]);

// MQTT 钩子路由（EMQX 回调，无需用户认证）
Route::group('app-api/mqtt', function () {
    Route::post('auth', 'api.MqttHook/auth');
    Route::post('acl', 'api.MqttHook/acl');
    Route::post('webhook', 'api.MqttHook/webhook');
})->middleware([\app\middleware\Tenant::class, \app\middleware\Cors::class]);

// 团购平台OAuth回调路由（无需认证）
Route::group('app-api/oauth', function () {
    Route::get('meituan/callback', 'api.GroupAuthCallback/meituan');
    Route::get('douyin/callback', 'api.GroupAuthCallback/douyin');
})->middleware([\app\middleware\Cors::class]);

// API路由组
Route::group('app-api', function () {
    
    // 认证相关（无需登录）
    Route::group('member/auth', function () {
        Route::post('wxLoginByCode', 'api.Auth/wxLoginByCode');
        Route::post('weixin-mini-app-login', 'api.Auth/weixinMiniAppLogin');
        Route::post('login', 'api.Auth/login');
        Route::post('logout', 'api.Auth/logout');
    });
    
    // 会员相关
    Route::group('member', function () {
        // 首页接口（兼容原生小程序）
        Route::group('index', function () {
            Route::post('getStoreList', 'api.Store/page');
            Route::get('getStoreList', 'api.Store/page');
            Route::get('getBannerList', 'api.Store/bannerList');
            Route::get('getStoreInfo/:id', 'api.Store/get');
            Route::post('getRoomInfoList', 'api.Store/roomList');
            Route::post('getRoomInfoList/:id', 'api.Store/roomList');
            Route::get('getRoomList/:id', 'api.Store/roomList');
            Route::post('getRoomInfo/:id', 'api.Order/getRoomInfo')->middleware(\app\middleware\Auth::class);
            Route::get('getCityList', 'api.Store/cityList');
            Route::get('getCityByLocation', 'api.Store/getCityByLocation');
            Route::get('getSysInfo', 'api.System/getSysInfo');
        });
        
        // 用户
        Route::post('user/login', 'api.Member/login');
        Route::get('user/info', 'api.Member/info')->middleware(\app\middleware\Auth::class);
        Route::get('user/get', 'api.Member/info')->middleware(\app\middleware\Auth::class); // 别名，兼容前端
        Route::post('user/update', 'api.Member/update')->middleware(\app\middleware\Auth::class);
        
        // 用户扩展接口
        Route::post('user/send-code', 'api.Member/sendCode');
        Route::post('user/update-mobile', 'api.Member/updateMobile')->middleware(\app\middleware\Auth::class);
        Route::post('user/updateAvatar', 'api.Member/updateAvatar')->middleware(\app\middleware\Auth::class);
        Route::post('user/updateNickname', 'api.Member/updateNickname')->middleware(\app\middleware\Auth::class);
        Route::get('user/getCouponPage', 'api.Member/getCouponPage')->middleware(\app\middleware\Auth::class);
        Route::post('user/getCouponPage', 'api.Member/getCouponPage')->middleware(\app\middleware\Auth::class);
        Route::get('user/getMoneyBillPage', 'api.Member/getMoneyBillPage')->middleware(\app\middleware\Auth::class);
        Route::post('user/getMoneyBillPage', 'api.Member/getMoneyBillPage')->middleware(\app\middleware\Auth::class);
        Route::get('user/getGiftBalanceList', 'api.Member/getGiftBalanceList')->middleware(\app\middleware\Auth::class);
        Route::get('user/getStoreBalance/:id', 'api.Member/getStoreBalance')->middleware(\app\middleware\Auth::class);
        Route::post('user/preRechargeBalance', 'api.Member/preRechargeBalance')->middleware(\app\middleware\Auth::class);
        Route::post('user/rechargeBalance', 'api.Member/rechargeBalance')->middleware(\app\middleware\Auth::class);
        Route::get('user/getFranchiseInfo', 'api.Member/getFranchiseInfo')->middleware(\app\middleware\Auth::class);
        Route::post('user/saveFranchiseInfo', 'api.Member/saveFranchiseInfo')->middleware(\app\middleware\Auth::class);
        
        // 门店管理（管理员功能）
        Route::post('store/getPageList', 'api.Store/getPageList')->middleware(\app\middleware\Auth::class);
        Route::post('store/openStoreDoor/:id', 'api.Store/openStoreDoor')->middleware(\app\middleware\Auth::class);
        Route::post('store/save', 'api.Store/save')->middleware(\app\middleware\Auth::class);
        Route::post('store/getServiceInfo', 'api.Store/getServiceInfo')->middleware(\app\middleware\Auth::class);
        Route::post('store/uploadImg', 'api.Upload/upload')->middleware(\app\middleware\Auth::class);
        
        // 门店管理扩展接口
        Route::post('store/addLock', 'api.Store/addLock')->middleware(\app\middleware\Auth::class);
        Route::post('store/addDevice', 'api.Store/addDevice')->middleware(\app\middleware\Auth::class);
        Route::post('store/delDevice/:id', 'api.Store/delDevice')->middleware(\app\middleware\Auth::class);
        Route::get('store/getRoomList/:id', 'api.Store/getRoomList')->middleware(\app\middleware\Auth::class);
        Route::get('store/getRoomInfoList/:id', 'api.Store/getRoomInfoList')->middleware(\app\middleware\Auth::class);
        Route::get('store/getRoomInfoList', 'api.Store/getRoomInfoList')->middleware(\app\middleware\Auth::class);
        Route::post('store/getRoomInfoList', 'api.Store/getRoomInfoList')->middleware(\app\middleware\Auth::class);
        Route::post('store/deleteRoomInfo/:id', 'api.Store/deleteRoomInfo')->middleware(\app\middleware\Auth::class);
        Route::post('store/disableRoom/:id', 'api.Store/disableRoom')->middleware(\app\middleware\Auth::class);
        Route::post('store/saveRoomInfo', 'api.Store/saveRoomInfo')->middleware(\app\middleware\Auth::class);
        Route::post('store/updateRoomInfo', 'api.Store/updateRoomInfo')->middleware(\app\middleware\Auth::class);
        Route::get('store/getDetail/:id', 'api.Store/getDetail')->middleware(\app\middleware\Auth::class);
        Route::get('store/getStatistics', 'api.Store/getStatistics')->middleware(\app\middleware\Auth::class);
        Route::post('store/getStatistics', 'api.Store/getStatistics')->middleware(\app\middleware\Auth::class);
        Route::post('store/checkBall/:id', 'api.Store/checkBall')->middleware(\app\middleware\Auth::class);
        Route::get('store/getStoreSoundInfo/:id', 'api.Store/getStoreSoundInfo')->middleware(\app\middleware\Auth::class);
        Route::post('store/saveStoreSoundInfo', 'api.Store/saveStoreSoundInfo')->middleware(\app\middleware\Auth::class);
        Route::get('store/getQrConfig', 'api.Store/getQrConfig')->middleware(\app\middleware\Auth::class);
        Route::post('store/setQrConfig', 'api.Store/setQrConfig')->middleware(\app\middleware\Auth::class);
        Route::post('store/resetQrcode', 'api.Store/resetQrcode')->middleware(\app\middleware\Auth::class);
        Route::get('store/getGroupPayAuthUrl', 'api.Store/getGroupPayAuthUrl')->middleware(\app\middleware\Auth::class);
        Route::post('store/getGroupPayAuthUrl', 'api.Store/getGroupPayAuthUrl')->middleware(\app\middleware\Auth::class);
        Route::post('store/setDouyinId', 'api.Store/setDouyinId')->middleware(\app\middleware\Auth::class);
        Route::get('store/getStoreListByAdmin', 'api.Store/getStoreListByAdmin')->middleware(\app\middleware\Auth::class);
        
        // 会员配置
        Route::post('store/getVipConfig/:id', 'api.VipConfig/getList')->middleware(\app\middleware\Auth::class);
        Route::post('store/saveVipConfig', 'api.VipConfig/save')->middleware(\app\middleware\Auth::class);
        Route::post('store/deleteVipConfig/:id', 'api.VipConfig/delete')->middleware(\app\middleware\Auth::class);
        
        // VIP积分信息（用户端）
        Route::get('user/getVipInfo', 'api.Member/getVipInfo')->middleware(\app\middleware\Auth::class);
        
        // 折扣规则
        Route::post('store/getDiscountRulesPage', 'api.DiscountRule/getPage')->middleware(\app\middleware\Auth::class);
        Route::get('store/getDiscountRuleDetail/:id', 'api.DiscountRule/getDetail')->middleware(\app\middleware\Auth::class);
        Route::post('store/saveDiscountRuleDetail', 'api.DiscountRule/save')->middleware(\app\middleware\Auth::class);
        Route::post('store/changeDiscountRulesStatus/:id', 'api.DiscountRule/changeStatus')->middleware(\app\middleware\Auth::class);
        Route::post('store/deleteDiscountRule/:id', 'api.DiscountRule/delete')->middleware(\app\middleware\Auth::class);
        
        // 保洁任务管理
        Route::post('manager/getClearManagerPage', 'api.ClearTask/getManagerPage')->middleware(\app\middleware\Auth::class);
        Route::post('manager/cancelClear/:id', 'api.ClearTask/cancel')->middleware(\app\middleware\Auth::class);
        Route::post('clear/jiedan/:id', 'api.ClearTask/jiedan')->middleware(\app\middleware\Auth::class);
        Route::post('clear/cancel/:id', 'api.ClearTask/cancelTake')->middleware(\app\middleware\Auth::class);
        Route::post('clear/start/:id', 'api.ClearTask/start')->middleware(\app\middleware\Auth::class);
        Route::post('clear/finish/:id', 'api.ClearTask/finish')->middleware(\app\middleware\Auth::class);
        Route::post('clear/assign/:id', 'api.ClearTask/assign')->middleware(\app\middleware\Auth::class);
        Route::post('clear/managerJiedan/:id', 'api.ClearTask/managerJiedan')->middleware(\app\middleware\Auth::class);
        Route::post('clear/managerStart/:id', 'api.ClearTask/managerStart')->middleware(\app\middleware\Auth::class);
        Route::post('clear/managerFinish/:id', 'api.ClearTask/managerFinish')->middleware(\app\middleware\Auth::class);
        Route::post('clear/createManual', 'api.ClearTask/createManual')->middleware(\app\middleware\Auth::class);
        Route::get('clear/getCleanerList', 'api.ClearTask/getCleanerList')->middleware(\app\middleware\Auth::class);
        Route::post('clear/getCleanerList', 'api.ClearTask/getCleanerList')->middleware(\app\middleware\Auth::class);
        Route::post('clear/settle/:id', 'api.ClearTask/settle')->middleware(\app\middleware\Auth::class);
        Route::post('clear/openStoreDoor/:id', 'api.ClearTask/openStoreDoor')->middleware(\app\middleware\Auth::class);
        Route::post('clear/openRoomDoor/:id', 'api.ClearTask/openRoomDoor')->middleware(\app\middleware\Auth::class);
        Route::get('clear/getDetail/:id', 'api.ClearTask/getDetail')->middleware(\app\middleware\Auth::class);
        Route::get('clear/getChartData', 'api.ClearTask/getChartData')->middleware(\app\middleware\Auth::class);
        Route::post('clear/getChartData', 'api.ClearTask/getChartData')->middleware(\app\middleware\Auth::class);
        Route::get('clear/getCleanerStats', 'api.ClearTask/getCleanerStats')->middleware(\app\middleware\Auth::class);
        Route::post('clear/getCleanerStats', 'api.ClearTask/getCleanerStats')->middleware(\app\middleware\Auth::class);
        Route::get('clear/getClearBillPage', 'api.ClearTask/getClearBillPage')->middleware(\app\middleware\Auth::class);
        Route::post('clear/getClearBillPage', 'api.ClearTask/getClearBillPage')->middleware(\app\middleware\Auth::class);
        
        // 订单
        Route::post('order/save', 'api.Order/save')->middleware(\app\middleware\Auth::class);
        Route::post('order/getOrderList', 'api.Order/getOrderList')->middleware(\app\middleware\Auth::class);
        Route::post('order/getOrderPage', 'api.Order/getOrderList')->middleware(\app\middleware\Auth::class); // 别名，兼容前端
        Route::get('order/getOrderInfo/:id', 'api.Order/getOrderInfo')->middleware(\app\middleware\Auth::class);
        Route::get('order/getOrderInfo', 'api.Order/getOrderInfo')->middleware(\app\middleware\Auth::class);
        Route::get('order/getOrderInfoByNo', 'api.Order/getOrderInfoByNo')->middleware(\app\middleware\Auth::class);
        Route::post('order/pay', 'api.Order/pay')->middleware(\app\middleware\Auth::class);
        Route::post('order/renew', 'api.Order/renew')->middleware(\app\middleware\Auth::class);
        Route::post('order/cancel', 'api.Order/cancel')->middleware(\app\middleware\Auth::class);
        Route::post('order/cancelOrder', 'api.Order/cancel')->middleware(\app\middleware\Auth::class); // 别名
        Route::post('order/cancelOrder/:id', 'api.Order/cancel')->middleware(\app\middleware\Auth::class); // 别名
        Route::post('order/openRoomDoor', 'api.Order/openRoomDoor')->middleware(\app\middleware\Auth::class);
        Route::post('order/openStoreDoor', 'api.Order/openStoreDoor')->middleware(\app\middleware\Auth::class);
        Route::post('order/openLockOnly', 'api.Order/openLockOnly')->middleware(\app\middleware\Auth::class);
        Route::post('order/getLockPwd', 'api.Order/getLockPwd')->middleware(\app\middleware\Auth::class);
        Route::post('order/controlKT', 'api.Order/controlKT')->middleware(\app\middleware\Auth::class);
        Route::post('order/controlDevice', 'api.Order/controlDevice')->middleware(\app\middleware\Auth::class);
        Route::post('order/startOrder/:id', 'api.Order/startOrder')->middleware(\app\middleware\Auth::class);
        Route::post('order/closeOrder/:id', 'api.Order/closeOrder')->middleware(\app\middleware\Auth::class);
        Route::get('order/getOrderByRoomId/:id', 'api.Order/getOrderByRoomId')->middleware(\app\middleware\Auth::class);
        Route::post('order/getOrderByRoomId/:id', 'api.Order/getOrderByRoomId')->middleware(\app\middleware\Auth::class);
        Route::get('order/getDiscountRules/:id', 'api.Order/getDiscountRules')->middleware(\app\middleware\Auth::class);
        Route::post('order/preGroupNo', 'api.Order/preGroupNo')->middleware(\app\middleware\Auth::class);
        Route::get('order/search', 'api.Order/search')->middleware(\app\middleware\Auth::class);
        Route::post('order/preOrder', 'api.Order/preOrder')->middleware(\app\middleware\Auth::class);
        Route::get('order/getPayMethods', 'api.Order/getPayMethods')->middleware(\app\middleware\Auth::class);
        Route::post('order/lockWxOrder', 'api.Order/lockOrder')->middleware(\app\middleware\Auth::class);
        Route::post('order/changeRoom', 'api.Order/changeRoom')->middleware(\app\middleware\Auth::class);
        Route::post('order/changeRoom/:orderId/:roomId', 'api.Order/changeRoom')->middleware(\app\middleware\Auth::class);
        
        // 管理员功能
        Route::group('manager', function () {
            Route::post('submitOrder', 'api.Order/managerSubmitOrder')->middleware(\app\middleware\Auth::class);
            Route::post('getAdminUserPage', 'api.Manager/getAdminUserPage')->middleware(\app\middleware\Auth::class);
            Route::post('saveAdminUser', 'api.Manager/saveAdminUser')->middleware(\app\middleware\Auth::class);
            Route::post('deleteAdminUser/:storeId/:userId', 'api.Manager/deleteAdminUser')->middleware(\app\middleware\Auth::class);
            Route::post('getClearUserPage', 'api.Manager/getClearUserPage')->middleware(\app\middleware\Auth::class);
            Route::post('saveClearUser', 'api.Manager/saveClearUser')->middleware(\app\middleware\Auth::class);
            Route::post('searchUserByPhone', 'api.Manager/searchUserByPhone')->middleware(\app\middleware\Auth::class);
            Route::post('deleteClearUser/:storeId/:userId', 'api.Manager/deleteClearUser')->middleware(\app\middleware\Auth::class);
            // VIP会员管理
            Route::post('getVipPage', 'api.Manager/getVipPage')->middleware(\app\middleware\Auth::class);
            Route::post('deleteVip', 'api.Manager/deleteVip')->middleware(\app\middleware\Auth::class);
            // 优惠券管理
            Route::post('getCouponPage', 'api.Manager/getCouponPage')->middleware(\app\middleware\Auth::class);
            Route::post('giftCoupon', 'api.Manager/giftCoupon')->middleware(\app\middleware\Auth::class);
            Route::post('deleteCoupon', 'api.Manager/deleteCoupon')->middleware(\app\middleware\Auth::class);
            // 预订取消审核
            Route::post('getYDCancelAuthList/:storeId', 'api.Manager/getYDCancelAuthList')->middleware(\app\middleware\Auth::class);
            Route::post('auditYD', 'api.Manager/auditYD')->middleware(\app\middleware\Auth::class);
            // 支付订单管理
            Route::post('getPayOrderPage', 'api.Manager/getPayOrderPage')->middleware(\app\middleware\Auth::class);
            Route::post('refundPayOrder', 'api.Manager/refundPayOrder')->middleware(\app\middleware\Auth::class);
            // 权限管理
            Route::get('getPermission', 'api.Manager/getPermission')->middleware(\app\middleware\Auth::class);
            Route::post('getPermissionList', 'api.Manager/getPermissionList')->middleware(\app\middleware\Auth::class);
            Route::post('savePermission', 'api.Manager/savePermission')->middleware(\app\middleware\Auth::class);
            // 团购验券
            Route::post('useGroupNo', 'api.Manager/useGroupNo')->middleware(\app\middleware\Auth::class);
            Route::post('getGroupVerifyPage', 'api.Manager/getGroupVerifyPage')->middleware(\app\middleware\Auth::class);
            Route::get('getGroupVerifyPage', 'api.Manager/getGroupVerifyPage')->middleware(\app\middleware\Auth::class);
            Route::post('getGroupCouponPage', 'api.Manager/getGroupCouponPage')->middleware(\app\middleware\Auth::class);
            Route::get('getGroupCouponPage', 'api.Manager/getGroupCouponPage')->middleware(\app\middleware\Auth::class);
            Route::post('saveGroupCoupon', 'api.Manager/saveGroupCoupon')->middleware(\app\middleware\Auth::class);
            Route::post('deleteGroupCoupon', 'api.Manager/deleteGroupCoupon')->middleware(\app\middleware\Auth::class);
        });
        
        // 会员VIP操作
        Route::post('store/editMemberVip', 'api.Store/editMemberVip')->middleware(\app\middleware\Auth::class);
        Route::post('store/addMemberVip', 'api.Store/addMemberVip')->middleware(\app\middleware\Auth::class);
        
        // 会员黑名单
        Route::post('store/vip/blacklist', 'api.Store/getVipBlacklist')->middleware(\app\middleware\Auth::class);
        Route::post('store/addBlackList', 'api.Store/addBlackList')->middleware(\app\middleware\Auth::class);
        Route::post('store/remove/:id', 'api.Store/removeBlackList')->middleware(\app\middleware\Auth::class);
        
        // 模板管理
        Route::post('store/getTemplateList', 'api.Store/getTemplateList')->middleware(\app\middleware\Auth::class);
        Route::post('store/setTemplate', 'api.Store/setTemplate')->middleware(\app\middleware\Auth::class);
        
        // 数据统计图表
        Route::group('chart', function () {
            Route::post('getRevenueChart', 'api.Chart/getRevenueChart')->middleware(\app\middleware\Auth::class);
            Route::post('getBusinessStatistics', 'api.Chart/getBusinessStatistics')->middleware(\app\middleware\Auth::class);
            Route::post('getRevenueStatistics', 'api.Chart/getRevenueStatistics')->middleware(\app\middleware\Auth::class);
            Route::post('getOrderStatistics', 'api.Chart/getOrderStatistics')->middleware(\app\middleware\Auth::class);
            Route::post('getMemberStatistics', 'api.Chart/getMemberStatistics')->middleware(\app\middleware\Auth::class);
            Route::post('getRoomUseStatistics', 'api.Chart/getRoomUseStatistics')->middleware(\app\middleware\Auth::class);
            Route::post('getRoomUseHour', 'api.Chart/getRoomUseHour')->middleware(\app\middleware\Auth::class);
            Route::post('getIncomeStatistics', 'api.Chart/getIncomeStatistics')->middleware(\app\middleware\Auth::class);
            Route::post('getRechargeStatistics', 'api.Chart/getRechargeStatistics')->middleware(\app\middleware\Auth::class);
        });
        
        // 推送规则
        Route::get('reserve/push/rule/:storeId', 'api.Store/getPushRule')->middleware(\app\middleware\Auth::class);
        Route::post('reserve/push/rule', 'api.Store/savePushRule')->middleware(\app\middleware\Auth::class);
        
        // 预付配置
        Route::get('store/getPrePayConfig/:roomId', 'api.Store/getPrePayConfig')->middleware(\app\middleware\Auth::class);
        Route::post('store/setPrePayConfig', 'api.Store/setPrePayConfig')->middleware(\app\middleware\Auth::class);
        
        // 优惠券活动
        Route::post('couponActive/getAdminByCouponId', 'api.CouponActive/getAdminByCouponId')->middleware(\app\middleware\Auth::class);
        Route::post('couponActive/saveAdminByCouponId', 'api.CouponActive/saveAdminByCouponId')->middleware(\app\middleware\Auth::class);
        Route::post('couponActive/stopActive', 'api.CouponActive/stopActive')->middleware(\app\middleware\Auth::class);
        
        // 套餐管理（管理员）
        Route::group('pkg/admin', function () {
            Route::post('getAdminPkgPage', 'api.Pkg/getAdminPkgPage')->middleware(\app\middleware\Auth::class);
            Route::post('enable/:id', 'api.Pkg/enable')->middleware(\app\middleware\Auth::class);
            Route::post('delete/:id', 'api.Pkg/delete')->middleware(\app\middleware\Auth::class);
            Route::post('saveAdminPkg', 'api.Pkg/saveAdminPkg')->middleware(\app\middleware\Auth::class);
        });
        
        // 优惠券详情
        Route::get('manager/getCouponDetail/:id', 'api.Manager/getCouponDetail')->middleware(\app\middleware\Auth::class);
        Route::post('manager/saveCouponDetail', 'api.Manager/saveCouponDetail')->middleware(\app\middleware\Auth::class);
        
        // 房间信息（用于订单页面）
        Route::post('index/getRoomInfo/:id', 'api.Order/getRoomInfo')->middleware(\app\middleware\Auth::class);
        
        // 余额
        Route::get('balance/info', 'api.Balance/info')->middleware(\app\middleware\Auth::class);
        Route::post('balance/recharge', 'api.Balance/recharge')->middleware(\app\middleware\Auth::class);
        Route::post('balance/rechargeCallback', 'api.Balance/rechargeCallback');
        Route::get('balance/packages', 'api.Balance/packages')->middleware(\app\middleware\Auth::class);
        Route::get('balance/rechargeList', 'api.Balance/rechargeList')->middleware(\app\middleware\Auth::class);
        Route::get('balance/consumeList', 'api.Balance/consumeList')->middleware(\app\middleware\Auth::class);
        Route::get('balance/logs', 'api.Balance/logs')->middleware(\app\middleware\Auth::class);
        
        // 优惠券
        Route::get('coupon/list', 'api.Coupon/list')->middleware(\app\middleware\Auth::class);
        Route::post('couponActive/putCoupon', 'api.Coupon/receive')->middleware(\app\middleware\Auth::class);
        Route::get('coupon/available', 'api.Coupon/available')->middleware(\app\middleware\Auth::class);
        Route::get('coupon/group-list', 'api.Coupon/groupList')->middleware(\app\middleware\Auth::class);
        Route::post('coupon/verify', 'api.Coupon/verify')->middleware(\app\middleware\Auth::class);
        Route::get('couponActive/getById', 'api.Coupon/getById')->middleware(\app\middleware\Auth::class);
        Route::post('couponActive/getById', 'api.Coupon/getById')->middleware(\app\middleware\Auth::class);
        
        // 会员卡
        Route::get('card/getMyCardPage', 'api.Card/getMyCardPage')->middleware(\app\middleware\Auth::class);
        Route::get('card/getSaleCardPage', 'api.Card/getSaleCardPage')->middleware(\app\middleware\Auth::class);
        Route::post('card/buy', 'api.Card/buy')->middleware(\app\middleware\Auth::class);
        Route::get('card/available', 'api.Card/getAvailableCards')->middleware(\app\middleware\Auth::class);
        Route::get('card/checkEnabled', 'api.Card/checkEnabled');
        
        // 游戏/拼场
        Route::get('game/getGamePage', 'api.Game/getGamePage')->middleware(\app\middleware\Auth::class);
        Route::post('game/getGamePage', 'api.Game/getGamePage')->middleware(\app\middleware\Auth::class);
        Route::post('game/save', 'api.Game/save')->middleware(\app\middleware\Auth::class);
        Route::post('game/join/:id', 'api.Game/join')->middleware(\app\middleware\Auth::class);
        Route::post('game/deleteUser/:id/:userId', 'api.Game/deleteUser')->middleware(\app\middleware\Auth::class);
        Route::post('game/deleteUser/:id', 'api.Game/deleteUser')->middleware(\app\middleware\Auth::class);
        Route::post('game/sendMessage', 'api.Game/sendMessage')->middleware(\app\middleware\Auth::class);
        Route::get('game/getMessages', 'api.Game/getMessages')->middleware(\app\middleware\Auth::class);
        Route::post('game/getMessages', 'api.Game/getMessages')->middleware(\app\middleware\Auth::class);
        
        // 套餐
        Route::get('pkg/getPkgPage', 'api.Pkg/getPkgPage')->middleware(\app\middleware\Auth::class);
        Route::post('pkg/getPkgPage', 'api.Pkg/getPkgPage')->middleware(\app\middleware\Auth::class);
        
        // 订单评价
        Route::post('review/save', 'api.Review/save')->middleware(\app\middleware\Auth::class);
        Route::get('review/list', 'api.Review/list');
        Route::get('review/myList', 'api.Review/myList')->middleware(\app\middleware\Auth::class);
        
        // 设备
        Route::post('device/getDevicePage', 'api.Device/getDevicePage')->middleware(\app\middleware\Auth::class);
        Route::get('device/getDevicePage', 'api.Device/getDevicePage')->middleware(\app\middleware\Auth::class);
        Route::post('device/register', 'api.Device/register')->middleware(\app\middleware\Auth::class);
        Route::post('device/save', 'api.Device/save')->middleware(\app\middleware\Auth::class);
        Route::post('device/delete', 'api.Device/delete')->middleware(\app\middleware\Auth::class);
        
        // 设备绑定（MQTT）
        Route::post('device/bind', 'api.DeviceBind/bind')->middleware(\app\middleware\Auth::class);
        Route::post('device/unbind', 'api.DeviceBind/unbind')->middleware(\app\middleware\Auth::class);
        Route::get('device/scan', 'api.DeviceBind/scan')->middleware(\app\middleware\Auth::class);
        Route::get('device/storeDevices', 'api.DeviceBind/storeDevices')->middleware(\app\middleware\Auth::class);
        Route::post('device/changeRoom', 'api.DeviceBind/changeRoom')->middleware(\app\middleware\Auth::class);
        
        // 商户账户
        Route::get('merchant-account/getApplyUrl', 'api.MerchantAccount/getApplyUrl')->middleware(\app\middleware\Auth::class);
        Route::post('merchant-account/getApplyUrl', 'api.MerchantAccount/getApplyUrl')->middleware(\app\middleware\Auth::class);
    });
    
    // 门店相关（无需登录）
    Route::group('store', function () {
        Route::get('store/page', 'api.Store/page');
        Route::get('store/get', 'api.Store/get');
        Route::get('room/list', 'api.Store/roomList');
        Route::get('room/get', 'api.Store/roomGet');
        Route::post('room/check', 'api.Store/roomCheck');
        Route::get('banner/list', 'api.Store/bannerList');
    });
    
    // 文件上传
    Route::post('infra/file/upload', 'api.Upload/upload')->middleware(\app\middleware\Auth::class);
    
    // 商品订单
    Route::group('product/order', function () {
        Route::get('page', 'api.ProductOrder/page')->middleware(\app\middleware\Auth::class);
        Route::post('page', 'api.ProductOrder/page')->middleware(\app\middleware\Auth::class);
        Route::get('manage/page', 'api.ProductOrder/managePage')->middleware(\app\middleware\Auth::class);
        Route::post('manage/page', 'api.ProductOrder/managePage')->middleware(\app\middleware\Auth::class);
        Route::post('create', 'api.ProductOrder/create')->middleware(\app\middleware\Auth::class);
        Route::get('info', 'api.ProductOrder/info')->middleware(\app\middleware\Auth::class);
        Route::post('info', 'api.ProductOrder/info')->middleware(\app\middleware\Auth::class);
        Route::get('info/:id', 'api.ProductOrder/info')->middleware(\app\middleware\Auth::class);
        Route::post('info/:id', 'api.ProductOrder/info')->middleware(\app\middleware\Auth::class);
        Route::post('cancel', 'api.ProductOrder/cancel')->middleware(\app\middleware\Auth::class);
        Route::post('cancelByAdmin', 'api.ProductOrder/cancel')->middleware(\app\middleware\Auth::class);
        Route::post('pay/:id', 'api.ProductOrder/pay')->middleware(\app\middleware\Auth::class);
        Route::post('finish/:id', 'api.ProductOrder/finish')->middleware(\app\middleware\Auth::class);
        Route::post('phone/:id', 'api.ProductOrder/phone')->middleware(\app\middleware\Auth::class);
        Route::get('getstore', 'api.ProductOrder/getstore')->middleware(\app\middleware\Auth::class);
    });
    
    // 商品
    Route::group('product/store-product', function () {
        Route::get('products', 'api.StoreProduct/products');
        Route::post('page', 'api.StoreProduct/page')->middleware(\app\middleware\Auth::class);
        Route::post('sale', 'api.StoreProduct/sale')->middleware(\app\middleware\Auth::class);
        Route::post('delete/:id', 'api.StoreProduct/delete')->middleware(\app\middleware\Auth::class);
        Route::post('create', 'api.StoreProduct/create')->middleware(\app\middleware\Auth::class);
        Route::get('info/:id', 'api.StoreProduct/info')->middleware(\app\middleware\Auth::class);
    });
    
    // 商品分类
    Route::group('product/category', function () {
        Route::get('list', 'api.ProductCategory/list')->middleware(\app\middleware\Auth::class);
        Route::post('create', 'api.ProductCategory/create')->middleware(\app\middleware\Auth::class);
        Route::post('update', 'api.ProductCategory/update')->middleware(\app\middleware\Auth::class);
        Route::post('delete/:id', 'api.ProductCategory/delete')->middleware(\app\middleware\Auth::class);
    });
    
    // 门店设备
    Route::group('store/device', function () {
        Route::post('open', 'api.StoreDevice/open')->middleware(\app\middleware\Auth::class);
    });
    
    // 系统
    Route::group('system', function () {
        Route::get('config', 'api.System/config');
        Route::get('config/get', 'api.System/getConfig');
        Route::post('feedback/create', 'api.System/createFeedback');
        Route::get('help/list', 'api.System/getHelpList');
        Route::get('help/get', 'api.System/getHelp');
    });
    
    // 支付回调（无需认证）
    Route::post('pay/wechat/callback', 'api.Pay/wechatCallback');
    Route::post('pay/notify', 'api.Pay/notify');
    
})->middleware([\app\middleware\Tenant::class, \app\middleware\Cors::class]);

// 健康检查
Route::get('app-api/health', function () {
    return json([
        'code' => 0,
        'msg' => 'ok',
        'data' => [
            'status' => 'healthy',
            'time' => date('Y-m-d H:i:s')
        ]
    ]);
});

// 定时任务：自动关闭到期订单（可通过 HTTP 调用，也可通过 cron 执行 php think auto:close_order）
Route::get('app-api/cron/auto-close-order', function () {
    date_default_timezone_set('Asia/Shanghai');
    $now = date('Y-m-d H:i:s');
    $result = ['closed' => 0, 'clear_tasks' => 0, 'cancelled' => 0];
    
    // 1. 关闭到期订单
    $expiredOrders = \think\facade\Db::query(
        "SELECT * FROM ss_order WHERE status = 1 AND end_time IS NOT NULL AND end_time <= ?",
        [$now]
    );
    
    foreach ($expiredOrders as $order) {
        \think\facade\Db::startTrans();
        try {
            \think\facade\Db::name('order')->where('id', $order['id'])->update([
                'status' => 2, 'actual_end_time' => $now, 'update_time' => $now,
            ]);
            \think\facade\Db::name('room')->where('id', $order['room_id'])->update([
                'status' => 2, 'update_time' => $now,
            ]);
            $storeName = \think\facade\Db::name('store')->where('id', $order['store_id'])->value('name') ?: '';
            $roomName = \think\facade\Db::name('room')->where('id', $order['room_id'])->value('name') ?: '';
            $existTask = \think\facade\Db::name('clear_task')->where('order_no', $order['order_no'])->find();
            if (!$existTask) {
                \think\facade\Db::name('clear_task')->insert([
                    'tenant_id' => $order['tenant_id'], 'store_id' => $order['store_id'],
                    'room_id' => $order['room_id'], 'order_no' => $order['order_no'],
                    'status' => 0, 'order_end_time' => $order['end_time'],
                    'store_name' => $storeName, 'room_name' => $roomName,
                    'create_time' => $now, 'update_time' => $now,
                ]);
                $result['clear_tasks']++;
            }
            \think\facade\Db::commit();
            $result['closed']++;
        } catch (\Exception $e) {
            \think\facade\Db::rollback();
        }
    }
    
    // 2. 取消超时未支付订单
    $cancelTime = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $unpaidOrders = \think\facade\Db::query(
        "SELECT * FROM ss_order WHERE status = 0 AND create_time < ?", [$cancelTime]
    );
    foreach ($unpaidOrders as $order) {
        try {
            \think\facade\Db::name('order')->where('id', $order['id'])->update([
                'status' => 3, 'update_time' => $now,
            ]);
            $result['cancelled']++;
        } catch (\Exception $e) {}
    }
    
    return json(['code' => 0, 'msg' => 'ok', 'data' => $result]);
});
