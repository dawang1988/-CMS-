-- ============================================
-- 修复团购相关表结构
-- 将旧表结构更新为新表结构
-- 执行时间: 2026-03-03
-- ============================================

-- 备份旧数据(如果需要)
-- CREATE TABLE ss_group_verify_log_backup AS SELECT * FROM ss_group_verify_log;
-- CREATE TABLE ss_group_coupon_backup AS SELECT * FROM ss_group_coupon;

-- ============================================
-- 1. 重建 ss_group_verify_log 表
-- ============================================

-- 删除旧表
DROP TABLE IF EXISTS `ss_group_verify_log`;

-- 创建新表(使用正确的结构)
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
  `verify_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '验证类型(1=API验证, 2=手动核销)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1=已核销, 2=已退款)',
  `refund_time` datetime DEFAULT NULL COMMENT '退款时间',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`),
  KEY `idx_group_pay_no` (`group_pay_no`),
  UNIQUE KEY `uk_group_pay_no_status` (`group_pay_no`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='团购券核销日志';

-- ============================================
-- 2. 重建 ss_group_coupon 表
-- ============================================

-- 删除旧表
DROP TABLE IF EXISTS `ss_group_coupon`;

-- 创建新表(使用正确的结构)
CREATE TABLE `ss_group_coupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL DEFAULT '0',
  `store_id` int NOT NULL DEFAULT '0',
  `title` varchar(200) NOT NULL DEFAULT '',
  `platform` varchar(50) NOT NULL DEFAULT '' COMMENT '平台(meituan/douyin/dianping)',
  `hours` decimal(5,1) NOT NULL DEFAULT '0.0' COMMENT '可使用时长(小时)',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '券面额',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1=启用, 0=禁用)',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `use_count` int DEFAULT '0' COMMENT '使用次数',
  `use_amount` decimal(10,2) DEFAULT '0.00' COMMENT '累计核销金额',
  `last_use_time` datetime DEFAULT NULL COMMENT '最后使用时间',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_store` (`tenant_id`,`store_id`),
  KEY `idx_platform_status` (`platform`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='团购券配置';

-- ============================================
-- 3. 确保 ss_group_pay_log 表存在(保持不变)
-- ============================================

CREATE TABLE IF NOT EXISTS `ss_group_pay_log` (
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

-- ============================================
-- 4. 为 ss_order 表添加 group_pay_no 索引(如果不存在)
-- ============================================

SET @dbname = DATABASE();
SET @tablename = 'ss_order';
SET @indexname = 'idx_group_pay_no';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD INDEX ', @indexname, ' (group_pay_no)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 5. 插入示例数据(可选)
-- ============================================

-- 插入示例团购券配置
INSERT INTO `ss_group_coupon` (`tenant_id`, `store_id`, `title`, `platform`, `hours`, `price`, `status`, `create_time`, `update_time`) VALUES
(88888888, 6, '美团2小时畅玩券', 'meituan', 2.0, 68.00, 1, NOW(), NOW()),
(88888888, 6, '美团3小时欢乐券', 'meituan', 3.0, 88.00, 1, NOW(), NOW()),
(88888888, 6, '抖音通宵畅玩券', 'douyin', 99.0, 128.00, 1, NOW(), NOW()),
(88888888, 6, '大众点评4小时券', 'dianping', 4.0, 108.00, 1, NOW(), NOW());

-- 完成
SELECT '团购表结构修复完成' AS message;
SELECT '已创建示例团购券配置' AS message;
