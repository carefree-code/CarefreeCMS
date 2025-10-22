<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Logger;
use app\model\CustomField;
use app\model\CustomFieldValue;
use app\model\OperationLog;
use think\Request;

/**
 * 自定义字段管理控制器
 */
class CustomFieldController extends BaseController
{
    /**
     * 获取字段列表
     */
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $modelType = $request->get('model_type', '');
        $modelId = $request->get('model_id', '');
        $groupName = $request->get('group_name', '');

        $query = CustomField::withSearch(['name', 'model_type', 'model_id', 'group_name'], [
            'name'       => $keyword,
            'model_type' => $modelType,
            'model_id'   => $modelId,
            'group_name' => $groupName,
        ])->with('contentModel')->order('sort', 'asc')->order('id', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return Response::paginate($list, $total, $page, $pageSize);
    }

    /**
     * 根据模型获取字段（不分页，用于表单）
     */
    public function getByModel(Request $request)
    {
        $modelType = $request->get('model_type');
        $modelId = $request->get('model_id', 0);

        if (!$modelType) {
            return Response::error('请指定模型类型');
        }

        $query = CustomField::where('model_type', $modelType)
            ->where('status', 1)
            ->order('sort', 'asc');

        if ($modelType === 'custom' && $modelId) {
            $query->where('model_id', $modelId);
        }

        $fields = $query->select();

        // 按字段组分组
        $grouped = [];
        foreach ($fields as $field) {
            $groupName = $field->group_name ?: '默认';
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [];
            }
            $grouped[$groupName][] = $field;
        }

        return Response::success([
            'fields' => $fields,
            'grouped' => $grouped,
        ]);
    }

    /**
     * 获取字段详情
     */
    public function read($id)
    {
        $field = CustomField::with('contentModel')->find($id);

        if (!$field) {
            return Response::notFound('字段不存在');
        }

        return Response::success($field);
    }

    /**
     * 创建字段
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证
        $validate = \think\facade\Validate::rule([
            'name'       => 'require|max:50',
            'field_key'  => 'require|max:50|regex:^[a-zA-Z_][a-zA-Z0-9_]*$',
            'field_type' => 'require|in:text,number,date,datetime,select,radio,checkbox,textarea,richtext,image,file',
            'model_type' => 'require|in:article,category,tag,page,custom',
        ])->message([
            'name.require'       => '字段名称不能为空',
            'name.max'           => '字段名称最多50个字符',
            'field_key.require'  => '字段键名不能为空',
            'field_key.max'      => '字段键名最多50个字符',
            'field_key.regex'    => '字段键名只能包含字母、数字和下划线，且不能以数字开头',
            'field_type.require' => '字段类型不能为空',
            'field_type.in'      => '字段类型不正确',
            'model_type.require' => '模型类型不能为空',
            'model_type.in'      => '模型类型不正确',
        ]);

        if (!$validate->check($data)) {
            return Response::error($validate->getError());
        }

        // 检查字段键名是否重复
        $modelId = $data['model_id'] ?? null;
        $exists = CustomField::where('field_key', $data['field_key'])
            ->where('model_type', $data['model_type'])
            ->where('model_id', $modelId)
            ->find();

        if ($exists) {
            return Response::error('该模型下已存在相同的字段键名');
        }

        try {
            $field = CustomField::create($data);

            // 记录日志
            Logger::create(OperationLog::MODULE_SYSTEM, '自定义字段', $field->id);

            return Response::success($field, '创建成功');
        } catch (\Exception $e) {
            return Response::error('创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新字段
     */
    public function update(Request $request, $id)
    {
        $field = CustomField::find($id);

        if (!$field) {
            return Response::notFound('字段不存在');
        }

        $data = $request->put();

        // 验证
        $validate = \think\facade\Validate::rule([
            'name'       => 'require|max:50',
            'field_key'  => 'require|max:50|regex:^[a-zA-Z_][a-zA-Z0-9_]*$',
            'field_type' => 'require|in:text,number,date,datetime,select,radio,checkbox,textarea,richtext,image,file',
            'model_type' => 'require|in:article,category,tag,page,custom',
        ])->message([
            'name.require'       => '字段名称不能为空',
            'name.max'           => '字段名称最多50个字符',
            'field_key.require'  => '字段键名不能为空',
            'field_key.max'      => '字段键名最多50个字符',
            'field_key.regex'    => '字段键名只能包含字母、数字和下划线，且不能以数字开头',
            'field_type.require' => '字段类型不能为空',
            'field_type.in'      => '字段类型不正确',
            'model_type.require' => '模型类型不能为空',
            'model_type.in'      => '模型类型不正确',
        ]);

        if (!$validate->check($data)) {
            return Response::error($validate->getError());
        }

        // 检查字段键名是否重复
        $modelId = $data['model_id'] ?? null;
        $exists = CustomField::where('field_key', $data['field_key'])
            ->where('model_type', $data['model_type'])
            ->where('model_id', $modelId)
            ->where('id', '<>', $id)
            ->find();

        if ($exists) {
            return Response::error('该模型下已存在相同的字段键名');
        }

        try {
            $field->save($data);

            // 记录日志
            Logger::update(OperationLog::MODULE_SYSTEM, '自定义字段', $id);

            return Response::success($field, '更新成功');
        } catch (\Exception $e) {
            return Response::error('更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除字段
     */
    public function delete($id)
    {
        $field = CustomField::find($id);

        if (!$field) {
            return Response::notFound('字段不存在');
        }

        try {
            // 删除关联的字段值
            CustomFieldValue::where('field_id', $id)->delete();

            $field->delete();

            // 记录日志
            Logger::delete(OperationLog::MODULE_SYSTEM, '自定义字段', $id);

            return Response::success([], '删除成功');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取字段类型列表
     */
    public function getFieldTypes()
    {
        return Response::success(CustomField::getFieldTypes());
    }

    /**
     * 获取模型类型列表
     */
    public function getModelTypes()
    {
        return Response::success(CustomField::getModelTypes());
    }

    /**
     * 获取实体的字段值
     */
    public function getEntityValues(Request $request)
    {
        $entityType = $request->get('entity_type');
        $entityId = $request->get('entity_id');

        if (!$entityType || !$entityId) {
            return Response::error('参数错误');
        }

        $values = CustomFieldValue::getEntityValues($entityType, $entityId);

        return Response::success($values);
    }

    /**
     * 保存实体的字段值
     */
    public function saveEntityValues(Request $request)
    {
        $entityType = $request->post('entity_type');
        $entityId = $request->post('entity_id');
        $fieldValues = $request->post('field_values', []);

        if (!$entityType || !$entityId) {
            return Response::error('参数错误');
        }

        try {
            CustomFieldValue::saveEntityValues($entityType, $entityId, $fieldValues);

            return Response::success([], '保存成功');
        } catch (\Exception $e) {
            return Response::error('保存失败：' . $e->getMessage());
        }
    }
}
