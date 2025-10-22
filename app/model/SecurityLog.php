<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 安全日志模型
 */
class SecurityLog extends Model
{
    protected $name = 'security_logs';

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

        // 类型筛选
        if (!empty($where['type'])) {
            $query->where('type', $where['type']);
        }

        // 级别筛选
        if (!empty($where['level'])) {
            $query->where('level', $where['level']);
        }

        // IP筛选
        if (!empty($where['ip'])) {
            $query->where('ip', 'like', '%' . $where['ip'] . '%');
        }

        // 是否已拦截
        if (isset($where['is_blocked'])) {
            $query->where('is_blocked', $where['is_blocked']);
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
     * 获取请求数据（格式化）
     * @param mixed $value
     * @param array $data
     * @return array|null
     */
    public function getRequestDataAttr($value, $data)
    {
        if (empty($value)) {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * 获取高危IP列表
     * @param int $limit
     * @return array
     */
    public static function getHighRiskIps($limit = 10)
    {
        return self::field('ip, COUNT(*) as count, MAX(level) as max_level')
            ->where('level', 'in', ['high', 'critical'])
            ->group('ip')
            ->order('count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
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
