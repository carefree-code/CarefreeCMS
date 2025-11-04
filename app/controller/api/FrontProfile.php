<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\FrontUser;
use app\model\UserFavorite;
use app\model\UserLike;
use app\model\UserReadHistory;
use app\model\UserPointLog;
use app\model\UserFollow;
use think\Request;
use think\facade\Db;

/**
 * 前台用户资料控制器
 */
class FrontProfile extends BaseController
{
    /**
     * 获取用户资料
     */
    public function index(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $user = FrontUser::find($userId);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        // 隐藏敏感字段
        $user->hidden(['password', 'reset_token', 'email_verify_token']);

        return Response::success($user->toArray());
    }

    /**
     * 更新用户资料
     */
    public function update(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $user = FrontUser::find($userId);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        // 可更新的字段
        $allowFields = [
            'nickname', 'real_name', 'phone', 'gender', 'birthday',
            'province', 'city', 'signature', 'bio'
        ];

        $data = [];
        foreach ($allowFields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->param($field);
            }
        }

        // 验证手机号格式
        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
                return Response::error('手机号格式不正确');
            }
        }

        // 验证性别值
        if (isset($data['gender']) && !in_array($data['gender'], [0, 1, 2])) {
            return Response::error('性别值不正确');
        }

        try {
            $user->save($data);
            return Response::success($user->toArray(), '资料更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 上传头像
     */
    public function uploadAvatar(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $user = FrontUser::find($userId);

        if (!$user) {
            return Response::notFound('用户不存在');
        }

        // 获取上传的文件
        $file = $request->file('avatar');

        if (!$file) {
            return Response::error('请选择要上传的图片');
        }

        // 验证文件
        try {
            validate([
                'avatar' => [
                    'fileSize' => 2 * 1024 * 1024, // 2MB
                    'fileExt'  => 'jpg,jpeg,png,gif',
                ]
            ])->check(['avatar' => $file]);
        } catch (\think\exception\ValidateException $e) {
            return Response::error($e->getMessage());
        }

        try {
            // 获取文件信息
            $ext = strtolower($file->extension());

            // 生成日期目录
            $datePath = date('Y/m/d');
            $savePath = 'uploads/avatar/front/' . $datePath;

            // 生成唯一文件名
            $fileName = 'avatar_' . $userId . '_' . date('YmdHis') . '.' . $ext;

            // 创建目录（如果不存在）
            $fullPath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . $savePath;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // 删除旧头像文件（如果存在）
            if ($user->avatar) {
                $oldAvatarPath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . $user->avatar;
                if (file_exists($oldAvatarPath)) {
                    @unlink($oldAvatarPath);
                }
            }

            // 移动文件
            $file->move($fullPath, $fileName);

            // 文件相对路径
            $filePath = $savePath . '/' . $fileName;

            // 更新用户头像
            $user->avatar = $filePath;
            $user->save();

            // 生成完整URL
            $siteUrl = \app\model\Config::getConfig('site_url', '');
            if (!empty($siteUrl)) {
                $avatarUrl = rtrim($siteUrl, '/') . '/' . $filePath;
            } else {
                $avatarUrl = $request->domain() . '/html/' . $filePath;
            }

            return Response::success([
                'avatar'     => $filePath,
                'avatar_url' => $avatarUrl
            ], '头像上传成功');

        } catch (\Exception $e) {
            return Response::error('头像上传失败：' . $e->getMessage());
        }
    }

    /**
     * 获取收藏列表
     */
    public function favorites(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $favorites = UserFavorite::with(['article'])
            ->where('user_id', $userId)
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return Response::success($favorites);
    }

    /**
     * 收藏文章
     */
    public function addFavorite(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $articleId = $request->post('article_id', 0);

        if (!$userId) {
            return Response::unauthorized();
        }

        if (!$articleId) {
            return Response::error('文章ID不能为空');
        }

        // 检查是否已收藏
        $exists = UserFavorite::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->find();

        if ($exists) {
            return Response::error('已经收藏过了');
        }

        try {
            UserFavorite::create([
                'user_id'    => $userId,
                'article_id' => $articleId,
            ]);

            // 更新用户收藏数
            $user = FrontUser::find($userId);
            $user->favorite_count += 1;
            $user->save();

            return Response::success([], '收藏成功');
        } catch (\Exception $e) {
            return Response::error('收藏失败：' . $e->getMessage());
        }
    }

    /**
     * 取消收藏
     */
    public function removeFavorite(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $articleId = $request->post('article_id', 0);

        if (!$userId) {
            return Response::unauthorized();
        }

        if (!$articleId) {
            return Response::error('文章ID不能为空');
        }

        $favorite = UserFavorite::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->find();

        if (!$favorite) {
            return Response::error('未收藏过该文章');
        }

        try {
            $favorite->delete();

            // 更新用户收藏数
            $user = FrontUser::find($userId);
            if ($user->favorite_count > 0) {
                $user->favorite_count -= 1;
                $user->save();
            }

            return Response::success([], '取消收藏成功');
        } catch (\Exception $e) {
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 点赞
     */
    public function addLike(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $targetType = $request->post('target_type', ''); // article 或 comment
        $targetId = $request->post('target_id', 0);

        if (!$userId) {
            return Response::unauthorized();
        }

        if (!in_array($targetType, ['article', 'comment'])) {
            return Response::error('目标类型不正确');
        }

        if (!$targetId) {
            return Response::error('目标ID不能为空');
        }

        // 检查是否已点赞
        $exists = UserLike::where('user_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->find();

        if ($exists) {
            return Response::error('已经点赞过了');
        }

        Db::startTrans();
        try {
            // 创建点赞记录
            UserLike::create([
                'user_id'     => $userId,
                'target_type' => $targetType,
                'target_id'   => $targetId,
            ]);

            // 更新目标的点赞数
            if ($targetType === 'article') {
                Db::name('articles')->where('id', $targetId)->inc('like_count')->update();
            } elseif ($targetType === 'comment') {
                Db::name('comments')->where('id', $targetId)->inc('like_count')->update();
            }

            Db::commit();
            return Response::success([], '点赞成功');
        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('点赞失败：' . $e->getMessage());
        }
    }

    /**
     * 取消点赞
     */
    public function removeLike(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $targetType = $request->post('target_type', '');
        $targetId = $request->post('target_id', 0);

        if (!$userId) {
            return Response::unauthorized();
        }

        $like = UserLike::where('user_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->find();

        if (!$like) {
            return Response::error('未点赞过');
        }

        Db::startTrans();
        try {
            $like->delete();

            // 更新目标的点赞数
            if ($targetType === 'article') {
                Db::name('articles')->where('id', $targetId)->dec('like_count')->update();
            } elseif ($targetType === 'comment') {
                Db::name('comments')->where('id', $targetId)->dec('like_count')->update();
            }

            Db::commit();
            return Response::success([], '取消点赞成功');
        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 获取阅读历史
     */
    public function readHistory(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $history = UserReadHistory::with(['article'])
            ->where('user_id', $userId)
            ->order('update_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return Response::success($history);
    }

    /**
     * 记录阅读历史
     */
    public function addReadHistory(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $articleId = $request->post('article_id', 0);
        $readProgress = $request->post('read_progress', 0);
        $readTime = $request->post('read_time', 0);

        if (!$userId) {
            return Response::unauthorized();
        }

        if (!$articleId) {
            return Response::error('文章ID不能为空');
        }

        try {
            // 查找是否存在记录
            $history = UserReadHistory::where('user_id', $userId)
                ->where('article_id', $articleId)
                ->find();

            if ($history) {
                // 更新记录
                $history->read_progress = $readProgress;
                $history->read_time += $readTime;
                $history->save();
            } else {
                // 创建新记录
                UserReadHistory::create([
                    'user_id'       => $userId,
                    'article_id'    => $articleId,
                    'read_progress' => $readProgress,
                    'read_time'     => $readTime,
                ]);
            }

            return Response::success([], '记录成功');
        } catch (\Exception $e) {
            return Response::error('记录失败：' . $e->getMessage());
        }
    }

    /**
     * 获取积分日志
     */
    public function pointLogs(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $logs = UserPointLog::where('user_id', $userId)
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return Response::success($logs);
    }

    /**
     * 关注用户
     */
    public function follow(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $followUserId = $request->post('follow_user_id', 0);

        if (!$userId) {
            return Response::unauthorized();
        }

        if (!$followUserId) {
            return Response::error('用户ID不能为空');
        }

        if ($userId == $followUserId) {
            return Response::error('不能关注自己');
        }

        // 检查是否已关注
        $exists = UserFollow::where('user_id', $userId)
            ->where('follow_user_id', $followUserId)
            ->find();

        if ($exists) {
            return Response::error('已经关注过了');
        }

        Db::startTrans();
        try {
            UserFollow::create([
                'user_id'        => $userId,
                'follow_user_id' => $followUserId,
            ]);

            // 更新关注数和粉丝数
            $user = FrontUser::find($userId);
            $user->following_count += 1;
            $user->save();

            $followUser = FrontUser::find($followUserId);
            $followUser->follower_count += 1;
            $followUser->save();

            Db::commit();
            return Response::success([], '关注成功');
        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('关注失败：' . $e->getMessage());
        }
    }

    /**
     * 取消关注
     */
    public function unfollow(Request $request)
    {
        $userId = $request->user['id'] ?? 0;
        $followUserId = $request->post('follow_user_id', 0);

        if (!$userId) {
            return Response::unauthorized();
        }

        $follow = UserFollow::where('user_id', $userId)
            ->where('follow_user_id', $followUserId)
            ->find();

        if (!$follow) {
            return Response::error('未关注过该用户');
        }

        Db::startTrans();
        try {
            $follow->delete();

            // 更新关注数和粉丝数
            $user = FrontUser::find($userId);
            if ($user->following_count > 0) {
                $user->following_count -= 1;
                $user->save();
            }

            $followUser = FrontUser::find($followUserId);
            if ($followUser->follower_count > 0) {
                $followUser->follower_count -= 1;
                $followUser->save();
            }

            Db::commit();
            return Response::success([], '取消关注成功');
        } catch (\Exception $e) {
            Db::rollback();
            return Response::error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 获取关注列表
     */
    public function followingList(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $following = UserFollow::with(['followUser'])
            ->where('user_id', $userId)
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return Response::success($following);
    }

    /**
     * 获取粉丝列表
     */
    public function followerList(Request $request)
    {
        $userId = $request->user['id'] ?? 0;

        if (!$userId) {
            return Response::unauthorized();
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $followers = UserFollow::with(['user'])
            ->where('follow_user_id', $userId)
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return Response::success($followers);
    }
}
