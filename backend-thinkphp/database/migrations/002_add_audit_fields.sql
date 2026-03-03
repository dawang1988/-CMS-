-- 审计字段添加脚本
-- 为关键表添加审计追踪字段

-- =============================================
-- 订单表添加审计字段
-- =============================================

ALTER TABLE `ss_order` 
ADD COLUMN `created_by` INT(11) DEFAULT NULL COMMENT '创建人ID' AFTER `create_time`,
ADD COLUMN `updated_by` INT(11) DEFAULT NULL COMMENT '更新人ID' AFTER `update_time`,
ADD COLUMN `ip_address` VARCHAR(50) DEFAULT NULL COMMENT '创建时IP地址' AFTER `updated_by`,
ADD COLUMN `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '创建时UA' AFTER `ip_address`;

-- =============================================
-- 用户表添加审计字段
-- =============================================

ALTER TABLE `ss_user`
ADD COLUMN `last_login_time` DATETIME DEFAULT NULL COMMENT '最后登录时间' AFTER `update_time`,
ADD COLUMN `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP' AFTER `last_login_time`,
ADD COLUMN `login_count` INT(11) DEFAULT 0 COMMENT '登录次数' AFTER `last_login_ip`;

-- =============================================
-- 管理员表添加审计字段
-- =============================================

ALTER TABLE `ss_admin`
ADD COLUMN `last_login_time` DATETIME DEFAULT NULL COMMENT '最后登录时间' AFTER `update_time`,
ADD COLUMN `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP' AFTER `last_login_time`;

-- =============================================
-- 创建操作日志表
-- =============================================

CREATE TABLE IF NOT EXISTS `ss_operation_log` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` VARCHAR(20) DEFAULT '150' COMMENT '租户ID',
  `user_id` INT(11) DEFAULT NULL COMMENT '操作用户ID',
  `user_type` TINYINT(1) DEFAULT 1 COMMENT '用户类型 1普通用户 2管理员',
  `module` VARCHAR(50) DEFAULT NULL COMMENT '模块',
  `action` VARCHAR(50) DEFAULT NULL COMMENT '操作',
  `target_type` VARCHAR(50) DEFAULT NULL COMMENT '目标类型',
  `target_id` INT(11) DEFAULT NULL COMMENT '目标ID',
  `before_data` JSON DEFAULT NULL COMMENT '操作前数据',
  `after_data` JSON DEFAULT NULL COMMENT '操作后数据',
  `ip_address` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User Agent',
  `request_id` VARCHAR(50) DEFAULT NULL COMMENT '请求ID',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_user` (`tenant_id`, `user_id`),
  KEY `idx_target` (`target_type`, `target_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- =============================================
-- 创建设备操作日志表
-- =============================================

CREATE TABLE IF NOT EXISTS `ss_device_log` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` VARCHAR(20) DEFAULT '150' COMMENT '租户ID',
  `target_id` INT(11) NOT NULL COMMENT '目标ID（房间ID或门店ID）',
  `device_type` VARCHAR(30) NOT NULL COMMENT '设备类型',
  `action` VARCHAR(30) NOT NULL COMMENT '操作',
  `success` TINYINT(1) DEFAULT 0 COMMENT '是否成功',
  `user_id` INT(11) DEFAULT NULL COMMENT '操作用户ID',
  `order_id` INT(11) DEFAULT NULL COMMENT '关联订单ID',
  `response` TEXT DEFAULT NULL COMMENT '设备响应',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tenant_target` (`tenant_id`, `target_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备操作日志表';

-- =============================================
-- 创建支付日志表
-- =============================================

CREATE TABLE IF NOT EXISTS `ss_payment_log` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` VARCHAR(20) DEFAULT '150' COMMENT '租户ID',
  `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
  `transaction_id` VARCHAR(100) DEFAULT NULL COMMENT '第三方交易号',
  `pay_type` TINYINT(1) DEFAULT 1 COMMENT '支付方式',
  `amount` DECIMAL(10,2) DEFAULT 0 COMMENT '金额',
  `action` VARCHAR(30) NOT NULL COMMENT '操作类型',
  `status` TINYINT(1) DEFAULT 0 COMMENT '状态',
  `request_data` JSON DEFAULT NULL COMMENT '请求数据',
  `response_data` JSON DEFAULT NULL COMMENT '响应数据',
  `ip_address` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_no` (`order_no`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付日志表';
