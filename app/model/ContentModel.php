<?php

namespace app\model;

use think\Model;

/**
 * 内容模型
 */
class ContentModel extends Model
{
    protected $name = 'content_models';

    protected $autoWriteTimestamp = true;

    protected $type = [
        'is_system' => 'integer',
        'status'    => 'integer',
        'sort'      => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0; // 禁用
    const STATUS_ENABLED = 1;  // 启用

    /**
     * 关联自定义字段
     */
    public function customFields()
    {
        return $this->hasMany(CustomField::class, 'model_id', 'id')
            ->where('model_type', 'custom')
            ->order('sort', 'asc');
    }

    /**
     * 搜索器：模型名称
     */
    public function searchNameAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('status', $value);
        }
    }

    /**
     * 获取器：状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']] ?? '未知';
    }

    /**
     * 获取器：系统模型文本
     */
    public function getIsSystemTextAttr($value, $data)
    {
        return $data['is_system'] ? '是' : '否';
    }
}
