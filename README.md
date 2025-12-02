# 逍遥内容管理系统 (CarefreeCMS)

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/php-8.1+-green.svg)
![Vue](https://img.shields.io/badge/vue-3.5-brightgreen.svg)
![License](https://img.shields.io/badge/license-MIT-orange.svg)

逍遥内容管理系统（CarefreeCMS）是一款现代化、高性能的内容管理平台，采用前后端分离架构，集成106个AI模型实现智能文章生成，支持多站点管理、模板包系统和全面升级的媒体库。系统基于PHP 8.0+ 和 Vue 3 开发，提供静态页面生成、全文搜索、SEO优化等完整功能，特别适合构建个人博客、企业官网、新闻媒体、内容营销等各类站点。完整开源代码，MIT协议，免费无限商用，没有任何使用限制。

QQ群：113572201

[Demo](https://cmsdemo.qysj.cn/)

## 系统简介

逍遥内容管理系统（CarefreeCMS）v2.0.0 是一款功能强大、技术先进的内容管理平台，专为现代Web应用设计。系统采用前后端完全分离架构，后端基于ThinkPHP 8框架，前端使用Vue 3 + Vite构建，提供流畅的用户体验和优秀的开发体验。

**AI赋能内容创作**：集成18个主流AI提供商的106个先进模型，包括OpenAI GPT-5、Claude Opus 4.5、Google Gemini 3、百度文心ERNIE 5.0、智谱GLM-4.5等顶级AI模型。支持批量生成高质量文章、自定义写作风格、智能配置管理，大幅提升内容生产效率。

**多站点架构**：支持单一数据库管理多个独立站点，实现完全的数据隔离。通过自动站点过滤、全局查询作用域和统一的站点上下文管理，确保各站点数据安全隔离，每个站点拥有独立的SEO配置、模板设置和内容管理。

**灵活的模板系统**：全新的模板包系统支持安装和管理多个模板包，每个站点可选择不同模板，并提供三级优先级解析（站点覆盖 > 站点包 > 默认包）。内置14种完整模板文件，配置自动合并，完美支持静态生成和批量构建。

**强大的媒体库**：v2.0全面升级的媒体库系统，基于SHA256哈希实现文件去重，大幅节省存储空间。支持无限级分类、灵活标签、9种缩略图预设、3种水印模式、10+种在线图片编辑操作。完整的使用追踪和操作日志，让媒体管理更加专业。

**高性能静态生成**：一键生成纯静态HTML页面，访问速度快、SEO友好。支持多站点批量生成、自动生成、定时生成，根据模板包自动适配，满足各类部署需求。

**完善的功能体系**：全文搜索、高级搜索、权限管理、操作日志、SEO优化、评论系统、标签系统、分类管理、单页管理等功能一应俱全。系统经过大量实践检验，稳定可靠，适用于个人博客、企业官网、新闻门户、内容营销、知识库等各类应用场景。

### 核心特性

- 🤖 **AI文章生成** - 集成106个AI模型，支持批量生成高质量文章内容
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
- 🎨 **现代化UI** - 基于 Element Plus 的美观界面

## 📁 项目结构

```
carefreecms/
├── backend/                      # ThinkPHP 8 后端API服务
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
├── frontend/                     # Vue 3 后台管理界面
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
- PHP 8.0+
- ThinkPHP 8.0
- MySQL 8.0
- JWT 认证
- ThinkORM
- 106个AI模型集成（18个提供商）

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
- MySQL >= 5.7 (推荐 8.0+)
- Node.js >= 16.0
- Composer
- npm 或 yarn

## ✨ 核心功能模块

### 1. AI文章生成 ⭐ v2.0新增
- **AI模型库**：集成106个AI模型，18个主流提供商
  - OpenAI (GPT-5, GPT-5.1, O3, O4-mini)
  - Claude (Opus 4.5, Sonnet 4.5, Haiku 4.5)
  - Google (Gemini 3, Gemini 3 Pro, Gemini 3 Deep Think)
  - DeepSeek (V3.2-Exp, V3.1, R2)
  - 百度文心、智谱ChatGLM、字节豆包、月之暗面Kimi等
- **批量生成**：支持多主题批量生成文章
- **AI配置管理**：灵活配置API密钥、模型、参数
- **自定义参数**：文章长度、写作风格、自动发布等
- **任务管理**：启动、停止、进度追踪
- **生成记录**：详细的生成历史和Token统计

### 2. 文章管理
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

### 2. 多站点管理 ⭐ v2.0新增
- **数据隔离**：支持同一数据库管理多个独立站点
- **自动站点过滤**：查询自动添加 site_id 条件
- **灵活的站点切换**：提供明确语义的查询方法
- **统一的站点上下文**：通过中间件和应用容器管理
- **独立站点配置**：每个站点独立的SEO、模板等配置

### 3. 模板包系统 ⭐ v2.0新增
- **多模板包管理**：支持安装和管理多个模板包
- **站点级别模板**：每个站点可选择不同模板包
- **模板优先级**：站点覆盖 > 站点包 > 默认包
- **配置合并**：模板包默认配置 + 站点自定义配置
- **完整模板文件**：包含14个模板文件（布局、首页、列表、详情等）

### 4. 媒体库系统 ⭐ v2.0全面升级
- **文件去重**：基于SHA256哈希自动去重，节省存储空间
- **分类标签系统**：无限级分类 + 灵活标签管理
- **缩略图生成**：9种内置预设，支持自定义尺寸
- **水印处理**：文字/图片/平铺三种水印模式
- **在线图片编辑**：裁剪、旋转、缩放等10+种操作
- **AI图片生成**：集成AI模型生成图片
- **元数据提取**：自动提取EXIF信息
- **完整操作日志**：记录所有媒体操作历史

### 5. 分类管理
- 多级分类支持
- 分类排序
- 分类SEO设置
- 站点级分类隔离

### 6. 标签管理
- 标签增删改查
- 标签关联统计
- 站点级标签隔离

### 7. 页面管理
- 单页面管理（关于我们、联系我们等）
- 自定义模板选择

### 8. 用户管理（多角色）
- **超级管理员**: 拥有所有权限
- **管理员**: 拥有大部分管理权限
- **编辑**: 可以管理文章、分类、标签
- **作者**: 只能管理自己的文章

### 9. 评论管理
- 评论审核
- 评论回复
- 评论删除

### 10. SEO设置
- 每篇文章独立SEO设置
- 站点级SEO配置
- Sitemap生成
- Robots.txt管理
- URL重定向管理
- 404监控

### 11. 静态页面生成
- **手动生成**: 后台按钮点击生成
- **自动生成**: 文章发布/更新时自动生成
- **定时生成**: 定时任务批量生成
- **多站点生成**: 支持批量生成所有站点 ⭐ v2.0新增
- **生成范围**: 首页、列表页、详情页、栏目页、标签聚合页
- **模板包支持**: 根据站点选择的模板包生成 ⭐ v2.0新增
- **生成日志**: 记录每次生成的详细信息

### 12. 系统管理
- 缓存管理（支持File和Redis）
- 数据库管理
- 系统日志、登录日志、安全日志
- 操作日志审计

## 📊 数据库设计

v2.0.0 完整数据库包含 **92张表**，涵盖以下核心模块：

**核心内容管理**：
- `articles` - 文章表
- `categories` - 分类表（支持多站点）
- `tags` - 标签表（支持多站点）
- `pages` - 单页面表
- `comments` - 评论表

**多站点系统** ⭐ v2.0新增：
- `sites` - 站点表
- `site_template_config` - 站点模板配置表
- `site_template_overrides` - 站点模板覆盖表

**模板包系统** ⭐ v2.0新增：
- `template_packages` - 模板包表
- `templates` - 模板文件表

**媒体库系统** ⭐ v2.0全面升级：
- `media_library` - 媒体库表
- `media_files` - 媒体文件表（去重）
- `media_categories` - 媒体分类表
- `media_tags` - 媒体标签表
- `media_thumbnail_presets` - 缩略图预设表
- `media_watermark_presets` - 水印预设表
- `media_usage_records` - 媒体使用记录表
- `media_operation_logs` - 媒体操作日志表

**AI系统** ⭐ v2.0新增：
- `ai_providers` - AI提供商表（18个）
- `ai_models` - AI模型表（106个）
- `ai_configs` - AI配置表
- `ai_prompt_templates` - AI提示词模板表
- `ai_article_tasks` - AI文章生成任务表
- `ai_article_records` - AI文章生成记录表

**用户权限**：
- `admin_users` - 管理员用户表
- `admin_roles` - 角色表
- `admin_role_permissions` - 角色权限关联表

**系统管理**：
- `site_config` - 站点配置表
- `static_build_log` - 静态生成日志表
- `admin_logs` - 操作日志表
- `system_logs` - 系统日志表
- `login_logs` - 登录日志表
- `security_logs` - 安全日志表

详见 `database_design.sql` 文件。

## 📖 文档

完整的技术文档请查看：[文档索引](DOCUMENTATION_INDEX.md)

**快速链接：**
- [完整部署指南](docs/deployment/DEPLOY.md) - 生产环境部署详细步骤
- [后端环境配置](docs/deployment/backend-env.md) - .env 配置说明
- [前端环境配置](docs/deployment/frontend-env.md) - 环境变量配置
- [API 接口文档](docs/api/API_DOCUMENTATION.md) - 完整的 API 接口说明
- [开发指南](docs/development/DEVELOPER_GUIDE.md) - 开发规范和最佳实践
- [权限系统文档](PERMISSION_SYSTEM_COMPLETE.md) - 权限系统完整说明
- [模板开发指南](docs/features/template/TEMPLATE_DEVELOPMENT_GUIDE.md) - 模板开发教程
- [Carefree标签库](docs/carefree-taglib/CAREFREE_QUICK_START.md) - 标签库快速入门

## 安装部署

### 1. 克隆项目

```bash
git clone https://github.com/carefree-code/CarefreeCMS.git

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
cd frontend

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
cd frontend
npm run build
```

#### 后端配置
- 配置 Nginx 或 Apache 指向 `backend/public` 目录
- 复制 `.env.production` 为 `.env` 并修改配置
- 确保 `runtime` 和 `public/uploads` 目录可写

更多细节请参考：[完整部署文档](docs/deployment/DEPLOY.md)

## 默认账号

- 用户名: `admin`
- 密码: `admin123`

**⚠️ 首次登录后请立即修改密码！**

## API 文档

后端 API 采用 RESTful 风格设计，所有接口都需要 JWT Token 认证（登录接口除外）。

**完整的 API 文档请查看：[API 接口文档](docs/api/API_DOCUMENTATION.md)**

### 核心接口

**用户认证**：
- `POST /api/auth/login` - 用户登录

**文章管理**：
- `GET /api/articles` - 文章列表
- `POST /api/articles` - 创建文章
- `GET /api/articles/fulltext-search` - 全文搜索 ⭐ v1.2新增
- `GET /api/articles/advanced-search` - 高级搜索 ⭐ v1.2新增
- `GET /api/articles/search-suggestions` - 搜索建议 ⭐ v1.2新增

**AI文章生成** ⭐ v2.0新增：
- `GET /api/ai-configs/providers` - 获取AI提供商列表
- `POST /api/ai-configs` - 创建AI配置
- `POST /api/ai-configs/:id/test` - 测试AI连接
- `POST /api/ai-article-tasks` - 创建生成任务
- `POST /api/ai-article-tasks/:id/start` - 启动任务
- `POST /api/ai-article-tasks/:id/stop` - 停止任务

**多站点管理** ⭐ v2.0新增：
- `GET /api/sites` - 站点列表
- `POST /api/sites` - 创建站点
- `PUT /api/sites/:id` - 更新站点
- `GET /api/sites/:id/template-config` - 获取站点模板配置

**模板包管理** ⭐ v2.0新增：
- `GET /api/template-packages` - 模板包列表
- `POST /api/template-packages` - 安装模板包
- `GET /api/template-packages/:id/templates` - 获取模板包文件列表

**媒体库** ⭐ v2.0全面升级：
- `POST /api/media/upload` - 文件上传（支持去重）
- `GET /api/media` - 媒体列表（支持分类、标签筛选）
- `POST /api/media/:id/thumbnail` - 生成缩略图
- `POST /api/media/:id/watermark` - 添加水印
- `POST /api/media/:id/edit` - 在线编辑图片
- `POST /api/ai-image/generate` - AI生成图片 ⭐ v2.0新增

**其他**：
- `GET /api/categories/tree` - 分类树
- `POST /api/build/all-sites` - 批量生成所有站点 ⭐ v2.0新增

## 常见问题

### 1. 后端接口无法访问？
检查后端服务是否启动，确保运行在 8000 端口。

### 2. 前端无法登录？
检查 `frontend/src/utils/request.js` 中的 `baseURL` 配置是否正确。

### 3. 上传文件失败？
确保 `backend/public/uploads` 目录存在且有写入权限。

### 4. 静态生成失败？
确保 `backend/public/static` 目录存在且有写入权限。

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

### v2.0.0 (2025-12-01)

**重大更新：AI模型库全面升级 + 关键Bug修复** 🎉

本次更新包含AI模型库的全面升级和5个关键Bug的修复，大幅提升系统的AI能力和稳定性。

**🌟 核心功能更新：**

1. **✅ AI模型库全面升级**（重大更新）
   - 新增4个国际顶级AI厂商（Meta、Mistral AI、xAI、Cohere）
   - 更新10个主流厂商的最新模型
   - 模型总数：106个，活跃模型：91个
   - 新增旗舰模型：
     - xAI Grok 4.1 Thinking（LMArena排名#1）
     - Claude Opus 4.5（代码能力世界第一）
     - OpenAI GPT-5 / GPT-5.1
     - Google Gemini 3 Deep Think
     - 百度 ERNIE 5.0 Preview（原生全模态）
     - 智谱 GLM-4.5（全球第三、开源第一）
     - 字节 Doubao Seed 1.6
     - 月之暗面 Kimi K2 Thinking（开源SOTA）
     - 讯飞 Spark X1.5（全国产算力）
     - MiniMax M2、Meta Llama 4 Scout等
   - 技术亮点：
     - 超长上下文：Llama 4（10M）、MiniMax-01（4M）、Gemini 3（2M）
     - 线性注意力：MiniMax Linear、Kimi Linear（速度6倍提升）
     - 原生全模态：ERNIE 5.0、Gemini 3 Pro
     - MoE架构：Llama 4（400B）、ERNIE 4.5（424B）、GLM-4.5（355B）

**🐛 关键Bug修复：**

2. **✅ SiteModel批量删除Bug修复**（高危修复）
   - 修复使用`$model->delete()`误删整站数据的严重bug
   - 影响11个控制器：AI配置、AI提示词、广告位、SEO日志等
   - 采用安全删除模式：`Db::name()->where('id', $id)->limit(1)->delete()`
   - 防止用户误删一条记录导致整站数据丢失

3. **✅ 分类删除clearCacheTag错误修复**
   - 修复`method not exist:think\db\Query->clearCacheTag`错误
   - 将Cacheable trait中的clearCacheTag方法改为public

4. **✅ 文章媒体使用追踪修复**
   - 修复文章删除时媒体使用检测不准确问题
   - 优化URL格式匹配（完整URL vs 路径）
   - 修正单URL字段的处理逻辑

5. **✅ 专题API重构为RESTful规范**
   - 重构5个专题相关API为标准RESTful风格
   - 修复ThinkPHP 8 JSON字段自动转换导致的bug
   - 提升API设计规范性和可维护性

**📚 文档更新：**

6. **✅ 新增完整技术文档** - 详见 [docs/updates/v2.0.0/](docs/updates/v2.0.0/)
   - AI模型库完整更新报告_2025年12月.md
   - SiteModel批量删除bug全面修复报告.md
   - 分类删除clearCacheTag错误修复报告.md
   - 文章媒体使用追踪修复报告.md
   - 专题API重构为RESTful规范报告.md
   - 2025年12月系统更新总结.md
   - 文档整理总结.md（新增）

7. **✅ 文档结构重组**
   - 所有文档统一整理到 `docs/` 文件夹
   - 创建清晰的分类体系（api、backend、frontend、features等20个子目录）
   - 完全重写 DOCUMENTATION_INDEX.md，提供完整的文档导航
   - 清理过期和重复文档14个
   - 详见：[文档整理总结](docs/updates/v2.0.0/文档整理总结.md)

**技术改进：**
- 代码质量：修改18个文件，约935行代码
- 数据库：新增4个AI提供商，48个新模型，清理36个重复记录
- 安全性：防止批量误删，提升数据安全
- 规范性：API设计符合RESTful标准

**升级说明：**
- 需执行3个SQL脚本更新AI模型库
- 代码完全兼容，无需修改现有功能
- 建议升级以获得最新AI能力和修复关键bug

详见：[2025年12月系统更新总结 v2.0.0](docs/updates/v2.0.0/2025年12月系统更新总结.md)

---

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

**模板系统增强：**

12. **✅ Carefree标签库 V1.6** - 全面支持变量参数 🆕
   - 9个核心标签支持变量参数（article, category, link, slider, related等）
   - 支持动态数据查询：`typeid='$category.id'`, `tagid='$tag.id'`
   - 完美适配分类页、标签页、文章详情等动态场景
   - 100%向后兼容，无需修改现有模板代码
   - 详见：[Carefree标签库V1.6文档](docs/carefree-taglib/CAREFREE_TAGLIB_V1.6.md)

13. **✅ Config标签修复** - 修复配置标签无法读取数据问题 🆕
   - 修正 ConfigTagService 使用错误的模型和字段名
   - 更新所有文档中的配置键名（web_name → site_name 等）
   - 添加完整的配置项列表和使用说明
   - 更新文档：CAREFREE_TAGLIB_GUIDE.md、CAREFREE_QUICK_REFERENCE.md、CAREFREE_TROUBLESHOOTING.md
   - 配置数据支持1小时缓存，提升访问性能

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

- 本项目自开发代码采用 MIT 开源协议。详见 [LICENSE](./LICENSE) 文件。
- 本项目引用其他项目的代码，遵循引用项目的开源协议。
- 例如 ThinkPHP遵循Apache2开源协议

## 联系我们

- **官网**: https://www.carefreecms.com
- **问题反馈**: https://github.com/carefree-code/CarefreeCMS/issues
- **邮箱**: sinma@qq.com

## 致谢

感谢以下开源项目：

- [ThinkPHP](https://www.thinkphp.cn/)
- [Vue.js](https://vuejs.org/)
- [Element Plus](https://element-plus.org/)
- [TinyMCE](https://www.tiny.cloud/)

---

Made with ❤️ by CarefreeCMS Team © 2025
![QQ群](/readme/pic/qqqun.jpg)