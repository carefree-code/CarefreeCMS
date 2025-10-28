# 日志记录开发指南

## 概述

本系统提供了完善的操作日志记录功能，用于记录用户的所有重要操作。所有日志都存储在 `operation_logs` 数据表中。

## 日志记录类

### Logger 类

位置：`api/app/common/Logger.php`

Logger 类提供了多种便捷方法来记录不同类型的操作。

### OperationLog 模型

位置：`api/app/model/OperationLog.php`

定义了模块常量和操作类型常量。

## 基本使用

### 1. 引入必要的类

在控制器文件顶部引入：

```php
use app\common\Logger;
use app\model\OperationLog;
```

### 2. 记录创建操作

```php
try {
    $item = Model::create($data);
    Logger::create(OperationLog::MODULE_XXX, '项目名称', $item->id);
    return Response::success(['id' => $item->id], '创建成功');
} catch (\Exception $e) {
    Logger::log(
        OperationLog::MODULE_XXX,
        OperationLog::ACTION_CREATE,
        '创建XXX失败',
        false,
        $e->getMessage()
    );
    return Response::error('创建失败：' . $e->getMessage());
}
```

### 3. 记录更新操作

```php
try {
    $item->save($data);
    Logger::update(OperationLog::MODULE_XXX, '项目名称', $id);
    return Response::success([], '更新成功');
} catch (\Exception $e) {
    Logger::log(
        OperationLog::MODULE_XXX,
        OperationLog::ACTION_UPDATE,
        "更新XXX失败 (ID: {$id})",
        false,
        $e->getMessage()
    );
    return Response::error('更新失败：' . $e->getMessage());
}
```

### 4. 记录删除操作

```php
try {
    $itemName = $item->name; // 保存名称用于日志
    $item->delete();
    Logger::delete(OperationLog::MODULE_XXX, "项目[{$itemName}]", $id);
    return Response::success([], '删除成功');
} catch (\Exception $e) {
    Logger::log(
        OperationLog::MODULE_XXX,
        OperationLog::ACTION_DELETE,
        "删除XXX失败 (ID: {$id})",
        false,
        $e->getMessage()
    );
    return Response::error('删除失败：' . $e->getMessage());
}
```

## 常用日志方法

### 基础操作日志

| 方法 | 说明 | 示例 |
|------|------|------|
| `Logger::create()` | 记录创建操作 | `Logger::create(OperationLog::MODULE_ARTICLE, '文章', $articleId)` |
| `Logger::update()` | 记录更新操作 | `Logger::update(OperationLog::MODULE_ARTICLE, '文章', $articleId)` |
| `Logger::delete()` | 记录删除操作 | `Logger::delete(OperationLog::MODULE_ARTICLE, '文章', $articleId)` |
| `Logger::publish()` | 记录发布操作 | `Logger::publish(OperationLog::MODULE_ARTICLE, '文章', $articleId)` |
| `Logger::offline()` | 记录下线操作 | `Logger::offline(OperationLog::MODULE_ARTICLE, '文章', $articleId)` |

### 批量操作日志

```php
// 批量删除
Logger::batchDelete(OperationLog::MODULE_ARTICLE, '文章', $ids);

// 批量操作失败
Logger::batchOperationFailed(
    OperationLog::MODULE_ARTICLE,
    OperationLog::ACTION_DELETE,
    '文章',
    count($ids),
    $errorMessage
);
```

### 特殊操作日志

```php
// 上传文件
Logger::upload($fileName, $fileSize);

// 静态生成
Logger::build('全部页面', '生成了100个页面');

// 导出数据
Logger::export(OperationLog::MODULE_ARTICLE, 'Excel文件', $count);

// 导入数据
Logger::import(OperationLog::MODULE_ARTICLE, 'Excel文件', $successCount, $failCount);

// 清理缓存
Logger::clearCache('文章缓存');

// 更新配置
Logger::updateConfig('网站基本信息');

// 重置密码
Logger::resetPassword($targetUsername);

// 修改密码
Logger::changePassword();
```

## 模块常量

当前系统支持的模块：

```php
OperationLog::MODULE_AUTH          // 认证
OperationLog::MODULE_ARTICLE       // 文章
OperationLog::MODULE_CATEGORY      // 分类
OperationLog::MODULE_TAG           // 标签
OperationLog::MODULE_ARTICLE_FLAG  // 文章属性
OperationLog::MODULE_PAGE          // 单页
OperationLog::MODULE_MEDIA         // 媒体
OperationLog::MODULE_USER          // 用户
OperationLog::MODULE_ROLE          // 角色
OperationLog::MODULE_CONFIG        // 配置
OperationLog::MODULE_PROFILE       // 个人信息
OperationLog::MODULE_BUILD         // 静态生成
```

## 操作类型常量

```php
OperationLog::ACTION_LOGIN          // 登录
OperationLog::ACTION_LOGOUT         // 登出
OperationLog::ACTION_CREATE         // 创建
OperationLog::ACTION_UPDATE         // 更新
OperationLog::ACTION_DELETE         // 删除
OperationLog::ACTION_PUBLISH        // 发布
OperationLog::ACTION_OFFLINE        // 下线
OperationLog::ACTION_UPLOAD         // 上传
OperationLog::ACTION_BUILD          // 生成
OperationLog::ACTION_RESET_PASSWORD // 重置密码
OperationLog::ACTION_CHANGE_PASSWORD // 修改密码
OperationLog::ACTION_EXPORT         // 导出
OperationLog::ACTION_IMPORT         // 导入
OperationLog::ACTION_CLEAR_CACHE    // 清理缓存
```

## 完整示例：Category 控制器

```php
<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Logger;
use app\model\Category as CategoryModel;
use app\model\OperationLog;
use think\Request;

class Category extends BaseController
{
    /**
     * 创建分类
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证逻辑...

        try {
            $category = CategoryModel::create($data);
            Logger::create(OperationLog::MODULE_CATEGORY, '分类', $category->id);
            return Response::success(['id' => $category->id], '分类创建成功');
        } catch (\Exception $e) {
            Logger::log(
                OperationLog::MODULE_CATEGORY,
                OperationLog::ACTION_CREATE,
                '创建分类失败',
                false,
                $e->getMessage()
            );
            return Response::error('分类创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新分类
     */
    public function update(Request $request, $id)
    {
        $category = CategoryModel::find($id);
        if (!$category) {
            return Response::notFound('分类不存在');
        }

        $data = $request->post();

        try {
            $category->save($data);
            Logger::update(OperationLog::MODULE_CATEGORY, '分类', $id);
            return Response::success([], '分类更新成功');
        } catch (\Exception $e) {
            Logger::log(
                OperationLog::MODULE_CATEGORY,
                OperationLog::ACTION_UPDATE,
                "更新分类失败 (ID: {$id})",
                false,
                $e->getMessage()
            );
            return Response::error('分类更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除分类
     */
    public function delete($id)
    {
        $category = CategoryModel::find($id);
        if (!$category) {
            return Response::notFound('分类不存在');
        }

        // 业务验证...

        try {
            $categoryName = $category->name;
            $category->delete();
            Logger::delete(OperationLog::MODULE_CATEGORY, "分类[{$categoryName}]", $id);
            return Response::success([], '分类删除成功');
        } catch (\Exception $e) {
            Logger::log(
                OperationLog::MODULE_CATEGORY,
                OperationLog::ACTION_DELETE,
                "删除分类失败 (ID: {$id})",
                false,
                $e->getMessage()
            );
            return Response::error('分类删除失败：' . $e->getMessage());
        }
    }
}
```

## 待完善的控制器

以下控制器需要添加日志记录：

### 高优先级
- [x] Category.php - 已完成
- [x] Tag.php - 已完成
- [ ] Page.php
- [ ] Media.php
- [ ] User.php
- [ ] Config.php
- [ ] TopicController.php

### 中优先级
- [ ] Role.php
- [ ] ArticleFlag.php
- [ ] SliderController.php
- [ ] SliderGroupController.php
- [ ] AdController.php
- [ ] AdPositionController.php
- [ ] LinkController.php
- [ ] LinkGroupController.php

### 低优先级
- [ ] SeoRedirectController.php
- [ ] SeoRobotController.php
- [ ] Template.php
- [ ] CacheController.php

## 日志记录最佳实践

### 1. 总是在try-catch中记录日志

```php
try {
    // 业务逻辑
    Logger::create(...);
    return Response::success(...);
} catch (\Exception $e) {
    Logger::log(..., false, $e->getMessage());
    return Response::error(...);
}
```

### 2. 记录详细的上下文信息

```php
// 好的实践
Logger::delete(OperationLog::MODULE_ARTICLE, "文章[{$article->title}]", $id);

// 不好的实践
Logger::delete(OperationLog::MODULE_ARTICLE, '文章', $id);
```

### 3. 失败操作必须记录错误信息

```php
Logger::log(
    $module,
    $action,
    $description,
    false,  // status = false 表示失败
    $e->getMessage()  // 必须包含错误信息
);
```

### 4. 敏感信息自动过滤

Logger 类会自动过滤以下敏感字段：
- password
- old_password
- new_password
- confirm_password
- token

这些字段在日志中会被替换为 `******`。

## 查看日志

### 后台界面

访问：系统管理 > 操作日志

支持：
- 按模块筛选
- 按操作类型筛选
- 按用户筛选
- 按时间范围筛选
- 按状态筛选（成功/失败）
- 按关键词搜索

### API接口

```
GET /api/operation-logs
```

参数：
- page: 页码
- pageSize: 每页数量
- module: 模块
- action: 操作类型
- user_id: 用户ID
- status: 状态（0/1）
- start_date: 开始日期
- end_date: 结束日期
- keyword: 关键词

## 注意事项

1. **不影响业务** - 日志记录失败不会抛出异常，不会影响正常业务
2. **性能考虑** - 日志采用异步方式记录，对性能影响极小
3. **存储空间** - 建议定期清理旧日志，保留30-90天即可
4. **查询优化** - operation_logs 表已建立必要的索引

## 扩展Logger类

如果需要添加新的日志方法，在 `Logger.php` 中添加：

```php
/**
 * 记录自定义操作
 */
public static function customAction(string $module, string $description): bool
{
    return self::log(
        $module,
        'custom_action',
        $description
    );
}
```

同时在 `OperationLog.php` 中添加常量：

```php
const ACTION_CUSTOM_ACTION = 'custom_action';
```

并更新 `getActionNames()` 方法：

```php
self::ACTION_CUSTOM_ACTION => '自定义操作',
```
