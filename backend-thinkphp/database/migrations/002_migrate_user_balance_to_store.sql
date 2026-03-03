-- ============================================
-- 数据迁移: 将用户表中的余额迁移到门店余额表
-- 迁移策略: 将用户的余额迁移到其关联的门店
-- 创建时间: 2026-03-01
-- ============================================

-- 步骤1: 迁移有关联门店的用户余额
INSERT INTO ss_user_store_balance (tenant_id, user_id, store_id, balance, gift_balance, create_time)
SELECT 
    tenant_id,
    id as user_id,
    store_id,
    balance,
    gift_balance,
    NOW()
FROM ss_user
WHERE store_id IS NOT NULL 
  AND store_id > 0
  AND (balance > 0 OR gift_balance > 0);

-- 步骤2: 查询没有关联门店但有余额的用户（需要手动处理）
SELECT 
    id as user_id,
    nickname,
    phone,
    balance,
    gift_balance,
    '需要指定门店' as note
FROM ss_user
WHERE (store_id IS NULL OR store_id = 0)
  AND (balance > 0 OR gift_balance > 0);

-- 步骤3: 如果有默认门店（如门店ID=1），可以将这些用户的余额迁移到默认门店
-- 取消下面的注释来执行
/*
INSERT INTO ss_user_store_balance (tenant_id, user_id, store_id, balance, gift_balance, create_time)
SELECT 
    tenant_id,
    id as user_id,
    1 as store_id,  -- 默认门店ID
    balance,
    gift_balance,
    NOW()
FROM ss_user
WHERE (store_id IS NULL OR store_id = 0)
  AND (balance > 0 OR gift_balance > 0);
*/

-- 步骤4: 验证迁移结果
SELECT 
    '用户表余额总计' as type,
    COUNT(*) as user_count,
    SUM(balance) as total_balance,
    SUM(gift_balance) as total_gift_balance
FROM ss_user
WHERE balance > 0 OR gift_balance > 0

UNION ALL

SELECT 
    '门店余额表总计' as type,
    COUNT(*) as user_count,
    SUM(balance) as total_balance,
    SUM(gift_balance) as total_gift_balance
FROM ss_user_store_balance;

-- 步骤5: 迁移完成后，可以选择清空用户表的余额字段（可选，建议保留作为备份）
-- 取消下面的注释来执行
/*
UPDATE ss_user 
SET balance = 0, gift_balance = 0
WHERE id IN (
    SELECT DISTINCT user_id FROM ss_user_store_balance
);
*/
