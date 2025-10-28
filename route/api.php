<?php

use think\facade\Route;

// ============================================
// API路由配置
// ============================================

// 跨域中间件应用于所有API路由
Route::group('api', function () {

    // ========== 认证相关（不需要JWT认证） ==========
    Route::group('auth', function () {
        Route::post('login', 'app\controller\api\Auth@login');          // 登录
    });

    // ========== 需要JWT认证的接口 ==========
    Route::group(function () {

        // 用户信息与认证操作
        Route::get('auth/info', 'app\controller\api\Auth@info');                  // 获取当前用户信息
        Route::post('auth/logout', 'app\controller\api\Auth@logout');             // 退出登录（需要认证）
        Route::post('auth/change-password', 'app\controller\api\Auth@changePassword'); // 修改密码

        // 仪表板统计
        Route::get('dashboard/stats', 'app\controller\api\Dashboard@stats');           // 获取统计数据
        Route::get('dashboard/server-info', 'app\controller\api\Dashboard@serverInfo'); // 获取服务器信息
        Route::get('dashboard/system-info', 'app\controller\api\Dashboard@systemInfo'); // 获取系统信息

        // 文章管理
        Route::get('articles/fulltext-search', 'app\controller\api\Article@fullTextSearch');       // 全文搜索
        Route::get('articles/advanced-search', 'app\controller\api\Article@advancedSearch');       // 高级搜索
        Route::get('articles/search-suggestions', 'app\controller\api\Article@searchSuggestions'); // 搜索建议
        Route::resource('articles', 'app\controller\api\Article');                // RESTful文章资源
        Route::post('articles/:id/publish', 'app\controller\api\Article@publish');  // 发布文章
        Route::post('articles/:id/offline', 'app\controller\api\Article@offline');  // 下线文章

        // 文章版本管理
        Route::get('articles/:article_id/versions', 'app\controller\api\ArticleVersion@index');           // 获取文章的版本列表
        Route::get('articles/:article_id/versions/statistics', 'app\controller\api\ArticleVersion@statistics'); // 获取版本统计
        Route::get('article-versions/:id', 'app\controller\api\ArticleVersion@read');                     // 获取版本详情
        Route::get('article-versions/compare', 'app\controller\api\ArticleVersion@compare');              // 对比两个版本
        Route::post('article-versions/:id/rollback', 'app\controller\api\ArticleVersion@rollback');       // 回滚到指定版本
        Route::delete('article-versions/:id', 'app\controller\api\ArticleVersion@delete');                // 删除版本
        Route::post('article-versions/batch-delete', 'app\controller\api\ArticleVersion@batchDelete');    // 批量删除版本

        // 分类管理
        Route::get('categories/tree', 'app\controller\api\Category@tree');        // 分类树（需在resource之前）
        Route::resource('categories', 'app\controller\api\Category');             // RESTful分类资源

        // 标签管理
        Route::get('tags/all', 'app\controller\api\Tag@all');                     // 所有标签（不分页）
        Route::resource('tags', 'app\controller\api\Tag');                        // RESTful标签资源

        // 文章属性管理
        Route::get('article-flags/all', 'app\controller\api\ArticleFlag@all');   // 所有文章属性（不分页）
        Route::resource('article-flags', 'app\controller\api\ArticleFlag');       // RESTful文章属性资源

        // 页面管理
        Route::get('pages/all', 'app\controller\api\Page@all');                   // 所有单页（不分页）
        Route::resource('pages', 'app\controller\api\Page');                      // RESTful页面资源

        // 评论管理
        Route::resource('comments', 'app\controller\api\Comment');                // RESTful评论资源
        Route::post('comments/:id/audit', 'app\controller\api\Comment@audit');   // 审核评论

        // 媒体库
        Route::post('media/upload', 'app\controller\api\Media@upload');           // 上传文件
        Route::get('media', 'app\controller\api\Media@index');                    // 文件列表
        Route::delete('media/:id', 'app\controller\api\Media@delete');            // 删除文件

        // 用户管理
        Route::post('users/:id/reset-password', 'app\controller\api\User@resetPassword'); // 重置密码（需在resource之前）
        Route::resource('users', 'app\controller\api\User');                      // RESTful用户资源

        // 角色管理
        Route::get('roles/all', 'app\controller\api\Role@all');                   // 所有角色（不分页）
        Route::resource('roles', 'app\controller\api\Role');                      // RESTful角色资源

        // 站点配置
        Route::get('config', 'app\controller\api\Config@index');                  // 获取配置
        Route::post('config', 'app\controller\api\Config@save');                  // 保存配置

        // 个人信息
        Route::get('profile', 'app\controller\api\Profile@index');                // 获取个人信息
        Route::put('profile', 'app\controller\api\Profile@update');               // 更新个人信息
        Route::post('profile/password', 'app\controller\api\Profile@updatePassword'); // 修改密码
        Route::post('profile/avatar', 'app\controller\api\Profile@uploadAvatar'); // 上传头像

        // 操作日志
        Route::get('operation-logs', 'app\controller\api\OperationLog@index');    // 日志列表
        Route::get('operation-logs/modules', 'app\controller\api\OperationLog@modules'); // 模块列表
        Route::get('operation-logs/actions', 'app\controller\api\OperationLog@actions'); // 操作类型列表
        Route::get('operation-logs/:id', 'app\controller\api\OperationLog@read'); // 日志详情
        Route::post('operation-logs/clear', 'app\controller\api\OperationLog@clear'); // 清空日志

        // 模板管理（注意：更具体的路由要放在前面）
        // 在线模板编辑（必须放在最前面，避免被 templates 通用路由匹配）
        Route::get('templates/file-tree', 'app\controller\api\Template@getFileTree');     // 获取文件树
        Route::get('templates/read-file', 'app\controller\api\Template@readFile');        // 读取文件
        Route::post('templates/save-file', 'app\controller\api\Template@saveFile');       // 保存文件
        Route::post('templates/create-file', 'app\controller\api\Template@createFile');   // 创建文件
        Route::post('templates/delete-file', 'app\controller\api\Template@deleteFile');   // 删除文件
        Route::get('templates/backups', 'app\controller\api\Template@getBackups');        // 获取历史记录列表
        Route::get('templates/history-content', 'app\controller\api\Template@getHistoryContent'); // 获取历史版本内容
        Route::post('templates/restore-history', 'app\controller\api\Template@restoreHistory');   // 恢复历史版本

        // 模板套装管理
        Route::get('templates/current-theme', 'app\controller\api\Template@getCurrentTheme'); // 获取当前模板套装
        Route::get('templates/themes', 'app\controller\api\Template@scanThemes');  // 扫描所有模板套装
        Route::get('templates/scan', 'app\controller\api\Template@scanTemplates'); // 扫描模板文件
        Route::post('templates/switch-theme', 'app\controller\api\Template@switchTheme'); // 切换模板套装
        Route::get('templates', 'app\controller\api\Template@scanTemplates');     // 获取当前套装的模板文件列表

        // 静态页面生成
        Route::post('build/all', 'app\controller\api\Build@all');                 // 生成所有静态页
        Route::post('build/index', 'app\controller\api\Build@index');             // 生成首页
        Route::post('build/articles', 'app\controller\api\Build@articles');       // 生成文章列表页
        Route::post('build/article/:id', 'app\controller\api\Build@article');     // 生成文章详情页
        Route::post('build/categories', 'app\controller\api\Build@categories');   // 生成所有分类页
        Route::post('build/category/:id', 'app\controller\api\Build@category');   // 生成单个分类页
        Route::post('build/tags', 'app\controller\api\Build@tags');               // 生成所有标签页
        Route::post('build/tag/:id', 'app\controller\api\Build@tag');             // 生成单个标签页
        Route::post('build/topics', 'app\controller\api\Build@topics');           // 生成所有专题页
        Route::post('build/topic/:id', 'app\controller\api\Build@topic');         // 生成单个专题页
        Route::post('build/pages', 'app\controller\api\Build@pages');             // 生成所有单页面
        Route::post('build/page/:id', 'app\controller\api\Build@page');           // 生成单个单页面
        Route::get('build/logs', 'app\controller\api\Build@logs');                // 生成日志

        // 模板资源管理
        Route::post('build/sync-assets', 'app\controller\api\Build@syncAssets');  // 同步模板资源到静态目录
        Route::post('build/clean-assets', 'app\controller\api\Build@cleanAssets'); // 清理旧资源
        Route::get('build/assets-list', 'app\controller\api\Build@getAssetsList'); // 获取资源列表

        // Sitemap生成
        Route::post('sitemap/all', 'app\controller\api\Sitemap@generateAll');     // 生成所有格式sitemap
        Route::post('sitemap/txt', 'app\controller\api\Sitemap@generateTxt');     // 生成TXT格式
        Route::post('sitemap/xml', 'app\controller\api\Sitemap@generateXml');     // 生成XML格式
        Route::post('sitemap/html', 'app\controller\api\Sitemap@generateHtml');   // 生成HTML格式

        // 回收站管理
        Route::get('recycle-bin/statistics', 'app\controller\api\RecycleBin@statistics');    // 回收站统计
        Route::post('recycle-bin/restore', 'app\controller\api\RecycleBin@restore');         // 恢复单个
        Route::post('recycle-bin/batch-restore', 'app\controller\api\RecycleBin@batchRestore'); // 批量恢复
        Route::post('recycle-bin/batch-destroy', 'app\controller\api\RecycleBin@batchDestroy'); // 批量彻底删除
        Route::post('recycle-bin/clear', 'app\controller\api\RecycleBin@clear');             // 清空回收站
        Route::delete('recycle-bin/:type/:id', 'app\controller\api\RecycleBin@destroy');     // 彻底删除
        Route::get('recycle-bin', 'app\controller\api\RecycleBin@index');                    // 回收站列表

        // 内容模型管理
        Route::get('content-models/all', 'app\controller\api\ContentModelController@all');   // 获取所有模型（不分页）
        Route::resource('content-models', 'app\controller\api\ContentModelController');      // RESTful内容模型资源

        // 自定义字段管理
        Route::get('custom-fields/field-types', 'app\controller\api\CustomFieldController@getFieldTypes');  // 字段类型列表
        Route::get('custom-fields/model-types', 'app\controller\api\CustomFieldController@getModelTypes');  // 模型类型列表
        Route::get('custom-fields/by-model', 'app\controller\api\CustomFieldController@getByModel');        // 根据模型获取字段
        Route::get('custom-fields/entity-values', 'app\controller\api\CustomFieldController@getEntityValues'); // 获取实体字段值
        Route::post('custom-fields/entity-values', 'app\controller\api\CustomFieldController@saveEntityValues'); // 保存实体字段值
        Route::resource('custom-fields', 'app\controller\api\CustomFieldController');        // RESTful自定义字段资源

        // 专题管理
        Route::get('topics/all', 'app\controller\api\TopicController@all');                         // 获取所有专题（不分页）
        Route::get('topics/:id/articles', 'app\controller\api\TopicController@articles');           // 获取专题的文章列表
        Route::post('topics/:id/add-article', 'app\controller\api\TopicController@addArticle');     // 添加文章到专题
        Route::post('topics/:id/remove-article', 'app\controller\api\TopicController@removeArticle'); // 从专题移除文章
        Route::post('topics/:id/set-articles', 'app\controller\api\TopicController@setArticles');   // 批量设置专题文章
        Route::post('topics/:id/update-article-sort', 'app\controller\api\TopicController@updateArticleSort'); // 更新文章排序
        Route::post('topics/:id/set-article-featured', 'app\controller\api\TopicController@setArticleFeatured'); // 设置精选文章
        Route::resource('topics', 'app\controller\api\TopicController');                             // RESTful专题资源

        // 友链分组管理
        Route::get('link-groups/all', 'app\controller\api\LinkGroupController@all');                // 获取所有分组（不分页）
        Route::resource('link-groups', 'app\controller\api\LinkGroupController');                    // RESTful分组资源

        // 友情链接管理
        Route::post('links/:id/audit', 'app\controller\api\LinkController@audit');                  // 审核链接
        Route::resource('links', 'app\controller\api\LinkController');                               // RESTful链接资源

        // 广告位管理
        Route::get('ad-positions/all', 'app\controller\api\AdPositionController@all');              // 获取所有广告位（不分页）
        Route::resource('ad-positions', 'app\controller\api\AdPositionController');                  // RESTful广告位资源

        // 广告管理
        Route::get('ads/:id/statistics', 'app\controller\api\AdController@statistics');             // 获取广告统计
        Route::post('ads/:id/click', 'app\controller\api\AdController@click');                      // 记录广告点击
        Route::resource('ads', 'app\controller\api\AdController');                                   // RESTful广告资源

        // 幻灯片组管理
        Route::get('slider-groups/all', 'app\controller\api\SliderGroupController@all');            // 获取所有分组（不分页）
        Route::resource('slider-groups', 'app\controller\api\SliderGroupController');                // RESTful幻灯片组资源

        // 幻灯片管理
        Route::get('sliders/group/:code', 'app\controller\api\SliderController@getByGroupCode');    // 按分组代码获取幻灯片
        Route::post('sliders/:id/click', 'app\controller\api\SliderController@click');              // 记录幻灯片点击
        Route::post('sliders/:id/view', 'app\controller\api\SliderController@view');                // 记录幻灯片展示
        Route::resource('sliders', 'app\controller\api\SliderController');                           // RESTful幻灯片资源

        // SEO重定向管理
        Route::get('seo-redirects/statistics', 'app\controller\api\SeoRedirectController@statistics');  // 重定向统计
        Route::post('seo-redirects/test', 'app\controller\api\SeoRedirectController@test');             // 测试重定向
        Route::post('seo-redirects/import', 'app\controller\api\SeoRedirectController@import');         // 导入规则
        Route::get('seo-redirects/export', 'app\controller\api\SeoRedirectController@export');          // 导出规则
        Route::get('seo-redirects/options', 'app\controller\api\SeoRedirectController@options');        // 获取配置选项
        Route::post('seo-redirects/batch-delete', 'app\controller\api\SeoRedirectController@batchDelete');  // 批量删除
        Route::post('seo-redirects/batch-toggle', 'app\controller\api\SeoRedirectController@batchToggle');  // 批量启用/禁用
        Route::resource('seo-redirects', 'app\controller\api\SeoRedirectController');                  // RESTful重定向资源

        // SEO 404日志管理
        Route::get('seo-404-logs/statistics', 'app\controller\api\Seo404LogController@statistics');    // 404统计
        Route::get('seo-404-logs/top-errors', 'app\controller\api\Seo404LogController@topErrors');     // 高频404
        Route::get('seo-404-logs/recent-errors', 'app\controller\api\Seo404LogController@recentErrors'); // 最近404
        Route::get('seo-404-logs/export', 'app\controller\api\Seo404LogController@export');            // 导出日志
        Route::post('seo-404-logs/:id/mark-fixed', 'app\controller\api\Seo404LogController@markFixed'); // 标记已修复
        Route::post('seo-404-logs/:id/ignore', 'app\controller\api\Seo404LogController@ignore');       // 忽略
        Route::post('seo-404-logs/:id/create-redirect', 'app\controller\api\Seo404LogController@createRedirect'); // 创建重定向
        Route::post('seo-404-logs/batch-mark-fixed', 'app\controller\api\Seo404LogController@batchMarkFixed'); // 批量标记
        Route::post('seo-404-logs/batch-delete', 'app\controller\api\Seo404LogController@batchDelete'); // 批量删除
        Route::post('seo-404-logs/clean', 'app\controller\api\Seo404LogController@clean');            // 清理旧日志
        Route::resource('seo-404-logs', 'app\controller\api\Seo404LogController');                     // RESTful 404日志资源

        // SEO Robots.txt管理
        Route::get('seo-robots/active', 'app\controller\api\SeoRobotController@active');               // 获取启用的配置
        Route::get('seo-robots/templates', 'app\controller\api\SeoRobotController@templates');         // 获取模板列表
        Route::get('seo-robots/current', 'app\controller\api\SeoRobotController@current');             // 查看当前文件
        Route::post('seo-robots/validate', 'app\controller\api\SeoRobotController@validateContent');    // 验证内容
        Route::post('seo-robots/apply-template', 'app\controller\api\SeoRobotController@applyTemplate'); // 应用模板
        Route::post('seo-robots/generate', 'app\controller\api\SeoRobotController@generate');          // 生成文件
        Route::post('seo-robots/:id/activate', 'app\controller\api\SeoRobotController@activate');      // 启用配置
        Route::resource('seo-robots', 'app\controller\api\SeoRobotController');                         // RESTful Robots资源

        // SEO分析工具
        Route::post('seo-analyzer/analyze', 'app\controller\api\SeoAnalyzerController@analyzeArticle');        // 分析文章SEO
        Route::get('seo-analyzer/analyze/:id', 'app\controller\api\SeoAnalyzerController@analyzeArticle');     // 分析指定文章
        Route::post('seo-analyzer/keyword-density', 'app\controller\api\SeoAnalyzerController@keywordDensity'); // 关键词密度
        Route::post('seo-analyzer/generate-title', 'app\controller\api\SeoAnalyzerController@generateTitle');  // 生成标题
        Route::post('seo-analyzer/generate-description', 'app\controller\api\SeoAnalyzerController@generateDescription'); // 生成描述
        Route::post('seo-analyzer/extract-keywords', 'app\controller\api\SeoAnalyzerController@extractKeywords'); // 提取关键词
        Route::get('seo-analyzer/suggestions/:id', 'app\controller\api\SeoAnalyzerController@getSuggestions'); // 获取建议
        Route::post('seo-analyzer/auto-optimize/:id', 'app\controller\api\SeoAnalyzerController@autoOptimize'); // 自动优化
        Route::post('seo-analyzer/batch-analyze', 'app\controller\api\SeoAnalyzerController@batchAnalyze');    // 批量分析

        // 增强Sitemap生成
        Route::post('seo-sitemap/generate', 'app\controller\api\SeoAnalyzerController@generateSitemap');       // 生成sitemap
        Route::post('seo-sitemap/ping', 'app\controller\api\SeoAnalyzerController@pingSitemap');               // Ping搜索引擎

        // 数据库管理
        Route::get('database/info', 'app\controller\api\DatabaseController@getInfo');                          // 获取数据库信息
        Route::get('database/tables', 'app\controller\api\DatabaseController@getTables');                      // 获取表信息
        Route::post('database/backup', 'app\controller\api\DatabaseController@backup');                        // 完整备份
        Route::post('database/backup-tables', 'app\controller\api\DatabaseController@backupTables');           // 备份指定表
        Route::get('database/backups', 'app\controller\api\DatabaseController@getBackups');                    // 备份列表
        Route::post('database/restore', 'app\controller\api\DatabaseController@restore');                      // 恢复数据库
        Route::post('database/validate-backup', 'app\controller\api\DatabaseController@validateBackup');       // 验证备份文件
        Route::delete('database/backup/:id', 'app\controller\api\DatabaseController@deleteBackup');            // 删除备份
        Route::get('database/download-backup', 'app\controller\api\DatabaseController@downloadBackup');        // 下载备份
        Route::post('database/optimize', 'app\controller\api\DatabaseController@optimize');                    // 优化表
        Route::post('database/repair', 'app\controller\api\DatabaseController@repair');                        // 修复表
        Route::post('database/clean-old-backups', 'app\controller\api\DatabaseController@cleanOldBackups');    // 清理旧备份

        // 缓存管理
        Route::get('cache/info', 'app\controller\api\CacheController@getInfo');                                // 获取缓存信息
        Route::get('cache/driver', 'app\controller\api\CacheController@getDriver');                            // 获取当前驱动
        Route::post('cache/switch-driver', 'app\controller\api\CacheController@switchDriver');                 // 切换缓存驱动
        Route::post('cache/test-redis', 'app\controller\api\CacheController@testRedis');                       // 测试Redis连接
        Route::post('cache/clear-all', 'app\controller\api\CacheController@clearAll');                         // 清空所有缓存
        Route::post('cache/clear-tag', 'app\controller\api\CacheController@clearTag');                         // 清除标签缓存
        Route::delete('cache', 'app\controller\api\CacheController@delete');                                   // 删除指定缓存
        Route::post('cache/clear-template', 'app\controller\api\CacheController@clearTemplate');               // 清除模板缓存
        Route::post('cache/clear-logs', 'app\controller\api\CacheController@clearLogs');                       // 清除日志文件
        Route::get('cache/keys', 'app\controller\api\CacheController@getKeys');                                // 获取缓存键列表
        Route::get('cache/get', 'app\controller\api\CacheController@get');                                     // 获取缓存值
        Route::post('cache/set', 'app\controller\api\CacheController@set');                                    // 设置缓存值
        Route::post('cache/warmup', 'app\controller\api\CacheController@warmup');                              // 缓存预热
        Route::post('cache/test-performance', 'app\controller\api\CacheController@testPerformance');           // 测试性能

        // 日志管理
        Route::get('logs/system', 'app\controller\api\LogController@getSystemLogs');                           // 获取系统日志
        Route::get('logs/login', 'app\controller\api\LogController@getLoginLogs');                             // 获取登录日志
        Route::get('logs/security', 'app\controller\api\LogController@getSecurityLogs');                       // 获取安全日志
        Route::get('logs/system/stats', 'app\controller\api\LogController@getSystemLogStats');                 // 系统日志统计
        Route::get('logs/login/stats', 'app\controller\api\LogController@getLoginLogStats');                   // 登录日志统计
        Route::get('logs/security/high-risk-ips', 'app\controller\api\LogController@getHighRiskIps');          // 高危IP列表
        Route::delete('logs/system/:id', 'app\controller\api\LogController@deleteSystemLog');                  // 删除系统日志
        Route::delete('logs/login/:id', 'app\controller\api\LogController@deleteLoginLog');                    // 删除登录日志
        Route::delete('logs/security/:id', 'app\controller\api\LogController@deleteSecurityLog');              // 删除安全日志
        Route::post('logs/system/batch-delete', 'app\controller\api\LogController@batchDeleteSystemLogs');     // 批量删除系统日志
        Route::post('logs/login/batch-delete', 'app\controller\api\LogController@batchDeleteLoginLogs');       // 批量删除登录日志
        Route::post('logs/security/batch-delete', 'app\controller\api\LogController@batchDeleteSecurityLogs'); // 批量删除安全日志
        Route::post('logs/clean-old', 'app\controller\api\LogController@cleanOldLogs');                        // 清理旧日志
        Route::get('logs/export', 'app\controller\api\LogController@exportLogs');                              // 导出日志

    })->middleware(\app\middleware\Auth::class);  // 应用JWT认证中间件

})->middleware(\app\middleware\Cors::class);  // 应用跨域中间件
