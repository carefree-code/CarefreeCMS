<?php

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 前台用户模型
 */
class FrontUser extends Model
{
    use SoftDelete;

    // 设置表名
    protected $name = 'front_users';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    // 隐藏字段（不在JSON中显示）
    protected $hidden = ['password', 'reset_token', 'email_verify_token'];

    // 只读字段
    protected $readonly = ['username', 'create_time'];

    // 类型转换
    protected $type = [
        'gender'          => 'integer',
        'points'          => 'integer',
        'level'           => 'integer',
        'article_count'   => 'integer',
        'comment_count'   => 'integer',
        'favorite_count'  => 'integer',
        'follower_count'  => 'integer',
        'following_count' => 'integer',
        'email_verified'  => 'integer',
        'phone_verified'  => 'integer',
        'status'          => 'integer',
        'is_vip'          => 'integer',
        'login_count'     => 'integer',
    ];

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
        $status = [0 => '禁用', 1 => '正常', 2 => '待验证'];
        return $status[$data['status']] ?? '未知';
    }

    /**
     * 获取器：性别文本
     */
    public function getGenderTextAttr($value, $data)
    {
        $gender = [0 => '保密', 1 => '男', 2 => '女'];
        return $gender[$data['gender'] ?? 0] ?? '保密';
    }

    /**
     * 获取器：等级文本
     */
    public function getLevelTextAttr($value, $data)
    {
        $level = $data['level'] ?? 1;
        $levels = [
            1 => '新手',
            2 => '初级会员',
            3 => '中级会员',
            4 => '高级会员',
            5 => 'VIP会员',
        ];
        return $levels[$level] ?? "LV{$level}";
    }

    /**
     * 搜索器：用户名
     */
    public function searchUsernameAttr($query, $value)
    {
        $query->where('username', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：昵称
     */
    public function searchNicknameAttr($query, $value)
    {
        $query->where('nickname', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：邮箱
     */
    public function searchEmailAttr($query, $value)
    {
        $query->where('email', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：手机号
     */
    public function searchPhoneAttr($query, $value)
    {
        $query->where('phone', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 搜索器：等级
     */
    public function searchLevelAttr($query, $value)
    {
        $query->where('level', $value);
    }

    /**
     * 搜索器：是否VIP
     */
    public function searchIsVipAttr($query, $value)
    {
        $query->where('is_vip', $value);
    }

    /**
     * 生成邮箱验证令牌
     * @return string
     */
    public function generateEmailVerifyToken(): string
    {
        $token = md5(uniqid() . microtime());
        $this->email_verify_token = $token;
        $this->email_verify_expire = date('Y-m-d H:i:s', time() + 24 * 3600); // 24小时有效
        $this->save();
        return $token;
    }

    /**
     * 生成密码重置令牌
     * @return string
     */
    public function generateResetToken(): string
    {
        $token = md5(uniqid() . microtime());
        $this->reset_token = $token;
        $this->reset_token_expire = date('Y-m-d H:i:s', time() + 3600); // 1小时有效
        $this->save();
        return $token;
    }

    /**
     * 验证邮箱
     * @param string $token
     * @return bool
     */
    public function verifyEmail(string $token): bool
    {
        if ($this->email_verify_token !== $token) {
            return false;
        }

        if ($this->email_verify_expire && strtotime($this->email_verify_expire) < time()) {
            return false;
        }

        $this->email_verified = 1;
        $this->email_verify_token = null;
        $this->email_verify_expire = null;
        $this->save();

        return true;
    }

    /**
     * 增加积分
     * @param int $points 积分数
     * @param string $type 类型
     * @param string $description 描述
     * @param string|null $relatedType 关联类型
     * @param int|null $relatedId 关联ID
     * @return bool
     */
    public function addPoints(int $points, string $type, string $description = '', ?string $relatedType = null, ?int $relatedId = null): bool
    {
        $this->points += $points;
        $this->save();

        // 记录积分日志
        UserPointLog::create([
            'user_id'      => $this->id,
            'points'       => $points,
            'balance'      => $this->points,
            'type'         => $type,
            'description'  => $description,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
        ]);

        return true;
    }

    /**
     * 扣除积分
     * @param int $points 积分数
     * @param string $type 类型
     * @param string $description 描述
     * @param string|null $relatedType 关联类型
     * @param int|null $relatedId 关联ID
     * @return bool
     */
    public function deductPoints(int $points, string $type, string $description = '', ?string $relatedType = null, ?int $relatedId = null): bool
    {
        if ($this->points < $points) {
            return false;
        }

        $this->points -= $points;
        $this->save();

        // 记录积分日志
        UserPointLog::create([
            'user_id'      => $this->id,
            'points'       => -$points,
            'balance'      => $this->points,
            'type'         => $type,
            'description'  => $description,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
        ]);

        return true;
    }

    /**
     * 关联文章（用户发布的文章）
     */
    public function articles()
    {
        return $this->hasMany(Article::class, 'user_id');
    }

    /**
     * 关联评论
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id');
    }

    /**
     * 关联收藏
     */
    public function favorites()
    {
        return $this->hasMany(UserFavorite::class, 'user_id');
    }

    /**
     * 关联点赞
     */
    public function likes()
    {
        return $this->hasMany(UserLike::class, 'user_id');
    }

    /**
     * 关联阅读历史
     */
    public function readHistory()
    {
        return $this->hasMany(UserReadHistory::class, 'user_id');
    }

    /**
     * 关联积分日志
     */
    public function pointLogs()
    {
        return $this->hasMany(UserPointLog::class, 'user_id');
    }

    /**
     * 关联关注（我关注的人）
     */
    public function following()
    {
        return $this->hasMany(UserFollow::class, 'user_id');
    }

    /**
     * 关联粉丝（关注我的人）
     */
    public function followers()
    {
        return $this->hasMany(UserFollow::class, 'follow_user_id');
    }
}
