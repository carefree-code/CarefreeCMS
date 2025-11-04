# 逍遥内容管理系统 (CarefreeCMS)

![Version](https://img.shields.io/badge/version-1.3.0-blue.svg)
![PHP](https://img.shields.io/badge/php-8.1+-green.svg)
![Vue](https://img.shields.io/badge/vue-3.5-brightgreen.svg)
![License](https://img.shields.io/badge/license-MIT-orange.svg)

一个现代化、轻量级的内容管理系统，专为快速构建静态网站而设计。

## 系统简介

逍遥内容管理系统（CarefreeCMS）是一款功能强大、易于使用的内容管理平台，采用前后端分离架构，支持静态页面生成，适用于个人博客、企业网站、新闻媒体等各类内容发布场景。

### 核心特性

- 🎨 **模板套装系统** - 支持多套模板自由切换，快速定制网站风格
- ⚡ **静态页面生成** - 一键生成纯静态HTML页面，访问速度快，SEO友好
- 📝 **文章管理** - 支持富文本编辑、草稿保存、文章属性标记、自动提取SEO
- 🔎 **全文搜索** - 基于MySQL FULLTEXT的高性能搜索，支持三种搜索模式
- 🔍 **高级搜索** - 多字段组合查询，支持15+个搜索条件和智能排序
- 📂 **分类管理** - 树形结构分类，支持自定义模板
- 🏷️ **标签系统** - 灵活的标签体系，方便内容组织
- 📄 **单页管理** - 独立页面管理，支持封面图和SEO自动提取
- 🖼️ **媒体库** - 统一媒体文件管理，支持按类型和日期查询
- 🔐 **权限管理** - 基于角色的访问控制（RBAC）
- 👥 **用户管理** - 多用户系统，支持用户角色分配
- 🔍 **SEO优化** - 自动提取TDK、Sitemap生成
- 📊 **操作日志** - 详细的用户操作审计记录，支持批量删除
- 🎨 **现代化UI** - 基于 Element Plus的美观界面

## 📁 项目结构

```
carefreecms/
├── api/                          # ThinkPHP 8 后端API服务
│   ├── app/                      # 应用目录
│   │   ├── controller/          # 控制器
│   │   ├── model/               # 模型
│   │   ├── validate/            # 验证器
│   │   ├── middleware/          # 中间件
│   │   └── service/             # 服务层
│   ├── config/                   # 配置文件
│   ├── public/                   # 入口文件和静态资源
│   ├── templates/                # 静态页面模板
│   │   └── default/             # 默认模板
│   │       ├── index.html       # 首页模板
│   │       ├── article.html     # 文章详情模板
│   │       ├── category.html    # 分类列表模板
│   │       └── page.html        # 单页面模板
│   ├── html/                     # 生成的静态文件
│   │   ├── index.html           # 首页
│   │   ├── article/             # 文章详情页
│   │   ├── category/            # 分类列表页
│   │   └── page/                # 单页面
│   ├── vendor/                   # Composer依赖
│   ├── composer.json
│   └── .env                      # 环境配置
│
├── backend/                      # Vue 3 后台管理界面
│   ├── src/
│   │   ├── api/                 # API接口封装
│   │   ├── assets/              # 静态资源
│   │   ├── components/          # 公共组件
│   │   ├── views/               # 页面视图
│   │   ├── router/              # 路由配置
│   │   ├── store/               # Pinia状态管理
│   │   ├── utils/               # 工具函数
│   │   ├── App.vue
│   │   └── main.js
│   ├── public/
│   ├── package.json
│   └── vite.config.js
│
├── database_design.sql           # 数据库设计文件
└── README.md                     # 项目说明文档
```

## 🚀 技术栈

### 后端
- PHP 8.2+
- ThinkPHP 8.0
- MySQL 8.0
- JWT 认证
- ThinkORM

### 前端
- Vue 3 (Composition API)
- Vite 7
- Element Plus
- Vue Router 4
- Pinia
- Axios
- TinyMCE (富文本编辑器)

## 环境要求

- PHP >= 8.0
- MySQL >= 5.7
- Node.js >= 16.0
- Composer
- npm 或 yarn

## ✨ 核心功能模块

### 1. 文章管理
- 文章的增删改查
- 文章分类、标签管理
- 文章置顶、推荐、热门标记
- 富文本编辑器（TinyMCE）
- 图片上传和管理
- **全文搜索**：支持自然语言、布尔、查询扩展三种模式
- **高级搜索**：多字段组合查询（标题、内容、作者、分类、标签等）
- **搜索建议**：实时自动完成，显示浏览量统计
- **搜索历史**：自动保存，一键重用
- **关键词高亮**：搜索结果自动高亮匹配关键词
- SEO自动提取和自定义设置

### 2. 分类管理
- 多级分类支持
- 分类排序
- 分类SEO设置

### 3. 标签管理
- 标签增删改查
- 标签关联统计

### 4. 页面管理
- 单页面管理（关于我们、联系我们等）
- 自定义模板选择

### 5. 用户管理（多角色）
- **超级管理员**: 拥有所有权限
- **管理员**: 拥有大部分管理权限
- **编辑**: 可以管理文章、分类、标签
- **作者**: 只能管理自己的文章

### 6. 评论管理
- 评论审核
- 评论回复
- 评论删除

### 7. 媒体库
- 图片、文件上传
- 媒体文件管理
- 多种存储方式支持

### 8. SEO设置
- 每篇文章独立SEO设置
- 全站SEO配置

### 9. 站点配置
- 网站基础信息
- 上传配置
- 模板配置

### 10. 模板管理
- 多套模板支持
- 模板切换

### 11. 静态页面生成
- **手动生成**: 后台按钮点击生成
- **自动生成**: 文章发布/更新时自动生成
- **定时生成**: 定时任务批量生成
- **生成范围**: 首页、列表页、详情页、栏目页、标签聚合页
- **生成日志**: 记录每次生成的详细信息

## 📊 数据库设计

共13张表：
1. `admin_users` - 管理员用户表
2. `admin_roles` - 角色表
3. `categories` - 分类表
4. `tags` - 标签表
5. `articles` - 文章表
6. `article_tags` - 文章标签关联表
7. `pages` - 单页面表
8. `comments` - 评论表
9. `media` - 媒体库表
10. `site_config` - 站点配置表
11. `templates` - 模板管理表
12. `static_build_log` - 静态页面生成日志表
13. `admin_logs` - 操作日志表

详见 `database_design.sql` 文件。

## 📖 文档

完整的技术文档请查看：[文档中心](README.md)

**快速链接：**
- [完整部署指南](docs/deployment/DEPLOY.md) - 生产环境部署详细步骤
- [后端环境配置](docs/deployment/backend-env.md) - .env 配置说明
- [前端环境配置](docs/deployment/frontend-env.md) - 环境变量配置
- [API 接口文档](docs/api/API.md) - 完整的 API 接口说明
- [前端开发指南](docs/development/frontend-guide.md) - 前端开发规范
- [权限管理指南](docs/development/permissions-guide.md) - 权限系统使用

## 安装部署

### 1. 克隆项目

```bash
git clone https://gitee.com/carefreeteam/carefreecms.git

```

### 2. 后端配置

```bash
# 进入后端目录
cd carefreecms

# 安装依赖
composer install

# 配置数据库
# 编辑 config/database.php 文件，设置数据库连接信息

# 导入数据库
# 将 database.sql 导入到 MySQL 数据库

# 启动开发服务器
php think run -p8000
```

后端服务将运行在 `http://localhost:8000`

### 3. 前端配置

```bash
# 进入前端目录
cd backend

# 安装依赖
npm install

# 启动开发服务器
npm run dev
```

前端服务将运行在 `http://localhost:3000`

### 4. 生产部署

**详细的生产环境部署指南，请查看：[完整部署文档](docs/deployment/DEPLOY.md)**

快速步骤：

#### 前端构建
```bash
cd backend
npm run build
```

#### 后端配置
- 配置 Nginx 或 Apache 指向 `api/public` 目录
- 复制 `.env.production` 为 `.env` 并修改配置
- 确保 `runtime` 和 `public/uploads` 目录可写

更多细节请参考：[完整部署文档](docs/deployment/DEPLOY.md)

## 默认账号

- 用户名: `admin`
- 密码: `admin123`

**⚠️ 首次登录后请立即修改密码！**

## API 文档

后端 API 采用 RESTful 风格设计，所有接口都需要 JWT Token 认证（登录接口除外）。

**完整的 API 文档请查看：[API 接口文档](docs/api/API.md)**

常用接口：
- `POST /api/auth/login` - 用户登录
- `GET /api/articles` - 文章列表
- `POST /api/articles` - 创建文章
- `GET /api/articles/fulltext-search` - 全文搜索 ⭐ 新增
- `GET /api/articles/advanced-search` - 高级搜索 ⭐ 新增
- `GET /api/articles/search-suggestions` - 搜索建议 ⭐ 新增
- `GET /api/categories/tree` - 分类树
- `POST /api/media/upload` - 文件上传

## 常见问题

### 1. 后端接口无法访问？
检查后端服务是否启动，确保运行在 8000 端口。

### 2. 前端无法登录？
检查 `backend/src/utils/request.js` 中的 `baseURL` 配置是否正确。

### 3. 上传文件失败？
确保 `api/public/uploads` 目录存在且有写入权限。

### 4. 静态生成失败？
确保 `api/public/static` 目录存在且有写入权限。

### 5. 全文搜索无结果？
检查：
- 确保后端数据库已创建 FULLTEXT INDEX（默认已创建）
- 搜索关键词长度（英文词至少4个字符）
- 确认有已发布的文章（status=1）
- 查看浏览器控制台确认API请求成功

### 6. 高级搜索不工作？
确保：
- 至少填写一个搜索条件
- 检查后端API是否正常响应
- 分类和标签数据已正确加载

## 更新日志

### v1.3.0 (2025-11-04)

**重大更新：系统稳定性全面提升** 🎉

本次更新修复了11个关键问题，大幅提升系统的稳定性、易用性和功能完整性。

**核心功能修复：**

1. **✅ 日志系统完善**
   - 修复系统日志、登录日志、安全日志无内容问题
   - 新增SystemLog中间件，自动记录所有API请求
   - 完善登录/登出事件的日志记录机制
   - 添加安全事件监控（失败登录、异常访问等）

2. **✅ 权限管理增强**
   - 补充所有新增功能的权限定义（从177行扩展到450+行）
   - 新增内容管理权限：文章属性、专题、友情链接、内容模型、自定义字段、回收站
   - 新增SEO管理权限：SEO设置、URL重定向、404监控、Robots.txt、SEO工具
   - 新增系统管理权限：数据库管理、缓存管理、系统日志、操作日志
   - 新增扩展功能权限：广告、幻灯片、会员、投稿、通知、短信、积分商城等
   - 新增模板管理权限：模板编辑器、模板标签教程

3. **✅ 缓存管理优化**
   - 修复切换缓存驱动后信息显示错误问题
   - 清除配置缓存确保驱动切换立即生效
   - 优化前端自动刷新逻辑
   - 完善Redis和File缓存统计信息显示

**用户体验优化：**

4. **✅ 投稿配置修复** - 修复分类下拉列表无法加载问题
5. **✅ 广告管理增强** - 新增快捷调用代码功能，一键复制Carefree标签
6. **✅ 幻灯片管理增强** - 新增分组快捷调用代码，包含完整HTML示例
7. **✅ 媒体库改进** - 添加全选/取消全选按钮，批量操作更便捷
8. **✅ 会员管理完善** - 会员列表新增VIP到期时间列，支持永久VIP标识
9. **✅ 消息通知修复** - 修复通知记录不显示问题，添加自动加载机制
10. **✅ 短信服务修复** - 修复统计数据显示错误，支持嵌套对象访问

**代码质量提升：**

11. **✅ 常量定义修复** - 修复`MODULE_SYSTEM`未定义错误，规范常量管理

**技术改进：**
- 使用双日志系统：Logger（操作日志） + SystemLogger（系统/登录/安全日志）
- 中间件自动化：SystemLog中间件拦截所有API请求并记录
- 敏感信息保护：自动过滤日志中的密码、token等敏感参数
- 慢请求监控：自动标记执行时间超过1000ms的请求
- 配置缓存管理：驱动切换时自动清除runtime配置缓存
- 前端状态同步：使用localStorage在页面刷新间传递状态

**文档更新：**
- 新增 `问题修复总结.md` - 详细记录所有11个问题的修复过程
- 完善项目文档结构
- 更新版本号至1.3.0

**升级说明：**
本次更新不涉及数据库结构变更，现有数据完全兼容。建议所有用户升级到此版本以获得更好的稳定性和完整功能。

---

### v1.2.0 (2025-10-28)

**重大更新：全文搜索和高级搜索功能** 🎉

**后端更新：**
- ✨ 新增全文搜索功能（基于MySQL FULLTEXT INDEX）
  - 支持自然语言模式（按相关度排序）
  - 支持布尔模式（+word -word "phrase"等操作符）
  - 支持查询扩展模式（自动扩展相关词汇）
  - 搜索结果自动高亮关键词
- ✨ 新增高级搜索功能
  - 支持15+个搜索字段和筛选条件
  - 支持标题、内容、摘要、作者等多字段查询
  - 支持分类、标签、状态等多维度筛选
  - 支持浏览量范围筛选
  - 支持多种排序方式（发布时间、浏览量、点赞数、评论数等）
- ✨ 新增搜索建议API（自动完成功能）
- 📝 新增3个搜索相关API接口
  - `/api/articles/fulltext-search` - 全文搜索
  - `/api/articles/advanced-search` - 高级搜索
  - `/api/articles/search-suggestions` - 搜索建议

**前端更新：**
- ✨ 新增 `AdvancedSearch.vue` 高级搜索对话框组件
  - 美观的双标签页布局（全文搜索/高级搜索）
  - 实时搜索建议/自动完成
  - 搜索历史记录功能（localStorage存储，最多10条）
  - 支持删除单条历史或清空全部
- ✨ 更新文章列表页面，集成高级搜索功能
  - 搜索结果关键词高亮显示（黄色背景标记）
  - 显示当前搜索条件和结果数量
  - 一键清除搜索返回普通列表
- 🎨 优化搜索用户体验
  - 智能表单验证
  - 友好的错误提示
  - 流畅的交互动画


**其他优化：**
- ✨ 新增媒体库选择器组件，支持从媒体库插入文件到文章编辑器
- ✨ 优化Sitemap生成页面布局，基础格式和高级类型并排显示
- 🐛 修复分类和标签模板中的分页代码错误
- 🐛 修复文章模板中categories字段引用错误

### v1.1.0 (2025-10-21)
- ✨ 新增缓存驱动切换功能，支持File和Redis两种驱动
- ✨ 优化Sitemap生成界面，合并基础格式和高级类型为单页
- ✨ TinyMCE编辑器优化：移除帮助功能，工具栏改为2行布局
- ✨ 增强缓存管理：支持Redis连接测试和实时驱动切换
- 🐛 修复API路由404错误
- 🐛 优化PHP Redis扩展检测和错误提示

### v1.0.0 (2025-10-15)
- 🎉 首个正式版本发布
- ✨ 完整的内容管理功能
- ✨ 用户权限管理系统
- ✨ 媒体文件管理
- ✨ SEO优化功能
- ✨ 操作日志记录
- 🐛 修复已知问题

## 许可证

本项目采用 MIT 开源协议。详见 [LICENSE](./LICENSE) 文件。

## 联系我们

- **官网**: https://www.carefreecms.com
- **问题反馈**: https://gitee.com/carefreeteam/issues
- **邮箱**: sinma@qq.com

## 致谢

感谢以下开源项目：

- [ThinkPHP](https://www.thinkphp.cn/)
- [Vue.js](https://vuejs.org/)
- [Element Plus](https://element-plus.org/)
- [TinyMCE](https://www.tiny.cloud/)

---

Made with ❤️ by CarefreeCMS Team © 2025
