-- 数据库: smart_store
-- 表前缀: ss_
-- 导出时间: 2026-02-22 20:39:20

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for product_order
-- ----------------------------
DROP TABLE IF EXISTS `product_order`;
CREATE TABLE `product_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(50) DEFAULT '88888888',
  `order_no` varchar(64) NOT NULL,
  `user_id` int unsigned NOT NULL,
  `store_id` int unsigned DEFAULT NULL,
  `room_id` int unsigned DEFAULT NULL,
  `product_info` text COMMENT '商品信息JSON',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `pay_amount` decimal(10,2) DEFAULT '0.00',
  `pay_type` tinyint DEFAULT '0',
  `status` tinyint DEFAULT '0',
  `mark` varchar(500) DEFAULT '',
  `pay_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_admin
-- ----------------------------
DROP TABLE IF EXISTS `ss_admin`;
CREATE TABLE `ss_admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `tenant_id` varchar(32) NOT NULL DEFAULT '88888888',
  `store_id` int NOT NULL DEFAULT '0',
  `permissions` text,
  `last_login_time` datetime DEFAULT NULL,
  `last_login_ip` varchar(50) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_admin_account
-- ----------------------------
DROP TABLE IF EXISTS `ss_admin_account`;
CREATE TABLE `ss_admin_account` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `store_id` int DEFAULT '0' COMMENT '所属门店ID 0为全部',
  `role` varchar(50) DEFAULT 'admin' COMMENT '角色',
  `permissions` text COMMENT '权限JSON',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_username` (`tenant_id`,`username`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员账号表';

-- ----------------------------
-- Table structure for ss_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_admin_log`;
CREATE TABLE `ss_admin_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` varchar(32) NOT NULL DEFAULT '88888888' COMMENT 'TenantID',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'AdminID',
  `admin_name` varchar(50) NOT NULL DEFAULT '' COMMENT 'AdminName',
  `module` varchar(30) NOT NULL DEFAULT '' COMMENT 'Module',
  `type` varchar(20) NOT NULL DEFAULT 'other' COMMENT 'Type',
  `action` varchar(255) NOT NULL DEFAULT '' COMMENT 'Action',
  `target_id` int unsigned DEFAULT NULL COMMENT 'TargetID',
  `data` text COMMENT 'Data',
  `ip` varchar(45) NOT NULL DEFAULT '' COMMENT 'IP',
  `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT 'UA',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'CreateTime',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_module` (`module`),
  KEY `idx_type` (`type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_balance_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_balance_log`;
CREATE TABLE `ss_balance_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int DEFAULT '0' COMMENT '门店ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1充值 2消费 3退款 4赠送',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '金额',
  `balance_before` decimal(10,2) DEFAULT '0.00' COMMENT '变动前余额',
  `balance_after` decimal(10,2) DEFAULT '0.00' COMMENT '变动后余额',
  `order_id` int DEFAULT NULL COMMENT '关联订单ID',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='余额记录表';

-- ----------------------------
-- Table structure for ss_banner
-- ----------------------------
DROP TABLE IF EXISTS `ss_banner`;
CREATE TABLE `ss_banner` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int DEFAULT '0',
  `title` varchar(100) DEFAULT NULL COMMENT '标题',
  `image` varchar(255) NOT NULL COMMENT '图片',
  `link` varchar(255) DEFAULT NULL COMMENT '链接',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='轮播图表';

-- ----------------------------
-- Table structure for ss_card
-- ----------------------------
DROP TABLE IF EXISTS `ss_card`;
CREATE TABLE `ss_card` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '会员卡ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int DEFAULT NULL COMMENT '适用门店ID，NULL表示全部门店',
  `name` varchar(100) NOT NULL COMMENT '会员卡名称',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1次卡 2时长卡 3储值卡',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '售价',
  `value` decimal(10,2) DEFAULT '0.00' COMMENT '面值/次数/时长',
  `discount` decimal(3,2) DEFAULT '1.00' COMMENT '折扣（0.8=8折）',
  `valid_days` int DEFAULT '0' COMMENT '有效天数（0=永久）',
  `description` text COMMENT '说明',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1在售 0停售',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员卡表';

-- ----------------------------
-- Table structure for ss_card_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_card_log`;
CREATE TABLE `ss_card_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888',
  `user_card_id` int NOT NULL,
  `user_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `type` tinyint(1) DEFAULT '1',
  `change_value` decimal(10,2) DEFAULT '0.00',
  `before_value` decimal(10,2) DEFAULT '0.00',
  `after_value` decimal(10,2) DEFAULT '0.00',
  `remark` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_card_id` (`user_card_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_card_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_card_order`;
CREATE TABLE `ss_card_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号(C开头)',
  `user_id` int NOT NULL COMMENT '用户ID',
  `card_id` int NOT NULL COMMENT '会员卡ID',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '支付金额',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待支付 1已支付 2已取消',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_card` (`card_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员卡购买订单表';

-- ----------------------------
-- Table structure for ss_cleaner
-- ----------------------------
DROP TABLE IF EXISTS `ss_cleaner`;
CREATE TABLE `ss_cleaner` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '???ID',
  `tenant_id` int DEFAULT '88888888' COMMENT '??ID',
  `store_id` int NOT NULL COMMENT '??ID',
  `name` varchar(50) DEFAULT '' COMMENT '??',
  `phone` varchar(20) DEFAULT '' COMMENT '???',
  `status` tinyint(1) DEFAULT '1' COMMENT '???0?? 1??',
  `create_time` datetime DEFAULT NULL COMMENT '????',
  `update_time` datetime DEFAULT NULL COMMENT '????',
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='????';

-- ----------------------------
-- Table structure for ss_clear_task
-- ----------------------------
DROP TABLE IF EXISTS `ss_clear_task`;
CREATE TABLE `ss_clear_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `room_id` int NOT NULL COMMENT '房间ID',
  `order_no` varchar(50) DEFAULT NULL COMMENT '关联订单号',
  `user_id` int DEFAULT NULL COMMENT '接单用户ID',
  `cleaner_id` int DEFAULT NULL COMMENT '保洁员ID',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待接单 1已接单 2已开始 3已完成 4已取消 5被驳回 6已结算',
  `order_end_time` datetime DEFAULT NULL COMMENT '订单结束时间',
  `take_time` datetime DEFAULT NULL COMMENT '接单时间',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '完成时间',
  `settle_amount` decimal(10,2) DEFAULT '0.00' COMMENT '结算金额',
  `settle_time` datetime DEFAULT NULL COMMENT '结算时间',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `store_name` varchar(100) DEFAULT NULL COMMENT '门店名称',
  `room_name` varchar(100) DEFAULT NULL COMMENT '房间名称',
  `nickname` varchar(50) DEFAULT NULL COMMENT '保洁员昵称',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '保洁费用',
  `finish_count` int DEFAULT '0' COMMENT '已完成数',
  `settlement_count` int DEFAULT '0' COMMENT '已结算数',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='保洁任务表';

-- ----------------------------
-- Table structure for ss_config
-- ----------------------------
DROP TABLE IF EXISTS `ss_config`;
CREATE TABLE `ss_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `description` varchar(255) DEFAULT NULL COMMENT '说明',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_key` (`tenant_id`,`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统配置表';

-- ----------------------------
-- Table structure for ss_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ss_coupon`;
CREATE TABLE `ss_coupon` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '优惠券ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `name` varchar(100) NOT NULL COMMENT '优惠券名称',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1抵扣券 2满减券 3加时券',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '金额/时长',
  `min_amount` decimal(10,2) DEFAULT '0.00' COMMENT '最低消费',
  `total` int DEFAULT '0' COMMENT '发行总量',
  `received` int DEFAULT '0' COMMENT '已领取数量',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0停用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `store_id` int DEFAULT NULL COMMENT '适用门店ID',
  `room_class` tinyint(1) DEFAULT NULL COMMENT '????: 0=??, 1=??, 2=KTV',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券表';

-- ----------------------------
-- Table structure for ss_coupon_active
-- ----------------------------
DROP TABLE IF EXISTS `ss_coupon_active`;
CREATE TABLE `ss_coupon_active` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '88888888' COMMENT '租户ID',
  `coupon_id` int NOT NULL COMMENT '优惠券ID',
  `active_name` varchar(100) DEFAULT NULL COMMENT '活动名称',
  `num` int DEFAULT '0' COMMENT '总数量',
  `balance_num` int DEFAULT '0' COMMENT '剩余数量',
  `end_time` varchar(50) DEFAULT NULL COMMENT '截止时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0结束 1进行中',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_coupon` (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券活动表';

-- ----------------------------
-- Table structure for ss_delay_task
-- ----------------------------
DROP TABLE IF EXISTS `ss_delay_task`;
CREATE TABLE `ss_delay_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `enant_id` varchar(20) DEFAULT '150' COMMENT '租户ID',
  `oom_id` int DEFAULT NULL COMMENT '房间ID',
  `device_no` varchar(50) DEFAULT NULL COMMENT '设备编号',
  `ask_type` varchar(30) NOT NULL COMMENT '任务类型: light_off',
  `execute_time` datetime NOT NULL COMMENT '执行时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态: 0待执行 1已执行 2已取消',
  `create_time` datetime DEFAULT NULL,
  `execute_at` datetime DEFAULT NULL COMMENT '实际执行时间',
  PRIMARY KEY (`id`),
  KEY `idx_execute` (`status`,`execute_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='延时任务表';

-- ----------------------------
-- Table structure for ss_device
-- ----------------------------
DROP TABLE IF EXISTS `ss_device`;
CREATE TABLE `ss_device` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '设备ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int DEFAULT '0' COMMENT '门店ID',
  `room_id` int DEFAULT NULL COMMENT '房间ID',
  `device_type` varchar(50) NOT NULL COMMENT '设备类型 lock/kt/light/camera',
  `device_no` varchar(100) NOT NULL COMMENT '设备编号',
  `device_key` varchar(64) DEFAULT NULL COMMENT '设备密钥',
  `device_name` varchar(100) DEFAULT NULL COMMENT '设备名称',
  `brand` varchar(50) DEFAULT NULL COMMENT '品牌',
  `model` varchar(50) DEFAULT NULL COMMENT '型号',
  `firmware_version` varchar(20) DEFAULT NULL COMMENT '固件版本',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 2故障 0停用',
  `online_status` tinyint(1) DEFAULT '0' COMMENT '在线状态 0离线 1在线',
  `last_heartbeat` datetime DEFAULT NULL COMMENT '最后心跳时间',
  `signal_strength` int DEFAULT NULL COMMENT '信号强度(dBm)',
  `battery_level` int DEFAULT NULL COMMENT '电池电量(%)',
  `bind_status` tinyint(1) DEFAULT '0' COMMENT '绑定状态 0未绑定 1已绑定',
  `bind_time` datetime DEFAULT NULL COMMENT '绑定时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `device_sn` varchar(100) DEFAULT NULL COMMENT '设备序列号',
  `lock_data` text COMMENT '门锁初始化数据',
  `lock_pwd` varchar(50) DEFAULT NULL COMMENT '门锁密码',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_key` (`device_key`),
  UNIQUE KEY `uk_tenant_room` (`tenant_id`,`room_id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_device_no` (`device_no`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='设备表';

-- ----------------------------
-- Table structure for ss_device_command_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_device_command_log`;
CREATE TABLE `ss_device_command_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT NULL COMMENT '租户ID',
  `device_no` varchar(100) NOT NULL COMMENT '设备编号',
  `request_id` varchar(64) NOT NULL COMMENT '请求ID',
  `cmd` varchar(50) NOT NULL COMMENT '指令类型',
  `params` json DEFAULT NULL COMMENT '指令参数',
  `direction` enum('down','up') NOT NULL COMMENT '方向',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0已发送 1成功 2失败 3超时',
  `response_code` int DEFAULT NULL COMMENT '设备回复code',
  `response_data` json DEFAULT NULL COMMENT '设备回复数据',
  `response_time` datetime DEFAULT NULL COMMENT '回复时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_device_no` (`device_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='设备指令日志表';

-- ----------------------------
-- Table structure for ss_device_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_device_log`;
CREATE TABLE `ss_device_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `target_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL COMMENT '操作用户ID',
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `room_id` int DEFAULT NULL COMMENT '房间ID',
  `device_type` varchar(50) DEFAULT NULL COMMENT '设备类型',
  `action` varchar(50) DEFAULT NULL COMMENT '操作',
  `success` tinyint(1) DEFAULT '0',
  `params` text COMMENT '操作参数(JSON)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='设备操作日志表';

-- ----------------------------
-- Table structure for ss_device_registry
-- ----------------------------
DROP TABLE IF EXISTS `ss_device_registry`;
CREATE TABLE `ss_device_registry` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `device_no` varchar(100) NOT NULL COMMENT '设备编号',
  `device_key` varchar(64) NOT NULL COMMENT '设备密钥',
  `device_type` varchar(50) NOT NULL COMMENT '设备类型',
  `batch_no` varchar(50) DEFAULT NULL COMMENT '生产批次号',
  `is_bound` tinyint(1) DEFAULT '0' COMMENT '是否已被绑定',
  `bound_tenant_id` varchar(20) DEFAULT NULL COMMENT '绑定的租户ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '入库时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_no` (`device_no`),
  UNIQUE KEY `uk_device_key` (`device_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='设备出厂注册表';

-- ----------------------------
-- Table structure for ss_dict_data
-- ----------------------------
DROP TABLE IF EXISTS `ss_dict_data`;
CREATE TABLE `ss_dict_data` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `dict_type` varchar(100) NOT NULL COMMENT '字典类型',
  `dict_label` varchar(100) NOT NULL COMMENT '字典标签',
  `dict_value` varchar(100) NOT NULL COMMENT '字典值',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0停用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_dict_type` (`dict_type`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='字典数据表';

-- ----------------------------
-- Table structure for ss_discount_rule
-- ----------------------------
DROP TABLE IF EXISTS `ss_discount_rule`;
CREATE TABLE `ss_discount_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `pay_money` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
  `gift_money` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `end_time` date DEFAULT NULL COMMENT '过期时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 2禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='折扣规则表';

-- ----------------------------
-- Table structure for ss_door_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_door_log`;
CREATE TABLE `ss_door_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int DEFAULT NULL COMMENT '门店ID',
  `room_id` int DEFAULT NULL COMMENT '房间ID',
  `user_id` int DEFAULT NULL COMMENT '操作用户ID',
  `order_id` int DEFAULT NULL,
  `order_key` varchar(100) DEFAULT NULL COMMENT '订单key',
  `device_no` varchar(50) DEFAULT NULL,
  `task_id` int DEFAULT NULL COMMENT '保洁任务ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `success` tinyint(1) DEFAULT '0',
  `message` varchar(255) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='开门日志表';

-- ----------------------------
-- Table structure for ss_face_blacklist
-- ----------------------------
DROP TABLE IF EXISTS `ss_face_blacklist`;
CREATE TABLE `ss_face_blacklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `store_name` varchar(100) DEFAULT NULL COMMENT '门店名称',
  `photo_data` text COMMENT '照片数据(base64)',
  `remark` varchar(255) DEFAULT NULL COMMENT '拉黑原因',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='门禁黑名单表';

-- ----------------------------
-- Table structure for ss_face_record
-- ----------------------------
DROP TABLE IF EXISTS `ss_face_record`;
CREATE TABLE `ss_face_record` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `device_id` int DEFAULT '0' COMMENT '设备ID',
  `photo_data` text COMMENT '照片数据(base64)',
  `type` tinyint(1) DEFAULT '2' COMMENT '类型：1黑名单用户 2陌生人',
  `show_time` varchar(50) DEFAULT NULL COMMENT '显示时间',
  `create_time` datetime DEFAULT NULL,
  `capture_photo` varchar(500) DEFAULT '' COMMENT '抓拍照片URL',
  `result` tinyint(1) DEFAULT '0' COMMENT '识别结果：0失败 1成功 2黑名单拦截',
  `match_name` varchar(50) DEFAULT '' COMMENT '匹配人员姓名',
  `similarity` decimal(5,2) DEFAULT '0.00' COMMENT '相似度百分比',
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='门禁识别记录表';

-- ----------------------------
-- Table structure for ss_feedback
-- ----------------------------
DROP TABLE IF EXISTS `ss_feedback`;
CREATE TABLE `ss_feedback` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `type` varchar(50) DEFAULT NULL COMMENT '反馈类型',
  `content` text NOT NULL COMMENT '反馈内容',
  `images` text COMMENT '图片（JSON）',
  `contact` varchar(100) DEFAULT NULL COMMENT '联系方式',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待处理 1已处理',
  `reply` text COMMENT '回复内容',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='反馈表';

-- ----------------------------
-- Table structure for ss_franchise_apply
-- ----------------------------
DROP TABLE IF EXISTS `ss_franchise_apply`;
CREATE TABLE `ss_franchise_apply` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `name` varchar(100) NOT NULL COMMENT '姓名',
  `phone` varchar(20) NOT NULL COMMENT '手机号',
  `city` varchar(100) DEFAULT NULL COMMENT '城市',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `remark` text COMMENT '备注',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待审核 1已通过 2已拒绝',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='加盟申请表';

-- ----------------------------
-- Table structure for ss_game
-- ----------------------------
DROP TABLE IF EXISTS `ss_game`;
CREATE TABLE `ss_game` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '游戏ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int NOT NULL COMMENT '发起人ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `room_id` int DEFAULT NULL COMMENT '房间ID',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `game_type` varchar(50) DEFAULT NULL COMMENT '游戏类型',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `max_players` int DEFAULT '4' COMMENT '最大人数',
  `current_players` int DEFAULT '1' COMMENT '当前人数',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0招募中 1已满员 2进行中 3已结束',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='游戏拼场表';

-- ----------------------------
-- Table structure for ss_game_message
-- ----------------------------
DROP TABLE IF EXISTS `ss_game_message`;
CREATE TABLE `ss_game_message` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `game_id` int NOT NULL COMMENT '游戏ID',
  `user_id` int NOT NULL COMMENT '发送人ID',
  `content` varchar(500) NOT NULL COMMENT '消息内容',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_game_id` (`game_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='游戏聊天消息表';

-- ----------------------------
-- Table structure for ss_game_user
-- ----------------------------
DROP TABLE IF EXISTS `ss_game_user`;
CREATE TABLE `ss_game_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `game_id` int NOT NULL COMMENT '游戏ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1已加入 0已退出',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_game_id` (`game_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='游戏用户表';

-- ----------------------------
-- Table structure for ss_gift_balance
-- ----------------------------
DROP TABLE IF EXISTS `ss_gift_balance`;
CREATE TABLE `ss_gift_balance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '剩余金额',
  `source` varchar(50) DEFAULT NULL COMMENT '来源',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_store` (`user_id`,`store_id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='赠送余额表';

-- ----------------------------
-- Table structure for ss_group_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ss_group_coupon`;
CREATE TABLE `ss_group_coupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '0',
  `store_id` int NOT NULL DEFAULT '0',
  `title` varchar(200) NOT NULL DEFAULT '',
  `platform` varchar(50) NOT NULL DEFAULT '',
  `hours` decimal(5,1) NOT NULL DEFAULT '0.0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `remark` varchar(500) DEFAULT '',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_group_pay_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_group_pay_log`;
CREATE TABLE `ss_group_pay_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '0' COMMENT '租户ID',
  `store_id` int NOT NULL DEFAULT '0' COMMENT '门店ID',
  `user_id` int DEFAULT NULL COMMENT '操作用户ID',
  `group_pay_no` varchar(100) NOT NULL COMMENT '团购券码',
  `platform` varchar(50) DEFAULT '' COMMENT '平台(meituan/douyin/kuaishou/dianping)',
  `title` varchar(255) DEFAULT '' COMMENT '券名称',
  `hours` decimal(5,1) DEFAULT '0.0' COMMENT '时长(小时)',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '券面额',
  `order_no` varchar(100) DEFAULT '' COMMENT '关联订单号',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=已验证 1=已使用 2=已退回',
  `verify_time` datetime DEFAULT NULL COMMENT '验证时间',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`),
  KEY `idx_group_pay_no` (`group_pay_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='团购券核销日志';

-- ----------------------------
-- Table structure for ss_group_verify_log
-- ----------------------------
DROP TABLE IF EXISTS `ss_group_verify_log`;
CREATE TABLE `ss_group_verify_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '0',
  `store_id` int NOT NULL DEFAULT '0',
  `group_coupon_id` int DEFAULT NULL,
  `group_pay_no` varchar(100) NOT NULL DEFAULT '',
  `platform` varchar(50) DEFAULT '',
  `title` varchar(200) DEFAULT '',
  `hours` decimal(5,1) DEFAULT '0.0',
  `order_id` int DEFAULT NULL,
  `order_no` varchar(64) DEFAULT '',
  `user_id` int DEFAULT NULL,
  `verify_type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`),
  KEY `idx_group_pay_no` (`group_pay_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_help
-- ----------------------------
DROP TABLE IF EXISTS `ss_help`;
CREATE TABLE `ss_help` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `sort` int DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1显示 0隐藏',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='帮助文档表';

-- ----------------------------
-- Table structure for ss_money_bill
-- ----------------------------
DROP TABLE IF EXISTS `ss_money_bill`;
CREATE TABLE `ss_money_bill` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `type` tinyint DEFAULT '1' COMMENT '类型 1充值 2消费 3退款 4赠送',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '变动金额',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '变动后余额',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `order_id` int DEFAULT NULL COMMENT '关联订单ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='资金流水表';

-- ----------------------------
-- Table structure for ss_notice
-- ----------------------------
DROP TABLE IF EXISTS `ss_notice`;
CREATE TABLE `ss_notice` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '??ID',
  `tenant_id` int DEFAULT '88888888' COMMENT '??ID',
  `store_id` int DEFAULT '0' COMMENT '??ID?0??????',
  `type` tinyint(1) DEFAULT '1' COMMENT '???1???? 2???? 3????',
  `title` varchar(200) DEFAULT '' COMMENT '????',
  `content` text COMMENT '????',
  `status` tinyint(1) DEFAULT '1' COMMENT '???0?? 1??',
  `create_time` datetime DEFAULT NULL COMMENT '????',
  `update_time` datetime DEFAULT NULL COMMENT '????',
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='???';

-- ----------------------------
-- Table structure for ss_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_order`;
CREATE TABLE `ss_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `user_id` int NOT NULL COMMENT '用户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `room_id` int NOT NULL COMMENT '房间ID',
  `order_type` tinyint(1) DEFAULT '1' COMMENT '订单类型 1小时开台 2团购 3套餐 4押金',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `actual_end_time` datetime DEFAULT NULL COMMENT '实际结束时间',
  `duration` int DEFAULT '0' COMMENT '时长（分钟）',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '单价',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '总金额',
  `discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠金额',
  `vip_discount` int DEFAULT '100' COMMENT 'VIP折扣值(100=无折扣,95=9.5折)',
  `vip_discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'VIP折扣金额',
  `coupon_discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠券折扣金额',
  `card_deduct_amount` decimal(10,2) DEFAULT '0.00' COMMENT '会员卡抵扣金额',
  `user_card_id` int DEFAULT NULL COMMENT '使用的会员卡ID',
  `pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '实付金额',
  `pay_type` tinyint(1) DEFAULT NULL COMMENT '支付方式 1微信 2余额 3易宝',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待支付 1使用中 2已完成 3已取消 4已退款',
  `coupon_id` int DEFAULT NULL COMMENT '优惠券ID',
  `pkg_id` int DEFAULT NULL COMMENT '套餐ID',
  `group_pay_no` varchar(50) DEFAULT NULL COMMENT '团购券码',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `is_reviewed` tinyint(1) DEFAULT '0',
  `reminded` tinyint(1) DEFAULT '0',
  `transaction_id` varchar(64) DEFAULT NULL,
  `actual_amount` decimal(10,2) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pay_order_no` varchar(50) DEFAULT NULL COMMENT '支付单号',
  `refund_price` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `goods_name` varchar(100) DEFAULT NULL COMMENT '商品名称',
  `pay_status` tinyint(1) DEFAULT '0' COMMENT '支付状态',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `sound_remind_30` tinyint(1) DEFAULT '0' COMMENT '30分钟语音提醒已发送',
  `sound_remind_5` tinyint(1) DEFAULT '0' COMMENT '5分钟语音提醒已发送',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_user_card_id` (`user_card_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单表';

-- ----------------------------
-- Table structure for ss_package
-- ----------------------------
DROP TABLE IF EXISTS `ss_package`;
CREATE TABLE `ss_package` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int DEFAULT NULL COMMENT '门店ID（NULL=全部门店）',
  `room_class` tinyint(1) DEFAULT NULL COMMENT '????: 0=??, 1=??, 2=KTV',
  `name` varchar(100) NOT NULL COMMENT '套餐名称',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格',
  `original_price` decimal(10,2) DEFAULT '0.00' COMMENT '原价',
  `duration` int DEFAULT '0' COMMENT '时长（分钟）',
  `description` text COMMENT '说明',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1在售 0停售',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enable` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `enable_holiday` tinyint(1) DEFAULT '0' COMMENT '节假日是否可用',
  `balance_buy` tinyint(1) DEFAULT '1' COMMENT '是否可余额支付',
  `enable_week` text COMMENT '可用星期(JSON数组)',
  `enable_time` text COMMENT '可用时间段(JSON数组)',
  `room_type` text COMMENT '房间类型限制(JSON数组)',
  `enable_room` text COMMENT '可用房间(JSON数组)',
  `mt_id` varchar(100) DEFAULT NULL COMMENT '美团团购商品ID',
  `dy_id` varchar(100) DEFAULT NULL COMMENT '抖音团购商品ID',
  `ks_id` varchar(100) DEFAULT NULL COMMENT '快手团购商品ID',
  `expire_day` int DEFAULT '0' COMMENT '过期天数',
  `max_num` int DEFAULT '0' COMMENT '单用户最大购买数量',
  `hours` int DEFAULT '1' COMMENT '时长(小时)',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='套餐表';

-- ----------------------------
-- Table structure for ss_product
-- ----------------------------
DROP TABLE IF EXISTS `ss_product`;
CREATE TABLE `ss_product` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int DEFAULT NULL COMMENT '门店ID（NULL=全部门店）',
  `name` varchar(100) NOT NULL COMMENT '商品名称',
  `store_name` varchar(100) DEFAULT NULL COMMENT '商品展示名称',
  `store_info` varchar(500) DEFAULT NULL COMMENT '商品简介',
  `category` varchar(50) DEFAULT NULL COMMENT '分类',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格',
  `stock` int DEFAULT '0' COMMENT '库存',
  `image` varchar(255) DEFAULT NULL COMMENT '图片',
  `description` text COMMENT '描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1在售 0下架',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cate_id` int DEFAULT NULL COMMENT '分类ID',
  `sales` int DEFAULT '0' COMMENT '销量',
  `items` text COMMENT '商品规格(JSON)',
  `attrs` text COMMENT '商品属性(JSON)',
  `spec_type` varchar(10) DEFAULT '1' COMMENT '规格类型',
  `slider_image` text COMMENT '轮播图(JSON)',
  `unit_name` varchar(50) DEFAULT NULL COMMENT '单位名称',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=166 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品表';

-- ----------------------------
-- Table structure for ss_product_category
-- ----------------------------
DROP TABLE IF EXISTS `ss_product_category`;
CREATE TABLE `ss_product_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '88888888' COMMENT '租户ID',
  `shop_id` int NOT NULL COMMENT '门店ID',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0禁用 1启用',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shop` (`shop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品分类表';

-- ----------------------------
-- Table structure for ss_product_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_product_order`;
CREATE TABLE `ss_product_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(50) DEFAULT '88888888',
  `order_no` varchar(64) NOT NULL,
  `user_id` int unsigned NOT NULL,
  `store_id` int unsigned DEFAULT NULL,
  `room_id` int unsigned DEFAULT NULL,
  `product_info` text COMMENT '商品信息JSON',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `pay_amount` decimal(10,2) DEFAULT '0.00',
  `pay_type` tinyint DEFAULT '0',
  `status` tinyint DEFAULT '0',
  `mark` varchar(500) DEFAULT '',
  `pay_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_push_rule
-- ----------------------------
DROP TABLE IF EXISTS `ss_push_rule`;
CREATE TABLE `ss_push_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `order_create` tinyint(1) DEFAULT '0' COMMENT '新订单通知',
  `order_pay` tinyint(1) DEFAULT '0' COMMENT '订单支付通知',
  `order_cancel` tinyint(1) DEFAULT '0' COMMENT '订单取消通知',
  `order_end` tinyint(1) DEFAULT '0' COMMENT '订单结束通知',
  `clear_create` tinyint(1) DEFAULT '0' COMMENT '保洁任务创建通知',
  `clear_finish` tinyint(1) DEFAULT '0' COMMENT '保洁任务完成通知',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='推送规则表';

-- ----------------------------
-- Table structure for ss_recharge_order
-- ----------------------------
DROP TABLE IF EXISTS `ss_recharge_order`;
CREATE TABLE `ss_recharge_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `store_id` int DEFAULT '0',
  `recharge_no` varchar(50) NOT NULL COMMENT '充值单号',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
  `gift_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `package_id` int DEFAULT '0' COMMENT '充值套餐ID',
  `pay_type` tinyint(1) DEFAULT NULL COMMENT '支付方式',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0待支付 1已支付 2已取消',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_recharge_no` (`recharge_no`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='充值订单表';

-- ----------------------------
-- Table structure for ss_recharge_package
-- ----------------------------
DROP TABLE IF EXISTS `ss_recharge_package`;
CREATE TABLE `ss_recharge_package` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '套餐ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `name` varchar(100) NOT NULL COMMENT '套餐名称',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '充值金额',
  `gift_amount` decimal(10,2) DEFAULT '0.00' COMMENT '赠送金额',
  `total_amount` decimal(10,2) DEFAULT '0.00' COMMENT '到账金额',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 0停用',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='充值套餐表';

-- ----------------------------
-- Table structure for ss_refund
-- ----------------------------
DROP TABLE IF EXISTS `ss_refund`;
CREATE TABLE `ss_refund` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `tenant_id` int DEFAULT '88888888' COMMENT '??ID',
  `store_id` int NOT NULL COMMENT '??ID',
  `user_id` int NOT NULL COMMENT '??ID',
  `order_id` int DEFAULT '0' COMMENT '??ID',
  `order_no` varchar(64) DEFAULT '' COMMENT '???',
  `order_amount` decimal(10,2) DEFAULT '0.00' COMMENT '????',
  `refund_amount` decimal(10,2) DEFAULT '0.00' COMMENT '????',
  `reason` varchar(500) DEFAULT '' COMMENT '????',
  `status` tinyint(1) DEFAULT '0' COMMENT '???0??? 1??? 2???',
  `reject_reason` varchar(500) DEFAULT '' COMMENT '????',
  `process_time` datetime DEFAULT NULL COMMENT '????',
  `create_time` datetime DEFAULT NULL COMMENT '????',
  `update_time` datetime DEFAULT NULL COMMENT '????',
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='?????';

-- ----------------------------
-- Table structure for ss_review
-- ----------------------------
DROP TABLE IF EXISTS `ss_review`;
CREATE TABLE `ss_review` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888',
  `user_id` int NOT NULL,
  `order_id` int NOT NULL,
  `store_id` int NOT NULL,
  `room_id` int DEFAULT NULL,
  `score` tinyint(1) DEFAULT '5',
  `content` varchar(500) DEFAULT '',
  `images` text,
  `tags` varchar(255) DEFAULT '',
  `reply` varchar(500) DEFAULT NULL,
  `reply_time` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0=隐藏 1=显示',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Table structure for ss_room
-- ----------------------------
DROP TABLE IF EXISTS `ss_room`;
CREATE TABLE `ss_room` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '房间ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `name` varchar(100) NOT NULL COMMENT '房间名称',
  `room_no` varchar(50) DEFAULT NULL COMMENT '房间编号',
  `type` varchar(50) DEFAULT NULL COMMENT '房间类型',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格（元/小时）',
  `images` text COMMENT '房间图片（JSON）',
  `facilities` text COMMENT '设施（JSON）',
  `lock_no` varchar(100) DEFAULT NULL COMMENT '门锁编号',
  `device_config` json DEFAULT NULL COMMENT '电器设备配置',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1空闲 2使用中 3维护中 0停用',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `room_type` tinyint DEFAULT '1' COMMENT '房间类型编号',
  `room_class` tinyint DEFAULT '0' COMMENT '房间分类 0棋牌 1台球',
  `pre_pay_enable` tinyint(1) DEFAULT '0' COMMENT '是否启用预付',
  `pre_pay_amount` decimal(10,2) DEFAULT '0.00' COMMENT '预付金额',
  `pre_pay_type` tinyint(1) DEFAULT '1' COMMENT '预付类型 1固定 2百分比',
  `ball_config` text COMMENT '球类配置(JSON)',
  `label` varchar(500) DEFAULT '' COMMENT '设施标签(逗号分隔)',
  `morning_price` decimal(10,2) DEFAULT '0.00' COMMENT '上午场价格',
  `afternoon_price` decimal(10,2) DEFAULT '0.00' COMMENT '下午场价格',
  `night_price` decimal(10,2) DEFAULT '0.00' COMMENT '夜间场价格',
  `tx_price` decimal(10,2) DEFAULT '0.00' COMMENT '通宵场价格',
  `min_hour` int DEFAULT '1' COMMENT '最低消费小时数',
  `deposit` decimal(10,2) DEFAULT '0.00' COMMENT '押金',
  `work_price` decimal(10,2) DEFAULT '0.00' COMMENT '工作日价格',
  `description` text COMMENT '房间描述',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_room_class` (`room_class`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='房间表';

-- ----------------------------
-- Table structure for ss_store
-- ----------------------------
DROP TABLE IF EXISTS `ss_store`;
CREATE TABLE `ss_store` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '门店ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `name` varchar(100) NOT NULL COMMENT '门店名称',
  `city` varchar(50) DEFAULT NULL COMMENT '所在城市',
  `address` varchar(255) DEFAULT NULL COMMENT '门店地址',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `longitude` decimal(10,6) DEFAULT NULL COMMENT '经度',
  `latitude` decimal(10,6) DEFAULT NULL COMMENT '纬度',
  `images` text COMMENT '门店图片（JSON）',
  `head_img` varchar(500) DEFAULT NULL COMMENT '门头照片URL',
  `banner_img` text COMMENT '轮播广告图,逗号分隔',
  `wifi_name` varchar(100) DEFAULT NULL COMMENT 'WiFi名称',
  `wifi_password` varchar(100) DEFAULT NULL COMMENT 'WiFi密码',
  `tx_start_hour` int DEFAULT '23' COMMENT '通宵开始小时(18-23)',
  `tx_hour` int DEFAULT '8' COMMENT '通宵时长(8-12小时)',
  `show_tx_price` tinyint(1) DEFAULT '1' COMMENT '是否显示通宵价格',
  `clear_time` int DEFAULT '5' COMMENT '清洁时间(分钟)',
  `clear_open` tinyint(1) DEFAULT '1' COMMENT '待清洁允许预订',
  `clear_open_door` tinyint(1) DEFAULT '0' COMMENT '保洁员任意开门',
  `delay_light` tinyint(1) DEFAULT '0' COMMENT '延时5分钟灯光',
  `order_door_open` tinyint(1) DEFAULT '0' COMMENT '消费中门禁常开',
  `order_webhook` varchar(255) DEFAULT NULL COMMENT '企业微信webhook',
  `lock_no` varchar(100) DEFAULT NULL COMMENT '门店大门锁编号',
  `douyin_id` varchar(100) DEFAULT NULL COMMENT '抖音门店ID',
  `sound_enabled` tinyint(1) DEFAULT '0' COMMENT '语音播报开关',
  `sound_volume` int DEFAULT '50' COMMENT '语音音量',
  `sound_start_time` varchar(10) DEFAULT '08:00' COMMENT '播报开始时间',
  `sound_end_time` varchar(10) DEFAULT '22:00' COMMENT '播报结束时间',
  `qr_code` varchar(255) DEFAULT NULL COMMENT '门店小程序码',
  `dy_id` varchar(100) DEFAULT NULL COMMENT '抖音ID',
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
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0停用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sound_config` text COMMENT '语音配置(JSON)',
  `qr_config` text COMMENT '二维码配置(JSON)',
  `group_pay_auth_url` varchar(500) DEFAULT NULL COMMENT '团购支付授权URL',
  `notice` text COMMENT '门店公告内容',
  `card_enabled` tinyint(1) DEFAULT '0' COMMENT 'card function switch',
  `meituan_auth` tinyint(1) DEFAULT '0' COMMENT '美团授权状态 0未授权 1已授权',
  `meituan_expire` datetime DEFAULT NULL COMMENT '美团授权到期时间',
  `meituan_shop_id` varchar(64) DEFAULT NULL COMMENT '美团商户ID',
  `meituan_access_token` varchar(512) DEFAULT NULL COMMENT '美团access_token',
  `meituan_refresh_token` varchar(512) DEFAULT NULL COMMENT '美团refresh_token',
  `douyin_auth` tinyint(1) DEFAULT '0' COMMENT '抖音授权状态 0未授权 1已授权',
  `douyin_expire` datetime DEFAULT NULL COMMENT '抖音授权到期时间',
  `douyin_access_token` varchar(512) DEFAULT NULL COMMENT '抖音access_token',
  `douyin_refresh_token` varchar(512) DEFAULT NULL COMMENT '抖音refresh_token',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='门店表';

-- ----------------------------
-- Table structure for ss_store_template
-- ----------------------------
DROP TABLE IF EXISTS `ss_store_template`;
CREATE TABLE `ss_store_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_key` varchar(50) NOT NULL COMMENT '模板标识',
  `template_img` varchar(500) DEFAULT NULL COMMENT '模板预览图',
  `eversion` varchar(50) DEFAULT NULL COMMENT '支持版本',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0禁用 1启用',
  `sort` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='门店模板表';

-- ----------------------------
-- Table structure for ss_store_user
-- ----------------------------
DROP TABLE IF EXISTS `ss_store_user`;
CREATE TABLE `ss_store_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `user_type` tinyint DEFAULT '1' COMMENT '用户类型 1管理员 2保洁员 12管理员 13超管 14保洁',
  `permissions` text COMMENT '功能权限JSON',
  `name` varchar(50) DEFAULT NULL COMMENT '姓名',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_user` (`store_id`,`user_id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='门店用户关联表';

-- ----------------------------
-- Table structure for ss_user
-- ----------------------------
DROP TABLE IF EXISTS `ss_user`;
CREATE TABLE `ss_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信openid',
  `unionid` varchar(100) DEFAULT NULL COMMENT '微信unionid',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `password` varchar(255) DEFAULT NULL COMMENT '密码',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
  `gift_balance` decimal(10,2) DEFAULT '0.00' COMMENT '赠送余额',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `store_id` int DEFAULT NULL COMMENT '关联门店ID',
  `vip_level` int DEFAULT '0' COMMENT 'VIP等级',
  `vip_name` varchar(50) DEFAULT '普通会员' COMMENT 'VIP名称',
  `score` int DEFAULT '0' COMMENT '积分',
  `user_type` tinyint DEFAULT '0' COMMENT '用户类型 0普通 12管理员 13超管 14保洁',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_openid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户表';

-- ----------------------------
-- Table structure for ss_user_card
-- ----------------------------
DROP TABLE IF EXISTS `ss_user_card`;
CREATE TABLE `ss_user_card` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888',
  `user_id` int NOT NULL,
  `card_id` int NOT NULL,
  `store_id` int DEFAULT NULL COMMENT '适用门店ID',
  `name` varchar(100) NOT NULL COMMENT '会员卡名称',
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '类型1次卡2时长卡3储值卡',
  `total_value` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总值',
  `remain_value` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '剩余值',
  `discount` decimal(3,2) NOT NULL DEFAULT '1.00' COMMENT '折扣',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态1正常0用完2过期',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户会员卡';

-- ----------------------------
-- Table structure for ss_user_coupon
-- ----------------------------
DROP TABLE IF EXISTS `ss_user_coupon`;
CREATE TABLE `ss_user_coupon` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `coupon_id` int NOT NULL COMMENT '优惠券ID',
  `coupon_name` varchar(100) DEFAULT NULL COMMENT '优惠券名称',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1抵扣券 2满减券 3加时券',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '金额/时长',
  `min_use_price` decimal(10,2) DEFAULT '0.00' COMMENT '使用门槛',
  `store_id` int DEFAULT NULL COMMENT '门店ID',
  `store_name` varchar(100) DEFAULT NULL COMMENT '门店名称',
  `room_type` int DEFAULT NULL COMMENT '包间类型',
  `expire_time` date DEFAULT NULL COMMENT '过期时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未使用 1已使用 2已过期',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户优惠券表';

-- ----------------------------
-- Table structure for ss_vip_blacklist
-- ----------------------------
DROP TABLE IF EXISTS `ss_vip_blacklist`;
CREATE TABLE `ss_vip_blacklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `add_time` bigint DEFAULT NULL COMMENT '添加时间戳',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_user` (`store_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员黑名单表';

-- ----------------------------
-- Table structure for ss_vip_config
-- ----------------------------
DROP TABLE IF EXISTS `ss_vip_config`;
CREATE TABLE `ss_vip_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `vip_name` varchar(50) NOT NULL COMMENT '会员名称',
  `vip_level` int DEFAULT '1' COMMENT '会员等级',
  `vip_discount` int DEFAULT '100' COMMENT '折扣(99表示0.99)',
  `score` int DEFAULT '0' COMMENT '积分门槛',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1启用 2禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`),
  KEY `idx_store_level` (`store_id`,`vip_level`),
  KEY `idx_tenant_level` (`tenant_id`,`vip_level`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员配置表';

SET FOREIGN_KEY_CHECKS = 1;
