<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 模板历史记录模型
 */
class TemplateHistory extends Model
{
    protected $name = 'template_history';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'file_size' => 'integer',
        'version' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(\app\model\AdminUser::class, 'user_id');
    }

    /**
     * 创建历史记录
     *
     * @param string $themeKey 模板套装key
     * @param string $filePath 文件路径
     * @param string $content 文件内容
     * @param int|null $userId 用户ID
     * @param string|null $description 修改描述
     * @return bool|TemplateHistory
     */
    public static function createHistory(
        string $themeKey,
        string $filePath,
        string $content,
        ?int $userId = null,
        ?string $description = null
    ) {
        try {
            // 获取当前版本号（最大版本号+1）
            $maxVersion = self::where('theme_key', $themeKey)
                ->where('file_path', $filePath)
                ->max('version');
            $version = $maxVersion ? $maxVersion + 1 : 1;

            // 创建历史记录
            $history = new self();
            $history->theme_key = $themeKey;
            $history->file_path = $filePath;
            $history->file_name = basename($filePath);
            $history->content = $content;
            $history->file_size = strlen($content);
            $history->version = $version;
            $history->change_description = $description;
            $history->user_id = $userId;

            if ($history->save()) {
                // 清理旧历史记录，只保留最近20个版本
                self::cleanOldHistory($themeKey, $filePath, 20);
                return $history;
            }

            return false;
        } catch (\Exception $e) {
            trace('创建模板历史失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 获取文件历史记录列表
     *
     * @param string $themeKey 模板套装key
     * @param string $filePath 文件路径
     * @param int $limit 返回数量限制
     * @return array
     */
    public static function getHistoryList(string $themeKey, string $filePath, int $limit = 20): array
    {
        return self::where('theme_key', $themeKey)
            ->where('file_path', $filePath)
            ->order('version', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 清理旧历史记录
     *
     * @param string $themeKey 模板套装key
     * @param string $filePath 文件路径
     * @param int $keepCount 保留数量
     * @return void
     */
    private static function cleanOldHistory(string $themeKey, string $filePath, int $keepCount = 20): void
    {
        try {
            // 获取所有历史记录数量
            $total = self::where('theme_key', $themeKey)
                ->where('file_path', $filePath)
                ->count();

            if ($total > $keepCount) {
                // 获取需要保留的最小版本号
                $minVersion = self::where('theme_key', $themeKey)
                    ->where('file_path', $filePath)
                    ->order('version', 'desc')
                    ->limit($keepCount)
                    ->column('version');

                if (!empty($minVersion)) {
                    $minKeepVersion = min($minVersion);

                    // 删除旧版本
                    self::where('theme_key', $themeKey)
                        ->where('file_path', $filePath)
                        ->where('version', '<', $minKeepVersion)
                        ->delete();
                }
            }
        } catch (\Exception $e) {
            trace('清理模板历史失败: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 获取历史记录详情（包含内容）
     *
     * @param int $id 历史记录ID
     * @return TemplateHistory|null
     */
    public static function getHistoryById(int $id): ?TemplateHistory
    {
        return self::find($id);
    }

    /**
     * 对比两个版本
     *
     * @param int $version1Id 版本1 ID
     * @param int $version2Id 版本2 ID
     * @return array|null
     */
    public static function compareVersions(int $version1Id, int $version2Id): ?array
    {
        $v1 = self::find($version1Id);
        $v2 = self::find($version2Id);

        if (!$v1 || !$v2) {
            return null;
        }

        return [
            'version1' => [
                'id' => $v1->id,
                'version' => $v1->version,
                'content' => $v1->content,
                'create_time' => $v1->create_time,
            ],
            'version2' => [
                'id' => $v2->id,
                'version' => $v2->version,
                'content' => $v2->content,
                'create_time' => $v2->create_time,
            ]
        ];
    }
}
