<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\Ad;
use app\model\AdClick;
use think\Request;
use think\facade\Validate;

/**
 * 广告管理控制器
 */
class AdController extends BaseController
{
    /**
     * 获取广告列表
     */
    public function index(Request $request)
    {
        $page = (int) $request->param('page', 1);
        $pageSize = (int) $request->param('page_size', 10);
        $keyword = $request->param('keyword', '');
        $positionId = $request->param('position_id', '');
        $type = $request->param('type', '');
        $status = $request->param('status', '');

        $query = Ad::with(['position'])
            ->order('sort', 'asc')
            ->order('id', 'desc');

        // 关键词搜索
        if (!empty($keyword)) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        // 广告位筛选
        if ($positionId !== '') {
            $query->where('position_id', (int) $positionId);
        }

        // 类型筛选
        if (!empty($type)) {
            $query->where('type', $type);
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
     * 获取广告详情
     */
    public function read($id)
    {
        $ad = Ad::with(['position'])->find($id);

        if (!$ad) {
            return $this->error('广告不存在');
        }

        return $this->success($ad);
    }

    /**
     * 创建广告
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'position_id',
            'name',
            'type',
            'content',
            'link_url',
            'images',
            'start_time',
            'end_time',
            'status',
            'sort',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'position_id' => 'require|number',
            'name' => 'require|max:100',
            'type' => 'require|in:image,code,carousel',
        ])->message([
            'position_id.require' => '请选择广告位',
            'position_id.number' => '广告位ID必须是数字',
            'name.require' => '广告名称不能为空',
            'name.max' => '广告名称最多100个字符',
            'type.require' => '请选择广告类型',
            'type.in' => '广告类型不正确',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        // 设置默认值
        if (!isset($data['status'])) {
            $data['status'] = Ad::STATUS_ENABLED;
        }
        if (!isset($data['sort'])) {
            $data['sort'] = 0;
        }

        // 处理轮播图片 - 确保是 JSON 字符串
        if (isset($data['images'])) {
            if (is_string($data['images'])) {
                // 如果是字符串，尝试解码后再编码（验证格式）
                $decoded = json_decode($data['images'], true);
                $data['images'] = $decoded !== null ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : '[]';
            } elseif (is_array($data['images'])) {
                // 如果是数组，直接编码为 JSON
                $data['images'] = json_encode($data['images'], JSON_UNESCAPED_UNICODE);
            } else {
                // 其他情况设为空数组
                $data['images'] = '[]';
            }
        }

        $ad = new Ad();
        $ad->save($data);

        return $this->success($ad, '创建成功');
    }

    /**
     * 更新广告
     */
    public function update(Request $request, $id)
    {
        $ad = Ad::find($id);

        if (!$ad) {
            return $this->error('广告不存在');
        }

        $data = $request->only([
            'position_id',
            'name',
            'type',
            'content',
            'link_url',
            'images',
            'start_time',
            'end_time',
            'status',
            'sort',
        ]);

        // 数据验证
        $validate = Validate::rule([
            'position_id' => 'require|number',
            'name' => 'require|max:100',
            'type' => 'require|in:image,code,carousel',
        ])->message([
            'position_id.require' => '请选择广告位',
            'position_id.number' => '广告位ID必须是数字',
            'name.require' => '广告名称不能为空',
            'name.max' => '广告名称最多100个字符',
            'type.require' => '请选择广告类型',
            'type.in' => '广告类型不正确',
        ]);

        if (!$validate->check($data)) {
            return $this->error($validate->getError());
        }

        // 处理轮播图片 - 确保是 JSON 字符串
        if (isset($data['images'])) {
            if (is_string($data['images'])) {
                // 如果是字符串，尝试解码后再编码（验证格式）
                $decoded = json_decode($data['images'], true);
                $data['images'] = $decoded !== null ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : '[]';
            } elseif (is_array($data['images'])) {
                // 如果是数组，直接编码为 JSON
                $data['images'] = json_encode($data['images'], JSON_UNESCAPED_UNICODE);
            } else {
                // 其他情况设为空数组
                $data['images'] = '[]';
            }
        }

        $ad->save($data);

        return $this->success($ad, '更新成功');
    }

    /**
     * 删除广告
     */
    public function delete($id)
    {
        $ad = Ad::find($id);

        if (!$ad) {
            return $this->error('广告不存在');
        }

        // 软删除
        $ad->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 获取广告统计
     */
    public function statistics(Request $request, $id)
    {
        $ad = Ad::find($id);

        if (!$ad) {
            return $this->error('广告不存在');
        }

        $startDate = $request->param('start_date');
        $endDate = $request->param('end_date');

        $stats = AdClick::getStatistics($id, $startDate, $endDate);

        $data = [
            'view_count' => $ad->view_count,
            'click_count' => $ad->click_count,
            'click_rate' => $ad->click_rate,
            'period_clicks' => $stats['total'],
            'period_unique_ip' => $stats['unique_ip'],
        ];

        return $this->success($data);
    }

    /**
     * 记录广告点击
     */
    public function click(Request $request, $id)
    {
        $ad = Ad::find($id);

        if (!$ad) {
            return $this->error('广告不存在');
        }

        // 增加点击次数
        $ad->incrementClickCount();

        // 记录点击详情
        $ip = $request->ip();
        $userAgent = $request->header('user-agent');
        $referer = $request->header('referer');

        AdClick::record($id, $ip, $userAgent, $referer);

        return $this->success(null, '记录成功');
    }
}
