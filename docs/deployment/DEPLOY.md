# 欢喜内容管理系统 - 生产环境部署指南

本文档详细说明如何在生产环境中部署欢喜内容管理系统。

## 目录
- [环境准备](#环境准备)
- [Nginx 部署](#nginx-部署)
- [Apache 部署](#apache-部署)
- [前端部署](#前端部署)
- [目录权限设置](#目录权限设置)
- [常见问题](#常见问题)

---

## 环境准备

### 系统要求
- **操作系统**: Linux (推荐 Ubuntu 20.04/22.04 或 CentOS 7/8)
- **PHP**: >= 8.0 (推荐 8.2)
- **MySQL**: >= 5.7 (推荐 8.0)
- **Web服务器**: Nginx 或 Apache
- **PHP扩展**:
  - mbstring
  - openssl
  - pdo_mysql
  - fileinfo
  - json
  - xml

### 安装 PHP 和必需扩展

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd
```

**CentOS/RHEL:**
```bash
sudo yum install php82 php82-fpm php82-mysqlnd php82-mbstring php82-xml php82-curl php82-zip php82-gd
```

### 安装 Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## Nginx 部署

### 1. 安装 Nginx

**Ubuntu/Debian:**
```bash
sudo apt install nginx
```

**CentOS/RHEL:**
```bash
sudo yum install nginx
```

### 2. 配置 Nginx

将项目中的 `api/nginx.conf` 文件内容复制到 Nginx 配置文件中：

```bash
sudo nano /etc/nginx/sites-available/huanxi-cms
```

**关键配置说明：**

```nginx
server {
    listen 80;
    server_name your-domain.com;  # 修改为你的域名
    root /var/www/huanxi-cms/api/public;  # 修改为实际路径，必须指向 public 目录
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;  # 根据实际 PHP 版本调整
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. 启用站点

**Ubuntu/Debian:**
```bash
sudo ln -s /etc/nginx/sites-available/huanxi-cms /etc/nginx/sites-enabled/
sudo nginx -t  # 测试配置
sudo systemctl reload nginx
```

**CentOS/RHEL:**
```bash
# 直接在 /etc/nginx/nginx.conf 中包含配置，或放在 /etc/nginx/conf.d/ 目录
sudo nginx -t
sudo systemctl reload nginx
```

### 4. 启动 PHP-FPM

```bash
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm
```

---

## Apache 部署

### 1. 安装 Apache 和 PHP 模块

**Ubuntu/Debian:**
```bash
sudo apt install apache2 libapache2-mod-php8.2
```

**CentOS/RHEL:**
```bash
sudo yum install httpd mod_php82
```

### 2. 启用必需的 Apache 模块

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo a2enmod deflate
```

### 3. 配置虚拟主机

创建配置文件：
```bash
sudo nano /etc/apache2/sites-available/huanxi-cms.conf
```

添加以下内容：
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAdmin admin@your-domain.com
    DocumentRoot /var/www/huanxi-cms/api/public

    <Directory /var/www/huanxi-cms/api/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/huanxi-cms-error.log
    CustomLog ${APACHE_LOG_DIR}/huanxi-cms-access.log combined
</VirtualHost>
```

### 4. 启用站点

```bash
sudo a2ensite huanxi-cms
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### 5. 验证 .htaccess

确保 `api/public/.htaccess` 文件存在（项目已包含此文件）。

---

## 后端部署步骤

### 1. 上传代码

```bash
# 上传到服务器
scp -r cms1 user@server:/var/www/huanxi-cms

# 或使用 Git
cd /var/www
git clone your-repository huanxi-cms
```

### 2. 安装依赖

```bash
cd /var/www/huanxi-cms/api
composer install --no-dev --optimize-autoloader
```

### 3. 配置数据库

编辑数据库配置文件：
```bash
nano config/database.php
```

修改数据库连接信息：
```php
'default' => env('database.driver', 'mysql'),
'connections' => [
    'mysql' => [
        'type'            => env('database.type', 'mysql'),
        'hostname'        => env('database.hostname', '127.0.0.1'),
        'database'        => env('database.database', 'cms_database'),
        'username'        => env('database.username', 'root'),
        'password'        => env('database.password', ''),
        'hostport'        => env('database.hostport', '3306'),
        'params'          => [],
        'charset'         => env('database.charset', 'utf8mb4'),
        'prefix'          => env('database.prefix', ''),
    ],
],
```

### 4. 导入数据库

```bash
mysql -u root -p < /var/www/huanxi-cms/database.sql
```

### 5. 设置目录权限

```bash
cd /var/www/huanxi-cms/api

# 设置运行时目录可写
sudo chown -R www-data:www-data runtime/
sudo chmod -R 755 runtime/

# 设置上传目录可写
sudo chown -R www-data:www-data public/uploads/
sudo chmod -R 755 public/uploads/

# 如果使用静态生成功能
sudo mkdir -p public/static
sudo chown -R www-data:www-data public/static/
sudo chmod -R 755 public/static/
```

注意：`www-data` 是 Ubuntu/Debian 的默认用户，CentOS/RHEL 使用 `apache`。

---

## 前端部署

### 1. 构建生产版本

在本地开发机器上：
```bash
cd backend
npm install
npm run build
```

### 2. 上传构建产物

将 `backend/dist` 目录上传到服务器：
```bash
scp -r dist/ user@server:/var/www/huanxi-cms/frontend
```

### 3. 配置前端访问

#### 方法 1: 单独域名部署

创建新的 Nginx 配置：
```nginx
server {
    listen 80;
    server_name admin.your-domain.com;
    root /var/www/huanxi-cms/frontend;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        access_log off;
    }
}
```

#### 方法 2: 子目录部署

将前端部署在主域名的子目录下：
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/huanxi-cms/api/public;

    # 后端 API
    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 前端管理界面
    location /admin/ {
        alias /var/www/huanxi-cms/frontend/;
        try_files $uri $uri/ /admin/index.html;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. 修改前端 API 地址

如果 API 地址有变化，需要修改前端的 API 请求地址。

编辑 `backend/src/utils/request.js`：
```javascript
const request = axios.create({
  baseURL: 'https://api.your-domain.com/api',  // 修改为实际 API 地址
  timeout: 10000
})
```

重新构建并上传。

---

## 目录权限设置

### 推荐权限配置

```bash
cd /var/www/huanxi-cms

# 所有文件属主设为 Web 服务器用户
sudo chown -R www-data:www-data .

# 基础目录权限
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;

# 可写目录权限
sudo chmod -R 775 api/runtime/
sudo chmod -R 775 api/public/uploads/
sudo chmod -R 775 api/public/static/

# 如果需要，设置特定文件可执行
sudo chmod +x api/think
```

### 使用 ACL（推荐）

如果系统支持 ACL：
```bash
sudo setfacl -R -m u:www-data:rwX api/runtime/
sudo setfacl -R -d -m u:www-data:rwX api/runtime/

sudo setfacl -R -m u:www-data:rwX api/public/uploads/
sudo setfacl -R -d -m u:www-data:rwX api/public/uploads/
```

---

## 安全配置

### 1. 隐藏 PHP 版本信息

编辑 `php.ini`：
```ini
expose_php = Off
```

### 2. 配置防火墙

```bash
# 只开放必要端口
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 3. 设置 HTTPS（推荐）

使用 Let's Encrypt 获取免费 SSL 证书：

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Certbot 会自动配置 Nginx 的 HTTPS。

### 4. 修改默认管理员密码

首次部署后，立即登录系统修改默认密码！

默认账号：admin / admin123

---

## 性能优化

### 1. 启用 OPcache

编辑 `php.ini`：
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### 2. 配置 PHP-FPM

编辑 `/etc/php/8.2/fpm/pool.d/www.conf`：
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### 3. Nginx 缓存配置

在 Nginx 配置中添加：
```nginx
# 启用 Gzip
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;

# 浏览器缓存
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
}
```

---

## 常见问题

### 1. 访问后端 API 返回 404

**原因**: Web 服务器根目录配置错误
**解决**: 确保 Nginx/Apache 的 `root` 或 `DocumentRoot` 指向 `api/public` 目录

### 2. 文件上传失败

**原因**: 目录权限不足
**解决**:
```bash
sudo chown -R www-data:www-data api/public/uploads/
sudo chmod -R 775 api/public/uploads/
```

### 3. 页面刷新后 404

**原因**: 前端路由配置问题
**解决**: 确保 Nginx 配置了 `try_files $uri $uri/ /index.html;`

### 4. API 跨域问题

**原因**: CORS 配置未生效
**解决**: 检查 `api/app/middleware/Cors.php` 是否正确配置

### 5. 数据库连接失败

**原因**: 数据库配置错误或权限不足
**解决**:
```bash
# 检查数据库配置
nano api/config/database.php

# 授予数据库权限
mysql -u root -p
GRANT ALL PRIVILEGES ON cms_database.* TO 'cms_user'@'localhost' IDENTIFIED BY 'password';
FLUSH PRIVILEGES;
```

### 6. PHP-FPM 无响应

**原因**: PHP-FPM 进程数不足
**解决**: 增加 PHP-FPM 进程池大小（见性能优化部分）

---

## 监控和维护

### 1. 查看错误日志

**Nginx:**
```bash
sudo tail -f /var/log/nginx/huanxi_cms_error.log
```

**PHP:**
```bash
sudo tail -f /var/log/php8.2-fpm.log
```

**应用日志:**
```bash
tail -f api/runtime/log/202510/15.log
```

### 2. 定期备份

**数据库备份:**
```bash
mysqldump -u root -p cms_database > backup_$(date +%Y%m%d).sql
```

**文件备份:**
```bash
tar -czf backup_$(date +%Y%m%d).tar.gz /var/www/huanxi-cms/api/public/uploads/
```

### 3. 清理日志

```bash
# 清理超过 30 天的日志
find api/runtime/log/ -name "*.log" -mtime +30 -delete
```

---

## 检查清单

部署完成后，请检查以下项目：

- [ ] 后端 API 可以正常访问
- [ ] 前端页面可以正常访问
- [ ] 可以正常登录管理后台
- [ ] 文件上传功能正常
- [ ] 已修改默认管理员密码
- [ ] 目录权限设置正确
- [ ] 数据库已备份
- [ ] 日志记录正常
- [ ] HTTPS 已配置（生产环境推荐）
- [ ] 防火墙规则已设置

---

## 技术支持

如遇到问题，请参考：

- 项目主页: https://www.sinma.net/
- 作者邮箱: sinma@qq.com

---

© 2025 sinma. All rights reserved.
