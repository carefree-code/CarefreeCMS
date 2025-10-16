<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 单页模型
 */
class Page extends Model
{
    protected $name = 'pages';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort'   => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_DRAFT = 0;      // 草稿
    const STATUS_PUBLISHED = 1;  // 已发布

    /**
     * 搜索器 - 标题
     */
    public function searchTitleAttr($query, $value)
    {
        $query->where('title', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器 - 状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 获取器：状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '草稿', 1 => '已发布'];
        return $status[$data['status']] ?? '未知';
    }
}
