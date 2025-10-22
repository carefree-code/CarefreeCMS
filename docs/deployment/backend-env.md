# 后端环境配置说明

欢喜内容管理系统后端使用 `.env` 文件管理不同环境的配置。

## 环境配置文件

- `.env` - 当前环境实际使用的配置文件（**不要提交到 Git**）
- `.env.example` - 配置模板，供参考
- `.env.production` - 生产环境配置示例

## 快速开始

### 开发环境

1. 复制配置模板：
```bash
cp .env.example .env
```

2. 修改 `.env` 文件中的数据库配置：
```ini
[DATABASE]
DB_HOST = 127.0.0.1
DB_NAME = cms_database
DB_USER = root
DB_PASS = your_password
DB_PORT = 3306
```

3. 确保调试模式开启：
```ini
APP_DEBUG = true
```

### 生产环境

1. 复制生产环境配置：
```bash
cp .env.production .env
```

2. 修改实际配置（**重要**）：
```ini
# 关闭调试模式
APP_DEBUG = false

# 数据库配置
DB_HOST = 127.0.0.1
DB_NAME = cms_database
DB_USER = cms_user  # 建议使用专用账户
DB_PASS = strong_password_here  # 使用强密码

# JWT 密钥
JWT_SECRET = use_openssl_to_generate_strong_key
```

3. 生成强 JWT 密钥：
```bash
openssl rand -base64 32
```

4. 设置文件权限：
```bash
chmod 600 .env
```

## 配置项详解

### APP_DEBUG - 调试模式

控制是否显示详细错误信息。

**开发环境：**
```ini
APP_DEBUG = true
```
- 显示详细错误信息
- 显示 SQL 查询日志
- 帮助开发调试

**生产环境：**
```ini
APP_DEBUG = false  # 必须关闭！
```
- 隐藏详细错误信息（安全）
- 提高性能
- 避免泄露敏感信息

### DATABASE - 数据库配置

#### DB_HOST - 数据库主机
```ini
# 本地数据库
DB_HOST = 127.0.0.1

# 远程数据库
DB_HOST = db.example.com
```

#### DB_NAME - 数据库名称
```ini
DB_NAME = cms_database
```

#### DB_USER - 数据库用户
```ini
# 开发环境可以使用 root
DB_USER = root

# 生产环境建议使用专用账户
DB_USER = cms_user
```

如何创建专用数据库用户：
```sql
-- 登录 MySQL
mysql -u root -p

-- 创建专用用户
CREATE USER 'cms_user'@'localhost' IDENTIFIED BY 'strong_password';

-- 授予权限
GRANT ALL PRIVILEGES ON cms_database.* TO 'cms_user'@'localhost';

-- 刷新权限
FLUSH PRIVILEGES;
```

#### DB_PASS - 数据库密码
```ini
# 开发环境
DB_PASS = simple_password

# 生产环境（必须使用强密码）
DB_PASS = aB3$xYz9!mN2#pQr7@wT5&sL8
```

强密码要求：
- 至少 16 位字符
- 包含大小写字母、数字、特殊字符
- 不使用常见词汇

#### DB_PORT - 数据库端口
```ini
# MySQL 默认端口
DB_PORT = 3306

# 自定义端口
DB_PORT = 3307
```

#### DB_CHARSET - 字符集
```ini
# 推荐使用 utf8mb4（支持 emoji）
DB_CHARSET = utf8mb4
```

#### DB_PREFIX - 表前缀
```ini
# 不使用前缀
DB_PREFIX =

# 使用前缀（多应用共享数据库时）
DB_PREFIX = cms_
```

### JWT - JWT 令牌配置

#### JWT_SECRET - JWT 密钥

用于签名 JWT token，**必须保密**！

```ini
# 弱密钥（不安全，仅开发环境）
JWT_SECRET = simple_key

# 强密钥（生产环境必需）
JWT_SECRET = xK8vQn2Lm9Wp5Rt7Yz3Bc4Jd6Fh8Gk0Sa1Qe5Uw9
```

生成强密钥的方法：

**方法 1：使用 OpenSSL**
```bash
openssl rand -base64 32
```

**方法 2：使用 PHP**
```php
php -r "echo bin2hex(random_bytes(32));"
```

**方法 3：在线生成**
访问：https://generate-random.org/api-key-generator

#### JWT_EXPIRE - Token 过期时间

单位：秒

```ini
# 2小时（开发环境）
JWT_EXPIRE = 7200

# 1小时（生产环境，更安全）
JWT_EXPIRE = 3600

# 24小时（不推荐）
JWT_EXPIRE = 86400
```

## 环境差异对照表

| 配置项 | 开发环境 | 生产环境 |
|--------|----------|----------|
| APP_DEBUG | `true` | `false` ✓ |
| DB_HOST | `127.0.0.1` | 实际地址 |
| DB_USER | `root` | 专用账户 ✓ |
| DB_PASS | 简单密码 | 强密码 ✓ |
| JWT_SECRET | 简单密钥 | 强密钥 ✓ |
| JWT_EXPIRE | 7200秒 | 3600秒 |

✓ = 必须不同

## 安全最佳实践

### 1. 保护 .env 文件

**.gitignore 配置**
```gitignore
.env
.env.local
.env.*.local
```

**文件权限（Linux）**
```bash
# 只有所有者可读写
chmod 600 .env

# 确认权限
ls -l .env
# 应显示：-rw------- 1 user user
```

### 2. 不同环境使用不同密钥

| 环境 | JWT_SECRET | DB_PASS |
|------|-----------|---------|
| 开发 | dev_key_123 | dev_pass |
| 测试 | test_key_456 | test_pass |
| 生产 | prod_key_789 | prod_pass |

**绝不要在多个环境使用相同的密钥！**

### 3. 定期更换密钥

建议每 3-6 个月更换一次：
- JWT_SECRET
- 数据库密码

更换 JWT_SECRET 会使所有现有 token 失效，用户需要重新登录。

### 4. 备份配置

```bash
# 加密备份配置文件
tar -czf env-backup-$(date +%Y%m%d).tar.gz .env
openssl enc -aes-256-cbc -salt -in env-backup-*.tar.gz -out env-backup-*.tar.gz.enc
rm env-backup-*.tar.gz

# 解密恢复
openssl enc -d -aes-256-cbc -in env-backup-*.tar.gz.enc -out env-backup.tar.gz
tar -xzf env-backup.tar.gz
```

## 常见问题

### Q1: 修改配置后没有生效？

**原因**：ThinkPHP 可能缓存了配置。

**解决**：
```bash
# 清除缓存
rm -rf runtime/cache/*
rm -rf runtime/temp/*

# 或使用命令
php think clear
```

### Q2: 数据库连接失败？

**检查清单**：
1. 数据库服务是否启动？
   ```bash
   systemctl status mysql
   ```

2. 配置是否正确？
   ```bash
   cat .env | grep DB_
   ```

3. 用户权限是否正确？
   ```sql
   SHOW GRANTS FOR 'cms_user'@'localhost';
   ```

4. 防火墙是否阻止？
   ```bash
   telnet 127.0.0.1 3306
   ```

### Q3: JWT 验证失败？

**可能原因**：
- JWT_SECRET 不正确
- Token 已过期
- Token 格式错误

**解决**：
```bash
# 检查 JWT 配置
cat .env | grep JWT_

# 查看日志
tail -f runtime/log/*/error.log
```

### Q4: 生产环境显示详细错误？

**原因**：APP_DEBUG 未关闭

**解决**：
```bash
# 检查配置
cat .env | grep APP_DEBUG

# 应该显示：
# APP_DEBUG = false

# 如果不对，修改：
sed -i 's/APP_DEBUG = true/APP_DEBUG = false/' .env
```

### Q5: 如何切换数据库？

修改 `.env` 文件：
```ini
[DATABASE]
DB_NAME = new_database_name
```

然后清除缓存：
```bash
php think clear
```

## 环境变量在代码中使用

### 读取环境变量

```php
// 读取配置，第二个参数是默认值
$debug = env('APP_DEBUG', false);
$dbHost = env('DB_HOST', '127.0.0.1');
$jwtSecret = env('JWT_SECRET');

// 在配置文件中使用
// config/database.php
return [
    'hostname' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_NAME', 'cms_database'),
];
```

### 添加自定义配置

在 `.env` 中添加：
```ini
[CUSTOM]
API_KEY = your_api_key
ENABLE_CACHE = true
MAX_UPLOAD_SIZE = 10485760
```

在代码中读取：
```php
$apiKey = env('API_KEY');
$enableCache = env('ENABLE_CACHE', false);
$maxSize = env('MAX_UPLOAD_SIZE', 5242880);
```

## 部署检查清单

部署到生产环境前，请确认：

- [ ] `.env` 文件已正确配置
- [ ] `APP_DEBUG = false`
- [ ] 数据库密码已修改为强密码
- [ ] JWT_SECRET 已修改为强密钥
- [ ] 数据库用户使用专用账户（非 root）
- [ ] `.env` 文件权限设置为 600
- [ ] `.gitignore` 包含 `.env`
- [ ] 已备份 `.env` 文件
- [ ] 已清除缓存
- [ ] 已测试数据库连接
- [ ] 已测试 API 接口

## 技术支持

遇到问题请查看：
- 项目文档：README.md
- 部署文档：DEPLOY.md
- 作者网站：https://www.sinma.net/
- 作者邮箱：sinma@qq.com

---

© 2025 sinma. All rights reserved.
