<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Logger;
use app\model\ContentModel;
use app\model\CustomField;
use app\model\OperationLog;
use think\Request;

/**
 * 内容模型管理控制器
 */
class ContentModelController extends BaseController
{
    /**
     * 获取模型列表
     */
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status', '');

        $query = ContentModel::withSearch(['name', 'status'], [
            'name'   => $keyword,
            'status' => $status,
        ])->order('sort', 'desc')->order('id', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->append(['status_text', 'is_system_text'])
            ->select()
            ->toArray();

        return Response::paginate($list, $total, $page, $pageSize);
    }

    /**
     * 获取所有模型（不分页）
     */
    public function all()
    {
        $list = ContentModel::where('status', 1)
            ->order('sort', 'desc')
            ->select();

        return Response::success($list);
    }

    /**
     * 获取模型详情
     */
    public function read($id)
    {
        $model = ContentModel::with('customFields')->find($id);

        if (!$model) {
            return Response::notFound('模型不存在');
        }

        return Response::success($model);
    }

    /**
     * 创建模型
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证
        $validate = \think\facade\Validate::rule([
            'name'       => 'require|max:50',
            'table_name' => 'require|max:50|unique:content_models',
        ])->message([
            'name.require'            => '模型名称不能为空',
            'name.max'                => '模型名称最多50个字符',
            'table_name.require'      => '数据表名不能为空',
            'table_name.max'          => '数据表名最多50个字符',
            'table_name.unique'       => '数据表名已存在',
        ]);

        if (!$validate->check($data)) {
            return Response::error($validate->getError());
        }

        try {
            $model = ContentModel::create($data);

            // 记录日志
            Logger::create(OperationLog::MODULE_SYSTEM, '内容模型', $model->id);

            return Response::success($model, '创建成功');
        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新模型
     */
    public function update(Request $request, $id)
    {
        $model = ContentModel::find($id);

        if (!$model) {
            return Response::notFound('模型不存在');
        }

        // 系统模型不能修改某些字段
        if ($model->is_system) {
            return Response::error('系统模型不能修改');
        }

        $data = $request->put();

        // 验证
        $validate = \think\facade\Validate::rule([
            'name'       => 'require|max:50',
            'table_name' => 'require|max:50|unique:content_models,table_name,' . $id,
        ])->message([
            'name.require'       => '模型名称不能为空',
            'name.max'           => '模型名称最多50个字符',
            'table_name.require' => '数据表名不能为空',
            'table_name.max'     => '数据表名最多50个字符',
            'table_name.unique'  => '数据表名已存在',
        ]);

        if (!$validate->check($data)) {
            return Response::error($validate->getError());
        }

        try {
            $model->save($data);

            // 记录日志
            Logger::update(OperationLog::MODULE_SYSTEM, '内容模型', $id);

            return Response::success($model, '更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除模型
     */
    public function delete($id)
    {
        $model = ContentModel::find($id);

        if (!$model) {
            return Response::notFound('模型不存在');
        }

        // 系统模型不能删除
        if ($model->is_system) {
            return Response::error('系统模型不能删除');
        }

        try {
            // 删除关联的字段
            CustomField::where('model_type', 'custom')
                ->where('model_id', $id)
                ->delete();

            $model->delete();

            // 记录日志
            Logger::delete(OperationLog::MODULE_SYSTEM, '内容模型', $id);

            return Response::success([], '删除成功');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }
}
