<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 广告模型
 */
class Ad extends Model
{
    use SoftDelete;

    protected $name = 'ads';
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型常量
    const TYPE_IMAGE = 'image';       // 图片广告
    const TYPE_CODE = 'code';         // 代码广告
    const TYPE_CAROUSEL = 'carousel'; // 轮播广告

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    /**
     * 关联广告位
     */
    public function position()
    {
        return $this->belongsTo(AdPosition::class, 'position_id', 'id');
    }

    /**
     * 关联点击统计
     */
    public function clicks()
    {
        return $this->hasMany(AdClick::class, 'ad_id', 'id');
    }

    /**
     * 增加展示次数
     */
    public function incrementViewCount()
    {
        $this->view_count++;
        $this->save();
    }

    /**
     * 增加点击次数
     */
    public function incrementClickCount()
    {
        $this->click_count++;
        $this->save();
    }

    /**
     * 检查是否在投放时间内
     */
    public function isInTimeRange()
    {
        $now = time();
        $startTime = $this->start_time ? strtotime($this->start_time) : 0;
        $endTime = $this->end_time ? strtotime($this->end_time) : PHP_INT_MAX;

        return $now >= $startTime && $now <= $endTime;
    }

    /**
     * 获取所有类型
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_IMAGE => '图片广告',
            self::TYPE_CODE => '代码广告',
            self::TYPE_CAROUSEL => '轮播广告',
        ];
    }

    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $type = $data['type'] ?? 'image';
        $list = self::getTypeList();
        return $list[$type] ?? '未知';
    }

    /**
     * 获取所有状态
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 0;
        $list = self::getStatusList();
        return $list[$status] ?? '未知';
    }

    /**
     * 获取点击率
     */
    public function getClickRateAttr($value, $data)
    {
        $viewCount = $data['view_count'] ?? 0;
        $clickCount = $data['click_count'] ?? 0;

        if ($viewCount == 0) {
            return 0;
        }

        return round(($clickCount / $viewCount) * 100, 2);
    }

    /**
     * 获取器：images 字段自动解码为数组
     */
    public function getImagesAttr($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * 搜索器：广告位
     */
    public function searchPositionIdAttr($query, $value)
    {
        $query->where('position_id', $value);
    }

    /**
     * 搜索器：类型
     */
    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }
}
