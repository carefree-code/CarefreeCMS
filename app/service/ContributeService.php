<?php

namespace app\service;

use app\model\Article;
use app\model\ContributeConfig;
use app\model\FrontUser;
use app\model\Category;

/**
 * 投稿服务
 */
class ContributeService
{
    /**
     * 用户投稿
     *
     * @param int $userId 用户ID
     * @param array $data 文章数据
     * @return array
     */
    public static function submit(int $userId, array $data): array
    {
        try {
            // 获取用户
            $user = FrontUser::find($userId);
            if (!$user) {
                return ['success' => false, 'message' => '用户不存在'];
            }

            // 验证必填字段
            if (empty($data['title']) || empty($data['content']) || empty($data['category_id'])) {
                return ['success' => false, 'message' => '请填写完整信息'];
            }

            // 获取投稿配置
            $config = ContributeConfig::getOrCreateDefault($data['category_id']);

            // 检查用户是否可以投稿
            $check = $config->canUserContribute($user);
            if (!$check['can_contribute']) {
                return ['success' => false, 'message' => $check['message']];
            }

            // 检查文章内容
            $contentCheck = $config->checkArticleContent($data['content']);
            if (!$contentCheck['valid']) {
                return ['success' => false, 'message' => $contentCheck['message']];
            }

            // 创建投稿文章
            $article = Article::create([
                'title'         => $data['title'],
                'content'       => $data['content'],
                'excerpt'       => $data['excerpt'] ?? mb_substr(strip_tags($data['content']), 0, 200),
                'cover_image'   => $data['cover_image'] ?? null,
                'category_id'   => $data['category_id'],
                'user_id'       => $userId,
                'is_contribute' => 1,
                'audit_status'  => 0, // 待审核
                'status'        => $config->need_audit ? 2 : 1, // 需要审核则为待审核，否则直接发布
            ]);

            // 如果不需要审核，自动通过并奖励积分
            if (!$config->need_audit) {
                if ($config->reward_points > 0) {
                    $user->addPoints($config->reward_points, 'contribute', "投稿奖励：{$article->title}", 'article', $article->id);
                    $article->reward_points = $config->reward_points;
                    $article->audit_status = 1;
                    $article->audit_time = date('Y-m-d H:i:s');
                    $article->save();
                }
            }

            return [
                'success' => true,
                'message' => $config->need_audit ? '投稿成功，等待审核' : '投稿成功',
                'data'    => $article->toArray(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '投稿失败：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 获取用户投稿列表
     */
    public static function getUserContributions(int $userId, int $page = 1, int $limit = 20): array
    {
        $query = Article::where('user_id', $userId)
            ->where('is_contribute', 1)
            ->order('create_time', 'desc');

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return $list->toArray();
    }

    /**
     * 获取待审核投稿列表
     */
    public static function getPendingContributions(int $page = 1, int $limit = 20): array
    {
        $query = Article::where('is_contribute', 1)
            ->where('audit_status', 0)
            ->with(['frontUser', 'category'])
            ->order('create_time', 'asc');

        $list = $query->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return $list->toArray();
    }

    /**
     * 获取投稿统计
     */
    public static function getStatistics(): array
    {
        $total = Article::where('is_contribute', 1)->count();
        $pending = Article::where('is_contribute', 1)->where('audit_status', 0)->count();
        $approved = Article::where('is_contribute', 1)->where('audit_status', 1)->count();
        $rejected = Article::where('is_contribute', 1)->where('audit_status', 2)->count();

        $todaySubmit = Article::where('is_contribute', 1)
            ->whereTime('create_time', 'today')
            ->count();

        $todayAudit = Article::where('is_contribute', 1)
            ->where('audit_status', '>', 0)
            ->whereTime('audit_time', 'today')
            ->count();

        return [
            'total'         => $total,
            'pending'       => $pending,
            'approved'      => $approved,
            'rejected'      => $rejected,
            'today_submit'  => $todaySubmit,
            'today_audit'   => $todayAudit,
        ];
    }

    /**
     * 获取可投稿分类列表
     */
    public static function getAvailableCategories(int $userId): array
    {
        $user = FrontUser::find($userId);
        if (!$user) {
            return [];
        }

        // 获取所有分类
        $categories = Category::where('status', 1)->select();

        $available = [];
        foreach ($categories as $category) {
            $config = ContributeConfig::getByCategoryId($category->id);

            // 如果没有配置，使用默认配置
            if (!$config) {
                $config = ContributeConfig::getOrCreateDefault($category->id);
            }

            $check = $config->canUserContribute($user);

            $available[] = [
                'id'             => $category->id,
                'name'           => $category->name,
                'can_contribute' => $check['can_contribute'],
                'message'        => $check['message'],
                'config'         => [
                    'need_audit'     => $config->need_audit,
                    'reward_points'  => $config->reward_points,
                    'min_words'      => $config->min_words,
                    'max_per_day'    => $config->max_per_day,
                    'level_required' => $config->level_required,
                ],
            ];
        }

        return $available;
    }
}
