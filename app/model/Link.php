<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 友情链接模型
 */
class Link extends Model
{
    use SoftDelete;

    protected $name = 'links';
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 状态常量
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已拒绝

    /**
     * 关联分组
     */
    public function group()
    {
        return $this->belongsTo(LinkGroup::class, 'group_id', 'id');
    }

    /**
     * 关联审核人
     */
    public function auditUser()
    {
        return $this->belongsTo(AdminUser::class, 'audit_user_id', 'id');
    }

    /**
     * 审核通过
     */
    public function approve($userId, $note = '')
    {
        $this->status = self::STATUS_APPROVED;
        $this->audit_time = date('Y-m-d H:i:s');
        $this->audit_user_id = $userId;
        $this->audit_note = $note;
        $this->save();
    }

    /**
     * 审核拒绝
     */
    public function reject($userId, $note = '')
    {
        $this->status = self::STATUS_REJECTED;
        $this->audit_time = date('Y-m-d H:i:s');
        $this->audit_user_id = $userId;
        $this->audit_note = $note;
        $this->save();
    }

    /**
     * 增加点击次数
     */
    public function incrementViewCount()
    {
        $this->view_count++;
        $this->save();
    }

    /**
     * 获取所有状态
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
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
     * 搜索器：名称
     */
    public function searchNameAttr($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
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
