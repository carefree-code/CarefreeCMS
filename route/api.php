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
        Route::post('logout', 'app\controller\api\Auth@logout');        // 退出
    });

    // ========== 需要JWT认证的接口 ==========
    Route::group(function () {

        // 用户信息
        Route::get('auth/info', 'app\controller\api\Auth@info');                  // 获取当前用户信息
        Route::post('auth/change-password', 'app\controller\api\Auth@changePassword'); // 修改密码

        // 仪表板统计
        Route::get('dashboard/stats', 'app\controller\api\Dashboard@stats');           // 获取统计数据
        Route::get('dashboard/server-info', 'app\controller\api\Dashboard@serverInfo'); // 获取服务器信息
        Route::get('dashboard/system-info', 'app\controller\api\Dashboard@systemInfo'); // 获取系统信息

        // 文章管理
        Route::resource('articles', 'app\controller\api\Article');                // RESTful文章资源
        Route::post('articles/:id/publish', 'app\controller\api\Article@publish');  // 发布文章
        Route::post('articles/:id/offline', 'app\controller\api\Article@offline');  // 下线文章

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
        Route::post('build/category/:id', 'app\controller\api\Build@category');   // 生成分类列表页
        Route::post('build/tags', 'app\controller\api\Build@tags');               // 生成所有标签页
        Route::post('build/tag/:id', 'app\controller\api\Build@tag');             // 生成单个标签页
        Route::post('build/pages', 'app\controller\api\Build@pages');             // 生成所有单页面
        Route::post('build/page/:id', 'app\controller\api\Build@page');           // 生成单个单页面
        Route::get('build/logs', 'app\controller\api\Build@logs');                // 生成日志

        // Sitemap生成
        Route::post('sitemap/all', 'app\controller\api\Sitemap@generateAll');     // 生成所有格式sitemap
        Route::post('sitemap/txt', 'app\controller\api\Sitemap@generateTxt');     // 生成TXT格式
        Route::post('sitemap/xml', 'app\controller\api\Sitemap@generateXml');     // 生成XML格式
        Route::post('sitemap/html', 'app\controller\api\Sitemap@generateHtml');   // 生成HTML格式

    })->middleware(\app\middleware\Auth::class);  // 应用JWT认证中间件

})->middleware(\app\middleware\Cors::class);  // 应用跨域中间件
