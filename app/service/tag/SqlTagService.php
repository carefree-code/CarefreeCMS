<?php
namespace app\service\tag;

use think\facade\Db;

/**
 * SQL查询标签服务类
 * 处理SQL查询标签的数据查询
 *
 * 注意：此服务具有安全风险，仅供高级用户使用
 */
class SqlTagService
{
    /**
     * 执行SQL查询
     *
     * @param string $sql SQL语句
     * @return array
     */
    public static function query($sql)
    {
        if (empty($sql)) {
            return [];
        }

        try {
            // 安全检查：禁止执行危险操作
            $dangerousKeywords = [
                'DROP', 'DELETE', 'UPDATE', 'INSERT', 'TRUNCATE',
                'ALTER', 'CREATE', 'GRANT', 'REVOKE'
            ];

            $upperSql = strtoupper($sql);
            foreach ($dangerousKeywords as $keyword) {
                if (strpos($upperSql, $keyword) !== false) {
                    // 记录安全警告
                    trace('SQL tag: Dangerous SQL attempt blocked: ' . $sql, 'warning');
                    return [];
                }
            }

            // 只允许SELECT查询
            if (strpos($upperSql, 'SELECT') !== 0) {
                return [];
            }

            // 执行查询
            $result = Db::query($sql);

            return $result ?: [];
        } catch (\Exception $e) {
            // 记录错误
            trace('SQL tag error: ' . $e->getMessage(), 'error');
            return [];
        }
    }
}
