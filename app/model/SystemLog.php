<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 系统日志模型
 */
class SystemLog extends Model
{
    protected $name = 'system_logs';

    protected $autoWriteTimestamp = false;

    /**
     * 获取日志列表
     * @param array $where
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getList($where = [], $page = 1, $perPage = 20)
    {
        // 确保分页参数为整数
        $page = (int) $page;
        $perPage = (int) $perPage;

        $query = self::order('create_time', 'desc');

        // 级别筛选
        if (!empty($where['level'])) {
            $query->where('level', $where['level']);
        }

        // 分类筛选
        if (!empty($where['category'])) {
            $query->where('category', $where['category']);
        }

        // 用户ID筛选
        if (isset($where['user_id'])) {
            $query->where('user_id', $where['user_id']);
        }

        // IP筛选
        if (!empty($where['ip'])) {
            $query->where('ip', 'like', '%' . $where['ip'] . '%');
        }

        // 关键词搜索
        if (!empty($where['keyword'])) {
            $query->where('message', 'like', '%' . $where['keyword'] . '%');
        }

        // 时间范围
        if (!empty($where['start_time'])) {
            $query->where('create_time', '>=', $where['start_time']);
        }

        if (!empty($where['end_time'])) {
            $query->where('create_time', '<=', $where['end_time']);
        }

        $total = $query->count();
        $list = $query->page($page, $perPage)->select();

        return [
            'list' => $list,
            'total' => $total
        ];
    }

    /**
     * 获取上下文数据（格式化）
     * @param mixed $value
     * @param array $data
     * @return array|null
     */
    public function getContextAttr($value, $data)
    {
        if (empty($value)) {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * 批量删除
     * @param array $ids
     * @return int
     */
    public static function batchDelete($ids)
    {
        return self::where('id', 'in', $ids)->delete();
    }
}
