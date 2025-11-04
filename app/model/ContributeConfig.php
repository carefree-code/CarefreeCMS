<?php

namespace app\model;

use think\Model;

/**
 * 投稿配置模型
 */
class ContributeConfig extends Model
{
    protected $name = 'contribute_config';

    // 设置字段信息
    protected $schema = [
        'id'              => 'int',
        'category_id'     => 'int',
        'allow_contribute' => 'int',
        'need_audit'      => 'int',
        'reward_points'   => 'int',
        'min_words'       => 'int',
        'max_per_day'     => 'int',
        'level_required'  => 'int',
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'category_id'      => 'integer',
        'allow_contribute' => 'boolean',
        'need_audit'       => 'boolean',
        'reward_points'    => 'integer',
        'min_words'        => 'integer',
        'max_per_day'      => 'integer',
        'level_required'   => 'integer',
    ];

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 获取分类投稿配置
     */
    public static function getByCategoryId(int $categoryId): ?ContributeConfig
    {
        return self::where('category_id', $categoryId)->find();
    }

    /**
     * 获取或创建默认配置
     */
    public static function getOrCreateDefault(int $categoryId): ContributeConfig
    {
        $config = self::getByCategoryId($categoryId);

        if (!$config) {
            $config = self::create([
                'category_id'      => $categoryId,
                'allow_contribute' => 1,
                'need_audit'       => 1,
                'reward_points'    => 10,
                'min_words'        => 100,
                'max_per_day'      => 5,
                'level_required'   => 1,
            ]);
        }

        return $config;
    }

    /**
     * 检查用户是否可以投稿
     *
     * @param FrontUser $user
     * @return array ['can_contribute' => bool, 'message' => string]
     */
    public function canUserContribute(FrontUser $user): array
    {
        // 检查是否允许投稿
        if (!$this->allow_contribute) {
            return ['can_contribute' => false, 'message' => '该分类不允许投稿'];
        }

        // 检查用户等级
        if ($this->level_required > 0 && $user->level < $this->level_required) {
            return ['can_contribute' => false, 'message' => "需要等级 {$this->level_required}"];
        }

        // 检查今日投稿次数
        if ($this->max_per_day > 0) {
            $todayCount = \app\model\Article::where('user_id', $user->id)
                ->where('is_contribute', 1)
                ->whereTime('create_time', 'today')
                ->count();

            if ($todayCount >= $this->max_per_day) {
                return ['can_contribute' => false, 'message' => "每日最多投稿 {$this->max_per_day} 篇"];
            }
        }

        return ['can_contribute' => true, 'message' => ''];
    }

    /**
     * 检查文章内容是否符合要求
     */
    public function checkArticleContent(string $content): array
    {
        // 计算字数（去除HTML标签）
        $plainText = strip_tags($content);
        $wordCount = mb_strlen($plainText, 'UTF-8');

        if ($this->min_words > 0 && $wordCount < $this->min_words) {
            return [
                'valid'   => false,
                'message' => "文章字数不足，至少需要 {$this->min_words} 字，当前 {$wordCount} 字",
            ];
        }

        return ['valid' => true, 'message' => ''];
    }
}
