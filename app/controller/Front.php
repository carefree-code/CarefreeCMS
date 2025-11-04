<?php

namespace app\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * 前台页面控制器
 * 处理需要动态渲染的前台模板页面
 */
class Front extends BaseController
{
    /**
     * 获取当前登录用户ID
     * 尝试从多个来源获取：Cookie、Header、Session
     */
    protected function getCurrentUserId()
    {
        // 1. 尝试从 Cookie 中获取 token
        $token = Request::cookie('token');

        // 2. 如果 Cookie 中没有，尝试从 Header 中获取
        if (empty($token)) {
            $token = Request::header('Authorization');
            // 移除 Bearer 前缀
            if (stripos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }
        }

        // 3. 如果没有token，返回0（未登录状态）
        if (empty($token)) {
            return 0;
        }

        // 4. 验证 token 并获取用户ID
        try {
            $key = config('jwt.key');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return $decoded->data->id ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 会员中心页面
     */
    public function members()
    {
        View::assign([
            'title' => '会员中心',
            'keywords' => '会员,排行榜,积分',
            'description' => '会员中心 - 查看会员等级和排行榜'
        ]);

        return View::fetch('official/members');
    }

    /**
     * 消息通知页面
     * 注意：用户认证由前端JavaScript处理，这里只准备数据
     */
    public function notifications()
    {
        $userId = $this->getCurrentUserId();

        $type = Request::param('type', '');
        $page = Request::param('page', 1);
        $pagesize = 20;

        // 查询消息总数（如果用户未登录，总数为0）
        $total = 0;
        if ($userId > 0) {
            $query = Db::name('notifications')->where('user_id', $userId);
            if (!empty($type)) {
                $query->where('type', $type);
            }
            $total = $query->count();
        }

        // 计算分页
        $lastPage = $total > 0 ? ceil($total / $pagesize) : 1;
        $currentPage = max(1, min($page, $lastPage));

        View::assign([
            'title' => '消息通知',
            'keywords' => '消息,通知,提醒',
            'description' => '查看您的最新消息和通知',
            'current_user_id' => $userId,
            'type' => $type,
            'total' => $total,
            'pagesize' => $pagesize,
            'current_page' => $currentPage,
            'last_page' => $lastPage
        ]);

        return View::fetch('official/notifications');
    }

    /**
     * 我的投稿列表页面
     * 注意：用户认证由前端JavaScript处理，这里只准备数据
     */
    public function contributions()
    {
        $userId = $this->getCurrentUserId();

        $status = Request::param('status', '');
        $page = Request::param('page', 1);
        $pagesize = 10;

        // 查询投稿总数（如果用户未登录，总数为0）
        $total = 0;
        if ($userId > 0) {
            $query = Db::name('contributions')->where('user_id', $userId);
            if ($status !== '') {
                $query->where('status', $status);
            }
            $total = $query->count();
        }

        // 计算分页
        $lastPage = $total > 0 ? ceil($total / $pagesize) : 1;
        $currentPage = max(1, min($page, $lastPage));

        View::assign([
            'title' => '我的投稿',
            'keywords' => '投稿,文章,创作',
            'description' => '查看和管理您的投稿内容',
            'current_user_id' => $userId,
            'status' => $status,
            'total' => $total,
            'pagesize' => $pagesize,
            'current_page' => $currentPage,
            'last_page' => $lastPage
        ]);

        return View::fetch('official/contributions');
    }

    /**
     * 投稿页面（新建/编辑）
     * 注意：用户认证由前端JavaScript处理，这里只准备数据
     */
    public function contribute()
    {
        $userId = $this->getCurrentUserId();

        $editId = Request::param('id', 0);

        View::assign([
            'title' => $editId ? '编辑投稿' : '投稿中心',
            'keywords' => '投稿,发布文章,创作',
            'description' => '分享您的精彩内容，与更多人交流',
            'current_user_id' => $userId,
            'edit_id' => $editId
        ]);

        return View::fetch('official/contribute');
    }

    /**
     * 个人中心页面
     * 注意：用户认证由前端JavaScript处理，这里只准备数据
     */
    public function profile()
    {
        $userId = $this->getCurrentUserId();

        // 获取用户信息（如果已登录）
        $user = [];
        if ($userId > 0) {
            $user = Db::name('front_users')
                ->alias('u')
                ->leftJoin('member_levels ml', 'u.level = ml.level')
                ->field('u.*, ml.name as level_name')
                ->where('u.id', $userId)
                ->find();

            if (empty($user)) {
                $user = [];
            }
        }

        View::assign([
            'title' => '个人中心',
            'keywords' => '个人中心,用户信息',
            'description' => '管理您的个人信息和账户设置',
            'current_user_id' => $userId,
            'user' => $user
        ]);

        return View::fetch('official/profile');
    }
}
