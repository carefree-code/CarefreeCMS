# CMS 前端管理系统开发指南

## 📁 项目目录结构

```
backend/
├── public/                  # 静态资源
├── src/
│   ├── api/                # API 接口封装
│   │   ├── request.js      # Axios 实例配置
│   │   ├── auth.js         # 认证相关接口
│   │   ├── article.js      # 文章接口
│   │   ├── category.js     # 分类接口
│   │   ├── tag.js          # 标签接口
│   │   └── media.js        # 媒体接口
│   ├── assets/             # 资源文件
│   │   └── styles/         # 样式文件
│   ├── components/         # 公共组件
│   │   ├── TinymceEditor.vue  # 富文本编辑器
│   │   └── ImageUpload.vue    # 图片上传组件
│   ├── layouts/            # 布局组件
│   │   └── MainLayout.vue  # 主布局
│   ├── router/             # 路由配置
│   │   └── index.js        # 路由文件
│   ├── store/              # Pinia 状态管理
│   │   ├── index.js        # Store 入口
│   │   ├── user.js         # 用户状态
│   │   └── app.js          # 应用状态
│   ├── utils/              # 工具函数
│   │   ├── auth.js         # Token 管理
│   │   └── validators.js   # 表单验证
│   ├── views/              # 页面组件
│   │   ├── auth/
│   │   │   └── Login.vue   # 登录页
│   │   ├── dashboard/
│   │   │   └── Index.vue   # 仪表板
│   │   ├── article/
│   │   │   ├── List.vue    # 文章列表
│   │   │   ├── Create.vue  # 新建文章
│   │   │   └── Edit.vue    # 编辑文章
│   │   ├── category/
│   │   │   └── List.vue    # 分类管理
│   │   └── tag/
│   │       └── List.vue    # 标签管理
│   ├── App.vue             # 根组件
│   └── main.js             # 入口文件
├── .env.development        # 开发环境配置
├── .env.production         # 生产环境配置
├── vite.config.js          # Vite 配置
├── package.json
└── README.md
```

---

## 🔧 关键配置文件

### 1. 环境配置 `.env.development`

```env
# API 基础地址
VITE_API_BASE_URL=http://localhost:8000/api

# 应用标题
VITE_APP_TITLE=CMS管理系统

# Token 存储键名
VITE_TOKEN_KEY=cms_token
```

### 2. Vite 配置 `vite.config.js`

```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src')
    }
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  }
})
```

### 3. Axios 请求封装 `src/api/request.js`

```javascript
import axios from 'axios'
import { ElMessage } from 'element-plus'
import { getToken, removeToken } from '@/utils/auth'
import router from '@/router'

// 创建 axios 实例
const service = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 30000
})

// 请求拦截器
service.interceptors.request.use(
  config => {
    const token = getToken()
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    console.error('请求错误:', error)
    return Promise.reject(error)
  }
)

// 响应拦截器
service.interceptors.response.use(
  response => {
    const res = response.data

    if (res.code !== 200) {
      ElMessage.error(res.message || '请求失败')

      // 401: 未授权，跳转登录
      if (res.code === 401) {
        removeToken()
        router.push('/login')
      }

      return Promise.reject(new Error(res.message || 'Error'))
    }

    return res
  },
  error => {
    console.error('响应错误:', error)
    ElMessage.error(error.message || '网络错误')
    return Promise.reject(error)
  }
)

export default service
```

### 4. Token 管理 `src/utils/auth.js`

```javascript
const TOKEN_KEY = import.meta.env.VITE_TOKEN_KEY || 'cms_token'

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token)
}

export function removeToken() {
  localStorage.removeItem(TOKEN_KEY)
}
```

### 5. 认证 API `src/api/auth.js`

```javascript
import request from './request'

// 登录
export function login(data) {
  return request({
    url: '/auth/login',
    method: 'post',
    data
  })
}

// 退出登录
export function logout() {
  return request({
    url: '/auth/logout',
    method: 'post'
  })
}

// 获取用户信息
export function getUserInfo() {
  return request({
    url: '/auth/info',
    method: 'get'
  })
}

// 修改密码
export function changePassword(data) {
  return request({
    url: '/auth/change-password',
    method: 'post',
    data
  })
}
```

### 6. 文章 API `src/api/article.js`

```javascript
import request from './request'

// 获取文章列表
export function getArticleList(params) {
  return request({
    url: '/articles',
    method: 'get',
    params
  })
}

// 获取文章详情
export function getArticleDetail(id) {
  return request({
    url: `/articles/${id}`,
    method: 'get'
  })
}

// 创建文章
export function createArticle(data) {
  return request({
    url: '/articles',
    method: 'post',
    data
  })
}

// 更新文章
export function updateArticle(id, data) {
  return request({
    url: `/articles/${id}`,
    method: 'put',
    data
  })
}

// 删除文章
export function deleteArticle(id) {
  return request({
    url: `/articles/${id}`,
    method: 'delete'
  })
}

// 发布文章
export function publishArticle(id) {
  return request({
    url: `/articles/${id}/publish`,
    method: 'post'
  })
}

// 下线文章
export function offlineArticle(id) {
  return request({
    url: `/articles/${id}/offline`,
    method: 'post'
  })
}
```

### 7. 用户状态管理 `src/store/user.js`

```javascript
import { defineStore } from 'pinia'
import { login, getUserInfo, logout } from '@/api/auth'
import { setToken, removeToken } from '@/utils/auth'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: '',
    userInfo: null
  }),

  getters: {
    isLoggedIn: (state) => !!state.token
  },

  actions: {
    // 登录
    async login(loginForm) {
      try {
        const res = await login(loginForm)
        this.token = res.data.token
        setToken(res.data.token)
        return res
      } catch (error) {
        return Promise.reject(error)
      }
    },

    // 获取用户信息
    async getUserInfo() {
      try {
        const res = await getUserInfo()
        this.userInfo = res.data
        return res
      } catch (error) {
        return Promise.reject(error)
      }
    },

    // 退出登录
    async logout() {
      try {
        await logout()
        this.token = ''
        this.userInfo = null
        removeToken()
      } catch (error) {
        console.error('退出登录失败:', error)
      }
    }
  }
})
```

### 8. 路由配置 `src/router/index.js`

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from '@/utils/auth'
import { ElMessage } from 'element-plus'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { title: '登录' }
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/Index.vue'),
        meta: { title: '仪表板', requiresAuth: true }
      },
      {
        path: 'articles',
        name: 'ArticleList',
        component: () => import('@/views/article/List.vue'),
        meta: { title: '文章列表', requiresAuth: true }
      },
      {
        path: 'articles/create',
        name: 'ArticleCreate',
        component: () => import('@/views/article/Create.vue'),
        meta: { title: '新建文章', requiresAuth: true }
      },
      {
        path: 'articles/:id/edit',
        name: 'ArticleEdit',
        component: () => import('@/views/article/Edit.vue'),
        meta: { title: '编辑文章', requiresAuth: true }
      },
      {
        path: 'categories',
        name: 'CategoryList',
        component: () => import('@/views/category/List.vue'),
        meta: { title: '分类管理', requiresAuth: true }
      },
      {
        path: 'tags',
        name: 'TagList',
        component: () => import('@/views/tag/List.vue'),
        meta: { title: '标签管理', requiresAuth: true }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  document.title = to.meta.title || 'CMS管理系统'

  const token = getToken()

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      ElMessage.warning('请先登录')
      next('/login')
    } else {
      next()
    }
  } else {
    if (token && to.path === '/login') {
      next('/')
    } else {
      next()
    }
  }
})

export default router
```

### 9. Store 入口 `src/store/index.js`

```javascript
import { createPinia } from 'pinia'

const pinia = createPinia()

export default pinia
```

### 10. 主入口文件 `src/main.js`

```javascript
import { createApp } from 'vue'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

import App from './App.vue'
import router from './router'
import pinia from './store'

const app = createApp(App)

// 注册所有图标
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

app.use(ElementPlus, { locale: zhCn })
app.use(router)
app.use(pinia)

app.mount('#app')
```

---

## 📝 核心页面组件示例

由于篇幅限制，完整的页面组件代码请查看：`前端页面代码示例.md`

---

## 🚀 启动开发服务器

```bash
cd backend
npm run dev
```

访问：`http://localhost:3000`

---

## 📦 构建生产版本

```bash
npm run build
```

构建文件将输出到 `dist/` 目录。

---

## 🔍 开发要点

### 1. 表单验证
使用 Element Plus 的表单验证功能

### 2. 文件上传
封装图片上传组件，支持预览和删除

### 3. 富文本编辑器
推荐使用 TinyMCE 或 WangEditor

### 4. 权限控制
基于路由元信息和用户角色进行权限控制

### 5. 响应式设计
确保在不同屏幕尺寸下都能正常使用

---

## 📚 参考资源

- Vue 3 官方文档: https://cn.vuejs.org/
- Element Plus 官方文档: https://element-plus.org/zh-CN/
- Vue Router 官方文档: https://router.vuejs.org/zh/
- Pinia 官方文档: https://pinia.vuejs.org/zh/
- Axios 文档: https://axios-http.com/zh/
