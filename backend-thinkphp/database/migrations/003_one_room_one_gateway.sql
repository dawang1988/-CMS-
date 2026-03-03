-- 一房间一网关约束
-- 执行前请先在管理后台清理重复绑定的设备

-- 1. 查看当前重复绑定的房间（仅查询，不执行修改）
-- SELECT room_id, COUNT(*) as cnt, GROUP_CONCAT(device_no) as devices 
-- FROM ss_device 
-- WHERE room_id IS NOT NULL AND room_id > 0 
-- GROUP BY room_id 
-- HAVING cnt > 1;

-- 2. 解绑重复设备（保留每个房间ID最小的设备，其他设备的room_id置空）
UPDATE ss_device d1
JOIN (
    SELECT room_id, MIN(id) as keep_id
    FROM ss_device
    WHERE room_id IS NOT NULL AND room_id > 0
    GROUP BY room_id
    HAVING COUNT(*) > 1
) d2 ON d1.room_id = d2.room_id AND d1.id != d2.keep_id
SET d1.room_id = NULL, d1.store_id = NULL;

-- 3. 添加唯一索引（room_id允许NULL，多个NULL不冲突）
-- 注意：MySQL的UNIQUE索引允许多个NULL值
ALTER TABLE ss_device ADD UNIQUE INDEX uk_room_id (tenant_id, room_id);
