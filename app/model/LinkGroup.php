<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 友链分组模型
 */
class LinkGroup extends Model
{
    protected $name = 'link_groups';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    /**
     * 关联友情链接
     */
    public function links()
    {
        return $this->hasMany(Link::class, 'group_id', 'id')
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
}
