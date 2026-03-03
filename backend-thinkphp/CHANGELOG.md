# 更新日志

## v1.2.0 - 生产环境改进

### 安全性改进 🔒

#### 1. 接口限流
新增 `RateLimit` 中间件，防止恶意请求：
- 默认每分钟60次请求
- 登录接口：每分钟10次
- 发送验证码：每分钟5次
- 支付接口：每分钟10次

#### 2. 全局异常处理
新增 `app/exception/Handler.php`：
- 统一异常响应格式
- 敏感信息脱敏
- 区分调试/生产模式
- 详细错误日志记录

#### 3. 数据验证器
- `OrderValidate.php` - 订单数据验证
- `UserValidate.php` - 用户数据验证

#### 4. 安全服务
新增 `SecurityService.php`：
- AES加密/解密
- 签名生成/验证
- 密码哈希
- 数据脱敏（手机号、身份证、银行卡）
- SQL注入检测
- XSS过滤
- 登录尝试限制

### 性能优化 ⚡

#### 1. 缓存服务
新增 `CacheService.php`：
- 门店/房间/用户信息缓存
- 系统配置缓存
- 分布式锁
- 幂等性检查

#### 2. 数据库索引
新增 `database/migrations/001_add_indexes.sql`：
- 订单表复合索引
- 用户表手机号索引
- 房间表门店+状态索引
- 优惠券有效期索引

### 可靠性改进 🛡️

#### 1. 幂等性服务
新增 `IdempotentService.php`：
- 订单创建防重复
- 支付防重复
- 回调防重复处理

#### 2. 日志服务
新增 `LogService.php`：
- 统一日志格式
- 敏感信息脱敏
- 请求链路追踪
- 业务/支付/安全/错误分类

#### 3. 请求日志中间件
新增 `RequestLog` 中间件：
- 记录所有API请求
- 响应时间统计
- 请求ID追踪

### 运维相关 🔧

#### 1. 健康检查增强
新增 `Health` 控制器：
- `GET /health` - 基础检查
- `GET /health/detail` - 详细检查（数据库、缓存、磁盘）
- `GET /health/ready` - K8s就绪探针
- `GET /health/live` - K8s存活探针

#### 2. 审计日志表
新增 `database/migrations/002_add_audit_fields.sql`：
- 操作日志表 `ss_operation_log`
- 设备日志表 `ss_device_log`
- 支付日志表 `ss_payment_log`

#### 3. 配置增强
更新 `.env.example`：
- APP_KEY 应用密钥
- 日志级别配置
- 限流开关配置

### 文件变更清单

新增文件：
- `app/middleware/RateLimit.php` - 限流中间件
- `app/middleware/RequestLog.php` - 请求日志中间件
- `app/exception/Handler.php` - 全局异常处理
- `app/validate/OrderValidate.php` - 订单验证器
- `app/validate/UserValidate.php` - 用户验证器
- `app/service/CacheService.php` - 缓存服务
- `app/service/LogService.php` - 日志服务
- `app/service/IdempotentService.php` - 幂等性服务
- `app/service/SecurityService.php` - 安全服务
- `app/controller/api/Health.php` - 健康检查
- `database/migrations/001_add_indexes.sql` - 索引优化
- `database/migrations/002_add_audit_fields.sql` - 审计字段

修改文件：
- `config/app.php` - 添加异常处理器
- `config/middleware.php` - 添加全局中间件
- `route/api.php` - 添加健康检查路由
- `.env.example` - 添加新配置项

---

## v1.1.0 - 代码重构与安全增强

### 后端改进

#### 1. 控制器拆分
将 `Order.php` (1860行) 的业务逻辑拆分到服务层：

- `app/service/OrderService.php` - 订单业务逻辑
  - `checkTimeConflict()` - 检查房间时间冲突
  - `calculateAmount()` - 计算订单金额
  - `applyCoupon()` - 处理优惠券
  - `deductBalance()` - 余额扣款
  - `generateOrderNo()` - 生成订单号
  - `formatOrderDetail()` - 格式化订单详情

- `app/service/DeviceService.php` - 设备控制逻辑
  - `openRoomDoor()` - 开房间门
  - `openStoreDoor()` - 开门店大门
  - `getLockPassword()` - 获取门锁密码
  - `controlAirConditioner()` - 控制空调

#### 2. 后台认证中间件
为管理后台API添加认证保护：

```php
// route/admin.php
// 登录接口（无需认证）
Route::group('app-api/admin/auth', function () {
    Route::post('login', 'admin.AdminAccount/login');
    Route::post('logout', 'admin.AdminAccount/logout');
})->middleware([Tenant::class, Cors::class]);

// 其他接口（需要认证）
Route::group('app-api/admin', function () {
    // ...
})->middleware([Tenant::class, Cors::class, AdminAuth::class]);
```

新增登录/退出接口：
- `POST /app-api/admin/auth/login` - 管理员登录
- `POST /app-api/admin/auth/logout` - 管理员退出

#### 3. 单元测试
新增测试文件：

- `tests/OrderServiceTest.php` - 订单服务测试
- `tests/AdminAuthTest.php` - 认证测试
- `tests/DeviceServiceTest.php` - 设备服务测试

运行测试：
```bash
cd backend-thinkphp
composer install
./vendor/bin/phpunit
```

### 前端改进

#### 1. 组件拆分
从首页拆分出可复用组件：

- `components/banner-swiper/banner-swiper.vue` - 轮播图组件
- `components/quick-actions/quick-actions.vue` - 快捷操作栏组件
- `components/svg-seat-map/svg-seat-map.vue` - SVG座位图组件（KTV）

#### 2. Mixin 拆分
将首页复杂逻辑拆分到 mixins：

- `mixins/storeMixin.js` - 门店相关逻辑
  - 获取定位
  - 获取门店信息
  - 获取房间列表
  - 导航、客服、WiFi操作

- `mixins/orderMixin.js` - 订单相关逻辑
  - 开门操作
  - 续费订单
  - 跳转下单
  - 图片预览

### 使用示例

#### 后端服务调用
```php
use app\service\OrderService;
use app\service\DeviceService;

// 检查时间冲突
$error = OrderService::checkTimeConflict($roomId, $startTime, $endTime);
if ($error) {
    return error($error);
}

// 计算金额
$amount = OrderService::calculateAmount($room, $startTime, $endTime);

// 开门
$result = DeviceService::openRoomDoor($roomId, $tenantId);
```

#### 前端组件使用
```vue
<template>
  <banner-swiper :images="bannerImages" @tap="onBannerTap" />
  <quick-actions :actions="actions" @action="onAction" />
</template>

<script>
import BannerSwiper from '@/components/banner-swiper/banner-swiper.vue'
import QuickActions from '@/components/quick-actions/quick-actions.vue'
import storeMixin from '@/mixins/storeMixin.js'
import orderMixin from '@/mixins/orderMixin.js'

export default {
  components: { BannerSwiper, QuickActions },
  mixins: [storeMixin, orderMixin]
}
</script>
```

### 文件变更清单

新增文件：
- `backend-thinkphp/app/service/OrderService.php`
- `backend-thinkphp/app/service/DeviceService.php`
- `backend-thinkphp/tests/OrderServiceTest.php`
- `backend-thinkphp/tests/AdminAuthTest.php`
- `backend-thinkphp/tests/DeviceServiceTest.php`
- `backend-thinkphp/phpunit.xml`
- `components/banner-swiper/banner-swiper.vue`
- `components/quick-actions/quick-actions.vue`
- `components/svg-seat-map/svg-seat-map.vue`
- `mixins/storeMixin.js`
- `mixins/orderMixin.js`

修改文件：
- `backend-thinkphp/route/admin.php` - 添加认证中间件
- `backend-thinkphp/app/controller/admin/AdminAccount.php` - 添加登录/退出方法
- `backend-thinkphp/composer.json` - 添加 phpunit 依赖
