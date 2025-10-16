<?php
declare(strict_types=1);

namespace app\common;

use app\model\OperationLog;
use think\facade\Request;

class Logger
{
    /**
     * 记录操作日志
     *
     * @param string $module 模块名称
     * @param string $action 操作类型
     * @param string $description 操作描述
     * @param bool $status 状态
     * @param string $errorMsg 错误信息
     * @param array $extraData 额外数据
     * @return bool
     */
    public static function log(
        string $module,
        string $action,
        string $description,
        bool $status = true,
        string $errorMsg = '',
        array $extraData = []
    ): bool {
        $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $executeTime = (int)((microtime(true) - $startTime) * 1000);

        // 获取当前用户信息（从JWT中间件注入的request属性中获取）
        $request = request();
        $user = $request->user ?? null;
        $userId = $user['id'] ?? null;
        $username = $user['username'] ?? null;

        // 获取请求参数（排除敏感信息）
        $params = Request::param();
        $sensitiveKeys = ['password', 'old_password', 'new_password', 'confirm_password', 'token'];
        foreach ($sensitiveKeys as $key) {
            if (isset($params[$key])) {
                $params[$key] = '******';
            }
        }

        $data = [
            'user_id' => $userId,
            'username' => $username,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'ip' => Request::ip(),
            'user_agent' => Request::header('user-agent'),
            'request_method' => Request::method(),
            'request_url' => Request::url(true),
            'request_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'status' => $status ? 1 : 0,
            'error_msg' => $errorMsg,
            'execute_time' => $executeTime,
        ];

        // 合并额外数据
        $data = array_merge($data, $extraData);

        return OperationLog::record($data);
    }

    /**
     * 记录登录日志
     */
    public static function login(string $username, bool $status = true, string $errorMsg = ''): bool
    {
        return self::log(
            OperationLog::MODULE_AUTH,
            OperationLog::ACTION_LOGIN,
            $status ? "用户 {$username} 登录成功" : "用户 {$username} 登录失败",
            $status,
            $errorMsg
        );
    }

    /**
     * 记录登出日志
     */
    public static function logout(string $username): bool
    {
        return self::log(
            OperationLog::MODULE_AUTH,
            OperationLog::ACTION_LOGOUT,
            "用户 {$username} 退出登录"
        );
    }

    /**
     * 记录创建操作
     */
    public static function create(string $module, string $itemName, int $itemId = 0): bool
    {
        return self::log(
            $module,
            OperationLog::ACTION_CREATE,
            "创建{$itemName}" . ($itemId > 0 ? " (ID: {$itemId})" : '')
        );
    }

    /**
     * 记录更新操作
     */
    public static function update(string $module, string $itemName, int $itemId): bool
    {
        return self::log(
            $module,
            OperationLog::ACTION_UPDATE,
            "更新{$itemName} (ID: {$itemId})"
        );
    }

    /**
     * 记录删除操作
     */
    public static function delete(string $module, string $itemName, int $itemId): bool
    {
        return self::log(
            $module,
            OperationLog::ACTION_DELETE,
            "删除{$itemName} (ID: {$itemId})"
        );
    }

    /**
     * 记录发布操作
     */
    public static function publish(string $module, string $itemName, int $itemId): bool
    {
        return self::log(
            $module,
            OperationLog::ACTION_PUBLISH,
            "发布{$itemName} (ID: {$itemId})"
        );
    }

    /**
     * 记录下线操作
     */
    public static function offline(string $module, string $itemName, int $itemId): bool
    {
        return self::log(
            $module,
            OperationLog::ACTION_OFFLINE,
            "下线{$itemName} (ID: {$itemId})"
        );
    }

    /**
     * 记录上传操作
     */
    public static function upload(string $fileName, string $fileSize = ''): bool
    {
        return self::log(
            OperationLog::MODULE_MEDIA,
            OperationLog::ACTION_UPLOAD,
            "上传文件: {$fileName}" . ($fileSize ? " ({$fileSize})" : '')
        );
    }

    /**
     * 记录静态生成操作
     */
    public static function build(string $buildType, string $detail = ''): bool
    {
        return self::log(
            OperationLog::MODULE_BUILD,
            OperationLog::ACTION_BUILD,
            "生成静态页面: {$buildType}" . ($detail ? " - {$detail}" : '')
        );
    }

    /**
     * 记录重置密码操作
     */
    public static function resetPassword(string $targetUsername): bool
    {
        return self::log(
            OperationLog::MODULE_USER,
            OperationLog::ACTION_RESET_PASSWORD,
            "重置用户密码: {$targetUsername}"
        );
    }

    /**
     * 记录修改密码操作
     */
    public static function changePassword(): bool
    {
        return self::log(
            OperationLog::MODULE_PROFILE,
            OperationLog::ACTION_CHANGE_PASSWORD,
            "修改个人密码"
        );
    }
}
