-- 修复房间状态不一致问题
-- 问题：房间状态显示"已预约"(status=4)但实际没有订单
-- 解决：将没有活跃订单的房间状态重置为空闲(status=1)

-- 1. 查看当前房间状态分布
SELECT 
    status,
    CASE status 
        WHEN 0 THEN '禁用'
        WHEN 1 THEN '空闲'
        WHEN 2 THEN '使用中'
        WHEN 3 THEN '使用中(旧)'
        WHEN 4 THEN '已预约/待清洁'
    END as status_name,
    COUNT(*) as count
FROM ss_room
GROUP BY status;

-- 2. 查看有问题的房间（状态为已预约/使用中但没有活跃订单）
SELECT r.id, r.name, r.status,
    CASE r.status 
        WHEN 0 THEN '禁用'
        WHEN 1 THEN '空闲'
        WHEN 2 THEN '使用中'
        WHEN 3 THEN '使用中(旧)'
        WHEN 4 THEN '已预约/待清洁'
    END as status_name
FROM ss_room r
WHERE r.status IN (2, 3, 4)
AND NOT EXISTS (
    SELECT 1 FROM ss_order o 
    WHERE o.room_id = r.id 
    AND o.status IN (0, 1)  -- 待支付或使用中
);

-- 3. 修复：将没有活跃订单的房间状态重置为空闲
-- 注意：status=4 可能是"待清洁"，需要根据业务判断是否重置
-- 这里只重置 status=2,3,4 且没有活跃订单的房间

UPDATE ss_room r
SET r.status = 1, r.update_time = NOW()
WHERE r.status IN (2, 3, 4)
AND NOT EXISTS (
    SELECT 1 FROM ss_order o 
    WHERE o.room_id = r.id 
    AND o.status IN (0, 1)
);

-- 4. 验证修复结果
SELECT 
    status,
    CASE status 
        WHEN 0 THEN '禁用'
        WHEN 1 THEN '空闲'
        WHEN 2 THEN '使用中'
        WHEN 3 THEN '使用中(旧)'
        WHEN 4 THEN '已预约/待清洁'
    END as status_name,
    COUNT(*) as count
FROM ss_room
GROUP BY status;
