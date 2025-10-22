<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\Slider;
use app\model\SliderGroup;
use think\Request;

/**
 * 幻灯片管理控制器
 */
class SliderController extends BaseController
{
    /**
     * 获取幻灯片列表（分页）
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->param('per_page', 15);
        $keyword = $request->param('keyword', '');
        $groupId = $request->param('group_id');
        $status = $request->param('status');

        $query = Slider::with(['group'])
            ->order('sort', 'asc')
            ->order('id', 'desc');

        if ($keyword) {
            $query->where('title|description', 'like', "%{$keyword}%");
        }

        if ($groupId !== null && $groupId !== '') {
            $query->where('group_id', (int) $groupId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->paginate($perPage);

        return $this->success($list);
    }

    /**
     * 获取单个幻灯片详情
     */
    public function read($id)
    {
        $slider = Slider::with(['group'])->find($id);

        if (!$slider) {
            return $this->error('幻灯片不存在');
        }

        return $this->success($slider);
    }

    /**
     * 创建幻灯片
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'group_id',
            'title',
            'image',
            'link_url',
            'link_target',
            'description',
            'button_text',
            'sort',
            'status',
            'start_time',
            'end_time'
        ]);

        // 验证必填字段
        if (empty($data['group_id'])) {
            return $this->error('请选择分组');
        }

        if (empty($data['image'])) {
            return $this->error('请上传图片');
        }

        // 验证分组是否存在
        $group = SliderGroup::find($data['group_id']);
        if (!$group) {
            return $this->error('分组不存在');
        }

        // 设置默认值
        $data['link_target'] = $data['link_target'] ?? '_blank';
        $data['sort'] = $data['sort'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['view_count'] = 0;
        $data['click_count'] = 0;

        $slider = Slider::create($data);

        return $this->success($slider, '创建成功');
    }

    /**
     * 更新幻灯片
     */
    public function update(Request $request, $id)
    {
        $slider = Slider::find($id);

        if (!$slider) {
            return $this->error('幻灯片不存在');
        }

        $data = $request->only([
            'group_id',
            'title',
            'image',
            'link_url',
            'link_target',
            'description',
            'button_text',
            'sort',
            'status',
            'start_time',
            'end_time'
        ]);

        // 验证必填字段
        if (isset($data['group_id'])) {
            if (empty($data['group_id'])) {
                return $this->error('请选择分组');
            }

            // 验证分组是否存在
            $group = SliderGroup::find($data['group_id']);
            if (!$group) {
                return $this->error('分组不存在');
            }
        }

        if (isset($data['image']) && empty($data['image'])) {
            return $this->error('请上传图片');
        }

        $slider->save($data);

        return $this->success($slider, '更新成功');
    }

    /**
     * 删除幻灯片（软删除）
     */
    public function delete($id)
    {
        $slider = Slider::find($id);

        if (!$slider) {
            return $this->error('幻灯片不存在');
        }

        $slider->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 记录幻灯片点击
     */
    public function click(Request $request, $id)
    {
        $slider = Slider::find($id);

        if (!$slider) {
            return $this->error('幻灯片不存在');
        }

        // 检查是否在有效时间内
        if (!$slider->isInTimeRange()) {
            return $this->error('幻灯片不在有效时间内');
        }

        // 检查状态是否启用
        if ($slider->status != Slider::STATUS_ENABLED) {
            return $this->error('幻灯片未启用');
        }

        $slider->incrementClickCount();

        return $this->success(null, '记录成功');
    }

    /**
     * 记录幻灯片展示
     */
    public function view(Request $request, $id)
    {
        $slider = Slider::find($id);

        if (!$slider) {
            return $this->error('幻灯片不存在');
        }

        $slider->incrementViewCount();

        return $this->success(null, '记录成功');
    }

    /**
     * 获取前台展示的幻灯片（按分组代码）
     */
    public function getByGroupCode(Request $request, $code)
    {
        $group = SliderGroup::where('code', $code)
            ->where('status', SliderGroup::STATUS_ENABLED)
            ->find();

        if (!$group) {
            return $this->error('分组不存在或未启用');
        }

        // 获取启用且在有效时间内的幻灯片
        $sliders = Slider::where('group_id', $group->id)
            ->where('status', Slider::STATUS_ENABLED)
            ->where(function($query) {
                $now = date('Y-m-d H:i:s');
                $query->where(function($q) use ($now) {
                    $q->whereNull('start_time')
                      ->whereOr('start_time', '<=', $now);
                })->where(function($q) use ($now) {
                    $q->whereNull('end_time')
                      ->whereOr('end_time', '>=', $now);
                });
            })
            ->order('sort', 'asc')
            ->select();

        return $this->success([
            'group' => $group,
            'sliders' => $sliders
        ]);
    }
}
