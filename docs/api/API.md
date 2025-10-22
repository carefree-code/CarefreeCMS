# CMS API 接口文档

## 基础信息

- **基础URL**: `http://localhost:8000/api`
- **数据格式**: JSON
- **字符编码**: UTF-8

## 统一响应格式

### 成功响应
```json
{
  "code": 200,
  "message": "success",
  "data": {},
  "timestamp": 1234567890
}
```

### 失败响应
```json
{
  "code": 400,
  "message": "错误信息",
  "data": {},
  "timestamp": 1234567890
}
```

### 状态码说明
- `200`: 成功
- `400`: 请求参数错误
- `401`: 未授权或登录已过期
- `403`: 无权限访问
- `404`: 资源不存在
- `500`: 服务器错误

---

## 认证接口

### 1. 用户登录

**请求地址**: `/api/auth/login`
**请求方式**: POST
**是否需要认证**: 否

**请求参数**:
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**响应示例**:
```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user_info": {
      "id": 1,
      "username": "admin",
      "real_name": "系统管理员",
      "email": "admin@example.com",
      "avatar": null,
      "role_id": 1
    }
  },
  "timestamp": 1234567890
}
```

---

### 2. 退出登录

**请求地址**: `/api/auth/logout`
**请求方式**: POST
**是否需要认证**: 否

**响应示例**:
```json
{
  "code": 200,
  "message": "退出成功",
  "data": [],
  "timestamp": 1234567890
}
```

---

### 3. 获取当前用户信息

**请求地址**: `/api/auth/info`
**请求方式**: GET
**是否需要认证**: 是

**请求头**:
```
Authorization: Bearer {token}
```

**响应示例**:
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "username": "admin",
    "real_name": "系统管理员",
    "email": "admin@example.com",
    "phone": null,
    "avatar": null,
    "role_id": 1,
    "role_name": "超级管理员",
    "status": 1,
    "status_text": "启用"
  },
  "timestamp": 1234567890
}
```

---

### 4. 修改密码

**请求地址**: `/api/auth/change-password`
**请求方式**: POST
**是否需要认证**: 是

**请求参数**:
```json
{
  "old_password": "admin123",
  "new_password": "newpassword123"
}
```

**响应示例**:
```json
{
  "code": 200,
  "message": "密码修改成功",
  "data": [],
  "timestamp": 1234567890
}
```

---

## 文章管理接口

### 1. 文章列表

**请求地址**: `/api/articles`
**请求方式**: GET
**是否需要认证**: 是

**请求参数**:
- `page`: 页码（默认1）
- `page_size`: 每页数量（默认20）
- `title`: 标题搜索（可选）
- `category_id`: 分类ID（可选）
- `status`: 状态（0=草稿，1=已发布，2=待审核，3=已下线）
- `is_top`: 是否置顶（0=否，1=是）
- `is_recommend`: 是否推荐（0=否，1=是）

---

### 2. 文章详情

**请求地址**: `/api/articles/:id`
**请求方式**: GET
**是否需要认证**: 是

---

### 3. 创建文章

**请求地址**: `/api/articles`
**请求方式**: POST
**是否需要认证**: 是

**请求参数**:
```json
{
  "category_id": 1,
  "title": "文章标题",
  "slug": "article-slug",
  "summary": "文章摘要",
  "content": "文章内容",
  "cover_image": "封面图片URL",
  "tags": [1, 2, 3],
  "seo_title": "SEO标题",
  "seo_keywords": "关键词",
  "seo_description": "SEO描述",
  "status": 1
}
```

---

### 4. 更新文章

**请求地址**: `/api/articles/:id`
**请求方式**: PUT
**是否需要认证**: 是

---

### 5. 删除文章

**请求地址**: `/api/articles/:id`
**请求方式**: DELETE
**是否需要认证**: 是

---

## 分类管理接口

### 1. 分类列表

**请求地址**: `/api/categories`
**请求方式**: GET
**是否需要认证**: 是

---

### 2. 分类树（树形结构）

**请求地址**: `/api/categories/tree`
**请求方式**: GET
**是否需要认证**: 是

---

### 3. 创建分类

**请求地址**: `/api/categories`
**请求方式**: POST
**是否需要认证**: 是

**请求参数**:
```json
{
  "parent_id": 0,
  "name": "分类名称",
  "slug": "category-slug",
  "description": "分类描述",
  "seo_title": "SEO标题",
  "seo_keywords": "关键词",
  "seo_description": "SEO描述",
  "sort": 0,
  "status": 1
}
```

---

## 标签管理接口

### 1. 标签列表

**请求地址**: `/api/tags`
**请求方式**: GET
**是否需要认证**: 是

---

### 2. 创建标签

**请求地址**: `/api/tags`
**请求方式**: POST
**是否需要认证**: 是

**请求参数**:
```json
{
  "name": "标签名称",
  "slug": "tag-slug",
  "description": "标签描述"
}
```

---

## 文件上传接口

### 上传文件

**请求地址**: `/api/media/upload`
**请求方式**: POST
**是否需要认证**: 是

**请求参数**: multipart/form-data
- `file`: 文件（必填）
- `type`: 文件类型（image/video/audio/document）

**响应示例**:
```json
{
  "code": 200,
  "message": "上传成功",
  "data": {
    "id": 1,
    "file_name": "image.jpg",
    "file_url": "http://localhost:8000/uploads/2024/01/01/image.jpg",
    "file_type": "image",
    "file_size": 102400,
    "width": 1920,
    "height": 1080
  },
  "timestamp": 1234567890
}
```

---

## 静态页面生成接口

### 1. 生成所有静态页

**请求地址**: `/api/build/all`
**请求方式**: POST
**是否需要认证**: 是

---

### 2. 生成首页

**请求地址**: `/api/build/index`
**请求方式**: POST
**是否需要认证**: 是

---

### 3. 生成文章详情页

**请求地址**: `/api/build/article/:id`
**请求方式**: POST
**是否需要认证**: 是

---

### 4. 生成分类列表页

**请求地址**: `/api/build/category/:id`
**请求方式**: POST
**是否需要认证**: 是

---

## 测试说明

### 1. 启动后端服务

```bash
cd api
php think run
```

默认启动在 `http://localhost:8000`

### 2. 测试登录接口

使用 Postman 或 curl 测试：

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 3. 使用Token访问需要认证的接口

```bash
curl -X GET http://localhost:8000/api/auth/info \
  -H "Authorization: Bearer {your_token}"
```

---

## 注意事项

1. 所有需要认证的接口都必须在请求头中携带 `Authorization: Bearer {token}`
2. Token有效期为2小时（7200秒），过期后需要重新登录
3. 默认管理员账号：`admin` / `admin123`
4. 首次登录后请及时修改密码
5. 所有时间字段格式为：`Y-m-d H:i:s`
6. 文件上传最大限制：10MB（可在配置中修改）
