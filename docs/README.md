# 欢喜内容管理系统 - 文档中心

欢迎查阅欢喜内容管理系统（Huanxi CMS）的技术文档。

## 📚 文档导航

### 快速开始

- [项目介绍](../README.md) - 系统简介、技术栈、安装指南
- [部署指南](deployment/DEPLOY.md) - 生产环境完整部署流程

### 部署与配置

- **[部署指南](deployment/DEPLOY.md)**
  - Nginx 部署配置
  - Apache 部署配置
  - 前后端部署步骤
  - 目录权限设置
  - 安全配置
  - 性能优化
  - 常见问题解决

- **[后端环境配置](deployment/backend-env.md)**
  - `.env` 文件配置说明
  - 开发环境与生产环境配置
  - 数据库配置详解
  - JWT 配置详解
  - 安全最佳实践
  - 常见问题排查

- **[前端环境配置](deployment/frontend-env.md)**
  - 环境变量配置
  - API 地址配置
  - 开发与生产环境切换
  - 构建部署流程

### API 文档

- **[API 接口文档](api/API.md)**
  - 认证接口
  - 文章管理接口
  - 分类管理接口
  - 标签管理接口
  - 单页管理接口
  - 媒体库接口
  - 用户管理接口
  - 角色权限接口
  - 配置管理接口
  - 静态生成接口
  - 操作日志接口

### 开发指南

- **[前端开发指南](development/frontend-guide.md)**
  - 项目结构说明
  - 开发环境搭建
  - 代码规范
  - 组件开发
  - 路由配置
  - 状态管理
  - API 请求封装
  - 常见问题

- **[权限管理使用指南](development/permissions-guide.md)**
  - 权限系统架构
  - 角色管理
  - 权限配置
  - 路由守卫
  - 按钮权限控制
  - 数据权限控制

## 📖 文档说明

### 文档结构

```
docs/
├── README.md                    # 文档索引（本文件）
├── deployment/                  # 部署相关文档
│   ├── DEPLOY.md               # 完整部署指南
│   ├── backend-env.md          # 后端环境配置
│   └── frontend-env.md         # 前端环境配置
├── api/                         # API 文档
│   └── API.md                  # API 接口文档
└── development/                 # 开发指南
    ├── frontend-guide.md       # 前端开发指南
    └── permissions-guide.md    # 权限管理指南
```

### 推荐阅读顺序

**新手入门：**
1. [项目介绍](../README.md)
2. [部署指南](deployment/DEPLOY.md)
3. [后端环境配置](deployment/backend-env.md)
4. [前端环境配置](deployment/frontend-env.md)

**开发人员：**
1. [前端开发指南](development/frontend-guide.md)
2. [API 接口文档](api/API.md)
3. [权限管理使用指南](development/permissions-guide.md)

**运维人员：**
1. [部署指南](deployment/DEPLOY.md)
2. [后端环境配置](deployment/backend-env.md)
3. [API 接口文档](api/API.md)（测试接口用）

## 🔗 相关资源

### 项目地址
- 主页：https://www.sinma.net/
- 作者邮箱：sinma@qq.com

### 技术栈文档
- [ThinkPHP 8](https://doc.thinkphp.cn/)
- [Vue 3](https://vuejs.org/)
- [Element Plus](https://element-plus.org/)
- [Vite](https://vitejs.dev/)

## 📝 文档更新记录

### v1.0.0 (2025-10-15)
- ✅ 完整的部署文档
- ✅ 后端环境配置说明
- ✅ 前端环境配置说明
- ✅ API 接口文档
- ✅ 前端开发指南
- ✅ 权限管理指南

## 💡 文档反馈

如果您在使用文档过程中发现问题或有改进建议，请通过以下方式反馈：

- 邮箱：sinma@qq.com
- 网站：https://www.sinma.net/

## 📄 许可证

本项目文档采用 Apache-2.0 开源协议。

---

© 2025 sinma. All rights reserved.
