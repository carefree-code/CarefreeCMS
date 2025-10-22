<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\SliderGroup;
use think\Request;

/**
 * 幻灯片组管理控制器
 */
class SliderGroupController extends BaseController
{
    /**
     * 获取幻灯片组列表（分页）
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->param('per_page', 15);
        $keyword = $request->param('keyword', '');

        $query = SliderGroup::order('id', 'desc');

        if ($keyword) {
            $query->where('name|code|description', 'like', "%{$keyword}%");
        }

        $list = $query->paginate($perPage);

        return $this->success($list);
    }

    /**
     * 获取所有幻灯片组（不分页）
     */
    public function all(Request $request)
    {
        $status = $request->param('status');

        $query = SliderGroup::order('id', 'desc');

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $list = $query->select();

        return $this->success($list);
    }

    /**
     * 获取单个幻灯片组详情
     */
    public function read($id)
    {
        $group = SliderGroup::with(['sliders'])->find($id);

        if (!$group) {
            return $this->error('幻灯片组不存在');
        }

        return $this->success($group);
    }

    /**
     * 创建幻灯片组
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'name',
            'code',
            'description',
            'width',
            'height',
            'auto_play',
            'play_interval',
            'animation',
            'status'
        ]);

        // 验证必填字段
        if (empty($data['name'])) {
            return $this->error('分组名称不能为空');
        }

        if (empty($data['code'])) {
            return $this->error('分组代码不能为空');
        }

        // 检查代码是否重复
        $exists = SliderGroup::where('code', $data['code'])->find();
        if ($exists) {
            return $this->error('分组代码已存在');
        }

        // 设置默认值
        $data['auto_play'] = $data['auto_play'] ?? 1;
        $data['play_interval'] = $data['play_interval'] ?? 3000;
        $data['animation'] = $data['animation'] ?? 'slide';
        $data['status'] = $data['status'] ?? 1;

        $group = SliderGroup::create($data);

        return $this->success($group, '创建成功');
    }

    /**
     * 更新幻灯片组
     */
    public function update(Request $request, $id)
    {
        $group = SliderGroup::find($id);

        if (!$group) {
            return $this->error('幻灯片组不存在');
        }

        $data = $request->only([
            'name',
            'code',
            'description',
            'width',
            'height',
            'auto_play',
            'play_interval',
            'animation',
            'status'
        ]);

        // 验证必填字段
        if (isset($data['name']) && empty($data['name'])) {
            return $this->error('分组名称不能为空');
        }

        if (isset($data['code'])) {
            if (empty($data['code'])) {
                return $this->error('分组代码不能为空');
            }

            // 检查代码是否重复（排除自己）
            $exists = SliderGroup::where('code', $data['code'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('分组代码已存在');
            }
        }

        $group->save($data);

        return $this->success($group, '更新成功');
    }

    /**
     * 删除幻灯片组
     */
    public function delete($id)
    {
        $group = SliderGroup::find($id);

        if (!$group) {
            return $this->error('幻灯片组不存在');
        }

        // 检查是否有关联的幻灯片
        $sliderCount = $group->sliders()->count();
        if ($sliderCount > 0) {
            return $this->error('该分组下还有幻灯片，无法删除');
        }

        $group->delete();

        return $this->success(null, '删除成功');
    }
}
