-- ============================================
-- 创建用户门店余额表
-- 用途: 实现余额按门店隔离，用户在不同门店有独立的余额账户
-- 创建时间: 2026-03-01
-- ============================================

-- 创建门店余额表
CREATE TABLE IF NOT EXISTS `ss_user_store_balance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(20) DEFAULT '88888888' COMMENT '租户ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `store_id` int NOT NULL COMMENT '门店ID',
  `balance` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
  `gift_balance` decimal(10,2) DEFAULT '0.00' COMMENT '赠送余额',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_store` (`tenant_id`, `user_id`, `store_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户门店余额表';

-- 添加索引说明
-- uk_user_store: 确保一个用户在一个门店只有一条余额记录
-- idx_user_id: 快速查询用户在所有门店的余额
-- idx_store_id: 快速查询门店的所有用户余额
-- idx_tenant_id: 租户隔离
