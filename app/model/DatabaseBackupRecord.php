<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 数据库备份记录模型
 */
class DatabaseBackupRecord extends Model
{
    protected $name = 'database_backups';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 记录备份
     * @param array $data
     * @return DatabaseBackupRecord|false
     */
    public static function recordBackup($data)
    {
        return self::create([
            'filename' => $data['filename'],
            'filepath' => $data['filepath'],
            'filesize' => $data['filesize'],
            'tables_count' => $data['tables_count'],
            'backup_type' => $data['backup_type'] ?? 'full',
            'description' => $data['description'] ?? '',
            'status' => $data['success'] ? 1 : 0,
            'error_message' => $data['message'] ?? null,
            'create_user_id' => $data['user_id'] ?? null
        ]);
    }

    /**
     * 获取备份列表
     * @param array $where
     * @param int|string $page
     * @param int|string $perPage
     * @return array
     */
    public static function getList($where = [], $page = 1, $perPage = 15)
    {
        // 确保分页参数为整数
        $page = (int) $page;
        $perPage = (int) $perPage;

        $query = self::order('create_time', 'desc');

        if (!empty($where['backup_type'])) {
            $query->where('backup_type', $where['backup_type']);
        }

        if (!empty($where['status'])) {
            $query->where('status', $where['status']);
        }

        if (!empty($where['start_time'])) {
            $query->where('create_time', '>=', $where['start_time']);
        }

        if (!empty($where['end_time'])) {
            $query->where('create_time', '<=', $where['end_time']);
        }

        // 先获取总数（在应用分页之前）
        $total = $query->count();

        // 再应用分页获取列表数据
        $list = $query->page($page, $perPage)->select();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 格式化文件大小
     * @param mixed $value
     * @return string
     */
    public function getFilesizeFormatAttr($value, $data)
    {
        $bytes = $data['filesize'] ?? 0;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * 删除备份记录
     * @param int $id
     * @return bool
     */
    public static function deleteRecord($id)
    {
        $record = self::find($id);
        if ($record) {
            // 删除文件
            if (file_exists($record->filepath)) {
                @unlink($record->filepath);
            }
            return $record->delete();
        }
        return false;
    }

    /**
     * 清理旧备份
     * @param int $days 保留天数
     * @return int 删除数量
     */
    public static function cleanOldBackups($days = 30)
    {
        $time = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $records = self::where('create_time', '<', $time)->select();

        $count = 0;
        foreach ($records as $record) {
            if (self::deleteRecord($record->id)) {
                $count++;
            }
        }

        return $count;
    }
}
