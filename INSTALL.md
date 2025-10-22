# 逍遥内容管理系统 - 安装指南

本文档详细说明如何安装和配置逍遥内容管理系统（CarefreeCMS）。

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

#### 2.3 配置环境变量

复制并配置环境变量文件：

```bash
cd api
cp .env.example .env
```

编辑 `.env` 文件，配置数据库和JWT密钥：

```ini
[DATABASE]
DB_HOST = 127.0.0.1
DB_NAME = cms_database
DB_USER = root
DB_PASS = your_database_password
DB_PORT = 3306
DB_CHARSET = utf8mb4

[JWT]
# 生成强随机密钥（必需！）
# 使用命令：php -r "echo base64_encode(random_bytes(32));"
JWT_SECRET = your_strong_random_secret_key_here
JWT_EXPIRE = 7200

[CORS]
# 开发环境允许的前端地址
CORS_ALLOWED_ORIGINS = http://localhost:5173,http://localhost:3000
```

> ⚠️ **安全警告**:
> - 必须设置强随机 JWT_SECRET，不能使用默认值
> - 生产环境请使用复杂的数据库密码

#### 2.4 导入数据库

按以下顺序导入SQL文件（从项目 `docs/` 目录）：

```bash
# 1. 创建数据库
mysql -u root -p -e "CREATE DATABASE cms_database DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. 导入基础设计（必需）
mysql -u root -p cms_database < docs/database_design.sql

# 3. 导入系统管理表（必需）
mysql -u root -p cms_database < docs/database_system.sql

# 4. 导入其他功能模块（可选）
mysql -u root -p cms_database < docs/database_template_theme.sql  # 模板主题
mysql -u root -p cms_database < docs/database_article_versions.sql  # 文章版本
mysql -u root -p cms_database < docs/database_topics.sql           # 专题管理
mysql -u root -p cms_database < docs/database_custom_fields_and_models.sql  # 自定义字段
mysql -u root -p cms_database < docs/database_links_and_ads.sql    # 友链和广告
mysql -u root -p cms_database < docs/database_sliders.sql          # 幻灯片
mysql -u root -p cms_database < docs/database_seo.sql              # SEO功能
```

> 💡 **提示**: 如需完整功能，建议导入所有SQL文件

#### 2.5 配置目录权限

```bash
# 确保以下目录可写
chmod -R 755 api/runtime
chmod -R 755 api/public/uploads
chmod -R 755 api/html  # 静态文件生成目录（在api目录下）
```

> 📁 **目录说明**:
> - `runtime/`: 框架运行时缓存
> - `public/uploads/`: 文件上传目录
> - `html/`: 静态化HTML文件目录（在api目录下，需手动创建）

#### 2.6 测试后端服务

```bash
# 在 api 目录下启动开发服务器
cd api
php think run

# 访问 http://localhost:8000/api 测试API
# 测试接口: http://localhost:8000/api/auth/login
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

### 4. 前后端联调验证

#### 4.1 测试登录功能

```bash
# 使用 curl 测试登录接口
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# 预期返回包含 token 的JSON响应
```

#### 4.2 访问管理后台

1. 打开浏览器访问: `http://localhost:5173`
2. 使用默认账号登录（见下文）
3. 检查各功能模块是否正常

#### 4.3 常见联调问题

**问题1: CORS跨域错误**

确保后端 `.env` 文件中配置了正确的 CORS_ALLOWED_ORIGINS：

```ini
CORS_ALLOWED_ORIGINS = http://localhost:5173,http://localhost:3000
```

**问题2: 401未授权错误**

检查：
- JWT_SECRET 是否正确配置
- token 是否已过期
- 请求头是否包含 Authorization

**问题3: 接口404错误**

确认：
- 后端服务是否启动（php think run）
- API基础地址是否正确（http://localhost:8000/api）
- 路由配置是否正确

### 5. 静态化功能配置

#### 5.1 创建静态文件目录和占位图

```bash
# 在 api 目录下创建 html 目录
cd api
mkdir -p html
mkdir -p html/assets/images/placeholder

# 设置写入权限
chmod -R 755 html
```

**占位图文件**：

系统已内置本地占位图（SVG格式），无需依赖外部服务，位于：
- `api/html/assets/images/placeholder/article.svg` - 文章封面占位图
- `api/html/assets/images/placeholder/avatar.svg` - 用户头像占位图
- `api/html/assets/images/placeholder/dashboard.svg` - 仪表板占位图
- 其他占位图文件...

这些占位图会在模板渲染时自动使用，不需要额外配置。

#### 5.2 配置静态化路径

静态文件将生成到 `api/html/` 目录，目录结构：

```
api/html/
├── assets/        # 静态资源
│   └── images/
│       └── placeholder/  # 占位图
├── article/       # 文章静态页
│   ├── 1.html
│   └── ...
├── category/      # 分类静态页
│   ├── news.html
│   └── ...
├── page/          # 单页静态页
├── index.html     # 首页
└── sitemap.xml    # 站点地图
```

#### 5.3 触发静态化

**方式1: 管理后台操作**
1. 登录后台
2. 进入 **内容管理** > **文章管理**
3. 编辑文章，点击"发布"或"生成静态页"按钮

**方式2: API调用**

```bash
# 生成指定文章的静态页
curl -X POST http://localhost:8000/api/articles/1/generate-static \
  -H "Authorization: Bearer YOUR_TOKEN"

# 批量生成所有文章静态页
curl -X POST http://localhost:8000/api/static/generate-all \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 5.4 访问静态页面

配置Nginx或其他Web服务器指向 `api/html/` 目录，即可通过浏览器访问静态页面。

示例Nginx配置：
```nginx
server {
    listen 80;
    server_name www.example.com;
    root /path/to/cms/api/html;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

### 6. 默认账号

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
