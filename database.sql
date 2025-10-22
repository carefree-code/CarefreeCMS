-- ============================================
-- CMS 内容管理系统数据库设计
-- 数据库: cms_database
-- MySQL 8.0+
-- ============================================

CREATE DATABASE IF NOT EXISTS cms_database DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cms_database;

-- ============================================
-- 1. 管理员用户表
-- ============================================
CREATE TABLE `admin_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码（哈希）',
  `real_name` varchar(50) DEFAULT NULL COMMENT '真实姓名',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `role_id` int unsigned NOT NULL DEFAULT '3' COMMENT '角色ID：1=超管，2=管理员，3=编辑，4=作者',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员用户表';

-- ============================================
-- 2. 角色表
-- ============================================
CREATE TABLE `admin_roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `description` varchar(255) DEFAULT NULL COMMENT '角色描述',
  `permissions` text COMMENT '权限列表（JSON格式）',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- ============================================
-- 3. 分类表
-- ============================================
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '父分类ID，0表示顶级分类',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `slug` varchar(100) DEFAULT NULL COMMENT 'URL别名',
  `description` text COMMENT '分类描述',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `seo_title` varchar(100) DEFAULT NULL COMMENT 'SEO标题',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'SEO关键词',
  `seo_description` varchar(500) DEFAULT NULL COMMENT 'SEO描述',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';

-- ============================================
-- 4. 标签表
-- ============================================
CREATE TABLE `tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `slug` varchar(100) DEFAULT NULL COMMENT 'URL别名',
  `description` varchar(255) DEFAULT NULL COMMENT '标签描述',
  `article_count` int unsigned NOT NULL DEFAULT '0' COMMENT '关联文章数',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

-- ============================================
-- 5. 文章表
-- ============================================
CREATE TABLE `articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `category_id` int unsigned NOT NULL COMMENT '分类ID',
  `user_id` int unsigned NOT NULL COMMENT '作者ID',
  `title` varchar(200) NOT NULL COMMENT '文章标题',
  `slug` varchar(200) DEFAULT NULL COMMENT 'URL别名',
  `summary` varchar(500) DEFAULT NULL COMMENT '文章摘要',
  `content` longtext NOT NULL COMMENT '文章内容',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `images` text COMMENT '文章图片集（JSON格式）',
  `author` varchar(50) DEFAULT NULL COMMENT '作者名称（显示用）',
  `source` varchar(100) DEFAULT NULL COMMENT '文章来源',
  `source_url` varchar(255) DEFAULT NULL COMMENT '来源链接',
  `view_count` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `like_count` int unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `comment_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `is_top` tinyint NOT NULL DEFAULT '0' COMMENT '是否置顶：0=否，1=是',
  `is_recommend` tinyint NOT NULL DEFAULT '0' COMMENT '是否推荐：0=否，1=是',
  `is_hot` tinyint NOT NULL DEFAULT '0' COMMENT '是否热门：0=否，1=是',
  `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
  `seo_title` varchar(100) DEFAULT NULL COMMENT 'SEO标题',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'SEO关键词',
  `seo_description` varchar(500) DEFAULT NULL COMMENT 'SEO描述',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态：0=草稿，1=已发布，2=待审核，3=已下线',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status_publish` (`status`, `publish_time`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_is_recommend` (`is_recommend`),
  KEY `idx_is_hot` (`is_hot`),
  FULLTEXT KEY `ft_title_content` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';

-- ============================================
-- 6. 文章标签关联表
-- ============================================
CREATE TABLE `article_tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '关联ID',
  `article_id` int unsigned NOT NULL COMMENT '文章ID',
  `tag_id` int unsigned NOT NULL COMMENT '标签ID',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_article_tag` (`article_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章标签关联表';

-- ============================================
-- 7. 单页面表
-- ============================================
CREATE TABLE `pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '页面ID',
  `title` varchar(200) NOT NULL COMMENT '页面标题',
  `slug` varchar(200) NOT NULL COMMENT 'URL别名',
  `content` longtext NOT NULL COMMENT '页面内容',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `template` varchar(50) DEFAULT 'default' COMMENT '模板名称',
  `seo_title` varchar(100) DEFAULT NULL COMMENT 'SEO标题',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'SEO关键词',
  `seo_description` varchar(500) DEFAULT NULL COMMENT 'SEO描述',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='单页面表';

-- ============================================
-- 8. 评论表
-- ============================================
CREATE TABLE `comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  `article_id` int unsigned NOT NULL COMMENT '文章ID',
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '父评论ID，0表示顶级评论',
  `user_name` varchar(50) NOT NULL COMMENT '评论者名称',
  `user_email` varchar(100) DEFAULT NULL COMMENT '评论者邮箱',
  `user_ip` varchar(50) DEFAULT NULL COMMENT '评论者IP',
  `content` text NOT NULL COMMENT '评论内容',
  `like_count` int unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `is_admin` tinyint NOT NULL DEFAULT '0' COMMENT '是否管理员：0=否，1=是',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态：0=待审核，1=已通过，2=已拒绝',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

-- ============================================
-- 9. 媒体库表
-- ============================================
CREATE TABLE `media` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '媒体ID',
  `user_id` int unsigned NOT NULL COMMENT '上传者ID',
  `file_name` varchar(255) NOT NULL COMMENT '文件名',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_url` varchar(500) NOT NULL COMMENT '文件URL',
  `file_type` varchar(50) NOT NULL COMMENT '文件类型：image/video/audio/document',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME类型',
  `file_size` bigint unsigned NOT NULL COMMENT '文件大小（字节）',
  `width` int unsigned DEFAULT NULL COMMENT '图片/视频宽度',
  `height` int unsigned DEFAULT NULL COMMENT '图片/视频高度',
  `storage_type` varchar(20) NOT NULL DEFAULT 'local' COMMENT '存储类型：local/qiniu/aliyun',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='媒体库表';

-- ============================================
-- 10. 站点配置表
-- ============================================
CREATE TABLE `site_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '配置类型：text/number/json/image',
  `group_name` varchar(50) NOT NULL DEFAULT 'basic' COMMENT '配置分组：basic/seo/upload/template',
  `description` varchar(255) DEFAULT NULL COMMENT '配置描述',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站点配置表';

-- ============================================
-- 11. 模板管理表
-- ============================================
CREATE TABLE `templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `name` varchar(50) NOT NULL COMMENT '模板名称',
  `template_key` varchar(50) NOT NULL COMMENT '模板标识',
  `description` varchar(255) DEFAULT NULL COMMENT '模板描述',
  `preview_image` varchar(255) DEFAULT NULL COMMENT '预览图',
  `template_path` varchar(200) NOT NULL COMMENT '模板路径',
  `is_default` tinyint NOT NULL DEFAULT '0' COMMENT '是否默认：0=否，1=是',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板管理表';

-- ============================================
-- 12. 静态页面生成日志表
-- ============================================
CREATE TABLE `static_build_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `build_type` varchar(50) NOT NULL COMMENT '生成类型：manual/auto/schedule',
  `build_scope` varchar(50) NOT NULL COMMENT '生成范围：all/article/category/page/index',
  `target_id` int unsigned DEFAULT NULL COMMENT '目标ID（文章ID/分类ID等）',
  `file_count` int unsigned NOT NULL DEFAULT '0' COMMENT '生成文件数量',
  `success_count` int unsigned NOT NULL DEFAULT '0' COMMENT '成功数量',
  `fail_count` int unsigned NOT NULL DEFAULT '0' COMMENT '失败数量',
  `build_time` decimal(10,2) DEFAULT NULL COMMENT '生成耗时（秒）',
  `error_msg` text COMMENT '错误信息',
  `user_id` int unsigned DEFAULT NULL COMMENT '操作者ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：0=失败，1=成功，2=部分成功',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_build_type` (`build_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='静态页面生成日志表';

-- ============================================
-- 13. 操作日志表
-- ============================================
CREATE TABLE `admin_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int unsigned DEFAULT NULL COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `action` varchar(50) NOT NULL COMMENT '操作动作',
  `module` varchar(50) NOT NULL COMMENT '操作模块',
  `description` varchar(500) DEFAULT NULL COMMENT '操作描述',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- ============================================
-- 初始化数据
-- ============================================

-- 插入默认角色
INSERT INTO `admin_roles` (`id`, `name`, `description`, `permissions`, `sort`, `status`) VALUES
(1, '超级管理员', '拥有所有权限', '["*"]', 1, 1),
(2, '管理员', '拥有大部分管理权限', '["article.*", "category.*", "tag.*", "page.*", "comment.*", "media.*"]', 2, 1),
(3, '编辑', '可以管理文章、分类、标签', '["article.*", "category.view", "tag.*", "comment.*", "media.*"]', 3, 1),
(4, '作者', '只能管理自己的文章', '["article.create", "article.edit_own", "media.upload"]', 4, 1);

-- 插入默认管理员账号（密码：admin123）
INSERT INTO `admin_users` (`username`, `password`, `real_name`, `email`, `role_id`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 'admin@example.com', 1, 1);

-- 插入默认站点配置
INSERT INTO `site_config` (`config_key`, `config_value`, `config_type`, `group_name`, `description`, `sort`) VALUES
('site_name', 'CMS内容管理系统', 'text', 'basic', '网站名称', 1),
('site_logo', '', 'image', 'basic', '网站Logo', 2),
('site_keywords', 'CMS,内容管理,ThinkPHP,Vue', 'text', 'seo', 'SEO关键词', 3),
('site_description', '基于ThinkPHP8和Vue3的内容管理系统', 'text', 'seo', 'SEO描述', 4),
('site_icp', '', 'text', 'basic', 'ICP备案号', 5),
('site_copyright', '© 2024 CMS. All rights reserved.', 'text', 'basic', '版权信息', 6),
('upload_max_size', '10', 'number', 'upload', '最大上传大小(MB)', 7),
('upload_allowed_ext', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,zip', 'text', 'upload', '允许的文件扩展名', 8),
('default_template', 'default', 'text', 'template', '默认模板', 9),
('article_page_size', '20', 'number', 'basic', '文章列表每页数量', 10),
('comment_need_audit', '1', 'number', 'basic', '评论是否需要审核：0=否，1=是', 11);

-- 插入默认模板
INSERT INTO `templates` (`name`, `template_key`, `description`, `template_path`, `is_default`, `status`) VALUES
('默认模板', 'default', '系统默认模板', 'default', 1, 1);

-- 插入示例分类
INSERT INTO `categories` (`name`, `slug`, `description`, `sort`, `status`) VALUES
('公司新闻', 'company-news', '公司最新动态', 1, 1),
('行业资讯', 'industry-news', '行业相关资讯', 2, 1),
('产品介绍', 'products', '产品相关介绍', 3, 1),
('技术文档', 'tech-docs', '技术相关文档', 4, 1);

-- 插入示例单页面
INSERT INTO `pages` (`title`, `slug`, `content`, `template`, `sort`, `status`) VALUES
('关于我们', 'about', '<p>关于我们的内容...</p>', 'default', 1, 1),
('联系我们', 'contact', '<p>联系方式...</p>', 'default', 2, 1);
