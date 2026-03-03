# 自助棋牌系统 - 完整数据库文档

## 📊 数据库概览

- **数据库名**: smart_store
- **表前缀**: ss_
- **字符集**: utf8mb4
- **表总数**: 55个
- **总记录数**: 565条
- **导出时间**: 2026-02-22 20:39:20

---

## 🏗️ 表结构分类

### 1️⃣ 核心业务表（10个）

#### ss_user - 用户表
```sql
字段:
- id: 用户ID
- tenant_id: 租户ID
- openid: 微信openid
- unionid: 微信unionid
- nickname: 昵称
- avatar: 头像
- password: 密码
- balance: 余额
- gift_balance: 赠送余额
- status: 状态 1正常 0禁用
- store_id: 关联门店ID
- vip_level: VIP等级
- vip_name: VIP名称
- score: 积分
- user_type: 用户类型 0普通 12管理员 13超管 14保洁
- phone: 手机号
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_store - 门店表
```sql
字段:
- id: 门店ID
- tenant_id: 租户ID
- name: 门店名称
- city: 所在城市
- address: 门店地址
- phone: 联系电话
- longitude: 经度
- latitude: 纬度
- images: 门店图片（JSON）
- head_img: 门头照片URL
- banner_img: 轮播广告图,逗号分隔
- wifi_name: WiFi名称
- wifi_password: WiFi密码
- tx_start_hour: 通宵开始小时(18-23)
- tx_hour: 通宵时长(8-12小时)
- show_tx_price: 是否显示通宵价格
- clear_time: 清洁时间(分钟)
- clear_open: 待清洁允许预订
- clear_open_door: 保洁员任意开门
- delay_light: 延时5分钟灯光
- order_door_open: 消费中门禁常开
- order_webhook: 企业微信webhook
- lock_no: 门店大门锁编号
- douyin_id: 抖音门店ID
- sound_enabled: 语音播报开关
- sound_volume: 语音音量
- sound_start_time: 播报开始时间
- sound_end_time: 播报结束时间
- qr_code: 门店小程序码
- dy_id: 抖音ID
- simple_model: 简洁模式
- template_key: 模板key
- btn_img: 立即预约按钮图
- qh_img: 切换门店按钮图
- tg_img: 团购兑换按钮图
- cz_img: 商品点单按钮图
- open_img: 一键开门按钮图
- wifi_img: WIFI信息按钮图
- kf_img: 联系客服按钮图
- env_images: 门店位置指引图(JSON)
- expire_time: 服务到期时间
- business_hours: 营业时间
- description: 门店描述
- status: 状态 1正常 0停用
- create_time: 创建时间
- update_time: 更新时间
- sound_config: 语音配置(JSON)
- qr_config: 二维码配置(JSON)
- group_pay_auth_url: 团购支付授权URL
- notice: 门店公告内容
```

#### ss_room - 房间表
```sql
字段:
- id: 房间ID
- tenant_id: 租户ID
- store_id: 门店ID
- name: 房间名称
- room_no: 房间编号
- type: 房间类型
- price: 价格（元/小时）
- images: 房间图片（JSON）
- facilities: 设施（JSON）
- lock_no: 门锁编号
- device_config: 电器设备配置
- status: 状态 1空闲 2使用中 3维护中 0停用
- sort: 排序
- create_time: 创建时间
- update_time: 更新时间
- room_type: 房间类型编号
- room_class: 房间分类 0棋牌 1台球
- pre_pay_enable: 是否启用预付
- pre_pay_amount: 预付金额
- pre_pay_type: 预付类型 1固定 2百分比
- ball_config: 球类配置(JSON)
- label: 设施标签(逗号分隔)
- morning_price: 上午场价格
- afternoon_price: 下午场价格
- night_price: 夜间场价格
- tx_price: 通宵场价格
- min_hour: 最低消费小时数
- deposit: 押金
- work_price: 工作日价格
- description: 房间描述
```

#### ss_order - 订单表
```sql
字段:
- id: 订单ID
- tenant_id: 租户ID
- order_no: 订单号
- user_id: 用户ID
- store_id: 门店ID
- room_id: 房间ID
- order_type: 订单类型 1小时开台 2团购 3套餐 4押金
- start_time: 开始时间
- end_time: 结束时间
- actual_end_time: 实际结束时间
- duration: 时长（分钟）
- price: 单价
- total_amount: 总金额
- discount_amount: 优惠金额
- vip_discount: VIP折扣值(100=无折扣,95=9.5折)
- vip_discount_amount: VIP折扣金额
- coupon_discount_amount: 优惠券折扣金额
- card_deduct_amount: 会员卡抵扣金额
- user_card_id: 使用的会员卡ID
- pay_amount: 实付金额
- pay_type: 支付方式 1微信 2余额 3易宝
- pay_time: 支付时间
- status: 状态 0待支付 1使用中 2已完成 3已取消 4已退款
- coupon_id: 优惠券ID
- pkg_id: 套餐ID
- group_pay_no: 团购券码
- remark: 备注
- is_reviewed: 是否已评价
- reminded: 是否已提醒
- transaction_id: 交易ID
- actual_amount: 实际金额
- create_time: 创建时间
- update_time: 更新时间
- pay_order_no: 支付单号
- refund_price: 退款金额
- goods_name: 商品名称
- pay_status: 支付状态
- phone: 联系电话
- sound_remind_30: 30分钟语音提醒已发送
- sound_remind_5: 5分钟语音提醒已发送
```

#### ss_device - 设备表
```sql
字段:
- id: 设备ID
- tenant_id: 租户ID
- store_id: 门店ID
- room_id: 房间ID
- device_type: 设备类型 lock/kt/light/camera
- device_no: 设备编号
- device_key: 设备密钥
- device_name: 设备名称
- brand: 品牌
- model: 型号
- firmware_version: 固件版本
- status: 状态 1正常 2故障 0停用
- online_status: 在线状态 0离线 1在线
- last_heartbeat: 最后心跳时间
- signal_strength: 信号强度(dBm)
- battery_level: 电池电量(%)
- bind_status: 绑定状态 0未绑定 1已绑定
- bind_time: 绑定时间
- create_time: 创建时间
- update_time: 更新时间
- device_sn: 设备序列号
- lock_data: 门锁初始化数据
- lock_pwd: 门锁密码
```

#### ss_product - 商品表
```sql
字段:
- id: 商品ID
- tenant_id: 租户ID
- store_id: 门店ID
- name: 商品名称
- category_id: 分类ID
- price: 价格
- image: 图片
- description: 描述
- status: 状态 1上架 0下架
- sort: 排序
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_product_order - 商品订单表
```sql
字段:
- id: 订单ID
- tenant_id: 租户ID
- order_no: 订单号
- user_id: 用户ID
- store_id: 门店ID
- room_id: 房间ID
- product_info: 商品信息JSON
- total_amount: 总金额
- pay_amount: 实付金额
- pay_type: 支付方式
- status: 状态
- mark: 备注
- pay_time: 支付时间
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_coupon - 优惠券表
```sql
字段:
- id: 优惠券ID
- tenant_id: 租户ID
- name: 优惠券名称
- type: 类型 1抵扣券 2满减券 3加时券
- amount: 金额/时长
- min_amount: 最低消费
- total: 发行总量
- received: 已领取数量
- start_time: 开始时间
- end_time: 结束时间
- status: 状态 1正常 0停用
- create_time: 创建时间
- update_time: 更新时间
- store_id: 适用门店ID
- room_class: 房间分类 0=棋牌, 1=台球, 2=KTV
```

#### ss_user_coupon - 用户优惠券表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- user_id: 用户ID
- coupon_id: 优惠券ID
- status: 状态 0未使用 1已使用 2已过期
- use_time: 使用时间
- expire_time: 过期时间
- create_time: 创建时间
```

#### ss_card - 会员卡表
```sql
字段:
- id: 会员卡ID
- tenant_id: 租户ID
- store_id: 适用门店ID，NULL表示全部门店
- name: 会员卡名称
- type: 类型 1次卡 2时长卡 3储值卡
- price: 售价
- value: 面值/次数/时长
- discount: 折扣（0.8=8折）
- valid_days: 有效天数（0=永久）
- description: 说明
- status: 状态 1在售 0停售
- sort: 排序
- create_time: 创建时间
- update_time: 更新时间
```

---

### 2️⃣ 用户相关表（3个）

#### ss_user_card - 用户会员卡表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- user_id: 用户ID
- card_id: 会员卡ID
- store_id: 适用门店ID
- name: 会员卡名称
- type: 类型1次卡2时长卡3储值卡
- total_value: 总值
- remain_value: 剩余值
- discount: 折扣
- pay_amount: 支付金额
- expire_time: 过期时间
- status: 状态1正常0用完2过期
- create_time: 创建时间
```

#### ss_balance_log - 余额记录表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- store_id: 门店ID
- user_id: 用户ID
- type: 类型 1充值 2消费 3退款 4赠送
- amount: 金额
- balance_before: 变动前余额
- balance_after: 变动后余额
- order_id: 关联订单ID
- remark: 备注
- create_time: 创建时间
```

#### ss_review - 评价表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- user_id: 用户ID
- order_id: 订单ID
- store_id: 门店ID
- room_id: 房间ID
- rating: 评分
- content: 内容
- images: 图片(JSON)
- reply: 回复
- create_time: 创建时间
```

---

### 3️⃣ 门店管理表（5个）

#### ss_admin_account - 管理员账号表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- username: 用户名
- password: 密码
- nickname: 昵称
- phone: 手机号
- store_id: 所属门店ID 0为全部
- role: 角色
- permissions: 权限JSON
- status: 状态 1正常 0禁用
- last_login_time: 最后登录时间
- last_login_ip: 最后登录IP
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_admin_log - 管理员操作日志表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- admin_id: 管理员ID
- admin_name: 管理员名称
- module: 模块
- type: 类型
- action: 操作
- target_id: 目标ID
- data: 数据
- ip: IP地址
- user_agent: 用户代理
- create_time: 创建时间
```

#### ss_cleaner - 保洁员表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- store_id: 门店ID
- name: 姓名
- phone: 手机号
- status: 状态 0禁用 1启用
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_clear_task - 保洁任务表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- store_id: 门店ID
- room_id: 房间ID
- order_no: 关联订单号
- user_id: 接单用户ID
- cleaner_id: 保洁员ID
- status: 状态 0待接单 1已接单 2已开始 3已完成 4已取消 5被驳回 6已结算
- order_end_time: 订单结束时间
- take_time: 接单时间
- start_time: 开始时间
- end_time: 完成时间
- settle_amount: 结算金额
- settle_time: 结算时间
- remark: 备注
- create_time: 创建时间
- update_time: 更新时间
- store_name: 门店名称
- room_name: 房间名称
- nickname: 保洁员昵称
- money: 保洁费用
- finish_count: 已完成数
- settlement_count: 已结算数
```

#### ss_config - 系统配置表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- config_key: 配置键
- config_value: 配置值
- description: 说明
- create_time: 创建时间
- update_time: 更新时间
```

---

### 4️⃣ 游戏活动表（2个）

#### ss_game - 拼场活动表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- user_id: 发起人ID
- store_id: 门店ID
- room_id: 房间ID
- name: 活动名称
- type: 类型 0棋牌 1台球 2KTV
- max_players: 最大人数
- current_players: 当前人数
- start_time: 开始时间
- end_time: 结束时间
- status: 状态 0招募中 1已满员 2已支付 3已失效 4已解散
- description: 描述
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_game_user - 游戏参与者表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- game_id: 活动ID
- user_id: 用户ID
- status: 状态 0待支付 1已支付 2已退出
- pay_amount: 支付金额
- pay_time: 支付时间
- create_time: 创建时间
```

---

### 5️⃣ 其他业务表（35个）

#### ss_package - 套餐表
```sql
字段:
- id: 套餐ID
- tenant_id: 租户ID
- store_id: 门店ID
- name: 套餐名称
- type: 业态 0棋牌 1台球 2KTV
- price: 价格
- original_price: 原价
- duration: 时长
- description: 描述
- status: 状态 1上架 0下架
- sort: 排序
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_feedback - 用户反馈表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- user_id: 用户ID
- type: 类型 1建议 2投诉 3咨询 4其他
- content: 内容
- images: 图片(JSON)
- status: 状态 0待处理 1处理中 2已处理
- reply: 回复
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_refund - 退款表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- order_id: 订单ID
- user_id: 用户ID
- amount: 退款金额
- reason: 退款原因
- status: 状态 0待审核 1已通过 2已拒绝
- refund_time: 退款时间
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_recharge_order - 充值订单表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- order_no: 订单号
- user_id: 用户ID
- amount: 充值金额
- pay_amount: 实付金额
- pay_type: 支付方式
- status: 状态 0待支付 1已支付 2已取消
- pay_time: 支付时间
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_vip_config - VIP配置表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- level: 等级
- name: 名称
- discount: 折扣
- description: 描述
- status: 状态
- create_time: 创建时间
- update_time: 更新时间
```

#### ss_vip_blacklist - VIP黑名单表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- user_id: 用户ID
- reason: 原因
- status: 状态
- create_time: 创建时间
```

#### ss_discount_rule - 折扣规则表
```sql
字段:
- id: ID
- tenant_id: 租户ID
- store_id: 门店ID
- name: 规则名称
- type: 类型
- discount: 折扣
- start_time: 开始时间
- end_time: 结束时间
- status: 状态
- create_time: 创建时间
- update_time: 更新时间
```

#### 其他表（29个）:
- ss_admin - 旧管理员表
- ss_banner - 轮播图表
- ss_card_log - 会员卡日志表
- ss_card_order - 会员卡购买订单表
- ss_coupon_active - 优惠券活动表
- ss_delay_task - 延时任务表
- ss_device_command_log - 设备指令日志表
- ss_device_log - 设备日志表
- ss_device_registry - 设备注册表
- ss_dict_data - 字典数据表
- ss_door_log - 门禁日志表
- ss_face_blacklist - 人脸黑名单表
- ss_face_record - 人脸记录表
- ss_franchise_apply - 加盟申请表
- ss_game_message - 游戏消息表
- ss_gift_balance - 礼品余额表
- ss_group_coupon - 团购优惠券表
- ss_group_pay_log - 团购支付日志表
- ss_group_verify_log - 团购验证日志表
- ss_help - 帮助文档表
- ss_money_bill - 余额账单表
- ss_notice - 公告表
- ss_product_category - 商品分类表
- ss_push_rule - 推送规则表
- ss_recharge_package - 充值套餐表
- ss_store_template - 门店模板表
- ss_store_user - 门店用户表
- product_order - 商品订单表（无前缀）

---

## 📈 数据统计

| 表名 | 记录数 | 说明 |
|------|--------|------|
| ss_user | 16 | 用户 |
| ss_store | 1 | 门店 |
| ss_room | 1 | 房间 |
| ss_order | 31 | 订单 |
| ss_device | 1 | 设备 |
| ss_admin_account | 1 | 管理员 |
| ss_config | 12 | 配置 |
| ss_feedback | 84 | 反馈 |
| ss_card | 130 | 会员卡 |
| ss_door_log | 19 | 门禁日志 |
| ss_device_command_log | 20 | 设备命令日志 |
| ss_device_log | 18 | 设备日志 |
| ss_franchise_apply | 72 | 加盟申请 |
| ss_dict_data | 42 | 字典数据 |
| ... | ... | 其他 |
| **总计** | **565** | **所有记录** |

---

## 🔍 表关系图

```
ss_user (用户)
  ├── ss_order (订单) [1:N]
  ├── ss_user_card (用户会员卡) [1:N]
  ├── ss_user_coupon (用户优惠券) [1:N]
  ├── ss_balance_log (余额记录) [1:N]
  ├── ss_review (评价) [1:N]
  ├── ss_game (游戏) [1:N]
  └── ss_feedback (反馈) [1:N]

ss_store (门店)
  ├── ss_room (房间) [1:N]
  ├── ss_device (设备) [1:N]
  ├── ss_order (订单) [1:N]
  ├── ss_product (商品) [1:N]
  ├── ss_cleaner (保洁员) [1:N]
  └── ss_clear_task (保洁任务) [1:N]

ss_room (房间)
  ├── ss_device (设备) [1:N]
  ├── ss_order (订单) [1:N]
  └── ss_clear_task (保洁任务) [1:N]

ss_order (订单)
  ├── ss_user (用户) [N:1]
  ├── ss_store (门店) [N:1]
  ├── ss_room (房间) [N:1]
  ├── ss_coupon (优惠券) [N:1]
  ├── ss_product_order (商品订单) [1:N]
  └── ss_review (评价) [1:1]
```

---

## ✅ 数据库完整性检查

### 存在的核心表（25个）✅
- ss_user, ss_store, ss_room, ss_order, ss_device
- ss_config, ss_coupon, ss_user_coupon, ss_card, ss_user_card
- ss_product, ss_product_order, ss_package, ss_game, ss_game_user
- ss_review, ss_feedback, ss_admin_account, ss_balance_log
- ss_recharge_order, ss_refund, ss_cleaner, ss_vip_config
- ss_vip_blacklist, ss_discount_rule

### 缺少的表（5个）❌
- ss_operation_log - 操作日志表
- ss_recharge_rule - 充值规则表
- ss_franchise - 加盟表
- ss_clean_task - 保洁任务表（实际是ss_clear_task）
- ss_system_config - 系统配置表（实际是ss_config）

### 多余的表（30个）⚠️
- product_order, ss_admin, ss_admin_log, ss_banner
- ss_card_log, ss_card_order, ss_clear_task, ss_coupon_active
- ss_delay_task, ss_device_command_log, ss_device_log, ss_device_registry
- ss_dict_data, ss_door_log, ss_face_blacklist, ss_face_record
- ss_franchise_apply, ss_gift_balance, ss_group_coupon
- ss_group_pay_log, ss_group_verify_log, ss_help, ss_money_bill
- ss_notice, ss_product_category, ss_push_rule, ss_recharge_package
- ss_store_template, ss_store_user

---

## 📝 备注

1. **表前缀**: 所有表使用 `ss_` 前缀（除了product_order）
2. **租户隔离**: 所有表都有 `tenant_id` 字段，支持多租户
3. **时间戳**: 大部分表都有 `create_time` 和 `update_time` 字段
4. **软删除**: 使用 `status` 字段标记删除状态
5. **索引优化**: 关键字段都建立了索引

---

## 🎯 总结

数据库结构完整，核心业务表齐全，可以支持系统正常运行。缺少的5个表不影响核心功能，多余的30个表可能是旧版本遗留或未使用的表。

**建议**:
- ✅ 可以正常使用
- ⚠️ 定期清理多余表
- 🔧 需要时再创建缺失表
