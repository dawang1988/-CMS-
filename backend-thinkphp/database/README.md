# 数据修复与Redis缓存优化说明

## 一、问题修复

### 1.1 tenant_id不匹配问题

**问题描述**：
- 系统默认使用 tenant_id = '88888888'
- 测试数据使用 tenant_id = 'test'
- 导致查询结果为空

**解决方案**：
执行SQL脚本修复所有测试数据的tenant_id

```bash
# 在MySQL中执行以下脚本
mysql -u root -p smart_store < database/fix_tenant_id.sql
```

或手动执行：
```sql
UPDATE ss_user SET tenant_id = '88888888' WHERE tenant_id = 'test';
UPDATE ss_store SET tenant_id = '88888888' WHERE tenant_id = 'test';
UPDATE ss_room SET tenant_id = '88888888' WHERE tenant_id = 'test';
UPDATE ss_order SET tenant_id = '88888888' WHERE tenant_id = 'test';
-- 其他表也需要更新...
```

### 1.2 数据库表缺失问题

**解决方案**：
运行完整的数据库初始化脚本

```bash
mysql -u root -p smart_store < database/init_database.sql
```

该脚本包含：
- 所有核心表结构创建
- 测试数据插入
- 索引优化

---

## 二、Redis缓存优化

### 2.1 Redis配置

Redis配置已在 `.env` 文件中配置：

```env
[REDIS]
HOST = 127.0.0.1
PORT = 6379
PASSWORD = 
SELECT = 0
TIMEOUT = 0
EXPIRE = 0
PERSISTENT = false
PREFIX = smart_store:
```

### 2.2 Redis缓存服务类

已创建 `app/service/RedisService.php`，提供以下功能：

#### 基础方法
- `get($key)` - 获取缓存
- `set($key, $value, $expire = 3600)` - 设置缓存
- `delete($key)` - 删除缓存
- `remember($key, $expire, $callback)` - 记忆缓存
- `has($key)` - 检查缓存是否存在
- `clear($pattern)` - 清除缓存

#### 业务方法
- `getStoreList($tenantId)` - 获取门店列表缓存
- `setStoreList($tenantId, $list, $expire = 1800)` - 设置门店列表缓存
- `getRoomList($storeId, $tenantId)` - 获取房间列表缓存
- `setRoomList($storeId, $tenantId, $list, $expire = 1800)` - 设置房间列表缓存
- `getUserInfo($userId, $tenantId)` - 获取用户信息缓存
- `setUserInfo($userId, $tenantId, $data, $expire = 1800)` - 设置用户信息缓存
- `getCouponList($userId, $tenantId)` - 获取优惠券列表缓存
- `setCouponList($userId, $tenantId, $list, $expire = 1800)` - 设置优惠券列表缓存
- `getProductList($storeId, $tenantId)` - 获取商品列表缓存
- `setProductList($storeId, $tenantId, $list, $expire = 1800)` - 设置商品列表缓存

#### 缓存清除方法
- `clearUserCache($userId, $tenantId)` - 清除用户缓存
- `clearStoreCache($tenantId)` - 清除门店缓存
- `clearRoomCache($storeId, $tenantId)` - 清除房间缓存
- `clearOrderCache($userId, $tenantId)` - 清除订单缓存
- `clearCouponCache($userId, $tenantId)` - 清除优惠券缓存
- `clearProductCache($storeId, $tenantId)` - 清除商品缓存
- `clearAllCache()` - 清除所有缓存

### 2.3 已优化的接口

#### Store控制器
- `list()` - 门店列表（缓存30分钟）
- `rooms()` - 房间列表（缓存30分钟）

#### Member控制器
- `info()` - 用户信息（缓存30分钟）

### 2.4 缓存策略

| 数据类型 | 缓存时间 | 说明 |
|---------|---------|------|
| 门店列表 | 1800秒（30分钟） | 门店信息变化不频繁 |
| 房间列表 | 1800秒（30分钟） | 房间信息变化不频繁 |
| 用户信息 | 1800秒（30分钟） | 用户信息相对稳定 |
| 优惠券列表 | 1800秒（30分钟） | 优惠券信息相对稳定 |
| 商品列表 | 1800秒（30分钟） | 商品信息相对稳定 |
| 订单列表 | 600秒（10分钟） | 订单状态变化较快 |
| 余额信息 | 600秒（10分钟） | 余额变化较快 |

### 2.5 使用示例

```php
use app\service\RedisService;

// 获取缓存
$list = RedisService::getStoreList('88888888');

// 设置缓存
RedisService::setStoreList('88888888', $list, 1800);

// 使用记忆缓存（自动处理缓存不存在的情况）
$list = RedisService::remember('store:list:88888888', 1800, function() {
    return StoreModel::where('tenant_id', '88888888')->select()->toArray();
});

// 清除缓存
RedisService::clearUserCache($userId, '88888888');
```

---

## 三、索引优化建议

为提高查询性能，建议添加以下索引：

```sql
-- 订单表索引
ALTER TABLE ss_order ADD INDEX idx_user_status (user_id, status);
ALTER TABLE ss_order ADD INDEX idx_store_status (store_id, status);
ALTER TABLE ss_order ADD INDEX idx_room_status (room_id, status);
ALTER TABLE ss_order ADD INDEX idx_create_time (create_time);

-- 房间表索引
ALTER TABLE ss_room ADD INDEX idx_store_status (store_id, status, room_class);

-- 用户表索引
ALTER TABLE ss_user ADD INDEX idx_phone (phone);
ALTER TABLE ss_user ADD INDEX idx_openid (openid);

-- 优惠券表索引
ALTER TABLE ss_user_coupon ADD INDEX idx_user_status (user_id, status);
ALTER TABLE ss_coupon ADD INDEX idx_status (status);

-- 余额记录表索引
ALTER TABLE ss_balance_log ADD INDEX idx_user_type (user_id, type);
ALTER TABLE ss_balance_log ADD INDEX idx_create_time (create_time);
```

---

## 四、部署步骤

### 4.1 修复数据

```bash
# 1. 备份数据库
mysqldump -u root -p smart_store > backup_$(date +%Y%m%d).sql

# 2. 执行tenant_id修复脚本
mysql -u root -p smart_store < database/fix_tenant_id.sql

# 3. 如果表缺失，执行初始化脚本
mysql -u root -p smart_store < database/init_database.sql
```

### 4.2 启动Redis

```bash
# Windows
redis-server.exe

# Linux
sudo systemctl start redis
sudo systemctl enable redis
```

### 4.3 验证Redis连接

```bash
redis-cli ping
# 应返回 PONG
```

### 4.4 重启PHP服务

```bash
cd backend-thinkphp
php think run -p 8900
```

### 4.5 测试缓存

```bash
# 测试门店列表接口
curl http://localhost:8900/app-api/store/list

# 检查Redis中的缓存
redis-cli
> KEYS smart_store:*
> GET smart_store:store:list:88888888
```

---

## 五、监控与维护

### 5.1 查看Redis使用情况

```bash
redis-cli INFO
redis-cli INFO memory
redis-cli INFO stats
```

### 5.2 清除所有缓存

```bash
redis-cli FLUSHDB
```

### 5.3 清除特定租户缓存

```php
// 在代码中调用
RedisService::clearStoreCache('88888888');
```

### 5.4 监控缓存命中率

```bash
redis-cli INFO stats | grep keyspace_hits
redis-cli INFO stats | grep keyspace_misses
```

---

## 六、注意事项

1. **Redis服务必须启动**：确保Redis服务正常运行，否则缓存功能会降级为直接查询数据库

2. **缓存一致性**：当数据更新时，需要清除相关缓存
   - 用户信息更新 → 清除用户缓存
   - 门店信息更新 → 清除门店缓存
   - 房间信息更新 → 清除房间缓存

3. **缓存过期时间**：根据业务需求调整缓存过期时间
   - 静态数据（门店、房间）：30分钟
   - 动态数据（订单、余额）：10分钟

4. **内存使用**：监控Redis内存使用情况，避免内存溢出
   ```bash
   redis-cli INFO memory | grep used_memory_human
   ```

5. **数据备份**：定期备份数据库和Redis数据
   ```bash
   # 备份Redis
   redis-cli SAVE
   cp dump.rdb backup_dump_$(date +%Y%m%d).rdb
   ```

---

## 七、性能提升预期

使用Redis缓存后，预期性能提升：

| 接口 | 优化前 | 优化后 | 提升 |
|------|--------|--------|------|
| 门店列表 | ~200ms | ~10ms | 95% |
| 房间列表 | ~150ms | ~10ms | 93% |
| 用户信息 | ~100ms | ~5ms | 95% |
| 优惠券列表 | ~120ms | ~10ms | 92% |

---

## 八、故障排查

### 8.1 缓存不生效

**检查项**：
1. Redis服务是否启动
2. Redis连接配置是否正确
3. 缓存键是否正确

**排查命令**：
```bash
redis-cli ping
redis-cli KEYS smart_store:*
```

### 8.2 数据不一致

**解决方法**：
```bash
# 清除所有缓存
redis-cli FLUSHDB

# 或在代码中清除特定缓存
RedisService::clearAllCache();
```

### 8.3 内存不足

**解决方法**：
1. 调整Redis最大内存配置
2. 设置内存淘汰策略
3. 减少缓存过期时间

```bash
# redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

---

## 九、联系支持

如有问题，请联系技术支持或查看项目文档。
