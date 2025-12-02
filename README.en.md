# CarefreeCMS - Content Management System

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/php-8.1+-green.svg)
![Vue](https://img.shields.io/badge/vue-3.5-brightgreen.svg)
![License](https://img.shields.io/badge/license-MIT-orange.svg)

CarefreeCMS is a modern, high-performance content management platform featuring a decoupled frontend-backend architecture. It integrates 106 AI models for intelligent article generation, supports multi-site management, template package system, and a comprehensively upgraded media library. Built with PHP 8.0+ and Vue 3, the system provides static page generation, full-text search, SEO optimization, and complete functionality, making it ideal for personal blogs, corporate websites, news media, content marketing, and various other sites. Fully open-source code under MIT license, free for unlimited commercial use without any restrictions.

QQ Group: 113572201

[Demo](https://cmsdemo.qysj.cn/)

## System Overview

CarefreeCMS v2.0.0 is a powerful and technologically advanced content management platform designed for modern web applications. The system adopts a fully decoupled frontend-backend architecture, with the backend based on ThinkPHP 8 framework and the frontend built with Vue 3 + Vite, providing smooth user experience and excellent developer experience.

**AI-Powered Content Creation**: Integrates 106 advanced models from 18 mainstream AI providers, including OpenAI GPT-5, Claude Opus 4.5, Google Gemini 3, Baidu ERNIE 5.0, Zhipu GLM-4.5, and other top-tier AI models. Supports batch generation of high-quality articles, customizable writing styles, and intelligent configuration management, significantly improving content production efficiency.

**Multi-Site Architecture**: Supports managing multiple independent sites within a single database with complete data isolation. Through automatic site filtering, global query scopes, and unified site context management, ensures secure data isolation for each site, with each site having independent SEO configuration, template settings, and content management.

**Flexible Template System**: The new template package system supports installing and managing multiple template packages, with each site able to choose different templates and providing three-level priority resolution (Site Override > Site Package > Default Package). Includes 14 complete template files, automatic configuration merging, and perfect support for static generation and batch building.

**Powerful Media Library**: v2.0 comprehensively upgraded media library system, implementing file deduplication based on SHA256 hash, significantly saving storage space. Supports unlimited hierarchical categories, flexible tags, 9 thumbnail presets, 3 watermark modes, and 10+ online image editing operations. Complete usage tracking and operation logs make media management more professional.

**High-Performance Static Generation**: One-click generation of pure static HTML pages, fast access speed, SEO-friendly. Supports multi-site batch generation, automatic generation, scheduled generation, and automatic adaptation according to template packages to meet various deployment requirements.

**Complete Feature System**: Full-text search, advanced search, permission management, operation logs, SEO optimization, comment system, tag system, category management, single page management, and other functions are all available. The system has been tested extensively in practice, is stable and reliable, and is suitable for various application scenarios such as personal blogs, corporate websites, news portals, content marketing, and knowledge bases.

### Core Features

- 🤖 **AI Article Generation** - Integrates 106 AI models, supports batch generation of high-quality article content
- 🎨 **Template Package System** - Supports free switching between multiple templates, quick customization of website styles
- ⚡ **Static Page Generation** - One-click generation of pure static HTML pages, fast access speed, SEO-friendly
- 📝 **Article Management** - Supports rich text editing, draft saving, article attribute marking, automatic SEO extraction
- 🔎 **Full-Text Search** - High-performance search based on MySQL FULLTEXT, supports three search modes
- 🔍 **Advanced Search** - Multi-field combined query, supports 15+ search conditions and intelligent sorting
- 📂 **Category Management** - Tree structure categories, supports custom templates
- 🏷️ **Tag System** - Flexible tag system, convenient content organization
- 📄 **Single Page Management** - Independent page management, supports cover images and automatic SEO extraction
- 🖼️ **Media Library** - Unified media file management, supports filtering by type and date
- 🔐 **Permission Management** - Role-Based Access Control (RBAC)
- 👥 **User Management** - Multi-user system, supports user role assignment
- 🔍 **SEO Optimization** - Automatic TDK extraction, Sitemap generation
- 📊 **Operation Logs** - Detailed user operation audit records, supports batch deletion
- 🎨 **Modern UI** - Beautiful interface based on Element Plus

## 📁 Project Structure

```
carefreecms/
├── backend/                      # ThinkPHP 8 Backend API Service
│   ├── app/                      # Application Directory
│   │   ├── controller/          # Controllers
│   │   ├── model/               # Models
│   │   ├── validate/            # Validators
│   │   ├── middleware/          # Middleware
│   │   └── service/             # Service Layer
│   ├── config/                   # Configuration Files
│   ├── public/                   # Entry Files and Static Resources
│   ├── templates/                # Static Page Templates
│   │   └── default/             # Default Template
│   │       ├── index.html       # Homepage Template
│   │       ├── article.html     # Article Detail Template
│   │       ├── category.html    # Category List Template
│   │       └── page.html        # Single Page Template
│   ├── html/                     # Generated Static Files
│   │   ├── index.html           # Homepage
│   │   ├── article/             # Article Detail Pages
│   │   ├── category/            # Category List Pages
│   │   └── page/                # Single Pages
│   ├── vendor/                   # Composer Dependencies
│   ├── composer.json
│   └── .env                      # Environment Configuration
│
├── frontend/                     # Vue 3 Admin Interface
│   ├── src/
│   │   ├── api/                 # API Interface Wrapper
│   │   ├── assets/              # Static Assets
│   │   ├── components/          # Common Components
│   │   ├── views/               # Page Views
│   │   ├── router/              # Router Configuration
│   │   ├── store/               # Pinia State Management
│   │   ├── utils/               # Utility Functions
│   │   ├── App.vue
│   │   └── main.js
│   ├── public/
│   ├── package.json
│   └── vite.config.js
│
├── database_design.sql           # Database Design File
└── README.md                     # Project Documentation
```

## 🚀 Tech Stack

### Backend
- PHP 8.0+
- ThinkPHP 8.0
- MySQL 8.0
- JWT Authentication
- ThinkORM
- 106 AI Models Integration (18 Providers)

### Frontend
- Vue 3 (Composition API)
- Vite 7
- Element Plus
- Vue Router 4
- Pinia
- Axios
- TinyMCE (Rich Text Editor)

## System Requirements

- PHP >= 8.0
- MySQL >= 5.7 (Recommended 8.0+)
- Node.js >= 16.0
- Composer
- npm or yarn

## ✨ Core Functional Modules

### 1. AI Article Generation ⭐ v2.0 New
- **AI Model Library**: Integrates 106 AI models from 18 mainstream providers
  - OpenAI (GPT-5, GPT-5.1, O3, O4-mini)
  - Claude (Opus 4.5, Sonnet 4.5, Haiku 4.5)
  - Google (Gemini 3, Gemini 3 Pro, Gemini 3 Deep Think)
  - DeepSeek (V3.2-Exp, V3.1, R2)
  - Baidu ERNIE, Zhipu ChatGLM, ByteDance Doubao, Moonshot Kimi, etc.
- **Batch Generation**: Supports multi-topic batch article generation
- **AI Configuration Management**: Flexible configuration of API keys, models, and parameters
- **Custom Parameters**: Article length, writing style, auto-publishing, etc.
- **Task Management**: Start, stop, progress tracking
- **Generation Records**: Detailed generation history and token statistics

### 2. Article Management
- Article CRUD operations
- Article categories and tag management
- Article pinning, recommendation, and hot marking
- Rich text editor (TinyMCE)
- Image upload and management
- **Full-Text Search**: Supports natural language, boolean, and query expansion modes
- **Advanced Search**: Multi-field combined query (title, content, author, category, tags, etc.)
- **Search Suggestions**: Real-time auto-complete, displays view count statistics
- **Search History**: Auto-save, one-click reuse
- **Keyword Highlighting**: Auto-highlight matching keywords in search results
- Automatic SEO extraction and custom settings

### 2. Multi-Site Management ⭐ v2.0 New
- **Data Isolation**: Supports managing multiple independent sites in the same database
- **Automatic Site Filtering**: Queries automatically add site_id conditions
- **Flexible Site Switching**: Provides clearly semantic query methods
- **Unified Site Context**: Managed through middleware and application containers
- **Independent Site Configuration**: Each site has independent SEO, template, and other configurations

### 3. Template Package System ⭐ v2.0 New
- **Multi-Template Package Management**: Supports installing and managing multiple template packages
- **Site-Level Templates**: Each site can choose different template packages
- **Template Priority**: Site Override > Site Package > Default Package
- **Configuration Merging**: Template package default configuration + site custom configuration
- **Complete Template Files**: Includes 14 template files (layout, homepage, list, detail, etc.)

### 4. Media Library System ⭐ v2.0 Comprehensive Upgrade
- **File Deduplication**: Automatic deduplication based on SHA256 hash, saving storage space
- **Category & Tag System**: Unlimited hierarchical categories + flexible tag management
- **Thumbnail Generation**: 9 built-in presets, supports custom dimensions
- **Watermark Processing**: Text/Image/Tile three watermark modes
- **Online Image Editing**: Crop, rotate, scale, and 10+ operations
- **AI Image Generation**: Integrates AI models to generate images
- **Metadata Extraction**: Automatically extracts EXIF information
- **Complete Operation Logs**: Records all media operation history

### 5. Category Management
- Multi-level category support
- Category sorting
- Category SEO settings
- Site-level category isolation

### 6. Tag Management
- Tag CRUD operations
- Tag association statistics
- Site-level tag isolation

### 7. Page Management
- Single page management (About Us, Contact Us, etc.)
- Custom template selection

### 8. User Management (Multi-Role)
- **Super Administrator**: Has all permissions
- **Administrator**: Has most management permissions
- **Editor**: Can manage articles, categories, and tags
- **Author**: Can only manage own articles

### 9. Comment Management
- Comment review
- Comment reply
- Comment deletion

### 10. SEO Settings
- Independent SEO settings for each article
- Site-level SEO configuration
- Sitemap generation
- Robots.txt management
- URL redirection management
- 404 monitoring

### 11. Static Page Generation
- **Manual Generation**: Backend button click generation
- **Automatic Generation**: Auto-generate when articles are published/updated
- **Scheduled Generation**: Batch generation via scheduled tasks
- **Multi-Site Generation**: Supports batch generation of all sites ⭐ v2.0 New
- **Generation Scope**: Homepage, list pages, detail pages, column pages, tag aggregation pages
- **Template Package Support**: Generate according to site-selected template package ⭐ v2.0 New
- **Generation Logs**: Records detailed information for each generation

### 12. System Management
- Cache management (supports File and Redis)
- Database management
- System logs, login logs, security logs
- Operation log auditing

## 📊 Database Design

v2.0.0 complete database contains **92 tables**, covering the following core modules:

**Core Content Management**:
- `articles` - Articles table
- `categories` - Categories table (supports multi-site)
- `tags` - Tags table (supports multi-site)
- `pages` - Single pages table
- `comments` - Comments table

**Multi-Site System** ⭐ v2.0 New:
- `sites` - Sites table
- `site_template_config` - Site template configuration table
- `site_template_overrides` - Site template overrides table

**Template Package System** ⭐ v2.0 New:
- `template_packages` - Template packages table
- `templates` - Template files table

**Media Library System** ⭐ v2.0 Comprehensive Upgrade:
- `media_library` - Media library table
- `media_files` - Media files table (deduplication)
- `media_categories` - Media categories table
- `media_tags` - Media tags table
- `media_thumbnail_presets` - Thumbnail presets table
- `media_watermark_presets` - Watermark presets table
- `media_usage_records` - Media usage records table
- `media_operation_logs` - Media operation logs table

**AI System** ⭐ v2.0 New:
- `ai_providers` - AI providers table (18 providers)
- `ai_models` - AI models table (106 models)
- `ai_configs` - AI configurations table
- `ai_prompt_templates` - AI prompt templates table
- `ai_article_tasks` - AI article generation tasks table
- `ai_article_records` - AI article generation records table

**User Permissions**:
- `admin_users` - Admin users table
- `admin_roles` - Roles table
- `admin_role_permissions` - Role permissions association table

**System Management**:
- `site_config` - Site configuration table
- `static_build_log` - Static generation log table
- `admin_logs` - Operation logs table
- `system_logs` - System logs table
- `login_logs` - Login logs table
- `security_logs` - Security logs table

See `database_design.sql` file for details.

## 📖 Documentation

For complete technical documentation, please see: [Documentation Index](DOCUMENTATION_INDEX.md)

**Quick Links:**
- [Complete Deployment Guide](docs/deployment/DEPLOY.md) - Detailed steps for production environment deployment
- [Backend Environment Configuration](docs/deployment/backend-env.md) - .env configuration instructions
- [Frontend Environment Configuration](docs/deployment/frontend-env.md) - Environment variable configuration
- [API Documentation](docs/api/API_DOCUMENTATION.md) - Complete API interface documentation
- [Development Guide](docs/development/DEVELOPER_GUIDE.md) - Development specifications and best practices
- [Permission System Documentation](PERMISSION_SYSTEM_COMPLETE.md) - Complete permission system documentation
- [Template Development Guide](docs/features/template/TEMPLATE_DEVELOPMENT_GUIDE.md) - Template development tutorial
- [Carefree Tag Library](docs/carefree-taglib/CAREFREE_QUICK_START.md) - Tag library quick start

## Installation and Deployment

### 1. Clone Project

```bash
git clone https://github.com/carefree-code/CarefreeCMS.git

```

### 2. Backend Configuration

```bash
# Enter backend directory
cd carefreecms

# Install dependencies
composer install

# Configure database
# Edit config/database.php file and set database connection information

# Import database
# Import database.sql into MySQL database

# Start development server
php think run -p8000
```

Backend service will run at `http://localhost:8000`

### 3. Frontend Configuration

```bash
# Enter frontend directory
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

Frontend service will run at `http://localhost:3000`

### 4. Production Deployment

**For detailed production environment deployment guide, please see: [Complete Deployment Documentation](docs/deployment/DEPLOY.md)**

Quick steps:

#### Frontend Build
```bash
cd frontend
npm run build
```

#### Backend Configuration
- Configure Nginx or Apache to point to `backend/public` directory
- Copy `.env.production` to `.env` and modify configuration
- Ensure `runtime` and `public/uploads` directories are writable

For more details, please refer to: [Complete Deployment Documentation](docs/deployment/DEPLOY.md)

## Default Account

- Username: `admin`
- Password: `admin123`

**⚠️ Please change password immediately after first login!**

## API Documentation

Backend API adopts RESTful design style, all interfaces require JWT Token authentication (except login interface).

**For complete API documentation, please see: [API Documentation](docs/api/API_DOCUMENTATION.md)**

### Core Interfaces

**User Authentication**:
- `POST /api/auth/login` - User login

**Article Management**:
- `GET /api/articles` - Article list
- `POST /api/articles` - Create article
- `GET /api/articles/fulltext-search` - Full-text search ⭐ v1.2 New
- `GET /api/articles/advanced-search` - Advanced search ⭐ v1.2 New
- `GET /api/articles/search-suggestions` - Search suggestions ⭐ v1.2 New

**AI Article Generation** ⭐ v2.0 New:
- `GET /api/ai-configs/providers` - Get AI provider list
- `POST /api/ai-configs` - Create AI configuration
- `POST /api/ai-configs/:id/test` - Test AI connection
- `POST /api/ai-article-tasks` - Create generation task
- `POST /api/ai-article-tasks/:id/start` - Start task
- `POST /api/ai-article-tasks/:id/stop` - Stop task

**Multi-Site Management** ⭐ v2.0 New:
- `GET /api/sites` - Site list
- `POST /api/sites` - Create site
- `PUT /api/sites/:id` - Update site
- `GET /api/sites/:id/template-config` - Get site template configuration

**Template Package Management** ⭐ v2.0 New:
- `GET /api/template-packages` - Template package list
- `POST /api/template-packages` - Install template package
- `GET /api/template-packages/:id/templates` - Get template package file list

**Media Library** ⭐ v2.0 Comprehensive Upgrade:
- `POST /api/media/upload` - File upload (supports deduplication)
- `GET /api/media` - Media list (supports category and tag filtering)
- `POST /api/media/:id/thumbnail` - Generate thumbnail
- `POST /api/media/:id/watermark` - Add watermark
- `POST /api/media/:id/edit` - Online edit image
- `POST /api/ai-image/generate` - AI generate image ⭐ v2.0 New

**Others**:
- `GET /api/categories/tree` - Category tree
- `POST /api/build/all-sites` - Batch generate all sites ⭐ v2.0 New

## FAQ

### 1. Backend interface not accessible?
Check if backend service is running and ensure it's running on port 8000.

### 2. Frontend unable to login?
Check if `baseURL` configuration in `frontend/src/utils/request.js` is correct.

### 3. File upload failed?
Ensure `backend/public/uploads` directory exists and has write permissions.

### 4. Static generation failed?
Ensure `backend/public/static` directory exists and has write permissions.

### 5. Full-text search returns no results?
Check:
- Ensure backend database has created FULLTEXT INDEX (created by default)
- Search keyword length (English words must be at least 4 characters)
- Confirm there are published articles (status=1)
- Check browser console to confirm API request succeeded

### 6. Advanced search not working?
Ensure:
- At least one search condition is filled
- Check if backend API responds normally
- Category and tag data has been loaded correctly

## Changelog

### v2.0.0 (2025-12-01)

**Major Update: AI Model Library Comprehensive Upgrade + Critical Bug Fixes** 🎉

This update includes comprehensive upgrade of AI model library and fixes for 5 critical bugs, significantly improving the system's AI capabilities and stability.

**🌟 Core Feature Updates:**

1. **✅ AI Model Library Comprehensive Upgrade** (Major Update)
   - Added 4 international top-tier AI vendors (Meta, Mistral AI, xAI, Cohere)
   - Updated latest models from 10 mainstream vendors
   - Total models: 106, Active models: 91
   - New flagship models:
     - xAI Grok 4.1 Thinking (LMArena Rank #1)
     - Claude Opus 4.5 (World's best coding ability)
     - OpenAI GPT-5 / GPT-5.1
     - Google Gemini 3 Deep Think
     - Baidu ERNIE 5.0 Preview (Native multimodal)
     - Zhipu GLM-4.5 (Global 3rd, Open-source 1st)
     - ByteDance Doubao Seed 1.6
     - Moonshot Kimi K2 Thinking (Open-source SOTA)
     - iFlytek Spark X1.5 (Full domestic computing power)
     - MiniMax M2, Meta Llama 4 Scout, etc.
   - Technical Highlights:
     - Ultra-long context: Llama 4 (10M), MiniMax-01 (4M), Gemini 3 (2M)
     - Linear attention: MiniMax Linear, Kimi Linear (6x speed improvement)
     - Native multimodal: ERNIE 5.0, Gemini 3 Pro
     - MoE architecture: Llama 4 (400B), ERNIE 4.5 (424B), GLM-4.5 (355B)

**🐛 Critical Bug Fixes:**

2. **✅ SiteModel Batch Delete Bug Fix** (High-Risk Fix)
   - Fixed serious bug where `$model->delete()` accidentally deletes entire site data
   - Affects 11 controllers: AI config, AI prompts, ad positions, SEO logs, etc.
   - Adopted safe delete mode: `Db::name()->where('id', $id)->limit(1)->delete()`
   - Prevents accidental deletion of entire site data from deleting one record

3. **✅ Category Delete clearCacheTag Error Fix**
   - Fixed `method not exist:think\db\Query->clearCacheTag` error
   - Changed clearCacheTag method in Cacheable trait to public

4. **✅ Article Media Usage Tracking Fix**
   - Fixed inaccurate media usage detection when deleting articles
   - Optimized URL format matching (full URL vs path)
   - Fixed single URL field processing logic

5. **✅ Topic API Refactoring to RESTful Standards**
   - Refactored 5 topic-related APIs to standard RESTful style
   - Fixed bug caused by ThinkPHP 8 JSON field auto-conversion
   - Improved API design standardization and maintainability

**📚 Documentation Updates:**

6. **✅ New Complete Technical Documentation** - See [docs/updates/v2.0.0/](docs/updates/v2.0.0/)
   - AI Model Library Complete Update Report_December 2025.md
   - SiteModel Batch Delete Bug Comprehensive Fix Report.md
   - Category Delete clearCacheTag Error Fix Report.md
   - Article Media Usage Tracking Fix Report.md
   - Topic API RESTful Refactoring Report.md
   - December 2025 System Update Summary.md
   - Documentation Organization Summary.md (New)

7. **✅ Documentation Structure Reorganization**
   - All documents unified into `docs/` folder
   - Created clear classification system (api, backend, frontend, features, etc., 20 subdirectories)
   - Completely rewrote DOCUMENTATION_INDEX.md, providing complete documentation navigation
   - Cleaned 14 outdated and duplicate documents
   - See: [Documentation Organization Summary](docs/updates/v2.0.0/文档整理总结.md)

**Technical Improvements:**
- Code quality: Modified 18 files, approximately 935 lines of code
- Database: Added 4 AI providers, 48 new models, cleaned 36 duplicate records
- Security: Prevents batch accidental deletion, improves data security
- Standardization: API design complies with RESTful standards

**Upgrade Instructions:**
- Need to execute 3 SQL scripts to update AI model library
- Code fully compatible, no need to modify existing features
- Recommended to upgrade for latest AI capabilities and critical bug fixes

See: [December 2025 System Update Summary v2.0.0](docs/updates/v2.0.0/2025年12月系统更新总结.md)

---

### v1.3.0 (2025-11-04)

**Major Update: System Stability Comprehensive Enhancement** 🎉

This update fixes 11 critical issues, significantly improving system stability, usability, and feature completeness.

**Core Feature Fixes:**

1. **✅ Log System Improvement**
   - Fixed system logs, login logs, security logs with no content issue
   - Added SystemLog middleware, automatically records all API requests
   - Improved login/logout event logging mechanism
   - Added security event monitoring (failed logins, abnormal access, etc.)

2. **✅ Permission Management Enhancement**
   - Supplemented permission definitions for all new features (expanded from 177 lines to 450+ lines)
   - New content management permissions: article attributes, topics, links, content models, custom fields, recycle bin
   - New SEO management permissions: SEO settings, URL redirection, 404 monitoring, Robots.txt, SEO tools
   - New system management permissions: database management, cache management, system logs, operation logs
   - New extension feature permissions: ads, sliders, members, submissions, notifications, SMS, point mall, etc.
   - New template management permissions: template editor, template tag tutorials

3. **✅ Cache Management Optimization**
   - Fixed cache driver switching display error
   - Clear configuration cache to ensure driver switching takes effect immediately
   - Optimized frontend auto-refresh logic
   - Improved Redis and File cache statistics display

**User Experience Optimization:**

4. **✅ Submission Configuration Fix** - Fixed category dropdown unable to load issue
5. **✅ Ad Management Enhancement** - Added quick call code feature, one-click copy Carefree tags
6. **✅ Slider Management Enhancement** - Added group quick call code, includes complete HTML examples
7. **✅ Media Library Improvement** - Added select all/deselect all buttons, more convenient batch operations
8. **✅ Member Management Improvement** - Member list added VIP expiration time column, supports permanent VIP identifier
9. **✅ Message Notification Fix** - Fixed notification records not displaying issue, added auto-load mechanism
10. **✅ SMS Service Fix** - Fixed statistics data display error, supports nested object access

**Code Quality Improvement:**

11. **✅ Constant Definition Fix** - Fixed `MODULE_SYSTEM` undefined error, standardized constant management

**Template System Enhancement:**

12. **✅ Carefree Tag Library V1.6** - Full support for variable parameters 🆕
   - 9 core tags support variable parameters (article, category, link, slider, related, etc.)
   - Supports dynamic data queries: `typeid='$category.id'`, `tagid='$tag.id'`
   - Perfect adaptation for category pages, tag pages, article details, and other dynamic scenarios
   - 100% backward compatible, no need to modify existing template code
   - See: [Carefree Tag Library V1.6 Documentation](docs/carefree-taglib/CAREFREE_TAGLIB_V1.6.md)

13. **✅ Config Tag Fix** - Fixed config tag unable to read data issue 🆕
   - Corrected ConfigTagService using wrong model and field names
   - Updated config key names in all documents (web_name → site_name, etc.)
   - Added complete config item list and usage instructions
   - Updated docs: CAREFREE_TAGLIB_GUIDE.md, CAREFREE_QUICK_REFERENCE.md, CAREFREE_TROUBLESHOOTING.md
   - Config data supports 1-hour cache, improves access performance

**Technical Improvements:**
- Uses dual log system: Logger (operation logs) + SystemLogger (system/login/security logs)
- Middleware automation: SystemLog middleware intercepts all API requests and records
- Sensitive information protection: Automatically filters passwords, tokens, and other sensitive parameters in logs
- Slow request monitoring: Automatically marks requests exceeding 1000ms execution time
- Config cache management: Automatically clears runtime config cache when switching drivers
- Frontend state synchronization: Uses localStorage to pass state between page refreshes

**Documentation Updates:**
- Added `Issue Fix Summary.md` - Detailed record of all 11 issue fixes
- Improved project documentation structure
- Updated version to 1.3.0

**Upgrade Instructions:**
This update does not involve database structure changes, existing data is fully compatible. All users are recommended to upgrade to this version for better stability and complete features.

---

### v1.2.0 (2025-10-28)

**Major Update: Full-Text Search and Advanced Search Features** 🎉

**Backend Updates:**
- ✨ New full-text search feature (based on MySQL FULLTEXT INDEX)
  - Supports natural language mode (sorted by relevance)
  - Supports boolean mode (+word -word "phrase" operators)
  - Supports query expansion mode (auto-expand related words)
  - Search results auto-highlight keywords
- ✨ New advanced search feature
  - Supports 15+ search fields and filter conditions
  - Supports multi-field queries (title, content, summary, author, etc.)
  - Supports multi-dimensional filtering (category, tags, status, etc.)
  - Supports view count range filtering
  - Supports multiple sorting methods (publish time, views, likes, comments, etc.)
- ✨ New search suggestions API (auto-complete feature)
- 📝 Added 3 search-related API interfaces
  - `/api/articles/fulltext-search` - Full-text search
  - `/api/articles/advanced-search` - Advanced search
  - `/api/articles/search-suggestions` - Search suggestions

**Frontend Updates:**
- ✨ New `AdvancedSearch.vue` advanced search dialog component
  - Beautiful dual-tab layout (full-text search/advanced search)
  - Real-time search suggestions/auto-complete
  - Search history feature (localStorage storage, max 10 records)
  - Supports deleting single history or clearing all
- ✨ Updated article list page, integrated advanced search feature
  - Search results keyword highlighting (yellow background marking)
  - Displays current search conditions and result count
  - One-click clear search to return to normal list
- 🎨 Optimized search user experience
  - Smart form validation
  - Friendly error prompts
  - Smooth interaction animations


**Other Optimizations:**
- ✨ New media library selector component, supports inserting files from media library to article editor
- ✨ Optimized Sitemap generation page layout, basic format and advanced types displayed side by side
- 🐛 Fixed pagination code error in category and tag templates
- 🐛 Fixed categories field reference error in article template

### v1.1.0 (2025-10-21)
- ✨ New cache driver switching feature, supports File and Redis drivers
- ✨ Optimized Sitemap generation interface, merged basic format and advanced types into single page
- ✨ TinyMCE editor optimization: removed help feature, toolbar changed to 2-line layout
- ✨ Enhanced cache management: supports Redis connection testing and real-time driver switching
- 🐛 Fixed API routing 404 error
- 🐛 Optimized PHP Redis extension detection and error prompts

### v1.0.0 (2025-10-15)
- 🎉 First official version release
- ✨ Complete content management features
- ✨ User permission management system
- ✨ Media file management
- ✨ SEO optimization features
- ✨ Operation log recording
- 🐛 Fixed known issues

## License

- This project's self-developed code adopts MIT open-source license. See [LICENSE](./LICENSE) file for details.
- This project references code from other projects, following the referenced project's open-source license.
- For example, ThinkPHP follows Apache2 open-source license

## Contact Us

- **Official Website**: https://www.carefreecms.com
- **Issue Feedback**: https://github.com/carefree-code/CarefreeCMS/issues
- **Email**: sinma@qq.com

## Acknowledgments

Thanks to the following open-source projects:

- [ThinkPHP](https://www.thinkphp.cn/)
- [Vue.js](https://vuejs.org/)
- [Element Plus](https://element-plus.org/)
- [TinyMCE](https://www.tiny.cloud/)

---

Made with ❤️ by CarefreeCMS Team © 2025
![QQ Group](/readme/pic/qqqun.jpg)
