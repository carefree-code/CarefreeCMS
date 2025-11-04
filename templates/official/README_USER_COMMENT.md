# Official模板 - 用户功能和评论系统使用说明

## 📦 已完成的集成

### ✅ 文件清单

#### 页面文件 (templates/official/)
- `login.html` - 登录页面
- `register.html` - 注册页面
- `layout.html` - 已修改（添加用户导航）
- `article.html` - 已修改（添加评论功能）

#### JavaScript文件 (templates/official/assets/js/)
- `auth.js` - 用户认证功能
- `comment.js` - 评论系统功能

#### CSS文件 (templates/official/assets/css/)
- `comment.css` - 评论样式

#### 图片资源 (templates/official/assets/images/)
- `avatar-placeholder.png` - 需要准备（见images/README.md）

## 🚀 使用步骤

### 1. 准备图片资源

创建或下载头像占位图：
```bash
# 临时可以使用在线URL：
# https://ui-avatars.com/api/?name=User&size=200&background=6c757d&color=fff
```

或者在 `templates/official/assets/images/` 目录下放置 `avatar-placeholder.png` 文件。

### 2. 生成静态页面

在后台或使用命令生成静态页面：

**方式一：后台操作**
```
登录后台 → 内容管理 → 生成静态页面
```

**方式二：命令行**
```bash
cd D:\work\cms\api
php think build:static
```

生成后，文件会被复制到 `html/` 目录：
```
html/
├── login.html
├── register.html
├── article-{id}.html
└── assets/
    ├── js/
    │   ├── auth.js
    │   └── comment.js
    └── css/
        └── comment.css
```

### 3. 测试功能

#### (1) 测试注册功能
```
访问: http://localhost:8000/register.html

填写信息：
- 用户名: testuser
- 邮箱: test@example.com
- 密码: test123456
- 确认密码: test123456
- 勾选服务条款

提交注册
```

#### (2) 测试登录功能
```
访问: http://localhost:8000/login.html

输入刚才注册的信息：
- 邮箱: test@example.com
- 密码: test123456

点击登录
```

登录成功后：
- 导航栏会显示用户名
- 登录/注册按钮消失
- 显示用户下拉菜单

#### (3) 测试评论功能

**注册用户评论：**
```
1. 确保已登录
2. 访问任意文章页面
3. 在评论框输入内容
4. 点击"发表评论"
```

**游客评论：**
```
1. 退出登录
2. 访问文章页面
3. 在评论框输入内容
4. 点击"发表评论"
5. 弹出提示输入昵称和邮箱
6. 填写后提交
```

#### (4) 测试评论互动
```
- 点赞评论（需要登录）
- 回复评论（点击回复按钮）
- 查看评论树形结构
```

## 🎯 功能说明

### 用户认证功能

**登录状态检测**
- 所有页面自动检测登录状态
- 已登录显示用户菜单
- 未登录显示登录/注册按钮

**退出登录**
- 点击用户菜单 → 退出登录
- 清除本地Token
- 跳转到首页

**Token存储**
- Token存储在 localStorage
- 键名: `userToken`
- 用户信息: `userInfo`

### 评论系统功能

**评论发表**
- 支持注册用户评论
- 支持游客评论（需填写昵称和邮箱）
- 自动检测敏感词
- 可配置是否需要审核

**评论互动**
- 点赞评论（需要登录）
- 回复评论（支持嵌套）
- 查看评论作者信息
- 显示管理员标识

**评论展示**
- 树形结构展示
- 实时显示评论数
- 加载动画
- 空状态提示

## 📝 API接口说明

所有API都指向后端 `/api` 路径：

### 用户认证接口
```
POST /api/front/auth/register   - 注册
POST /api/front/auth/login      - 登录
POST /api/front/auth/logout     - 退出登录
GET  /api/front/auth/info       - 获取用户信息
```

### 评论接口
```
GET    /api/front/comments                - 获取评论列表
POST   /api/front/comments                - 发表评论
POST   /api/front/comments/like           - 点赞评论
POST   /api/front/comments/unlike         - 取消点赞
POST   /api/front/comments/report         - 举报评论
```

## ⚙️ 配置选项

### 评论系统配置

在后台系统配置中可以设置：
```php
[
    'enable_guest_comment' => true,   // 是否允许游客评论
    'auto_approve' => false,          // 是否自动审核通过
    'enable_sensitive_filter' => true // 是否启用敏感词过滤
]
```

## 🎨 样式定制

### 修改主色调

编辑 `templates/official/assets/css/comment.css`：
```css
/* 修改评论区的主色调 */
.comment-form textarea:focus {
    border-color: #your-color; /* 改为你的颜色 */
}
```

### 修改头像尺寸

在 `comment.css` 中修改：
```css
.comment-avatar img {
    width: 48px;   /* 修改宽度 */
    height: 48px;  /* 修改高度 */
}
```

## 🔧 故障排查

### 1. 登录后导航栏没有变化
**原因**: auth.js未加载或Token未保存
**解决**:
- 检查浏览器控制台是否有错误
- 检查localStorage中是否有userToken
- 刷新页面重试

### 2. 评论无法加载
**原因**: API接口错误或文章ID未传递
**解决**:
- 打开浏览器控制台查看错误
- 检查网络请求是否正常
- 确认 `{$article.id}` 变量正确传递

### 3. 游客评论提示登录
**原因**: 后台配置不允许游客评论
**解决**:
- 检查后台系统配置
- 设置 `enable_guest_comment` 为 true

### 4. 评论发表后不显示
**原因**: 评论需要审核
**解决**:
- 这是正常情况
- 在后台评论管理中审核通过即可显示
- 或设置 `auto_approve` 为 true

### 5. 样式错乱
**原因**: CSS文件未加载
**解决**:
- 检查 `html/assets/css/comment.css` 文件是否存在
- 重新生成静态页面
- 清除浏览器缓存

## 📊 数据流程

### 登录流程
```
用户输入邮箱密码
    ↓
POST /api/front/auth/login
    ↓
后端验证用户信息
    ↓
返回Token和用户信息
    ↓
保存到localStorage
    ↓
更新导航栏UI
```

### 评论流程
```
用户输入评论内容
    ↓
检查登录状态
    ├─ 已登录: 直接提交
    └─ 未登录: 提示输入昵称邮箱
        ↓
POST /api/front/comments
    ↓
后端验证和过滤
    ├─ 敏感词检测
    ├─ 长度验证
    └─ 防刷检测
        ↓
保存评论（待审核/已通过）
    ↓
返回结果
    ↓
刷新评论列表
```

## 🚨 注意事项

1. **JavaScript语法**: 所有代码使用传统function语法，避免箭头函数

2. **路径问题**: 静态资源路径都是相对于html目录的绝对路径 `/assets/...`

3. **Token安全**: Token存储在localStorage中，生产环境建议添加过期检查

4. **跨域问题**: 如果前后端分离部署，需要配置CORS

5. **图片资源**: 确保头像占位图存在，否则会显示损坏的图片图标

## 📚 参考文档

- 评论系统开发文档: `/docs/COMMENT_SYSTEM.md`
- 模板开发指南: `/docs/TEMPLATE_DEVELOPMENT_GUIDE.md`
- 模板快速参考: `/docs/TEMPLATE_QUICK_REFERENCE.md`

## 🎓 扩展功能建议

1. **用户中心页面** - 显示个人信息、收藏、评论历史
2. **评论分页** - 当评论数量较多时添加分页
3. **评论排序** - 支持按时间、热度排序
4. **评论编辑** - 允许用户编辑自己的评论
5. **评论删除** - 允许用户删除自己的评论
6. **表情支持** - 在评论中添加表情选择器
7. **图片上传** - 支持在评论中上传图片
8. **@提及** - 支持@其他用户
9. **邮件通知** - 评论被回复时发送邮件通知

---

**版本**: v1.0
**更新日期**: 2025-10-28
**适用模板**: official
**维护者**: CMS开发团队
