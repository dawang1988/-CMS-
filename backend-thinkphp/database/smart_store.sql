-- 自助棋牌室数据库初始化脚本
-- 基于模型文件生成的完整表结构
-- MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. 用户表 ss_user
-- ----------------------------
DROP TABLE IF EXISTS `ss_user`;
CREATE TABLE `ss_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信openid',
  `unionid` varchar(100) DEFAULT NULL COMMENT '微信unionid',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `password` varchar(255) DEFAULT NULL COMMENT '密码',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ----------------------------
-- 2. 门店表 ss_store
-- ----------------------------
DROP TABLE IF EXISTS `ss_store`;
CREATE TABLE `ss_store` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '门店ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `name` varchar(100) NOT NULL COMMENT '门店名称',
  `city` varchar(50) DEFAULT NULL COMMENT '所在城市',
  `address` varchar(255) DEFAULT NULL COMMENT '门店地址',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `longitude` decimal(10,6) DEFAULT NULL COMMENT '经度',
  `latitude` decimal(10,6) DEFAULT NULL COMMENT '纬度',
  `images` text COMMENT '门店图片JSON',
  `head_img` varchar(500) DEFAULT NULL COMMENT '门头照片URL',
  `banner_img` text COMMENT '轮播广告图(逗号分隔)',
  `wifi_name` varchar(100) DEFAULT NULL COMMENT 'WiFi名称',
  `wifi_password` varchar(100) DEFAULT NULL COMMENT 'WiFi密码',
  `tx_start_hour` int(11) DEFAULT '23' COMMENT '通宵开始小时(18-23)',
  `tx_hour` int(11) DEFAULT '8' COMMENT '通宵时长(8-12小时)',
  `show_tx_price` tinyint(1) DEFAULT '1' COMMENT '是否显示通宵价格',
  `clear_time` int(11) DEFAULT '5' COMMENT '清洁时间(分钟)',
  `clear_open` tinyint(1) DEFAULT '1' COMMENT '待清洁允许预订',
  `clear_open_door` tinyint(1) DEFAULT '0' COMMENT '保洁员任意开门',
  `delay_light` tinyint(1) DEFAULT '0' COMMENT '延时5分钟灯光',
  `order_door_open` tinyint(1) DEFAULT '0' COMMENT '消费中门禁常开',
  `order_webhook` varchar(255) DEFAULT NULL COMMENT '企业微信webhook',
  `lock_no` varchar(100) DEFAULT NULL COMMENT '门店大门锁编号',
  `douyin_id` varchar(100) DEFAULT NULL COMMENT '抖音门店ID',
  `sound_enabled` tinyint(1) DEFAULT '0' COMMENT '语音播报开关',
  `sound_volume` int(11) DEFAULT '50' COMMENT '语音音量',
  `sound_start_time` varchar(10) DEFAULT '08:00' COMMENT '播报开始时间',
  `sound_end_time` varchar(10) DEFAULT '22:00' COMMENT '播报结束时间',
  `sound_config` text COMMENT '语音配置(JSON)',
  `qr_code` varchar(255) DEFAULT NULL COMMENT '门店小程序码',
  `qr_config` text COMMENT '二维码配置(JSON)',
  `simple_model` tinyint(1) DEFAULT '1' COMMENT '简洁模式',
  `template_key` varchar(50) DEFAULT NULL COMMENT '模板key',
  `btn_img` varchar(255) DEFAULT NULL COMMENT '立即预约按钮图',
  `qh_img` varchar(255) DEFAULT NULL COMMENT '切换门店按钮图',
  `tg_img` varchar(255) DEFAULT NULL COMMENT '团购兑换按钮图',
  `cz_img` varchar(255) DEFAULT NULL COMMENT '商品点单按钮图',
  `open_img` varchar(255) DEFAULT NULL COMMENT '一键开门按钮图',
  `wifi_img` varchar(255) DEFAULT NULL COMMENT 'WIFI信息按钮图',
  `kf_img` varchar(255) DEFAULT NULL COMMENT '联系客服按钮图',
  `env_images` text COMMENT '门店位置指引图(JSON)',
  `expire_time` datetime DEFAULT NULL COMMENT '服务到期时间',
  `business_hours` varchar(100) DEFAULT NULL COMMENT '营业时间',
  `description` text COMMENT '门店描述',
  `notice` text COMMENT '门店公告内容',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0停用',
  `card_enabled` tinyint(1) DEFAULT '0' COMMENT '会员卡功能开关 0关闭 1开启',
  `group_pay_auth_url` varchar(500) DEFAULT NULL COMMENT '团购支付授权URL',
  `meituan_auth` tinyint(1) DEFAULT '0' COMMENT '美团授权状态 0未授权 1已授权',
  `meituan_expire` datetime DEFAULT NULL COMMENT '美团授权到期时间',
  `meituan_shop_id` varchar(64) DEFAULT NULL COMMENT '美团商户ID',
  `meituan_access_token` varchar(512) DEFAULT NULL COMMENT '美团access_token',
  `meituan_refresh_token` varchar(512) DEFAULT NULL COMMENT '美团refresh_token',
  `douyin_auth` tinyint(1) DEFAULT '0' COMMENT '抖音授权状态 0未授权 1已授权',
  `douyin_expire` datetime DEFAULT NULL COMMENT '抖音授权到期时间',
  `douyin_access_token` varchar(512) DEFAULT NULL COMMENT '抖音access_token',
  `douyin_refresh_token` varchar(512) DEFAULT NULL COMMENT '抖音refresh_token',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门店表';

-- ----------------------------
-- 3. 房间表 ss_room
-- ----------------------------
DROP TABLE IF EXISTS `ss_room`;
CREATE TABLE `ss_room` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '房间ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `name` varchar(100) NOT NULL COMMENT '房间名称',
  `room_no` varchar(50) DEFAULT NULL COMMENT '房间编号',
  `type` varchar(50) DEFAULT NULL COMMENT '房间类型',
  `room_type` tinyint(1) DEFAULT '1' COMMENT '房间类型编号',
  `room_class` tinyint(1) DEFAULT '0' COMMENT '房间分类 0棋牌 1台球 2KTV',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格(元/小时)',
  `work_price` decimal(10,2) DEFAULT '0.00' COMMENT '工作日价格',
  `morning_price` decimal(10,2) DEFAULT '0.00' COMMENT '上午场价格',
  `afternoon_price` decimal(10,2) DEFAULT '0.00' COMMENT '下午场价格',
  `night_price` decimal(10,2) DEFAULT '0.00' COMMENT '夜间场价格',
  `tx_price` decimal(10,2) DEFAULT '0.00' COMMENT '通宵场价格',
  `deposit` decimal(10,2) DEFAULT '0.00' COMMENT '押金',
  `pre_pay_enable` tinyint(1) DEFAULT '0' COMMENT '是否启用预付',
  `pre_pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '预付金额',
  `pre_pay_type` tinyint(1) DEFAULT '1' COMMENT '预付类型 1固定 2百分比',
  `min_hour` int(11) DEFAULT '1' COMMENT '最低消费小时数',
  `images` text COMMENT '房间图片JSON',
  `facilities` text COMMENT '设施JSON',
  `label` varchar(500) DEFAULT '' COMMENT '设施标签(逗号分隔)',
  `lock_no` varchar(100) DEFAULT NULL COMMENT '门锁编号',
  `device_config` text COMMENT '电器设备配置(JSON)',
  `ball_config` text COMMENT '球类配置(JSON)',
  `description` text COMMENT '房间描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1空闲 2使用中 3维护中 4待清洁 0停用',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_room_class` (`room_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='房间表';

-- ----------------------------
-- 4. 订单表 ss_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_order`;
CREATE TABLE `ss_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `room_id` int(11) NOT NULL COMMENT '房间ID',
  `order_type` tinyint(1) DEFAULT '1' COMMENT '订单类型 1小时开台 2团购 3套餐 4押金',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `actual_end_time` datetime DEFAULT NULL COMMENT '实际结束时间',
  `duration` int(11) DEFAULT '0' COMMENT '时长(分钟)',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '单价',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '总金额',
  `discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠金额',
  `vip_discount` int(11) DEFAULT '100' COMMENT 'VIP折扣值 100=无折扣 95=9.5折',
  `vip_discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'VIP折扣金额',
  `coupon_discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠券折扣金额',
  `card_deduct_amount` decimal(10,2) DEFAULT '0.00' COMMENT '会员卡抵扣金额',
  `user_card_id` int(11) DEFAULT NULL COMMENT '使用的会员卡ID',
  `pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '实付金额',
  `actual_amount` decimal(10,2) DEFAULT NULL COMMENT '实际金额',
  `refund_price` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `pay_type` tinyint(1) DEFAULT NULL COMMENT '支付方式 1微信 2余额 3易宝',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `pay_status` tinyint(1) DEFAULT '0' COMMENT '支付状态',
  `pay_order_no` varchar(50) DEFAULT NULL COMMENT '支付单号',
  `transaction_id` varchar(64) DEFAULT NULL COMMENT '交易流水号',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待支付 1使用中 2已完成 3已取消 4已退款',
  `coupon_id` int(11) DEFAULT NULL COMMENT '优惠券ID',
  `pkg_id` int(11) DEFAULT NULL COMMENT '套餐ID',
  `group_pay_no` varchar(50) DEFAULT NULL COMMENT '团购券码',
  `goods_name` varchar(100) DEFAULT NULL COMMENT '商品名称',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `is_reviewed` tinyint(1) DEFAULT '0' COMMENT '是否已评价',
  `reminded` tinyint(1) DEFAULT '0' COMMENT '是否已提醒',
  `sound_remind_30` tinyint(1) DEFAULT '0' COMMENT '30分钟语音提醒已发送',
  `sound_remind_5` tinyint(1) DEFAULT '0' COMMENT '5分钟语音提醒已发送',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_user_card_id` (`user_card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- ----------------------------
-- 5. 优惠券表 ss_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ss_coupon`;
CREATE TABLE `ss_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '优惠券ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `name` varchar(100) NOT NULL COMMENT '优惠券名称',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1抵扣券 2满减券 3加时券',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '金额或时长',
  `min_amount` decimal(10,2) DEFAULT '0.00' COMMENT '最低消费',
  `total` int(11) DEFAULT '0' COMMENT '发行总量',
  `received` int(11) DEFAULT '0' COMMENT '已领取数量',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0停用',
  `store_id` int(11) DEFAULT NULL COMMENT '适用门店ID，NULL表示不限',
  `room_class` tinyint(1) DEFAULT NULL COMMENT '适用业态 0棋牌 1台球 2KTV，NULL表示不限',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_room_class` (`room_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优惠券表';

-- ----------------------------
-- 6. 用户优惠券表 ss_user_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ss_user_coupon`;
CREATE TABLE `ss_user_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未使用 1已使用 2已过期',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `order_id` int(11) DEFAULT NULL COMMENT '订单ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户优惠券表';

-- ----------------------------
-- 7. 余额记录表 ss_balance_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_balance_log`;
CREATE TABLE `ss_balance_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT 0 COMMENT '门店ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1充值 2消费 3退款 4赠送',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '金额',
  `balance_before` decimal(10,2) DEFAULT '0.00' COMMENT '变动前余额',
  `balance_after` decimal(10,2) DEFAULT '0.00' COMMENT '变动后余额',
  `order_id` int(11) DEFAULT NULL COMMENT '关联订单ID',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='余额记录表';

-- ----------------------------
-- 8. 轮播图表 ss_banner
-- ----------------------------
DROP TABLE IF EXISTS `ss_banner`;
CREATE TABLE `ss_banner` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `title` varchar(100) DEFAULT NULL COMMENT '标题',
  `image` varchar(255) NOT NULL COMMENT '图片',
  `link` varchar(255) DEFAULT NULL COMMENT '链接',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='轮播图表';

-- ----------------------------
-- 9. 会员卡表 ss_card
-- ----------------------------
DROP TABLE IF EXISTS `ss_card`;
CREATE TABLE `ss_card` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '会员卡ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '适用门店ID，NULL为全部门店',
  `name` varchar(100) NOT NULL COMMENT '会员卡名称',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1次卡 2时长卡 3储值卡',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '购买价格',
  `value` decimal(10,2) DEFAULT '0.00' COMMENT '卡面值(次数/分钟/金额)',
  `discount` decimal(3,2) DEFAULT '1.00' COMMENT '折扣比例',
  `valid_days` int(11) DEFAULT '0' COMMENT '有效天数，0为永久',
  `description` text COMMENT '权益说明',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 0禁用 1启用',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员卡表';

-- ----------------------------
-- 10. 用户会员卡表 ss_user_card
-- ----------------------------
DROP TABLE IF EXISTS `ss_user_card`;
CREATE TABLE `ss_user_card` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `card_id` int(11) NOT NULL COMMENT '会员卡类型ID',
  `store_id` int(11) DEFAULT NULL COMMENT '适用门店ID',
  `name` varchar(100) DEFAULT NULL COMMENT '会员卡名称',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1次卡 2时长卡 3储值卡',
  `card_no` varchar(50) DEFAULT NULL COMMENT '会员卡号',
  `total_value` decimal(10,2) DEFAULT '0.00' COMMENT '总面值',
  `remain_value` decimal(10,2) DEFAULT '0.00' COMMENT '剩余面值',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '卡内余额(兼容字段)',
  `discount` decimal(3,2) DEFAULT '1.00' COMMENT '折扣比例',
  `pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '购买金额',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 0冻结 1正常 2已用完 3已过期',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  `buy_time` datetime DEFAULT NULL COMMENT '购买时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_card_id` (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户会员卡表';

-- ----------------------------
-- 11. 系统配置表 ss_config
-- ----------------------------
DROP TABLE IF EXISTS `ss_config`;
CREATE TABLE `ss_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `description` varchar(255) DEFAULT NULL COMMENT '配置说明',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  UNIQUE KEY `uk_tenant_key` (`tenant_id`, `config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ----------------------------
-- 12. 设备表 ss_device
-- ----------------------------
DROP TABLE IF EXISTS `ss_device`;
CREATE TABLE `ss_device` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '设备ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `room_id` int(11) DEFAULT NULL COMMENT '房间ID',
  `device_no` varchar(100) NOT NULL COMMENT '设备编号',
  `device_name` varchar(100) DEFAULT NULL COMMENT '设备名称',
  `device_type` tinyint(1) DEFAULT '1' COMMENT '设备类型 1门锁 2灯光 3空调 4电视 5排风',
  `mqtt_topic` varchar(255) DEFAULT NULL COMMENT 'MQTT主题',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0离线 1在线 2故障',
  `power_status` tinyint(1) DEFAULT '0' COMMENT '电源状态 0关 1开',
  `last_online_time` datetime DEFAULT NULL COMMENT '最后在线时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_room_id` (`room_id`),
  UNIQUE KEY `uk_device_no` (`device_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备表';

-- ----------------------------
-- 13. 字典数据表 ss_dict_data
-- ----------------------------
DROP TABLE IF EXISTS `ss_dict_data`;
CREATE TABLE `ss_dict_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `dict_type` varchar(100) NOT NULL COMMENT '字典类型',
  `dict_label` varchar(100) NOT NULL COMMENT '字典标签',
  `dict_value` varchar(100) NOT NULL COMMENT '字典值',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 0禁用 1启用',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_dict_type` (`dict_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='字典数据表';

-- ----------------------------
-- 14. 用户反馈表 ss_feedback
-- ----------------------------
DROP TABLE IF EXISTS `ss_feedback`;
CREATE TABLE `ss_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1建议 2投诉 3咨询 4其他',
  `content` text COMMENT '反馈内容',
  `images` text COMMENT '图片JSON',
  `contact` varchar(50) DEFAULT NULL COMMENT '联系方式',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待处理 1处理中 2已处理',
  `reply` text COMMENT '回复内容',
  `reply_time` datetime DEFAULT NULL COMMENT '回复时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户反馈表';

-- ----------------------------
-- 15. 加盟申请表 ss_franchise
-- ----------------------------
DROP TABLE IF EXISTS `ss_franchise`;
CREATE TABLE `ss_franchise` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `name` varchar(50) NOT NULL COMMENT '申请人姓名',
  `phone` varchar(20) NOT NULL COMMENT '联系电话',
  `city` varchar(100) DEFAULT NULL COMMENT '意向城市',
  `address` varchar(255) DEFAULT NULL COMMENT '详细地址',
  `budget` varchar(50) DEFAULT NULL COMMENT '预算金额',
  `experience` text COMMENT '从业经验',
  `remark` text COMMENT '备注',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待审核 1通过 2拒绝',
  `audit_remark` varchar(255) DEFAULT NULL COMMENT '审核备注',
  `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='加盟申请表';

-- ----------------------------
-- 16. 拼场活动表 ss_game
-- ----------------------------
DROP TABLE IF EXISTS `ss_game`;
CREATE TABLE `ss_game` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) NOT NULL COMMENT '发起人ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `room_id` int(11) DEFAULT NULL COMMENT '房间ID',
  `title` varchar(100) DEFAULT NULL COMMENT '拼场标题',
  `game_type` varchar(50) DEFAULT NULL COMMENT '游戏类型',
  `max_players` int(11) DEFAULT '4' COMMENT '最大人数',
  `current_players` int(11) DEFAULT '1' COMMENT '当前人数',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `remark` text COMMENT '备注',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0招募中 1已满员 2已支付 3已失效 4已解散',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='拼场活动表';

-- ----------------------------
-- 17. 拼场参与者表 ss_game_user
-- ----------------------------
DROP TABLE IF EXISTS `ss_game_user`;
CREATE TABLE `ss_game_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL COMMENT '拼场ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0已报名 1已确认 2已取消',
  `join_time` datetime DEFAULT NULL COMMENT '报名时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_game_id` (`game_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='拼场参与者表';

-- ----------------------------
-- 18. 帮助文档表 ss_help
-- ----------------------------
DROP TABLE IF EXISTS `ss_help`;
CREATE TABLE `ss_help` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `category` varchar(50) DEFAULT NULL COMMENT '分类',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 0隐藏 1显示',
  `view_count` int(11) DEFAULT '0' COMMENT '浏览次数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助文档表';

-- ----------------------------
-- 19. 套餐表 ss_package
-- ----------------------------
DROP TABLE IF EXISTS `ss_package`;
CREATE TABLE `ss_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `room_class` tinyint(1) DEFAULT '0' COMMENT '业态 0棋牌 1台球 2KTV',
  `name` varchar(100) NOT NULL COMMENT '套餐名称',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '售价',
  `original_price` decimal(10,2) DEFAULT '0.00' COMMENT '原价',
  `duration` int(11) DEFAULT '0' COMMENT '时长分钟',
  `hours` int(11) DEFAULT '0' COMMENT '时长小时',
  `description` text COMMENT '套餐说明',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1在售 0停售',
  `enable` tinyint(1) DEFAULT '1' COMMENT '启用 1是 0否',
  `enable_holiday` tinyint(1) DEFAULT '1' COMMENT '节假日可用 1是 0否',
  `balance_buy` tinyint(1) DEFAULT '1' COMMENT '余额购买 1允许 0不允许',
  `enable_week` varchar(50) DEFAULT NULL COMMENT '可用星期JSON',
  `enable_time` text COMMENT '可用时段JSON',
  `room_type` text COMMENT '适用房间类型JSON',
  `enable_room` text COMMENT '适用房间ID JSON',
  `mt_id` varchar(100) DEFAULT NULL COMMENT '美团套餐ID',
  `dy_id` varchar(100) DEFAULT NULL COMMENT '抖音套餐ID',
  `ks_id` varchar(100) DEFAULT NULL COMMENT '快手套餐ID',
  `expire_day` int(11) DEFAULT '0' COMMENT '有效天数',
  `max_num` int(11) DEFAULT '0' COMMENT '最大购买数量',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='套餐表';

-- ----------------------------
-- 20. 商品表 ss_product
-- ----------------------------
DROP TABLE IF EXISTS `ss_product`;
CREATE TABLE `ss_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `name` varchar(100) NOT NULL COMMENT '商品名称',
  `category` varchar(50) DEFAULT NULL COMMENT '分类',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '售价',
  `stock` int(11) DEFAULT '0' COMMENT '库存',
  `image` varchar(255) DEFAULT NULL COMMENT '商品图片',
  `description` text COMMENT '商品描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1在售 0下架',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';

-- ----------------------------
-- 21. 商品订单表 ss_product_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_product_order`;
CREATE TABLE `ss_product_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单编号',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `room_id` int(11) DEFAULT NULL COMMENT '配送房间ID',
  `product_info` text COMMENT '商品信息JSON',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '总金额',
  `pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '实付金额',
  `pay_type` tinyint(1) DEFAULT NULL COMMENT '支付方式 1微信 2余额',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未支付 1待配送 2已完成 3已取消',
  `mark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品订单表';

-- ----------------------------
-- 22. 充值套餐表 ss_recharge_package
-- ----------------------------
DROP TABLE IF EXISTS `ss_recharge_package`;
CREATE TABLE `ss_recharge_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `name` varchar(100) NOT NULL COMMENT '套餐名称',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
  `gift_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '到账总额',
  `description` varchar(255) DEFAULT NULL COMMENT '套餐描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 0下架 1上架',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值套餐表';

-- ----------------------------
-- 23. 评价表 ss_review
-- ----------------------------
DROP TABLE IF EXISTS `ss_review`;
CREATE TABLE `ss_review` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `order_id` int(11) DEFAULT NULL COMMENT '订单ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `room_id` int(11) DEFAULT NULL COMMENT '房间ID',
  `score` tinyint(1) DEFAULT '5' COMMENT '评分1到5',
  `content` text COMMENT '评价内容',
  `images` text COMMENT '图片JSON',
  `reply` text COMMENT '商家回复',
  `reply_time` datetime DEFAULT NULL COMMENT '回复时间',
  `tags` text COMMENT '评价标签JSON',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价表';

-- ----------------------------
-- 24. 积分记录表 ss_score_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_score_log`;
CREATE TABLE `ss_score_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1增加 2扣减',
  `score` int(11) DEFAULT '0' COMMENT '积分变动',
  `before_score` int(11) DEFAULT '0' COMMENT '变动前积分',
  `after_score` int(11) DEFAULT '0' COMMENT '变动后积分',
  `reason` varchar(255) DEFAULT NULL COMMENT '原因',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分记录表';

-- ----------------------------
-- 25. 充值订单表 ss_recharge_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_recharge_order`;
CREATE TABLE `ss_recharge_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
  `gift_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `pay_type` tinyint(1) DEFAULT '1' COMMENT '支付方式 1微信',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待支付 1已支付 2已取消',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值订单表';

-- ----------------------------
-- 26. 加盟申请表 ss_franchise_apply
-- ----------------------------
DROP TABLE IF EXISTS `ss_franchise_apply`;
CREATE TABLE `ss_franchise_apply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `name` varchar(50) NOT NULL COMMENT '申请人姓名',
  `phone` varchar(20) NOT NULL COMMENT '联系电话',
  `city` varchar(100) DEFAULT NULL COMMENT '意向城市',
  `address` varchar(255) DEFAULT NULL COMMENT '详细地址',
  `budget` varchar(50) DEFAULT NULL COMMENT '预算金额',
  `experience` text COMMENT '从业经验',
  `remark` text COMMENT '备注',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待审核 1通过 2拒绝',
  `audit_remark` varchar(255) DEFAULT NULL COMMENT '审核备注',
  `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='加盟申请表';

-- ----------------------------
-- 27. 折扣规则表 ss_discount_rule (用于充值规则)
-- ----------------------------
DROP TABLE IF EXISTS `ss_discount_rule`;
CREATE TABLE `ss_discount_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `pay_money` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
  `gift_money` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `end_time` date DEFAULT NULL COMMENT '有效期至',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值规则表';

-- ----------------------------
-- 28. 设备出厂注册表 ss_device_registry
-- ----------------------------
DROP TABLE IF EXISTS `ss_device_registry`;
CREATE TABLE `ss_device_registry` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_no` varchar(100) NOT NULL COMMENT '设备编号',
  `device_key` varchar(255) NOT NULL COMMENT '设备密钥',
  `device_type` tinyint(1) DEFAULT '1' COMMENT '设备类型',
  `firmware_version` varchar(50) DEFAULT NULL COMMENT '固件版本',
  `registered` tinyint(1) DEFAULT '0' COMMENT '是否已绑定 0否 1是',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_no` (`device_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备出厂注册表';

-- ----------------------------
-- 29. 管理员账号表 ss_admin_account
-- ----------------------------
DROP TABLE IF EXISTS `ss_admin_account`;
CREATE TABLE `ss_admin_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `store_id` int(11) DEFAULT '0' COMMENT '所属门店ID 0为全部',
  `role` varchar(50) DEFAULT 'admin' COMMENT '角色',
  `permissions` text COMMENT '权限JSON',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  UNIQUE KEY `uk_tenant_username` (`tenant_id`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员账号表';

-- ----------------------------
-- 30. 操作日志表 ss_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_operation_log`;
CREATE TABLE `ss_operation_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) DEFAULT '0' COMMENT '操作人ID',
  `module` varchar(50) DEFAULT NULL COMMENT '模块',
  `action` varchar(50) DEFAULT NULL COMMENT '操作',
  `data` text COMMENT '操作数据JSON',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- ----------------------------
-- 31. 系统配置表 ss_system_config（兼容代码中的 system_config 引用）
-- ----------------------------
DROP TABLE IF EXISTS `ss_system_config`;
CREATE TABLE `ss_system_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `key` varchar(100) NOT NULL COMMENT '配置键',
  `value` text COMMENT '配置值',
  `description` varchar(255) DEFAULT NULL COMMENT '说明',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  UNIQUE KEY `uk_tenant_key` (`tenant_id`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ----------------------------
-- 32. 会员卡使用记录表 ss_card_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_card_log`;
CREATE TABLE `ss_card_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_card_id` int(11) NOT NULL COMMENT '用户会员卡ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `order_id` int(11) DEFAULT NULL COMMENT '关联订单ID',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1购买 2使用 3退款',
  `change_value` decimal(10,2) DEFAULT '0.00' COMMENT '变动值',
  `before_value` decimal(10,2) DEFAULT '0.00' COMMENT '变动前余额',
  `after_value` decimal(10,2) DEFAULT '0.00' COMMENT '变动后余额',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_card_id` (`user_card_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员卡使用记录表';

-- ----------------------------
-- 33. 会员卡订单表 ss_card_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_card_order`;
CREATE TABLE `ss_card_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `card_id` int(11) NOT NULL COMMENT '会员卡类型ID',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '支付金额',
  `pay_type` tinyint(1) DEFAULT '1' COMMENT '支付方式 1微信 2余额',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待支付 1已支付 2已取消',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员卡订单表';

-- ----------------------------
-- 补充字段：ss_user 表增加 mobile, score, vip_level, vip_name, gift_balance, user_type 字段
-- ----------------------------
ALTER TABLE `ss_user` ADD COLUMN `mobile` varchar(20) DEFAULT NULL COMMENT '手机号（兼容字段）' AFTER `phone`;
ALTER TABLE `ss_user` ADD COLUMN `score` int(11) DEFAULT '0' COMMENT '积分' AFTER `balance`;
ALTER TABLE `ss_user` ADD COLUMN `gift_balance` decimal(10,2) DEFAULT '0.00' COMMENT '赠送余额' AFTER `balance`;
ALTER TABLE `ss_user` ADD COLUMN `vip_level` tinyint(1) DEFAULT '0' COMMENT 'VIP等级' AFTER `score`;
ALTER TABLE `ss_user` ADD COLUMN `vip_name` varchar(50) DEFAULT '普通会员' COMMENT 'VIP名称' AFTER `vip_level`;
ALTER TABLE `ss_user` ADD COLUMN `user_type` tinyint(1) DEFAULT '11' COMMENT '用户类型 11普通用户' AFTER `status`;

-- ----------------------------
-- 补充字段：ss_device 表增加 device_key, online_status, last_heartbeat 字段
-- ----------------------------
ALTER TABLE `ss_device` ADD COLUMN `device_key` varchar(255) DEFAULT NULL COMMENT '设备密钥' AFTER `device_no`;
ALTER TABLE `ss_device` ADD COLUMN `online_status` tinyint(1) DEFAULT '0' COMMENT '在线状态 0离线 1在线' AFTER `status`;
ALTER TABLE `ss_device` ADD COLUMN `last_heartbeat` datetime DEFAULT NULL COMMENT '最后心跳时间' AFTER `last_online_time`;

-- ----------------------------
-- 补充字段：ss_coupon 表增加 store_id, room_class 字段
-- ----------------------------
ALTER TABLE `ss_coupon` ADD COLUMN `store_id` int(11) DEFAULT NULL COMMENT '适用门店ID' AFTER `tenant_id`;
ALTER TABLE `ss_coupon` ADD COLUMN `room_class` tinyint(1) DEFAULT '0' COMMENT '适用业态 0棋牌 1台球 2KTV' AFTER `store_id`;

-- ----------------------------
-- 34. 保洁员表 ss_cleaner
-- ----------------------------
DROP TABLE IF EXISTS `ss_cleaner`;
CREATE TABLE `ss_cleaner` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '保洁员ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `user_id` int(11) DEFAULT NULL COMMENT '关联用户ID',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='保洁员表';

-- ----------------------------
-- 35. 清洁任务表 ss_clear_task
-- ----------------------------
DROP TABLE IF EXISTS `ss_clear_task`;
CREATE TABLE `ss_clear_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `room_id` int(11) NOT NULL COMMENT '房间ID',
  `order_id` int(11) DEFAULT NULL COMMENT '关联订单ID',
  `cleaner_id` int(11) DEFAULT NULL COMMENT '保洁员ID',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待清洁 1清洁中 2已完成 3已取消',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`),
  KEY `idx_room_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='清洁任务表';

-- ----------------------------
-- 36. 公告表 ss_notice
-- ----------------------------
DROP TABLE IF EXISTS `ss_notice`;
CREATE TABLE `ss_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '公告ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID(NULL为全部)',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1普通 2重要',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告表';

-- ----------------------------
-- 37. 商品分类表 ss_product_category
-- ----------------------------
DROP TABLE IF EXISTS `ss_product_category`;
CREATE TABLE `ss_product_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品分类表';

-- ----------------------------
-- 38. 团购券表 ss_group_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ss_group_coupon`;
CREATE TABLE `ss_group_coupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `platform` varchar(20) DEFAULT 'meituan' COMMENT '平台 meituan/douyin',
  `coupon_code` varchar(100) DEFAULT NULL COMMENT '券码',
  `coupon_name` varchar(200) DEFAULT NULL COMMENT '券名称',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '券价格',
  `duration` int(11) DEFAULT '0' COMMENT '时长(分钟)',
  `room_class` tinyint(1) DEFAULT '0' COMMENT '适用业态',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购券表';

-- ----------------------------
-- 39. 团购验券日志表 ss_group_verify_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_group_verify_log`;
CREATE TABLE `ss_group_verify_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `order_id` int(11) DEFAULT NULL COMMENT '订单ID',
  `platform` varchar(20) DEFAULT NULL COMMENT '平台',
  `coupon_code` varchar(100) DEFAULT NULL COMMENT '券码',
  `verify_result` text COMMENT '验券结果JSON',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1成功 0失败',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购验券日志表';

-- ----------------------------
-- 40. 开门日志表 ss_door_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_door_log`;
CREATE TABLE `ss_door_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店ID',
  `room_id` int(11) DEFAULT NULL COMMENT '房间ID',
  `order_id` int(11) DEFAULT NULL COMMENT '订单ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `device_no` varchar(100) DEFAULT NULL COMMENT '设备编号',
  `action` varchar(20) DEFAULT 'open' COMMENT '动作 open/close',
  `result` tinyint(1) DEFAULT '1' COMMENT '结果 1成功 0失败',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`, `store_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='开门日志表';

-- ----------------------------
-- 41. 赠送余额记录表 ss_gift_balance
-- ----------------------------
DROP TABLE IF EXISTS `ss_gift_balance`;
CREATE TABLE `ss_gift_balance` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1充值赠送 2消费扣减 3退款',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '金额',
  `balance_before` decimal(10,2) DEFAULT '0.00' COMMENT '变动前余额',
  `balance_after` decimal(10,2) DEFAULT '0.00' COMMENT '变动后余额',
  `order_id` int(11) DEFAULT NULL COMMENT '关联订单ID',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='赠送余额记录表';

-- ----------------------------
-- 42. VIP配置表 ss_vip_config
-- ----------------------------
DROP TABLE IF EXISTS `ss_vip_config`;
CREATE TABLE `ss_vip_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `level` tinyint(1) NOT NULL COMMENT 'VIP等级',
  `name` varchar(50) NOT NULL COMMENT 'VIP名称',
  `min_score` int(11) DEFAULT '0' COMMENT '最低积分',
  `discount` int(11) DEFAULT '100' COMMENT '折扣 100=无折扣 95=9.5折',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标',
  `color` varchar(20) DEFAULT NULL COMMENT '颜色',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  UNIQUE KEY `uk_tenant_level` (`tenant_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='VIP配置表';

SET FOREIGN_KEY_CHECKS = 1;
