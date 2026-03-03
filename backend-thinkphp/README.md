# 自助棋牌后端 - ThinkPHP 6.x

## 项目说明

基于 ThinkPHP 6.1 开发的自助棋牌管理系统后端API

**✅ 项目状态：已完成**

### 已实现功能

- ✅ 用户系统（登录、信息管理）
- ✅ 门店管理（门店列表、详情、房间管理）
- ✅ 订单系统（创建、支付、续费、取消、换房）
- ✅ 支付系统（微信支付、余额支付）
- ✅ 优惠券系统（领取、使用、查询）
- ✅ 余额系统（充值、消费、记录）
- ✅ 文件上传
- ✅ 支付回调处理
- ✅ 智能门锁开门接口
- ✅ PHP管理后台界面
- ✅ 完整的API文档

## 技术栈

- ThinkPHP 6.1+
- MySQL 8.0
- Redis 6.0+
- PHP 7.2+
- Composer

## 快速开始

### 1. 安装项目

```bash
# 使用 Composer 创建项目
composer create-project topthink/think smart-store

# 进入项目目录
cd smart-store

# 复制本项目文件到 smart-store 目录
```

### 2. 配置环境

复制 `.env.example` 为 `.env`：

```bash
cp .env.example .env
```

修改 `.env` 配置：

```ini
APP_DEBUG = true

[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = smart_store
USERNAME = root
PASSWORD = your_password
HOSTPORT = 3306
CHARSET = utf8mb4
PREFIX = ss_

[REDIS]
HOST = 127.0.0.1
PORT = 6379
PASSWORD = 
SELECT = 0
```

### 3. 创建数据库

```sql
CREATE DATABASE `smart_store` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. 导入数据表

```bash
mysql -u root -p smart_store < database/smart_store.sql
```

### 5. 启动服务

```bash
# 开发环境
php think run -p 8000

# 访问
http://localhost:8000
```

### 6. 测试接口

```bash
# 测试门店列表
curl http://localhost:8000/app-api/store/store/page

# 测试登录
curl -X POST http://localhost:8000/app-api/member/user/login \
  -H "Content-Type: application/json" \
  -d '{"code":"test_code"}'
```

## 项目结构

```
smart-store/
├── app/
│   ├── controller/
│   │   └── api/              # API控制器
│   ├── model/                # 数据模型
│   ├── middleware/           # 中间件
│   ├── service/              # 业务逻辑
│   ├── validate/             # 验证器
│   └── common.php            # 公共函数
├── config/                   # 配置文件
├── route/                    # 路由
├── database/                 # 数据库文件
├── public/                   # 入口文件
└── .env                      # 环境配置
```

## API 文档

详细的API测试文档请查看：[API测试文档.md](./API测试文档.md)

### 基础信息

- 基础URL: `http://localhost:8000/app-api`
- 管理后台: `http://localhost:8000/admin/login.php` (首次使用请通过数据库创建管理员账号)
- 请求头:
  - `Content-Type: application/json`
  - `Authorization: Bearer {token}` (需要登录的接口)
  - `tenant-id: 150` (租户ID)

### 核心接口列表

#### 用户相关 (/member/user/)

- `POST /login` - 用户登录
- `GET /info` - 获取用户信息
- `POST /update` - 更新用户信息

#### 订单相关 (/member/order/)

- `POST /save` - 创建订单
- `POST /getOrderList` - 获取订单列表
- `GET /getOrderInfo/:id` - 获取订单详情
- `GET /getOrderInfoByNo` - 根据订单号获取订单
- `POST /pay` - 订单支付（支持微信、余额）
- `POST /renew` - 订单续费
- `POST /cancel` - 取消订单
- `POST /changeRoom` - 更换房间
- `POST /openRoomDoor` - 开门
- `GET /search` - 搜索订单
- `POST /preOrder` - 预下单（计算价格）
- `POST /lockWxOrder` - 锁定订单

#### 门店相关 (/store/)

- `GET /store/page` - 获取门店列表
- `GET /store/get` - 获取门店详情
- `GET /room/list` - 获取房间列表
- `GET /room/get` - 获取房间详情
- `POST /room/check` - 检查房间可用性
- `GET /banner/list` - 获取轮播图

#### 余额相关 (/member/balance/)

- `GET /info` - 获取余额信息
- `POST /recharge` - 余额充值

#### 优惠券相关 (/member/coupon/)

- `GET /list` - 获取优惠券列表
- `POST /receive` - 领取优惠券（路径：/member/couponActive/putCoupon）
- `GET /available` - 获取可用优惠券

#### 文件上传

- `POST /infra/file/upload` - 上传文件

#### 支付回调

- `POST /pay/wechat/callback` - 微信支付回调

## 部署

### Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/smart-store/public;
    index index.php;

    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=$1 last;
        }
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 常见问题

### 1. 跨域问题

已配置 CORS 中间件，支持跨域请求

### 2. Token 过期

Token 默认 7 天过期，可在 `app/middleware/Auth.php` 中修改

### 3. 多租户

通过 `tenant-id` 请求头实现租户隔离

## 开发指南

### 创建新控制器

```bash
php think make:controller api/YourController
```

### 创建新模型

```bash
php think make:model YourModel
```

### 清除缓存

```bash
php think clear
```

## License

MIT
