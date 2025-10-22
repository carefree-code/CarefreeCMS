# CMS 权限管理使用指南

## 📚 目录
1. [权限系统架构](#权限系统架构)
2. [添加新功能时如何同步权限](#添加新功能时如何同步权限)
3. [权限配置文件详解](#权限配置文件详解)
4. [在页面中使用权限控制](#在页面中使用权限控制)
5. [常见场景示例](#常见场景示例)
6. [最佳实践](#最佳实践)

---

## 权限系统架构

```
权限定义 (permissions.js)
    ↓
角色配置 (数据库 admin_roles 表)
    ↓
用户分配角色 (数据库 admin_users 表)
    ↓
登录时加载权限 (user store)
    ↓
前端权限控制 (v-permission 指令 / hasPermission 方法)
```

---

## 添加新功能时如何同步权限

### 步骤 1: 更新权限配置文件

文件位置：`backend/src/config/permissions.js`

#### 场景 A：添加新菜单

```javascript
export const permissions = [
  // ... 已有菜单
  {
    id: 'new_menu',              // 唯一标识，使用下划线命名
    name: '新菜单名称',           // 显示名称
    icon: 'IconName',            // Element Plus 图标名
    type: 'menu',                // 类型：menu
    children: [
      {
        id: 'new_menu.view',     // 菜单.操作
        name: '查看新菜单',
        type: 'page'             // 类型：page
      }
    ]
  }
]
```

#### 场景 B：在现有菜单下添加新页面

```javascript
{
  id: 'content',
  name: '内容管理',
  icon: 'Document',
  type: 'menu',
  children: [
    // ... 已有页面
    {
      id: 'comments',              // 新页面ID
      name: '评论管理',            // 新页面名称
      type: 'page',
      children: [
        { id: 'comments.view', name: '查看评论列表', type: 'action' },
        { id: 'comments.approve', name: '审核评论', type: 'action' },
        { id: 'comments.delete', name: '删除评论', type: 'action' }
      ]
    }
  ]
}
```

#### 场景 C：在现有页面添加新按钮/操作

```javascript
{
  id: 'articles',
  name: '文章管理',
  type: 'page',
  children: [
    { id: 'articles.view', name: '查看文章列表', type: 'action' },
    { id: 'articles.create', name: '创建文章', type: 'action' },
    { id: 'articles.edit', name: '编辑文章', type: 'action' },
    { id: 'articles.delete', name: '删除文章', type: 'action' },
    // 新增操作
    { id: 'articles.export', name: '导出文章', type: 'action' },
    { id: 'articles.import', name: '导入文章', type: 'action' }
  ]
}
```

### 步骤 2: 在路由中添加路由（如果是新页面）

文件位置：`backend/src/router/index.js`

```javascript
{
  path: 'comments',
  name: 'CommentList',
  component: () => import('@/views/comment/List.vue'),
  meta: { title: '评论管理' }
}
```

### 步骤 3: 在侧边栏菜单中添加（如果是新菜单）

文件位置：`backend/src/layouts/MainLayout.vue`

```vue
<el-menu-item index="/comments">
  <el-icon><comment /></el-icon>
  <span>评论管理</span>
</el-menu-item>
```

### 步骤 4: 在页面中使用权限控制

文件位置：新建或编辑的 Vue 组件

```vue
<template>
  <!-- 页面级权限：整个模块需要 comments.view 权限 -->
  <div v-permission="'comments.view'" class="comment-list">

    <!-- 按钮级权限：创建按钮需要 comments.create 权限 -->
    <el-button
      v-permission="'comments.create'"
      type="primary"
      @click="handleCreate"
    >
      创建评论
    </el-button>

    <!-- 操作按钮：审核需要 comments.approve 权限 -->
    <el-button
      v-permission="'comments.approve'"
      size="small"
      @click="handleApprove(row)"
    >
      审核
    </el-button>

    <!-- 删除按钮：需要 comments.delete 权限 -->
    <el-button
      v-permission="'comments.delete'"
      size="small"
      type="danger"
      @click="handleDelete(row.id)"
    >
      删除
    </el-button>
  </div>
</template>
```

### 步骤 5: 为现有角色分配新权限

1. 登录后台管理系统
2. 进入：系统管理 → 角色管理
3. 点击角色的"设置权限"按钮
4. 在权限树中勾选新添加的权限
5. 点击"保存权限"

---

## 权限配置文件详解

### 权限 ID 命名规范

```javascript
// 格式：[模块].[子模块].[操作]
// 使用小写字母和下划线

// ✅ 推荐
'articles.view'           // 文章-查看
'articles.create'         // 文章-创建
'users.reset_password'    // 用户-重置密码
'build.logs'              // 静态生成-日志

// ❌ 不推荐
'ArticlesView'            // 不使用驼峰
'articles-view'           // 不使用连字符
'ARTICLES_VIEW'           // 不使用全大写
```

### 权限类型说明

| 类型 | 说明 | 示例 |
|------|------|------|
| `menu` | 菜单级权限 | 内容管理、系统管理 |
| `page` | 页面级权限 | 文章列表、用户列表 |
| `action` | 操作级权限 | 创建、编辑、删除按钮 |

### 完整示例

```javascript
{
  id: 'content',                    // 一级：菜单ID
  name: '内容管理',
  icon: 'Document',
  type: 'menu',
  children: [
    {
      id: 'articles',               // 二级：页面ID
      name: '文章管理',
      type: 'page',
      children: [
        {
          id: 'articles.view',      // 三级：操作ID
          name: '查看文章列表',
          type: 'action'
        },
        {
          id: 'articles.create',
          name: '创建文章',
          type: 'action'
        }
      ]
    }
  ]
}
```

---

## 在页面中使用权限控制

### 方式 1: 使用 v-permission 指令（推荐）

#### 单个权限
```vue
<el-button v-permission="'articles.create'">创建</el-button>
```

#### 任一权限（OR 逻辑）
```vue
<!-- 有编辑或删除权限就显示 -->
<el-button v-permission="['articles.edit', 'articles.delete']">
  操作
</el-button>
```

#### 所有权限（AND 逻辑）
```vue
<!-- 同时有编辑和发布权限才显示 -->
<el-button v-permission.all="['articles.edit', 'articles.publish']">
  编辑并发布
</el-button>
```

### 方式 2: 在 JavaScript 中检查权限

```vue
<script setup>
import { usePermissionStore } from '@/store/permission'

const permissionStore = usePermissionStore()

// 检查单个权限
const canCreate = permissionStore.hasPermission('articles.create')

// 检查任一权限
const canOperate = permissionStore.hasAnyPermission([
  'articles.edit',
  'articles.delete'
])

// 检查所有权限
const canPublish = permissionStore.hasAllPermissions([
  'articles.edit',
  'articles.publish'
])

// 在方法中使用
const handleCreate = () => {
  if (!permissionStore.hasPermission('articles.create')) {
    ElMessage.error('您没有创建权限')
    return
  }
  // 执行创建逻辑
}
</script>
```

### 方式 3: 在计算属性中使用

```vue
<script setup>
import { computed } from 'vue'
import { usePermissionStore } from '@/store/permission'

const permissionStore = usePermissionStore()

// 是否可以编辑
const canEdit = computed(() =>
  permissionStore.hasPermission('articles.edit')
)

// 可用的操作列表
const availableActions = computed(() => {
  const actions = []
  if (permissionStore.hasPermission('articles.edit')) {
    actions.push({ label: '编辑', command: 'edit' })
  }
  if (permissionStore.hasPermission('articles.delete')) {
    actions.push({ label: '删除', command: 'delete' })
  }
  return actions
})
</script>

<template>
  <el-button v-if="canEdit" @click="handleEdit">编辑</el-button>

  <el-dropdown v-if="availableActions.length > 0">
    <span>操作</span>
    <template #dropdown>
      <el-dropdown-menu>
        <el-dropdown-item
          v-for="action in availableActions"
          :key="action.command"
          :command="action.command"
        >
          {{ action.label }}
        </el-dropdown-item>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
</template>
```

---

## 常见场景示例

### 场景 1: 表格操作列根据权限显示按钮

```vue
<el-table-column label="操作" width="200">
  <template #default="{ row }">
    <el-button
      v-permission="'articles.edit'"
      size="small"
      @click="handleEdit(row)"
    >
      编辑
    </el-button>

    <el-button
      v-permission="'articles.delete'"
      size="small"
      type="danger"
      @click="handleDelete(row.id)"
    >
      删除
    </el-button>
  </template>
</el-table-column>
```

### 场景 2: 表单提交按钮权限控制

```vue
<el-form>
  <!-- 表单字段 -->

  <el-form-item>
    <!-- 创建模式：需要创建权限 -->
    <el-button
      v-if="!isEdit"
      v-permission="'articles.create'"
      type="primary"
      @click="handleSubmit"
    >
      创建
    </el-button>

    <!-- 编辑模式：需要编辑权限 -->
    <el-button
      v-else
      v-permission="'articles.edit'"
      type="primary"
      @click="handleSubmit"
    >
      保存
    </el-button>
  </el-form-item>
</el-form>
```

### 场景 3: 批量操作权限控制

```vue
<template>
  <div>
    <!-- 批量删除按钮 -->
    <el-button
      v-if="selectedRows.length > 0"
      v-permission="'articles.delete'"
      type="danger"
      @click="handleBatchDelete"
    >
      批量删除 ({{ selectedRows.length }})
    </el-button>

    <!-- 批量发布按钮 -->
    <el-button
      v-if="selectedRows.length > 0"
      v-permission="'articles.publish'"
      type="success"
      @click="handleBatchPublish"
    >
      批量发布
    </el-button>
  </div>
</template>
```

### 场景 4: 下拉菜单根据权限动态显示

```vue
<el-dropdown>
  <span>更多操作</span>
  <template #dropdown>
    <el-dropdown-menu>
      <el-dropdown-item
        v-permission="'articles.publish'"
        @click="handlePublish"
      >
        发布
      </el-dropdown-item>

      <el-dropdown-item
        v-permission="'articles.offline'"
        @click="handleOffline"
      >
        下线
      </el-dropdown-item>

      <el-dropdown-item
        v-permission="'articles.export'"
        @click="handleExport"
      >
        导出
      </el-dropdown-item>
    </el-dropdown-menu>
  </template>
</el-dropdown>
```

---

## 最佳实践

### ✅ DO (推荐做法)

1. **权限 ID 命名清晰**
   ```javascript
   // 好
   'articles.create'
   'users.reset_password'

   // 不好
   'create'
   'reset'
   ```

2. **页面入口添加权限检查**
   ```vue
   <template>
     <div v-permission="'articles.view'" class="article-list">
       <!-- 页面内容 -->
     </div>
   </template>
   ```

3. **每个操作按钮都加权限**
   ```vue
   <el-button v-permission="'articles.create'" @click="create">
     创建
   </el-button>
   ```

4. **权限配置保持层级结构**
   ```javascript
   // 好：清晰的树形结构
   content -> articles -> articles.create

   // 不好：扁平化
   articles_create
   ```

5. **为新功能及时添加权限定义**
   - 开发新功能时立即在 `permissions.js` 中添加
   - 不要等到上线前才添加

### ❌ DON'T (避免做法)

1. **不要硬编码权限判断**
   ```javascript
   // 错误
   if (userInfo.role_id === 1) { ... }

   // 正确
   if (permissionStore.hasPermission('articles.create')) { ... }
   ```

2. **不要忘记同步更新权限配置**
   ```javascript
   // 添加了新按钮但忘记在 permissions.js 定义权限
   <el-button v-permission="'articles.archive'">归档</el-button>
   // ❌ 但 permissions.js 中没有 articles.archive
   ```

3. **不要重复定义相同的权限ID**
   ```javascript
   // 错误：两个模块使用相同ID
   { id: 'delete', name: '删除文章', type: 'action' }
   { id: 'delete', name: '删除用户', type: 'action' }

   // 正确：使用唯一ID
   { id: 'articles.delete', name: '删除文章', type: 'action' }
   { id: 'users.delete', name: '删除用户', type: 'action' }
   ```

4. **不要在生产环境给测试角色过多权限**
   - 始终遵循最小权限原则
   - 只给予必要的权限

---

## 快速检查清单

添加新功能时，请确认以下步骤：

- [ ] 1. 在 `permissions.js` 中添加权限定义
- [ ] 2. 在 `router/index.js` 中添加路由（如果是新页面）
- [ ] 3. 在 `MainLayout.vue` 中添加菜单项（如果是新菜单）
- [ ] 4. 在页面组件中使用 `v-permission` 控制按钮
- [ ] 5. 登录后台为角色分配新权限
- [ ] 6. 测试不同角色的访问权限

---

## 常见问题

### Q: 添加权限后，为什么用户看不到新功能？

A: 需要重新为角色分配权限：
1. 进入：系统管理 → 角色管理
2. 点击"设置权限"
3. 勾选新权限并保存
4. 用户重新登录

### Q: 超级管理员需要手动分配权限吗？

A: 不需要。role_id=1 的超级管理员自动拥有所有权限（包括未来新增的权限）

### Q: 权限 ID 可以修改吗？

A: 可以，但需要：
1. 更新 `permissions.js`
2. 更新所有使用该权限的页面
3. 数据库中已分配的权限会失效，需重新分配

### Q: 如何批量为多个角色分配相同权限？

A: 目前需要逐个角色设置。建议：
1. 先为一个角色设置好权限
2. 复制该角色的权限JSON
3. 直接在数据库更新其他角色的permissions字段

---

## 技术支持

如有问题，请参考：
- 权限配置文件：`backend/src/config/permissions.js`
- 权限Store：`backend/src/store/permission.js`
- 权限指令：`backend/src/directives/permission.js`
- 用户Store：`backend/src/store/user.js`

最后更新：2025-10-13
