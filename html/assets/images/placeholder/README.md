# 占位图说明

本目录包含系统使用的本地SVG占位图，用于替代外部占位图服务（如 via.placeholder.com），确保在国内环境下正常显示。

## 占位图列表

| 文件名 | 尺寸 | 用途 | 颜色 |
|--------|------|------|------|
| article.svg | 400×250 | 文章封面占位图 | 蓝紫色 (#667eea) |
| article-purple.svg | 400×250 | 标签页文章封面 | 紫色 (#764ba2) |
| article-small.svg | 400×200 | 相关文章小图 | 蓝紫色 (#667eea) |
| avatar.svg | 80×80 | 用户头像占位图 | 蓝紫色 (#667eea) |
| dashboard.svg | 600×450 | 仪表板展示图 | 蓝紫色 (#667eea) |
| showcase-corporate.svg | 400×250 | 企业官网展示 | 蓝紫色 (#667eea) |
| showcase-news.svg | 400×250 | 新闻门户展示 | 紫色 (#764ba2) |
| showcase-ecommerce.svg | 400×250 | 电商平台展示 | 蓝紫色 (#667eea) |

## 技术说明

- 格式：SVG（可缩放矢量图形）
- 优点：
  - 体积小，加载快
  - 无需外部依赖
  - 可任意缩放不失真
  - 国内访问无障碍

## 模板使用示例

```html
<!-- 文章封面图 -->
<img src="{$article.cover_image ?: '/assets/images/placeholder/article.svg'}" alt="文章封面">

<!-- 用户头像 -->
<img src="{$user.avatar ?: '/assets/images/placeholder/avatar.svg'}" alt="用户头像">
```

## 自定义占位图

如需自定义占位图，可直接编辑 SVG 文件或创建新的 SVG 文件。

SVG 模板示例：

```xml
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="250" viewBox="0 0 400 250">
  <rect fill="#667eea" width="400" height="250"/>
  <g fill="#ffffff" font-family="Arial, sans-serif" text-anchor="middle">
    <text x="200" y="115" font-size="24">📝</text>
    <text x="200" y="145" font-size="18">Your Text</text>
  </g>
</svg>
```
