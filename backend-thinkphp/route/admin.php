<?php
// 管理后台路由配置
use think\facade\Route;

// 管理后台登录接口（无需认证）
Route::group('app-api/admin/auth', function () {
    Route::post('login', 'admin.AdminAccount/login');
    Route::post('logout', 'admin.AdminAccount/logout');
})->middleware([\app\middleware\Tenant::class, \app\middleware\Cors::class]);

// 管理后台API路由组（需要认证）
Route::group('app-api/admin', function () {
    
    // 门店管理
    Route::get('store/list', 'admin.Store/list');
    Route::get('store/get', 'admin.Store/get');
    Route::post('store/add', 'admin.Store/add');
    Route::post('store/update', 'admin.Store/update');
    Route::post('store/delete', 'admin.Store/delete');
    Route::get('store/getSoundConfig', 'admin.Store/getSoundConfig');
    Route::post('store/saveSoundConfig', 'admin.Store/saveSoundConfig');
    Route::post('store/testSound', 'admin.Store/testSound');
    Route::get('store/detail', 'admin.Store/detail');
    Route::get('store/getGroupAuthStatus', 'admin.Store/getGroupAuthStatus');
    Route::post('store/saveGroupAuth', 'admin.Store/saveGroupAuth');
    Route::post('store/getGroupPayAuthUrl', 'admin.Store/getGroupPayAuthUrl');
    Route::post('store/setDouyinId', 'admin.Store/setDouyinId');
    
    // 房间管理
    Route::get('room/list', 'admin.Room/list');
    Route::get('room/get', 'admin.Room/get');
    Route::post('room/add', 'admin.Room/add');
    Route::post('room/update', 'admin.Room/update');
    Route::post('room/delete', 'admin.Room/delete');
    
    // 订单管理
    Route::get('order/list', 'admin.Order/list');
    Route::get('order/get', 'admin.Order/get');
    Route::post('order/refund', 'admin.Order/refund');
    Route::post('order/renew', 'admin.Order/renew');
    Route::post('order/cancel', 'admin.Order/cancel');
    Route::post('order/close', 'admin.Order/close');
    Route::post('order/close/:id', 'admin.Order/close');
    Route::get('order/availableRooms', 'admin.Order/availableRooms');
    Route::post('order/changeRoom', 'admin.Order/changeRoom');
    
    // 用户管理
    Route::get('user/list', 'admin.User/list');
    Route::get('user/get', 'admin.User/get');
    Route::post('user/adjustBalance', 'admin.User/adjustBalance');
    Route::post('user/updateStatus', 'admin.User/updateStatus');
    Route::post('user/vipPage', 'admin.User/vipPage');
    Route::get('user/vipPage', 'admin.User/vipPage');
    Route::post('user/recharge', 'admin.User/recharge');
    
    // 优惠券管理
    Route::get('coupon/list', 'admin.Coupon/list');
    Route::get('coupon/get', 'admin.Coupon/get');
    Route::post('coupon/add', 'admin.Coupon/add');
    Route::post('coupon/update', 'admin.Coupon/update');
    Route::post('coupon/delete', 'admin.Coupon/delete');
    Route::post('coupon/gift', 'admin.Coupon/gift');
    Route::post('coupon/saveActivity', 'admin.Coupon/saveActivity');
    Route::get('coupon/getActivity', 'admin.Coupon/getActivity');
    Route::post('coupon/stopActivity', 'admin.Coupon/stopActivity');
    Route::get('coupon/searchUser', 'admin.Coupon/searchUser');
    
    // 游戏拼场管理
    Route::get('game/list', 'admin.Game/list');
    Route::get('game/get', 'admin.Game/get');
    Route::post('game/delete', 'admin.Game/delete');
    Route::get('game/messages', 'admin.Game/messages');
    Route::get('game/stats', 'admin.Game/stats');
    Route::post('game/updateStatus', 'admin.Game/updateStatus');
    Route::post('game/update', 'admin.Game/update');
    
    // 会员卡管理
    Route::get('card/list', 'admin.Card/list');
    Route::get('card/get', 'admin.Card/get');
    Route::post('card/save', 'admin.Card/save');
    Route::post('card/delete', 'admin.Card/delete');
    Route::get('card/storeEnabled', 'admin.Card/getStoreCardEnabled');
    Route::post('card/setStoreEnabled', 'admin.Card/setStoreCardEnabled');
    Route::get('card/userCardList', 'admin.Card/userCardList');
    
    // 套餐管理
    Route::get('package/list', 'admin.Package/list');
    Route::get('package/get', 'admin.Package/get');
    Route::post('package/save', 'admin.Package/save');
    Route::post('package/delete', 'admin.Package/delete');
    
    // 商品管理
    Route::get('product/list', 'admin.Product/list');
    Route::get('product/get', 'admin.Product/get');
    Route::post('product/save', 'admin.Product/save');
    Route::post('product/delete', 'admin.Product/delete');
    Route::get('product/categoryList', 'admin.Product/categoryList');
    Route::post('product/updateStatus', 'admin.Product/updateStatus');
    
    // 商品订单管理
    Route::get('product-order/list', 'admin.ProductOrder/list');
    Route::post('product-order/list', 'admin.ProductOrder/list');
    Route::get('product-order/get', 'admin.ProductOrder/get');
    Route::post('product-order/finish', 'admin.ProductOrder/finish');
    Route::post('product-order/cancel', 'admin.ProductOrder/cancel');
    Route::post('product-order/refund', 'admin.ProductOrder/refund');
    Route::post('product-order/pickup', 'admin.ProductOrder/finish');  // 标记已取=完成
    
    // 设备管理
    Route::get('device/list', 'admin.Device/list');
    Route::get('device/get', 'admin.Device/get');
    Route::post('device/register', 'admin.Device/register');
    Route::post('device/save', 'admin.Device/save');
    Route::post('device/delete', 'admin.Device/delete');
    Route::post('device/approve', 'admin.Device/approve');
    
    // 用户反馈管理
    Route::get('feedback/list', 'admin.Feedback/list');
    Route::get('feedback/get', 'admin.Feedback/get');
    Route::post('feedback/reply', 'admin.Feedback/reply');
    Route::post('feedback/complete', 'admin.Feedback/complete');
    
    // 加盟申请管理
    Route::get('franchise/list', 'admin.Franchise/list');
    Route::get('franchise/get', 'admin.Franchise/get');
    Route::post('franchise/audit', 'admin.Franchise/audit');
    
    // 系统配置管理
    Route::get('config/list', 'admin.Config/list');
    Route::post('config/save', 'admin.Config/save');
    Route::get('config/payment', 'admin.Config/payment');
    Route::post('config/savePayment', 'admin.Config/savePayment');
    
    // 帮助文档管理
    Route::get('help/list', 'admin.Help/list');
    Route::get('help/get', 'admin.Help/get');
    Route::post('help/save', 'admin.Help/save');
    Route::post('help/delete', 'admin.Help/delete');
    
    // 数据统计
    Route::get('statistics/overview', 'admin.Statistics/overview');
    Route::get('statistics/revenueTrend', 'admin.Statistics/revenueTrend');
    Route::get('statistics/roomRanking', 'admin.Statistics/roomRanking');
    Route::get('statistics/storeRevenue', 'admin.Statistics/storeRevenue');
    Route::get('statistics/storeList', 'admin.Statistics/storeList');
    Route::post('statistics/revenueChart', 'admin.Statistics/revenueChart');
    Route::post('statistics/businessStatistics', 'admin.Statistics/businessStatistics');
    Route::post('statistics/revenueStatistics', 'admin.Statistics/revenueStatistics');
    Route::post('statistics/orderStatistics', 'admin.Statistics/orderStatistics');
    Route::post('statistics/memberStatistics', 'admin.Statistics/memberStatistics');
    Route::post('statistics/incomeStatistics', 'admin.Statistics/incomeStatistics');
    Route::post('statistics/rechargeStatistics', 'admin.Statistics/rechargeStatistics');
    Route::post('statistics/roomUseStatistics', 'admin.Statistics/roomUseStatistics');
    Route::post('statistics/roomUseHour', 'admin.Statistics/roomUseHour');
    Route::get('statistics/paymentStats', 'admin.Statistics/paymentStats');
    Route::get('statistics/export', 'admin.Statistics/export');
    Route::post('statistics/export', 'admin.Statistics/export');
    
    // 评价管理
    Route::get('review/list', 'admin.Review/list');
    Route::get('review/get', 'admin.Review/get');
    Route::post('review/reply', 'admin.Review/reply');
    Route::post('review/delete', 'admin.Review/delete');
    
    // 轮播图管理
    Route::get('banner/list', 'admin.Banner/list');
    Route::get('banner/get', 'admin.Banner/get');
    Route::post('banner/save', 'admin.Banner/save');
    Route::post('banner/delete', 'admin.Banner/delete');
    
    // 保洁管理
    Route::rule('clean/list', 'admin.Statistics/cleanList', 'GET|POST');
    Route::get('clean/detail/:id', 'admin.Statistics/cleanDetail');
    Route::post('clean/cancel/:id', 'admin.Statistics/cancelClean');
    Route::post('clean/assign/:id', 'admin.Statistics/assignClean');
    Route::post('clean/jiedan/:id', 'admin.Statistics/jiedanClean');
    Route::post('clean/start/:id', 'admin.Statistics/startClean');
    Route::post('clean/finish/:id', 'admin.Statistics/finishClean');
    Route::post('clean/settle/:id', 'admin.Statistics/settleClean');
    Route::post('clean/create', 'admin.Statistics/createClean');
    Route::get('clean/cleanerList', 'admin.Statistics/cleanerList');
    Route::get('clean/roomList/:id', 'admin.Statistics/cleanRoomList');
    
    // 房间控制
    Route::post('room/disable/:id', 'admin.Room/disable');
    Route::post('room/openDoor/:id', 'admin.Room/openDoor');
    Route::post('room/closeDoor/:id', 'admin.Room/closeDoor');
    Route::post('room/openStoreDoor/:id', 'admin.Room/openStoreDoor');
    Route::post('room/forceFinish/:id', 'admin.Room/forceFinish');
    Route::post('room/controlDevice/:id', 'admin.Room/controlDevice');
    Route::get('room/deviceStatus/:id', 'admin.Room/getDeviceStatus');
    
    // 团购验券
    Route::post('group/verify', 'admin.Statistics/verifyGroupCoupon');
    Route::get('group/verify-log', 'admin.Statistics/groupVerifyLog');
    Route::get('group/coupon-list', 'admin.Statistics/groupCouponList');
    Route::post('group/coupon-save', 'admin.Statistics/groupCouponSave');
    Route::post('group/coupon-delete', 'admin.Statistics/groupCouponDelete');
    
    // 后台账号管理
    Route::get('account/list', 'admin.AdminAccount/list');
    Route::post('account/add', 'admin.AdminAccount/add');
    Route::post('account/update', 'admin.AdminAccount/update');
    Route::post('account/changePassword', 'admin.AdminAccount/changePassword');
    Route::post('account/delete', 'admin.AdminAccount/delete');
    
    // 权限管理
    Route::post('permission/list', 'admin.Permission/list');
    Route::get('permission/list', 'admin.Permission/list');
    Route::post('permission/save', 'admin.Permission/save');
    Route::post('permission/remove', 'admin.Permission/remove');
    Route::get('permission/searchUser', 'admin.Permission/searchUser');
    Route::post('permission/searchUser', 'admin.Permission/searchUser');
    Route::get('permission/storeList', 'admin.Permission/storeList');
    
    // VIP配置管理
    Route::get('vip-config/list', 'admin.VipConfig/list');
    Route::post('vip-config/save', 'admin.VipConfig/save');
    Route::post('vip-config/delete', 'admin.VipConfig/delete');
    Route::post('vip-config/adjustScore', 'admin.VipConfig/adjustScore');
    
    // 充值规则管理
    Route::get('recharge-rule/list', 'admin.RechargeRule/list');
    Route::post('recharge-rule/list', 'admin.RechargeRule/list');
    Route::post('recharge-rule/save', 'admin.RechargeRule/save');
    Route::post('recharge-rule/delete', 'admin.RechargeRule/delete');
    Route::post('recharge-rule/toggleStatus', 'admin.RechargeRule/toggleStatus');
    
    // 充值订单管理
    Route::get('recharge-order/list', 'admin.RechargeOrder/list');
    Route::post('recharge-order/list', 'admin.RechargeOrder/list');
    
    // 公告管理
    Route::get('notice/list', 'admin.Notice/list');
    Route::get('notice/detail', 'admin.Notice/detail');
    Route::post('notice/add', 'admin.Notice/add');
    Route::post('notice/update', 'admin.Notice/update');
    Route::post('notice/delete', 'admin.Notice/delete');
    
    // 保洁员管理
    Route::get('cleaner/list', 'admin.Cleaner/list');
    Route::get('cleaner/detail', 'admin.Cleaner/detail');
    Route::post('cleaner/add', 'admin.Cleaner/add');
    Route::post('cleaner/update', 'admin.Cleaner/update');
    Route::post('cleaner/delete', 'admin.Cleaner/delete');
    
    // VIP黑名单管理
    Route::get('vip/blacklist', 'admin.Vip/blacklist');
    Route::post('vip/blacklist', 'admin.Vip/blacklist');
    Route::post('vip/addBlacklist', 'admin.Vip/addBlacklist');
    Route::post('vip/removeBlacklist', 'admin.Vip/removeBlacklist');
    
    // 退款管理
    Route::get('refund/list', 'admin.Refund/list');
    Route::get('refund/detail', 'admin.Refund/detail');
    Route::post('refund/approve', 'admin.Refund/approve');
    Route::post('refund/reject', 'admin.Refund/reject');
    
    // 文件上传
    Route::post('upload', 'api.Upload/upload');
    
    // 操作日志管理
    Route::get('adminLog/list', 'admin.AdminLog/list');
    Route::get('adminLog/get', 'admin.AdminLog/get');
    Route::post('adminLog/clean', 'admin.AdminLog/clean');
    Route::get('adminLog/adminList', 'admin.AdminLog/adminList');
    
    // 数据导出
    Route::get('export/orders', 'admin.Export/orders');
    Route::get('export/users', 'admin.Export/users');
    Route::get('export/statistics', 'admin.Export/statistics');
    Route::get('export/reviews', 'admin.Export/reviews');
    
})->middleware([\app\middleware\Tenant::class, \app\middleware\Cors::class, \app\middleware\AdminAuth::class]);
// 使用 AdminAuth 中间件进行后台API认证
