<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 幻灯片模型
 */
class Slider extends Model
{
    use SoftDelete;

    protected $name = 'sliders';
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    // 链接打开方式常量
    const TARGET_BLANK = '_blank';  // 新窗口
    const TARGET_SELF = '_self';    // 当前窗口

    /**
     * 关联分组
     */
    public function group()
    {
        return $this->belongsTo(SliderGroup::class, 'group_id', 'id');
    }

    /**
     * 检查是否在有效时间内
     */
    public function isInTimeRange()
    {
        $now = time();
        $startTime = $this->start_time ? strtotime($this->start_time) : 0;
        $endTime = $this->end_time ? strtotime($this->end_time) : PHP_INT_MAX;

        return $now >= $startTime && $now <= $endTime;
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
     * 获取所有链接打开方式
     */
    public static function getTargetList()
    {
        return [
            self::TARGET_BLANK => '新窗口',
            self::TARGET_SELF => '当前窗口',
        ];
    }

    /**
     * 搜索器：分组
     */
    public function searchGroupIdAttr($query, $value)
    {
        $query->where('group_id', $value);
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }
}
