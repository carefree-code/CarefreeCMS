<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\Group;
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

        $query = Group::where('type', Group::TYPE_AD)
            ->order('id', 'desc');

        // 关键词搜索
        if (!empty($keyword)) {
            $query->where('name|slug', 'like', '%' . $keyword . '%');
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
        $list = Group::where('type', Group::TYPE_AD)
            ->where('status', Group::STATUS_ENABLED)
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
        $position = Group::where('type', Group::TYPE_AD)->find($id);

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
            'slug',
            'description',
            'width',
            'height',
            'status',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:50',
            'slug' => 'require|max:50|regex:^[a-z0-9_]+$',
        ])->message([
            'name.require' => '广告位名称不能为空',
            'name.max' => '广告位名称最多50个字符',
            'slug.require' => '广告位代码不能为空',
            'slug.max' => '广告位代码最多50个字符',
            'slug.regex' => '广告位代码只能包含小写字母、数字和下划线',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        // 检查代码是否重复
        $exists = Group::where('type', Group::TYPE_AD)
            ->where('slug', $data['slug'])
            ->find();
        if ($exists) {
            return $this->error('广告位代码已存在');
        }

        // 提取广告位特定配置
        $config = [
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
        ];

        // 构建分组数据
        $groupData = [
            'type' => Group::TYPE_AD,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'config' => json_encode($config),
            'status' => $data['status'] ?? Group::STATUS_ENABLED,
        ];

        $position = Group::create($groupData);

        return $this->success($position, '创建成功');
    }

    /**
     * 更新广告位
     */
    public function update(Request $request, $id)
    {
        $position = Group::where('type', Group::TYPE_AD)->find($id);

        if (!$position) {
            return $this->error('广告位不存在');
        }

        $data = $request->only([
            'name',
            'slug',
            'description',
            'width',
            'height',
            'status',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'name' => 'require|max:50',
            'slug' => 'require|max:50|regex:^[a-z0-9_]+$',
        ])->message([
            'name.require' => '广告位名称不能为空',
            'name.max' => '广告位名称最多50个字符',
            'slug.require' => '广告位代码不能为空',
            'slug.max' => '广告位代码最多50个字符',
            'slug.regex' => '广告位代码只能包含小写字母、数字和下划线',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        // 检查代码是否重复（排除自己）
        if (isset($data['slug'])) {
            $exists = Group::where('type', Group::TYPE_AD)
                ->where('slug', $data['slug'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return $this->error('广告位代码已存在');
            }
        }

        // 如果有广告位配置字段，更新config
        $configFields = ['width', 'height'];
        $hasConfigUpdate = false;
        foreach ($configFields as $field) {
            if (isset($data[$field])) {
                $hasConfigUpdate = true;
                break;
            }
        }

        if ($hasConfigUpdate) {
            // 获取现有配置
            //判断是否为json格式
            // $currentConfig = json_decode($position->config ?? '{}', true) ?: [];
            $currentConfig = $position->config;
            // 更新配置字段
            foreach ($configFields as $field) {
                if (isset($data[$field])) {
                    $currentConfig[$field] = $data[$field];
                    unset($data[$field]);
                }
            }

            $data['config'] = json_encode($currentConfig);
        }

        $position->save($data);

        return $this->success($position, '更新成功');
    }

    /**
     * 删除广告位
     */
    public function delete($id)
    {
        $position = Group::where('type', Group::TYPE_AD)->find($id);

        if (!$position) {
            return $this->error('广告位不存在');
        }

        // 检查是否有广告
        $adCount = \app\model\Ad::where('position_id', $id)->count();
        if ($adCount > 0) {
            return $this->error('该广告位下还有广告，无法删除');
        }

        $positionId = $position->id;

        // 使用Db类直接删除，确保WHERE条件精确
        $affected = \think\facade\Db::name('groups')
            ->where('id', '=', $positionId)
            ->limit(1)
            ->delete();

        if ($affected === 0) {
            return $this->error('广告位删除失败：未找到该广告位');
        }

        return $this->success(null, '删除成功');
    }
}
