<?php

namespace app\model;

use think\Model;

/**
 * 角色模型
 */
class AdminRole extends Model
{
    protected $name = 'admin_roles';

    protected $autoWriteTimestamp = true;

    protected $type = [
        'permissions' => 'json',
        'sort'        => 'integer',
        'status'      => 'integer',
    ];

    /**
     * 关联用户
     */
    public function users()
    {
        return $this->hasMany(AdminUser::class, 'role_id');
    }
}
