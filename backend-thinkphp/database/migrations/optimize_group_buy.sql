-- ============================================
-- 团购功能优化数据库迁移脚本
-- 执行时间: 2026-03-03
-- ============================================

-- 1. 为 ss_group_coupon 添加统计字段
SET @dbname = DATABASE();
SET @tablename = 'ss_group_coupon';

-- 添加 use_count 字段
SET @columnname = 'use_count';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT DEFAULT 0 COMMENT ''使用次数''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 添加 use_amount 字段
SET @columnname = 'use_amount';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(10,2) DEFAULT 0.00 COMMENT ''累计核销金额''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 添加 last_use_time 字段
SET @columnname = 'last_use_time';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DATETIME COMMENT ''最后使用时间''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. 为 ss_group_verify_log 添加退款相关字段
SET @tablename = 'ss_group_verify_log';

-- 添加 refund_time 字段
SET @columnname = 'refund_time';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DATETIME COMMENT ''退款时间''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 修改 status 字段注释
ALTER TABLE ss_group_verify_log 
MODIFY COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态(1=已核销, 2=已退款)';

-- 3. 为 ss_group_verify_log 添加唯一索引(防止重复核销)
-- 先删除重复数据(保留最早的记录)
DELETE t1 FROM ss_group_verify_log t1
INNER JOIN ss_group_verify_log t2 
WHERE t1.id > t2.id 
AND t1.group_pay_no = t2.group_pay_no 
AND t1.status = t2.status;

-- 然后添加唯一索引
SET @dbname = DATABASE();
SET @tablename = 'ss_group_verify_log';
SET @indexname = 'uk_group_pay_no';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE ss_group_verify_log ADD UNIQUE KEY uk_group_pay_no (group_pay_no, status)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4. 为 ss_store 添加平台授权过期提醒字段(如果不存在)
-- 检查字段是否存在,如果不存在则添加
SET @dbname = DATABASE();
SET @tablename = 'ss_store';
SET @columnname = 'meituan_token_expire_remind';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TINYINT(1) DEFAULT 0 COMMENT ''美团Token过期提醒(0=未提醒, 1=已提醒)''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'douyin_token_expire_remind';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TINYINT(1) DEFAULT 0 COMMENT ''抖音Token过期提醒(0=未提醒, 1=已提醒)''')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 5. 初始化统计数据(从现有核销记录计算)
UPDATE ss_group_coupon gc
SET 
    use_count = (
        SELECT COUNT(*) 
        FROM ss_group_verify_log gvl 
        WHERE gvl.group_coupon_id = gc.id 
        AND gvl.status = 1
    ),
    use_amount = (
        SELECT IFNULL(SUM(gc.price), 0) 
        FROM ss_group_verify_log gvl 
        WHERE gvl.group_coupon_id = gc.id 
        AND gvl.status = 1
    ),
    last_use_time = (
        SELECT MAX(create_time) 
        FROM ss_group_verify_log gvl 
        WHERE gvl.group_coupon_id = gc.id 
        AND gvl.status = 1
    );

-- 6. 迁移旧的 group_verify 表数据到 group_verify_log (如果表存在)
-- 注意: 这个操作需要根据实际情况调整
-- INSERT INTO ss_group_verify_log (tenant_id, store_id, group_pay_no, status, create_time, update_time)
-- SELECT tenant_id, store_id, group_no, status, use_time, update_time
-- FROM ss_group_verify
-- WHERE NOT EXISTS (
--     SELECT 1 FROM ss_group_verify_log 
--     WHERE group_pay_no = ss_group_verify.group_no
-- );

-- 7. 为 ss_order 表的 group_pay_no 添加索引(如果不存在)
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

-- 完成
SELECT '团购功能优化迁移完成' AS message;
