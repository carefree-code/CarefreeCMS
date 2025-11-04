<?php

namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 会员等级配置模型
 */
class MemberLevel extends Model
{
    use SoftDelete;

    protected $name = 'member_levels';

    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    protected $type = [
        'level' => 'integer',
        'points_required' => 'integer',
        'articles_required' => 'integer',
        'comments_required' => 'integer',
        'days_required' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    protected $json = ['privileges'];

    /**
     * 获取privileges数组
     */
    public function getPrivilegesArrayAttr($value, $data)
    {
        if (isset($data['privileges'])) {
            return is_string($data['privileges']) ? json_decode($data['privileges'], true) : $data['privileges'];
        }
        return [];
    }

    /**
     * 根据等级获取配置
     */
    public static function getByLevel(int $level): ?MemberLevel
    {
        return self::where('level', $level)->where('status', 1)->find();
    }

    /**
     * 获取所有启用的等级配置（按等级排序）
     */
    public static function getAllEnabled(): array
    {
        return self::where('status', 1)
            ->order('level', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 计算用户应该达到的等级
     *
     * @param int $points 用户积分
     * @param int $articles 用户文章数
     * @param int $comments 用户评论数
     * @param int $days 注册天数
     * @return int 应该达到的等级
     */
    public static function calculateUserLevel(int $points, int $articles, int $comments, int $days): int
    {
        $levels = self::getAllEnabled();

        $targetLevel = 0;
        foreach ($levels as $levelConfig) {
            // 检查是否满足所有条件
            if ($points >= $levelConfig['points_required'] &&
                $articles >= $levelConfig['articles_required'] &&
                $comments >= $levelConfig['comments_required'] &&
                $days >= $levelConfig['days_required']) {
                $targetLevel = $levelConfig['level'];
            } else {
                // 因为是按等级排序的，如果不满足条件就跳出
                break;
            }
        }

        return $targetLevel;
    }

    /**
     * 获取等级进度信息
     *
     * @param FrontUser $user
     * @return array
     */
    public static function getUserLevelProgress($user): array
    {
        $currentLevel = self::getByLevel($user->level);
        $nextLevel = self::getByLevel($user->level + 1);

        if (!$nextLevel) {
            // 已经是最高等级
            return [
                'current_level' => $currentLevel ? $currentLevel->toArray() : null,
                'next_level' => null,
                'is_max_level' => true,
                'progress' => [
                    'points' => 100,
                    'articles' => 100,
                    'comments' => 100,
                    'days' => 100,
                ],
            ];
        }

        // 计算注册天数
        $regTime = strtotime($user->create_time);
        $days = floor((time() - $regTime) / 86400);

        // 计算各项进度百分比
        $progress = [
            'points' => $nextLevel->points_required > 0 ? min(100, round($user->points / $nextLevel->points_required * 100, 2)) : 100,
            'articles' => $nextLevel->articles_required > 0 ? min(100, round($user->article_count / $nextLevel->articles_required * 100, 2)) : 100,
            'comments' => $nextLevel->comments_required > 0 ? min(100, round($user->comment_count / $nextLevel->comments_required * 100, 2)) : 100,
            'days' => $nextLevel->days_required > 0 ? min(100, round($days / $nextLevel->days_required * 100, 2)) : 100,
        ];

        return [
            'current_level' => $currentLevel ? $currentLevel->toArray() : null,
            'next_level' => $nextLevel->toArray(),
            'is_max_level' => false,
            'progress' => $progress,
            'current_stats' => [
                'points' => $user->points,
                'articles' => $user->article_count,
                'comments' => $user->comment_count,
                'days' => $days,
            ],
            'required_stats' => [
                'points' => $nextLevel->points_required,
                'articles' => $nextLevel->articles_required,
                'comments' => $nextLevel->comments_required,
                'days' => $nextLevel->days_required,
            ],
        ];
    }
}
