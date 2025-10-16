<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\ArticleFlag as ArticleFlagModel;
use think\Request;

/**
 * 文章属性控制器
 */
class ArticleFlag extends BaseController
{
    /**
     * 获取属性列表
     */
    public function index(Request $request)
    {
        $page = $request->param('page', 1);
        $pageSize = $request->param('pageSize', 10);
        $name = $request->param('name', '');

        $query = ArticleFlagModel::order('sort_order', 'asc');

        // 按名称搜索
        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        return Response::success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }

    /**
     * 获取所有启用的属性（不分页）
     */
    public function all(Request $request)
    {
        $list = ArticleFlagModel::getAllEnabled();
        return Response::success($list);
    }

    /**
     * 获取属性详情
     */
    public function read(Request $request, $id)
    {
        $flag = ArticleFlagModel::find($id);

        if (!$flag) {
            return Response::notFound('属性不存在');
        }

        return Response::success($flag);
    }

    /**
     * 创建属性
     */
    public function save(Request $request)
    {
        $data = $request->only(['name', 'flag_value', 'is_show', 'sort_order', 'status']);

        // 验证必填字段
        if (empty($data['name'])) {
            return Response::error('属性名称不能为空');
        }

        if (empty($data['flag_value'])) {
            return Response::error('属性值不能为空');
        }

        // 检查属性值是否已存在
        $exists = ArticleFlagModel::where('flag_value', $data['flag_value'])->find();
        if ($exists) {
            return Response::error('属性值已存在');
        }

        try {
            $flag = ArticleFlagModel::create($data);
            return Response::success($flag, '创建成功');
        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新属性
     */
    public function update(Request $request, $id)
    {
        $flag = ArticleFlagModel::find($id);

        if (!$flag) {
            return Response::notFound('属性不存在');
        }

        $data = $request->only(['name', 'flag_value', 'is_show', 'sort_order', 'status']);

        // 验证必填字段
        if (isset($data['name']) && empty($data['name'])) {
            return Response::error('属性名称不能为空');
        }

        if (isset($data['flag_value']) && empty($data['flag_value'])) {
            return Response::error('属性值不能为空');
        }

        // 如果修改了属性值，检查是否已存在
        if (isset($data['flag_value']) && $data['flag_value'] != $flag->flag_value) {
            $exists = ArticleFlagModel::where('flag_value', $data['flag_value'])->find();
            if ($exists) {
                return Response::error('属性值已存在');
            }
        }

        try {
            $flag->save($data);
            return Response::success($flag, '更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除属性
     */
    public function delete(Request $request, $id)
    {
        $flag = ArticleFlagModel::find($id);

        if (!$flag) {
            return Response::notFound('属性不存在');
        }

        try {
            $flag->delete();
            return Response::success([], '删除成功');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }
}
