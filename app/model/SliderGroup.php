<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 幻灯片组模型
 */
class SliderGroup extends Model
{
    protected $name = 'slider_groups';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    // 动画效果常量
    const ANIMATION_SLIDE = 'slide';  // 滑动
    const ANIMATION_FADE = 'fade';    // 淡入淡出

    /**
     * 关联幻灯片
     */
    public function sliders()
    {
        return $this->hasMany(Slider::class, 'group_id', 'id')
            ->order('sort', 'asc');
    }

    /**
     * 获取启用的幻灯片
     */
    public function activeSliders()
    {
        return $this->hasMany(Slider::class, 'group_id', 'id')
            ->where('status', Slider::STATUS_ENABLED)
            ->where(function($query) {
                $now = date('Y-m-d H:i:s');
                $query->where(function($q) use ($now) {
                    $q->whereNull('start_time')
                      ->whereOr('start_time', '<=', $now);
                })->where(function($q) use ($now) {
                    $q->whereNull('end_time')
                      ->whereOr('end_time', '>=', $now);
                });
            })
            ->order('sort', 'asc');
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
     * 获取所有动画效果
     */
    public static function getAnimationList()
    {
        return [
            self::ANIMATION_SLIDE => '滑动',
            self::ANIMATION_FADE => '淡入淡出',
        ];
    }

    /**
     * 获取动画效果文本
     */
    public function getAnimationTextAttr($value, $data)
    {
        $animation = $data['animation'] ?? 'slide';
        $list = self::getAnimationList();
        return $list[$animation] ?? '未知';
    }
}
