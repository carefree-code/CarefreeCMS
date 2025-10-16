<?php

namespace app\model;

use think\Model;

/**
 * 管理员用户模型
 */
class AdminUser extends Model
{
    // 设置表名
    protected $name = 'admin_users';

    // 设置字段信息
    protected $schema = [
        'id'              => 'int',
        'username'        => 'string',
        'password'        => 'string',
        'real_name'       => 'string',
        'email'           => 'string',
        'phone'           => 'string',
        'avatar'          => 'string',
        'role_id'         => 'int',
        'status'          => 'int',
        'last_login_time' => 'datetime',
        'last_login_ip'   => 'string',
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;

    // 隐藏字段（不在JSON中显示）
    protected $hidden = ['password'];

    // 只读字段
    protected $readonly = ['username'];

    // 类型转换
    protected $type = [
        'role_id' => 'integer',
        'status'  => 'integer',
    ];

    /**
     * 关联角色
     */
    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    /**
     * 搜索器：用户名
     */
    public function searchUsernameAttr($query, $value)
    {
        $query->where('username', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 搜索器：角色
     */
    public function searchRoleIdAttr($query, $value)
    {
        $query->where('role_id', $value);
    }

    /**
     * 修改器：密码加密
     */
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * 验证密码
     * @param string $password 输入的密码
     * @return bool
     */
    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->getData('password'));
    }

    /**
     * 获取器：状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']] ?? '未知';
    }
}
