<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\AdPosition;
use think\Request;
use think\facade\Validate;

/**
 * 广告位管理控制器
 */
class AdPositionController extends BaseController
{
    /**
     * 获取广告位列表
     */
    public function index(Request $request)
    {
        $page = (int) $request->param('page', 1);
        $pageSize = (int) $request->param('page_size', 10);
        $keyword = $request->param('keyword', '');
        $status = $request->param('status', '');

        $query = AdPosition::order('id', 'desc');

        // 关键词搜索
        if (!empty($keyword)) {
            $query->where('name|code', 'like', '%' . $keyword . '%');
        }

        // 状态筛选
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * 获取所有广告位（不分页）
     */
    public function all()
    {
        $list = AdPosition::where('status', AdPosition::STATUS_ENABLED)
            ->select();

        return $this->success([
            'list' => $list,
        ]);
    }

    /**
     * 获取广告位详情
     */
    public function read($id)
    {
        $position = AdPosition::find($id);

        if (!$position) {
            return $this->error('广告位不存在');
        }

        return $this->success($position);
    }

    /**
     * 创建广告位
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'name',
            'code',
            'description',
            'width',
            'height',
            'status',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:50',
            'code' => 'require|max:50|regex:^[a-z0-9_]+$|unique:ad_positions',
        ])->message([
            'name.require' => '广告位名称不能为空',
            'name.max' => '广告位名称最多50个字符',
            'code.require' => '广告位代码不能为空',
            'code.max' => '广告位代码最多50个字符',
            'code.regex' => '广告位代码只能包含小写字母、数字和下划线',
            'code.unique' => '广告位代码已存在',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        // 设置默认值
        if (!isset($data['status'])) {
            $data['status'] = AdPosition::STATUS_ENABLED;
        }

        $position = new AdPosition();
        $position->save($data);

        return $this->success($position, '创建成功');
    }

    /**
     * 更新广告位
     */
    public function update(Request $request, $id)
    {
        $position = AdPosition::find($id);

        if (!$position) {
            return $this->error('广告位不存在');
        }

        $data = $request->only([
            'name',
            'code',
            'description',
            'width',
            'height',
            'status',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:50',
            'code' => 'require|max:50|regex:^[a-z0-9_]+$|unique:ad_positions,code,' . $id,
        ])->message([
            'name.require' => '广告位名称不能为空',
            'name.max' => '广告位名称最多50个字符',
            'code.require' => '广告位代码不能为空',
            'code.max' => '广告位代码最多50个字符',
            'code.regex' => '广告位代码只能包含小写字母、数字和下划线',
            'code.unique' => '广告位代码已存在',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        $position->save($data);

        return $this->success($position, '更新成功');
    }

    /**
     * 删除广告位
     */
    public function delete($id)
    {
        $position = AdPosition::find($id);

        if (!$position) {
            return $this->error('广告位不存在');
        }

        // 检查是否有广告
        $adCount = \app\model\Ad::where('position_id', $id)->count();
        if ($adCount > 0) {
            return $this->error('该广告位下还有广告，无法删除');
        }

        $position->delete();

        return $this->success(null, '删除成功');
    }
}
