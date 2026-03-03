# 自助棋牌

基于 uni-app 开发的自助棋牌智能门店管理系统，支持微信小程序、H5、App 多端运行。

## 功能模块

- 门店浏览与定位导航
- 房间预订与在线支付（微信支付 / 余额支付）
- 拼场组局
- 优惠券领取与使用
- 余额充值
- 订单管理（下单、续费、换房、退款）
- 商品购买
- 智能门锁开门
- 管理端：门店管理、房间控制、订单管理、用户管理、数据统计、保洁任务

## 技术栈

- 前端：uni-app (Vue 2) + uView UI
- 后端：ThinkPHP 6.x + MySQL + Redis
- 管理后台：PHP + Bootstrap 5

## 快速开始

### 前端

```bash
# 安装依赖
npm install

# 微信小程序开发
npm run dev:mp-weixin

# H5 开发
npm run dev:h5
```

使用 HBuilderX 打开项目可直接运行到各平台。

### 后端

```bash
cd backend-thinkphp
composer install
cp .env.example .env
# 编辑 .env 配置数据库信息
php think run -p 8000
```

详细后端文档见 [backend-thinkphp/README.md](./backend-thinkphp/README.md)

## 配置说明

部署前需要配置以下内容：

| 配置项 | 文件 | 说明 |
|--------|------|------|
| API 地址 | `config/index.js` | 生产环境 API 域名 |
| 微信 AppID | `manifest.json` | mp-weixin.appid |
| 腾讯地图 Key | `manifest.json` | h5.sdkConfigs.maps.qqmap.key |
| 高德地图 Key | `components/amap-wx/js/util.js` | 高德 Web 服务 Key |
| 租户 ID | `config/index.js` | tenantId |
| 客服电话 | `config/index.js` | servicePhone |

## 项目结构

```
├── api/                # API 接口定义
├── components/         # 公共组件
├── config/             # 全局配置
├── pages/              # 主包页面
├── pagesA/             # 分包页面（管理端）
├── static/             # 静态资源
├── store/              # Vuex 状态管理
├── utils/              # 工具函数与 HTTP 封装
├── backend-thinkphp/   # 后端项目
└── uni_modules/        # uni-app 插件
```


联系QQ：2189969140
