# 逍遥内容管理系统 - 安装指南

本文档详细说明如何安装和配置逍遥内容管理系统（CarefreeC MS）。

## 环境要求

### 最低要求

- **PHP**: >= 8.1
- **MySQL**: >= 5.7 或 MariaDB >= 10.2
- **Node.js**: >= 16.0
- **Composer**: 最新版本
- **Web服务器**: Nginx (推荐) 或 Apache

### 推荐配置

- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- 2GB+ 内存
- SSD硬盘

### PHP扩展要求

确保以下PHP扩展已启用：

```
- PDO
- pdo_mysql
- mbstring
- openssl
- json
- fileinfo
- gd (或 imagick)
- zip
```

## 安装步骤

### 1. 获取源代码

```bash
# 方式1：通过Git克隆
git clone https://github.com/carefreecms/carefreecms.git
cd carefreecms

# 方式2：下载压缩包并解压
# 下载后解压到目标目录
```

### 2. 安装后端

#### 2.1 安装PHP依赖

```bash
cd api
composer install
```

#### 2.2 配置数据库

编辑 `api/config/database.php` 文件，配置数据库连接：

```php
return [
    // 默认使用的数据库连接配置
    'default'         => env('database.driver', 'mysql'),

    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'            => env('database.type', 'mysql'),
            // 服务器地址
            'hostname'        => env('database.hostname', '127.0.0.1'),
            // 数据库名
            'database'        => env('database.database', 'carefreecms'),
            // 用户名
            'username'        => env('database.username', 'root'),
            // 密码
            'password'        => env('database.password', ''),
            // 端口
            'hostport'        => env('database.hostport', '3306'),
            // 数据库字符集
            'charset'         => env('database.charset', 'utf8mb4'),
            // 数据库表前缀
            'prefix'          => env('database.prefix', ''),
        ],
    ],
];
```

#### 2.3 导入数据库

```bash
# 创建数据库
mysql -u root -p -e "CREATE DATABASE carefreecms DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 导入数据库结构和数据
mysql -u root -p carefreecms < database.sql
```

#### 2.4 配置目录权限

```bash
# 确保以下目录可写
chmod -R 755 api/runtime
chmod -R 755 api/public/uploads
chmod -R 755 api/html
```

#### 2.5 测试后端服务

```bash
# 开发环境
php think run

# 访问 http://localhost:8000 测试API
```

### 3. 安装前端

#### 3.1 安装Node.js依赖

```bash
cd backend
npm install
```

#### 3.2 配置API地址

编辑 `backend/.env.development` 文件：

```
VITE_API_BASE_URL=http://localhost:8000
```

#### 3.3 启动开发服务器

```bash
npm run dev
```

前端将运行在 `http://localhost:5173`

### 4. 默认账号

安装完成后，使用以下账号登录：

- **用户名**: `admin`
- **密码**: `admin123`

> ⚠️ **安全提示**: 首次登录后请立即修改密码！

## 生产环境部署

### 1. 构建前端

```bash
cd backend
npm run build
```

构建完成后，`dist` 目录包含所有静态文件。

### 2. 配置Nginx

创建Nginx配置文件 `/etc/nginx/sites-available/carefreecms`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/carefreecms/api/public;
    index index.php index.html;

    # 后端API
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # 安全设置
    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# 前端管理后台
server {
    listen 80;
    server_name admin.your-domain.com;
    root /var/www/carefreecms/backend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

启用站点：

```bash
ln -s /etc/nginx/sites-available/carefreecms /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 3. 配置生产环境变量

编辑 `api/.env.production` 并重命名为 `.env`:

```
APP_DEBUG = false
APP_TRACE = false

[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = carefreecms
USERNAME = root
PASSWORD = your_password
HOSTPORT = 3306
CHARSET = utf8mb4
PREFIX =

[JWT]
SECRET_KEY = your_secret_key_here
EXPIRE = 7200
```

### 4. 优化配置

#### 4.1 启用OPcache

编辑 `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

#### 4.2 配置PHP-FPM

编辑 `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

## 常见问题

### 1. Composer安装失败

```bash
# 使用中国镜像
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
```

### 2. 数据库连接失败

- 检查数据库配置是否正确
- 确认MySQL服务是否启动
- 验证数据库用户权限

### 3. 权限问题

```bash
# 设置正确的所有者（假设Web服务器用户为www-data）
chown -R www-data:www-data api/runtime
chown -R www-data:www-data api/public/uploads
chown -R www-data:www-data api/html
```

### 4. 前端构建失败

```bash
# 清除缓存重新安装
rm -rf node_modules
rm package-lock.json
npm install
npm run build
```

### 5. 静态页面生成失败

- 确保 `api/templates` 目录存在且模板文件完整
- 检查 `api/html` 目录是否有写入权限
- 查看生成日志了解具体错误信息

## 安全建议

1. **修改默认密码**: 首次登录后立即修改admin密码
2. **配置HTTPS**: 生产环境强烈建议使用SSL证书
3. **定期备份**: 定期备份数据库和上传文件
4. **更新依赖**: 及时更新系统依赖包
5. **日志监控**: 定期查看操作日志，监控异常行为
6. **限制访问**: 配置防火墙，限制不必要的端口访问

## 升级指南

### 从旧版本升级

1. 备份数据库和文件
2. 下载最新版本代码
3. 更新依赖包
4. 执行数据库迁移脚本
5. 清除缓存
6. 测试功能

具体升级步骤会在版本发布时提供。

## 技术支持

如遇到安装问题，可以通过以下方式获取帮助：

- 查看文档：https://docs.carefreecms.com
- 提交Issue：https://github.com/carefreecms/carefreecms/issues
- 邮件支持：support@carefreecms.com

---

祝您使用愉快！
