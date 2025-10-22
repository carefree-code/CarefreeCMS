# 前端环境变量配置说明

本项目使用 Vite 的环境变量功能来管理不同环境的配置。

## 环境变量文件

- `.env.development` - 开发环境配置（npm run dev）
- `.env.production` - 生产环境配置（npm run build）

## 配置项说明

### VITE_API_BASE_URL
API 接口的基础地址，所有 API 请求都会使用此地址作为前缀。

**开发环境：**
```
VITE_API_BASE_URL=http://localhost:8000/api
```

**生产环境：**
```
VITE_API_BASE_URL=https://cmsapi.sinma.net/api
```

### VITE_APP_TITLE
应用标题，显示在浏览器标签页。

### VITE_TOKEN_KEY
Token 在 localStorage 中的存储键名。

## 如何修改生产环境 API 地址

如果您的后端 API 部署在其他域名，请修改 `.env.production` 文件：

```env
VITE_API_BASE_URL=https://your-api-domain.com/api
```

**注意：**
- 地址末尾需要包含 `/api`
- 必须包含协议（http:// 或 https://）
- 不要以斜杠结尾

## 构建生产版本

修改配置后，需要重新构建前端：

```bash
cd backend
npm run build
```

构建产物位于 `dist/` 目录，将其上传到服务器即可。

## 开发调试

开发环境会自动读取 `.env.development` 文件：

```bash
npm run dev
```

开发环境还配置了代理（vite.config.js），所以开发时不会有跨域问题。

## 验证配置

可以在浏览器控制台查看当前使用的 API 地址：

```javascript
console.log(import.meta.env.VITE_API_BASE_URL)
```

## 示例配置

### 示例 1：后端和前端在同一域名
```
后端: https://example.com/api/
前端: https://example.com/admin/

.env.production:
VITE_API_BASE_URL=https://example.com/api
```

### 示例 2：后端和前端在不同域名（当前配置）
```
后端: https://cmsapi.sinma.net/api/
前端: https://cmsadmin.sinma.net/

.env.production:
VITE_API_BASE_URL=https://cmsapi.sinma.net/api
```

### 示例 3：后端在子目录
```
后端: https://example.com/cms/api/
前端: https://example.com/cms/admin/

.env.production:
VITE_API_BASE_URL=https://example.com/cms/api
```

## 常见问题

### Q: 修改了配置但没有生效？
A: 需要重新构建：`npm run build`，然后将新的 dist 目录上传到服务器。

### Q: 接口 404 Not Found？
A: 检查 API 地址是否正确，确保包含 `/api` 前缀。

### Q: 跨域问题？
A: 确保后端配置了 CORS，参考 `api/app/middleware/Cors.php`。

### Q: 开发环境正常，生产环境不行？
A: 检查 `.env.production` 文件是否正确配置，并重新构建。

---

© 2025 sinma. All rights reserved.
