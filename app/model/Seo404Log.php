<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 404错误日志模型
 */
class Seo404Log extends Model
{
    protected $name = 'seo_404_logs';

    // 修复方式常量
    const FIXED_REDIRECT = 'redirect';  // 重定向
    const FIXED_DELETED = 'deleted';    // 已删除
    const FIXED_IGNORED = 'ignored';    // 忽略

    /**
     * 记录404错误
     * @param string $url 404 URL
     * @param string $referer 来源页面
     * @param string $ip IP地址
     * @param string $userAgent 用户代理
     * @return Seo404Log
     */
    public static function record($url, $referer = '', $ip = '', $userAgent = '')
    {
        // 查找是否已存在
        $log = self::where('url', $url)->find();

        if ($log) {
            // 已存在，增加计数
            $log->hit_count++;
            $log->last_hit_time = date('Y-m-d H:i:s');
            $log->save();
        } else {
            // 新建记录
            $log = self::create([
                'url' => $url,
                'referer' => $referer,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'hit_count' => 1,
                'first_hit_time' => date('Y-m-d H:i:s'),
                'last_hit_time' => date('Y-m-d H:i:s'),
                'is_fixed' => 0
            ]);
        }

        return $log;
    }

    /**
     * 标记为已修复
     * @param string $method 修复方式
     * @param string $notes 备注
     */
    public function markAsFixed($method, $notes = '')
    {
        $this->is_fixed = 1;
        $this->fixed_time = date('Y-m-d H:i:s');
        $this->fixed_method = $method;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }

    /**
     * 标记为忽略
     * @param string $notes 备注
     */
    public function ignore($notes = '')
    {
        $this->markAsFixed(self::FIXED_IGNORED, $notes);
    }

    /**
     * 获取高频404列表（按命中次数排序）
     * @param int $limit
     * @return array
     */
    public static function getTopErrors($limit = 20)
    {
        return self::where('is_fixed', 0)
            ->order('hit_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取最近的404错误
     * @param int $limit
     * @return array
     */
    public static function getRecentErrors($limit = 20)
    {
        return self::where('is_fixed', 0)
            ->order('last_hit_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取统计信息
     * @return array
     */
    public static function getStatistics()
    {
        $total = self::where('is_fixed', 0)->count();
        $totalHits = self::where('is_fixed', 0)->sum('hit_count');
        $fixed = self::where('is_fixed', 1)->count();
        $today = self::where('is_fixed', 0)
            ->whereTime('last_hit_time', 'today')
            ->count();

        return [
            'total' => $total,           // 未修复总数
            'total_hits' => $totalHits,  // 总命中次数
            'fixed' => $fixed,           // 已修复数量
            'today' => $today            // 今日新增
        ];
    }

    /**
     * 批量清理旧日志
     * @param int $days 保留天数
     * @return int 删除数量
     */
    public static function cleanOldLogs($days = 90)
    {
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return self::where('is_fixed', 1)
            ->where('fixed_time', '<', $cutoffTime)
            ->delete();
    }

    /**
     * 获取所有修复方式
     */
    public static function getFixedMethods()
    {
        return [
            self::FIXED_REDIRECT => '已重定向',
            self::FIXED_DELETED => '已删除',
            self::FIXED_IGNORED => '已忽略',
        ];
    }

    /**
     * 搜索器：修复状态
     */
    public function searchIsFixedAttr($query, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where('is_fixed', $value);
        }
    }

    /**
     * 搜索器：URL关键词
     */
    public function searchUrlAttr($query, $value)
    {
        if ($value) {
            $query->where('url', 'like', "%{$value}%");
        }
    }
}
