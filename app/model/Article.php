<?php

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 文章模型
 */
class Article extends Model
{
    use SoftDelete;

    protected $name = 'articles';

    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    protected $type = [
        'images'        => 'json',
        'category_id'   => 'integer',
        'user_id'       => 'integer',
        'view_count'    => 'integer',
        'like_count'    => 'integer',
        'comment_count' => 'integer',
        'is_top'        => 'integer',
        'is_recommend'  => 'integer',
        'is_hot'        => 'integer',
        'sort'          => 'integer',
        'status'        => 'integer',
        'is_contribute' => 'integer',
        'audit_status'  => 'integer',
        'audit_user_id' => 'integer',
        'reward_points' => 'integer',
    ];

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * 关联作者
     */
    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id', 'id');
    }

    /**
     * 关联标签（多对多）
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, ArticleTag::class, 'tag_id', 'article_id');
    }

    /**
     * 关联所有分类（主分类+副分类）
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, ArticleCategory::class, 'category_id', 'article_id')
            ->withField('is_main');
    }

    /**
     * 获取主分类
     */
    public function mainCategory()
    {
        return $this->hasOneThrough(
            Category::class,
            ArticleCategory::class,
            'article_id',
            'id',
            'id',
            'category_id'
        )->where('article_categories.is_main', 1);
    }

    /**
     * 获取副分类列表
     */
    public function subCategories()
    {
        return $this->belongsToMany(Category::class, ArticleCategory::class, 'category_id', 'article_id')
            ->where('article_categories.is_main', 0);
    }

    /**
     * 关联专题（多对多）
     */
    public function topics()
    {
        return $this->belongsToMany(Topic::class, TopicArticle::class, 'topic_id', 'article_id');
    }

    /**
     * 搜索器：标题
     */
    public function searchTitleAttr($query, $value)
    {
        $query->where('title', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：分类
     */
    public function searchCategoryIdAttr($query, $value)
    {
        $query->where('category_id', $value);
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 搜索器：是否置顶
     */
    public function searchIsTopAttr($query, $value)
    {
        $query->where('is_top', $value);
    }

    /**
     * 搜索器：是否推荐
     */
    public function searchIsRecommendAttr($query, $value)
    {
        $query->where('is_recommend', $value);
    }

    /**
     * 获取器：状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '草稿', 1 => '已发布', 2 => '待审核', 3 => '已下线'];
        return $status[$data['status']] ?? '未知';
    }

    /**
     * 关联前台用户（投稿作者）
     */
    public function frontUser()
    {
        return $this->belongsTo(FrontUser::class, 'user_id', 'id');
    }

    /**
     * 关联审核管理员
     */
    public function auditor()
    {
        return $this->belongsTo(AdminUser::class, 'audit_user_id', 'id');
    }

    /**
     * 获取器：审核状态文本
     */
    public function getAuditStatusTextAttr($value, $data)
    {
        if (!isset($data['audit_status'])) {
            return '';
        }

        $status = [0 => '待审核', 1 => '已通过', 2 => '已拒绝'];
        return $status[$data['audit_status']] ?? '未知';
    }

    /**
     * 审核通过
     *
     * @param int $auditorId 审核人ID
     * @param string $remark 审核备注
     * @return bool
     */
    public function auditPass(int $auditorId, string $remark = ''): bool
    {
        if (!$this->is_contribute) {
            throw new \Exception('非投稿文章');
        }

        if ($this->audit_status != 0) {
            throw new \Exception('文章已审核');
        }

        \think\facade\Db::startTrans();

        try {
            // 更新文章状态
            $this->audit_status = 1;
            $this->audit_user_id = $auditorId;
            $this->audit_time = date('Y-m-d H:i:s');
            $this->audit_remark = $remark;
            $this->status = 1; // 发布文章
            $this->save();

            // 奖励积分
            $config = ContributeConfig::getByCategoryId($this->category_id);
            $rewardPoints = $config ? $config->reward_points : 10;

            if ($rewardPoints > 0) {
                $user = FrontUser::find($this->user_id);
                if ($user) {
                    $user->addPoints($rewardPoints, 'contribute', "投稿通过奖励：{$this->title}", 'article', $this->id);
                    $this->reward_points = $rewardPoints;
                    $this->save();
                }
            }

            // 发送通知
            \app\service\NotificationService::sendArticleAuditNotification(
                $this->user_id,
                $this->title,
                true,
                $remark
            );

            \think\facade\Db::commit();

            return true;

        } catch (\Exception $e) {
            \think\facade\Db::rollback();
            throw $e;
        }
    }

    /**
     * 审核拒绝
     *
     * @param int $auditorId 审核人ID
     * @param string $remark 拒绝原因
     * @return bool
     */
    public function auditReject(int $auditorId, string $remark): bool
    {
        if (!$this->is_contribute) {
            throw new \Exception('非投稿文章');
        }

        if ($this->audit_status != 0) {
            throw new \Exception('文章已审核');
        }

        $this->audit_status = 2;
        $this->audit_user_id = $auditorId;
        $this->audit_time = date('Y-m-d H:i:s');
        $this->audit_remark = $remark;
        $this->status = 3; // 下线
        $this->save();

        // 发送通知
        \app\service\NotificationService::sendArticleAuditNotification(
            $this->user_id,
            $this->title,
            false,
            $remark
        );

        return true;
    }
}
