<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Logger;
use app\model\Category as CategoryModel;
use app\model\OperationLog;
use think\Request;

/**
 * 分类管理控制器
 */
class Category extends BaseController
{
    /**
     * 分类列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 50);
        $name = $request->get('name', '');
        $parentId = $request->get('parent_id', '');
        $status = $request->get('status', '');

        // 构建查询
        $query = CategoryModel::with(['parent']);

        // 搜索条件
        if (!empty($name)) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if ($parentId !== '') {
            $query->where('parent_id', $parentId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 排序
        $query->order(['sort' => 'asc', 'id' => 'asc']);

        // 分页
        $list = $query->page($page, $pageSize)->select();
        $total = $query->count();

        return Response::paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 分类树形结构
     */
    public function tree(Request $request)
    {
        $status = $request->get('status', 1);

        // 获取所有分类
        $query = CategoryModel::order(['sort' => 'asc', 'id' => 'asc']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        $categories = $query->select()->toArray();

        // 构建树形结构
        $tree = $this->buildTree($categories);

        return Response::success($tree);
    }

    /**
     * 构建树形结构
     */
    private function buildTree($items, $parentId = 0)
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 分类详情
     */
    public function read($id)
    {
        $category = CategoryModel::with(['parent'])->find($id);

        if (!$category) {
            return Response::notFound('分类不存在');
        }

        return Response::success($category->toArray());
    }

    /**
     * 创建分类
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['name'])) {
            return Response::error('分类名称不能为空');
        }

        // 检查父分类是否存在
        if (isset($data['parent_id']) && $data['parent_id'] > 0) {
            $parent = CategoryModel::find($data['parent_id']);
            if (!$parent) {
                return Response::error('父分类不存在');
            }
        }

        // 处理parent_id：null转换为0（顶级分类）
        if (!isset($data['parent_id']) || $data['parent_id'] === null || $data['parent_id'] === '') {
            $data['parent_id'] = 0;
        }

        // 检查同名分类
        $exists = CategoryModel::where('name', $data['name'])
            ->where('parent_id', $data['parent_id'])
            ->find();
        if ($exists) {
            return Response::error('同名分类已存在');
        }

        try {
            $category = CategoryModel::create($data);
            Logger::create(OperationLog::MODULE_CATEGORY, '分类', $category->id);
            return Response::success(['id' => $category->id], '分类创建成功');
        } catch (\Exception $e) {
            Logger::log(
                OperationLog::MODULE_CATEGORY,
                OperationLog::ACTION_CREATE,
                '创建分类失败',
                false,
                $e->getMessage()
            );
            return Response::error('分类创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新分类
     */
    public function update(Request $request, $id)
    {
        $category = CategoryModel::find($id);
        if (!$category) {
            return Response::notFound('分类不存在');
        }

        $data = $request->post();

        // 验证父分类
        if (isset($data['parent_id']) && $data['parent_id'] > 0) {
            // 不能设置自己为父分类
            if ($data['parent_id'] == $id) {
                return Response::error('不能将自己设置为父分类');
            }

            // 检查父分类是否存在
            $parent = CategoryModel::find($data['parent_id']);
            if (!$parent) {
                return Response::error('父分类不存在');
            }

            // 防止循环引用（简单检查，父分类的父分类不能是当前分类）
            if ($parent->parent_id == $id) {
                return Response::error('不能创建循环引用的分类结构');
            }
        }

        // 检查同名分类
        if (isset($data['name'])) {
            $exists = CategoryModel::where('name', $data['name'])
                ->where('parent_id', $data['parent_id'] ?? $category->parent_id)
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return Response::error('同名分类已存在');
            }
        }

        try {
            $category->save($data);
            Logger::update(OperationLog::MODULE_CATEGORY, '分类', $id);
            return Response::success([], '分类更新成功');
        } catch (\Exception $e) {
            Logger::log(
                OperationLog::MODULE_CATEGORY,
                OperationLog::ACTION_UPDATE,
                "更新分类失败 (ID: {$id})",
                false,
                $e->getMessage()
            );
            return Response::error('分类更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除分类
     */
    public function delete($id)
    {
        $category = CategoryModel::find($id);
        if (!$category) {
            return Response::notFound('分类不存在');
        }

        // 检查是否有子分类
        $children = CategoryModel::where('parent_id', $id)->count();
        if ($children > 0) {
            return Response::error('该分类下有子分类，无法删除');
        }

        // 检查是否有关联文章
        $articleCount = $category->articles()->count();
        if ($articleCount > 0) {
            return Response::error('该分类下有文章，无法删除');
        }

        try {
            $categoryName = $category->name;
            $category->delete();
            Logger::delete(OperationLog::MODULE_CATEGORY, "分类[{$categoryName}]", $id);
            return Response::success([], '分类删除成功');
        } catch (\Exception $e) {
            Logger::log(
                OperationLog::MODULE_CATEGORY,
                OperationLog::ACTION_DELETE,
                "删除分类失败 (ID: {$id})",
                false,
                $e->getMessage()
            );
            return Response::error('分类删除失败：' . $e->getMessage());
        }
    }
}
