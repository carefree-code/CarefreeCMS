<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class StaticBuildLog extends Model
{
    protected $table = 'static_build_log';

    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 生成类型常量
    const BUILD_TYPE_MANUAL = 'manual';      // 手动生成
    const BUILD_TYPE_AUTO = 'auto';          // 自动生成
    const BUILD_TYPE_SCHEDULE = 'schedule';  // 定时生成

    // 生成范围常量
    const SCOPE_ALL = 'all';
    const SCOPE_ARTICLE = 'article';
    const SCOPE_PAGE = 'page';
    const SCOPE_INDEX = 'index';
    const SCOPE_CATEGORY = 'category';

    // 状态常量
    const STATUS_FAILED = 0;       // 失败
    const STATUS_SUCCESS = 1;      // 成功
    const STATUS_PARTIAL = 2;      // 部分成功

    /**
     * 关联文章
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'target_id', 'id');
    }

    /**
     * 关联页面
     */
    public function page()
    {
        return $this->belongsTo(Page::class, 'target_id', 'id');
    }

    /**
     * 记录生成日志
     *
     * @param string $buildType 生成类型：manual/auto/schedule
     * @param string $scope 生成范围：all/article/category/page/index
     * @param int $targetId 目标ID
     * @param int $status 状态：0=失败，1=成功，2=部分成功
     * @param string $errorMsg 错误信息
     * @param int $fileCount 文件数量
     * @param int $successCount 成功数量
     * @param int $failCount 失败数量
     * @param float $buildTime 生成耗时（秒）
     * @return bool
     */
    public static function log(
        string $buildType,
        string $scope,
        int $targetId = 0,
        int $status = self::STATUS_SUCCESS,
        string $errorMsg = '',
        int $fileCount = 1,
        int $successCount = 1,
        int $failCount = 0,
        float $buildTime = 0.0
    ): bool
    {
        $data = [
            'build_type' => $buildType,
            'build_scope' => $scope,
            'target_id' => $targetId,
            'status' => $status,
            'error_msg' => $errorMsg,
            'file_count' => $fileCount,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'build_time' => $buildTime,
            'user_id' => request()->userId ?? null, // 从请求中获取当前用户ID
        ];

        return self::create($data) ? true : false;
    }
}
