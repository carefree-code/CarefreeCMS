<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\LinkGroup;
use think\Request;
use think\facade\Validate;

/**
 * 友链分组管理控制器
 */
class LinkGroupController extends BaseController
{
    /**
     * 获取分组列表
     */
    public function index(Request $request)
    {
        $page = (int) $request->param('page', 1);
        $pageSize = (int) $request->param('page_size', 10);
        $keyword = $request->param('keyword', '');
        $status = $request->param('status', '');

        $query = LinkGroup::order('sort', 'asc')
            ->order('id', 'desc');

        // 关键词搜索
        if (!empty($keyword)) {
            $query->where('name', 'like', '%' . $keyword . '%');
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
     * 获取所有分组（不分页）
     */
    public function all()
    {
        $list = LinkGroup::where('status', LinkGroup::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->select();

        return $this->success([
            'list' => $list,
        ]);
    }

    /**
     * 获取分组详情
     */
    public function read($id)
    {
        $group = LinkGroup::find($id);

        if (!$group) {
            return $this->error('分组不存在');
        }

        return $this->success($group);
    }

    /**
     * 创建分组
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'name',
            'description',
            'sort',
            'status',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:50',
        ])->message([
            'name.require' => '分组名称不能为空',
            'name.max' => '分组名称最多50个字符',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        // 设置默认值
        if (!isset($data['status'])) {
            $data['status'] = LinkGroup::STATUS_ENABLED;
        }
        if (!isset($data['sort'])) {
            $data['sort'] = 0;
        }

        $group = new LinkGroup();
        $group->save($data);

        return $this->success($group, '创建成功');
    }

    /**
     * 更新分组
     */
    public function update(Request $request, $id)
    {
        $group = LinkGroup::find($id);

        if (!$group) {
            return $this->error('分组不存在');
        }

        $data = $request->only([
            'name',
            'description',
            'sort',
            'status',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:50',
        ])->message([
            'name.require' => '分组名称不能为空',
            'name.max' => '分组名称最多50个字符',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        $group->save($data);

        return $this->success($group, '更新成功');
    }

    /**
     * 删除分组
     */
    public function delete($id)
    {
        $group = LinkGroup::find($id);

        if (!$group) {
            return $this->error('分组不存在');
        }

        // 检查是否有友链
        $linkCount = \app\model\Link::where('group_id', $id)->count();
        if ($linkCount > 0) {
            return $this->error('该分组下还有友链，无法删除');
        }

        $group->delete();

        return $this->success(null, '删除成功');
    }
}
