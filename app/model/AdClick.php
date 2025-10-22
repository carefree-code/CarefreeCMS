<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 广告点击统计模型
 */
class AdClick extends Model
{
    protected $name = 'ad_clicks';

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'click_time';
    protected $updateTime = false;

    /**
     * 关联广告
     */
    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id', 'id');
    }

    /**
     * 记录点击
     */
    public static function record($adId, $ip, $userAgent, $referer)
    {
        return self::create([
            'ad_id' => $adId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'referer' => $referer,
        ]);
    }

    /**
     * 获取广告的点击统计
     */
    public static function getStatistics($adId, $startDate = null, $endDate = null)
    {
        $query = self::where('ad_id', $adId);

        if ($startDate) {
            $query->where('click_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('click_time', '<=', $endDate);
        }

        return [
            'total' => $query->count(),
            'unique_ip' => $query->distinct(true)->count('ip'),
        ];
    }
}
