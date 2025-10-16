<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class OperationLog extends Model
{
    protected $name = 'operation_logs';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 模块常量
    const MODULE_AUTH = 'auth';              // 认证
    const MODULE_ARTICLE = 'article';        // 文章
    const MODULE_CATEGORY = 'category';      // 分类
    const MODULE_TAG = 'tag';                // 标签
    const MODULE_ARTICLE_FLAG = 'article_flag'; // 文章属性
    const MODULE_PAGE = 'page';              // 单页
    const MODULE_MEDIA = 'media';            // 媒体
    const MODULE_USER = 'user';              // 用户
    const MODULE_ROLE = 'role';              // 角色
    const MODULE_CONFIG = 'config';          // 配置
    const MODULE_PROFILE = 'profile';        // 个人信息
    const MODULE_BUILD = 'build';            // 静态生成

    // 操作类型常量
    const ACTION_LOGIN = 'login';            // 登录
    const ACTION_LOGOUT = 'logout';          // 登出
    const ACTION_CREATE = 'create';          // 创建
    const ACTION_UPDATE = 'update';          // 更新
    const ACTION_DELETE = 'delete';          // 删除
    const ACTION_PUBLISH = 'publish';        // 发布
    const ACTION_OFFLINE = 'offline';        // 下线
    const ACTION_UPLOAD = 'upload';          // 上传
    const ACTION_BUILD = 'build';            // 生成
    const ACTION_RESET_PASSWORD = 'reset_password'; // 重置密码
    const ACTION_CHANGE_PASSWORD = 'change_password'; // 修改密码

    /**
     * 记录操作日志
     */
    public static function record(array $data): bool
    {
        try {
            $log = new self();
            $log->save($data);
            return true;
        } catch (\Exception $e) {
            // 日志记录失败不影响业务
            trace($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 获取模块名称映射
     */
    public static function getModuleNames(): array
    {
        return [
            self::MODULE_AUTH => '认证',
            self::MODULE_ARTICLE => '文章',
            self::MODULE_CATEGORY => '分类',
            self::MODULE_TAG => '标签',
            self::MODULE_ARTICLE_FLAG => '文章属性',
            self::MODULE_PAGE => '单页',
            self::MODULE_MEDIA => '媒体',
            self::MODULE_USER => '用户',
            self::MODULE_ROLE => '角色',
            self::MODULE_CONFIG => '配置',
            self::MODULE_PROFILE => '个人信息',
            self::MODULE_BUILD => '静态生成',
        ];
    }

    /**
     * 获取操作类型映射
     */
    public static function getActionNames(): array
    {
        return [
            self::ACTION_LOGIN => '登录',
            self::ACTION_LOGOUT => '登出',
            self::ACTION_CREATE => '创建',
            self::ACTION_UPDATE => '更新',
            self::ACTION_DELETE => '删除',
            self::ACTION_PUBLISH => '发布',
            self::ACTION_OFFLINE => '下线',
            self::ACTION_UPLOAD => '上传',
            self::ACTION_BUILD => '生成',
            self::ACTION_RESET_PASSWORD => '重置密码',
            self::ACTION_CHANGE_PASSWORD => '修改密码',
        ];
    }
}
