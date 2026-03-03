# 生产环境安全检查清单

## 🔴 必须修改的配置（高危）

### 1. 关闭调试模式
```ini
APP_DEBUG = false
```
**风险**：开启调试会暴露敏感信息（数据库密码、文件路径、错误详情）

### 2. 修改 APP_KEY
```ini
APP_KEY = 请生成32位随机字符串
```
**生成方法**：
```bash
php -r "echo bin2hex(random_bytes(16));"
```
**风险**：默认 KEY 会导致加密数据被破解

### 3. 设置数据库密码
```ini
PASSWORD = 强密码（至少12位，包含大小写字母、数字、特号）
```
**风险**：空密码会被直接入侵

### 4. 设置 Redis 密码
```ini
PASSWORD = Redis强密码
```
**风险**：无密码的 Redis 可被远程访问，导致数据泄露

### 5. 修改 CORS 域名
```ini
ALLOWED_ORIGINS = https://你的实际域名.com
```
**风险**：允许所有域名会导致 CSRF 攻击

---

## 🟡 建议修改的配置（中危）

### 6. 修改数据库用户
- 不要使用 `root` 用户
- 创建专用数据库用户，只授予必要权限

```sql
CREATE USER 'smart_store'@'localhost' IDENTIFIED BY '强密码';
GRANT SELECT, INSERT, UPDATE, DELETE ON smart_store.* TO 'smart_store'@'localhost';
FLUSH PRIVILEGES;
```

### 7. 限制数据库访问
```ini
HOSTNAME = 127.0.0.1  # 只允许本地访问
```

### 8. 配置 HTTPS
- 使用 SSL 证书（Let's Encrypt 免费）
- 强制 HTTPS 访问

---

## 🟢 性能优化建议

### 9. Session 存储选择

**单服务器（当前配置）：**
```ini
[SESSION]
TYPE = file
```
✅ 优点：简单、稳定
❌ 缺点：性能一般

**多服务器或高并发：**
```ini
[SESSION]
TYPE = cache
STORE = redis
```
✅ 优点：快速、支持分布式
❌ 缺点：依赖 Redis

### 10. 缓存配置
```ini
[CACHE]
DRIVER = redis  # 生产环境推荐
```

---

## 🔒 服务器安全配置

### 11. 文件权限
```bash
# 设置正确的文件权限
chmod 755 /path/to/backend-thinkphp
chmod 644 /path/to/backend-thinkphp/.env
chmod 777 /path/to/backend-thinkphp/runtime
```

### 12. 隐藏敏感文件
在 Nginx 配置中禁止访问：
```nginx
location ~ /\.(env|git) {
    deny all;
}
```

### 13. 防火墙配置
```bash
# 只开放必要端口
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw allow 22/tcp    # SSH
ufw enable
```

### 14. 定期备份
- 数据库每天自动备份
- 代码使用 Git 版本控制
- 上传文件定期备份

---

## 📋 部署前检查

- [ ] APP_DEBUG = false
- [ ] APP_KEY 已修改
- [ ] 数据库密码已设置
- [ ] Redis 密码已设置
- [ ] CORS 域名已修改
- [ ] 数据库用户不是 root
- [ ] 配置了 HTTPS
- [ ] 文件权限正确
- [ ] 防火墙已配置
- [ ] 备份策略已设置

---

## 🚨 安全事件响应

如果发现安全问题：
1. 立即修改所有密码
2. 检查日志文件（runtime/log/）
3. 检查数据库是否被篡改
4. 更新所有依赖包
5. 联系安全专家

---

## 📞 技术支持

遇到问题可以：
- 查看 ThinkPHP 官方文档
- 查看项目 README.md
- 联系开发团队
