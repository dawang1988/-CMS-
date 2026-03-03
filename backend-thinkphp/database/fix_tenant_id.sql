-- 修复测试数据的tenant_id问题
-- 将所有测试数据的tenant_id从'test'更新为'88888888'

SET NAMES utf8mb4;

-- 更新用户表
UPDATE ss_user SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新门店表
UPDATE ss_store SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新房间表
UPDATE ss_room SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新订单表
UPDATE ss_order SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新优惠券表
UPDATE ss_coupon SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新用户优惠券表
UPDATE ss_user_coupon SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新会员卡表
UPDATE ss_user_card SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新商品表
UPDATE ss_product SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新商品订单表
UPDATE ss_product_order SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新评价表
UPDATE ss_review SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新余额记录表
UPDATE ss_balance_log SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新VIP配置表
UPDATE ss_vip_config SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新团购券表
UPDATE ss_group_coupon SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新团购核销记录表
UPDATE ss_group_verify_log SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新设备表
UPDATE ss_device SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新保洁任务表
UPDATE ss_clear_task SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新反馈表
UPDATE ss_feedback SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新帮助表
UPDATE ss_help SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新充值订单表
UPDATE ss_recharge_order SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新充值套餐表
UPDATE ss_recharge_package SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新游戏表
UPDATE ss_game SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新游戏用户表
UPDATE ss_game_user SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新通知表
UPDATE ss_notice SET tenant_id = '88888888' WHERE tenant_id = 'test';

-- 更新套餐表
UPDATE ss_package SET tenant_id = '88888888' WHERE tenant_id = 'test';

SELECT 'tenant_id修复完成！' AS result;
