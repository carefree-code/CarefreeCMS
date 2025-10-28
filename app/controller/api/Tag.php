<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Logger;
use app\model\Tag as TagModel;
use app\model\ArticleTag;
use app\model\OperationLog;
use think\Request;

/**
 * 标签管理控制器
 */
class Tag extends BaseController
{
    /**
     * 标签列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 50);
        $name = $request->get('name', '');
        $status = $request->get('status', '');

        // 构建查询
        $query = TagModel::order(['sort' => 'asc', 'id' => 'desc']);

        // 搜索条件
        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 分页
        $list = $query->page($page, $pageSize)->select();
        $total = $query->count();

        return Response::paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 获取所有标签（不分页）
     */
    public function all(Request $request)
    {
        $status = $request->get('status', 1);

        $query = TagModel::order(['sort' => 'asc', 'id' => 'desc']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        $list = $query->select();

        return Response::success($list->toArray());
    }

    /**
     * 标签详情
     */
    public function read($id)
    {
        $tag = TagModel::find($id);

        if (!$tag) {
            return Response::notFound('标签不存在');
        }

        return Response::success($tag->toArray());
    }

    /**
     * 创建标签
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['name'])) {
            return Response::error('标签名称不能为空');
        }

        // 检查同名标签
        $exists = TagModel::where('name', $data['name'])->find();
        if ($exists) {
            return Response::error('标签已存在');
        }

        try {
            $tag = TagModel::create($data);
            Logger::create(OperationLog::MODULE_TAG, '标签', $tag->id);
            return Response::success(['id' => $tag->id], '标签创建成功');
        } catch (\Exception $e) {
            Logger::log(OperationLog::MODULE_TAG, OperationLog::ACTION_CREATE, '创建标签失败', false, $e->getMessage());
            return Response::error('标签创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新标签
     */
    public function update(Request $request, $id)
    {
        $tag = TagModel::find($id);
        if (!$tag) {
            return Response::notFound('标签不存在');
        }

        $data = $request->post();

        // 检查同名标签
        if (isset($data['name'])) {
            $exists = TagModel::where('name', $data['name'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return Response::error('标签名称已存在');
            }
        }

        try {
            $tag->save($data);
            Logger::update(OperationLog::MODULE_TAG, '标签', $id);
            return Response::success([], '标签更新成功');
        } catch (\Exception $e) {
            Logger::log(OperationLog::MODULE_TAG, OperationLog::ACTION_UPDATE, "更新标签失败 (ID: {$id})", false, $e->getMessage());
            return Response::error('标签更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除标签
     */
    public function delete($id)
    {
        $tag = TagModel::find($id);
        if (!$tag) {
            return Response::notFound('标签不存在');
        }

        // 检查是否有关联文章
        $articleCount = ArticleTag::where('tag_id', $id)->count();
        if ($articleCount > 0) {
            return Response::error('该标签下有关联文章，无法删除');
        }

        try {
            $tagName = $tag->name;
            $tag->delete();
            Logger::delete(OperationLog::MODULE_TAG, "标签[{$tagName}]", $id);
            return Response::success([], '标签删除成功');
        } catch (\Exception $e) {
            Logger::log(OperationLog::MODULE_TAG, OperationLog::ACTION_DELETE, "删除标签失败 (ID: {$id})", false, $e->getMessage());
            return Response::error('标签删除失败：' . $e->getMessage());
        }
    }
}
