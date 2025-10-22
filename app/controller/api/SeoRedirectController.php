<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\SeoRedirect;
use think\Request;

/**
 * URL重定向管理控制器
 */
class SeoRedirectController extends BaseController
{
    /**
     * 获取重定向规则列表（分页）
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->param('per_page', 15);
        $keyword = $request->param('keyword', '');
        $isEnabled = $request->param('is_enabled');
        $redirectType = $request->param('redirect_type');
        $matchType = $request->param('match_type');

        $query = SeoRedirect::order('hit_count', 'desc')->order('id', 'desc');

        if ($keyword) {
            $query->where('from_url|to_url|description', 'like', "%{$keyword}%");
        }

        if ($isEnabled !== null && $isEnabled !== '') {
            $query->where('is_enabled', (int) $isEnabled);
        }

        if ($redirectType) {
            $query->where('redirect_type', (int) $redirectType);
        }

        if ($matchType) {
            $query->where('match_type', $matchType);
        }

        $list = $query->paginate($perPage);

        return $this->success($list);
    }

    /**
     * 获取单个重定向规则详情
     */
    public function read($id)
    {
        $redirect = SeoRedirect::find($id);

        if (!$redirect) {
            return $this->error('重定向规则不存在');
        }

        return $this->success($redirect);
    }

    /**
     * 创建重定向规则
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'from_url',
            'to_url',
            'redirect_type',
            'match_type',
            'is_enabled',
            'description'
        ]);

        // 验证必填字段
        if (empty($data['from_url'])) {
            return $this->error('源URL不能为空');
        }

        if (empty($data['to_url'])) {
            return $this->error('目标URL不能为空');
        }

        // 设置默认值
        $data['redirect_type'] = $data['redirect_type'] ?? 301;
        $data['match_type'] = $data['match_type'] ?? 'exact';
        $data['is_enabled'] = $data['is_enabled'] ?? 1;
        $data['hit_count'] = 0;

        // 验证正则表达式
        if ($data['match_type'] === 'regex') {
            if (@preg_match($data['from_url'], '') === false) {
                return $this->error('正则表达式格式错误');
            }
        }

        $redirect = SeoRedirect::create($data);

        return $this->success($redirect, '创建成功');
    }

    /**
     * 更新重定向规则
     */
    public function update(Request $request, $id)
    {
        $redirect = SeoRedirect::find($id);

        if (!$redirect) {
            return $this->error('重定向规则不存在');
        }

        $data = $request->only([
            'from_url',
            'to_url',
            'redirect_type',
            'match_type',
            'is_enabled',
            'description'
        ]);

        // 验证必填字段
        if (isset($data['from_url']) && empty($data['from_url'])) {
            return $this->error('源URL不能为空');
        }

        if (isset($data['to_url']) && empty($data['to_url'])) {
            return $this->error('目标URL不能为空');
        }

        // 验证正则表达式
        if (isset($data['match_type']) && $data['match_type'] === 'regex') {
            $pattern = $data['from_url'] ?? $redirect->from_url;
            if (@preg_match($pattern, '') === false) {
                return $this->error('正则表达式格式错误');
            }
        }

        $redirect->save($data);

        return $this->success($redirect, '更新成功');
    }

    /**
     * 删除重定向规则
     */
    public function delete($id)
    {
        $redirect = SeoRedirect::find($id);

        if (!$redirect) {
            return $this->error('重定向规则不存在');
        }

        $redirect->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 批量删除重定向规则
     */
    public function batchDelete(Request $request)
    {
        $ids = $request->param('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的规则');
        }

        SeoRedirect::destroy($ids);

        return $this->success(null, '批量删除成功');
    }

    /**
     * 批量启用/禁用
     */
    public function batchToggle(Request $request)
    {
        $ids = $request->param('ids', []);
        $isEnabled = $request->param('is_enabled');

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要操作的规则');
        }

        if ($isEnabled === null) {
            return $this->error('请指定启用状态');
        }

        SeoRedirect::where('id', 'in', $ids)->update(['is_enabled' => $isEnabled]);

        return $this->success(null, '批量操作成功');
    }

    /**
     * 测试重定向规则
     */
    public function test(Request $request)
    {
        $url = $request->param('url');

        if (empty($url)) {
            return $this->error('请输入要测试的URL');
        }

        $rule = SeoRedirect::findMatchingRule($url);

        if ($rule) {
            $targetUrl = $rule->applyRedirect($url);

            return $this->success([
                'matched' => true,
                'rule' => $rule,
                'target_url' => $targetUrl
            ]);
        }

        return $this->success([
            'matched' => false,
            'message' => '未找到匹配的重定向规则'
        ]);
    }

    /**
     * 获取重定向统计
     */
    public function statistics()
    {
        $total = SeoRedirect::count();
        $enabled = SeoRedirect::where('is_enabled', 1)->count();
        $disabled = SeoRedirect::where('is_enabled', 0)->count();
        $totalHits = SeoRedirect::sum('hit_count');
        $topRules = SeoRedirect::where('is_enabled', 1)
            ->order('hit_count', 'desc')
            ->limit(10)
            ->select();

        return $this->success([
            'total' => $total,
            'enabled' => $enabled,
            'disabled' => $disabled,
            'total_hits' => $totalHits,
            'top_rules' => $topRules
        ]);
    }

    /**
     * 导入重定向规则（CSV格式）
     */
    public function import(Request $request)
    {
        $content = $request->param('content');

        if (empty($content)) {
            return $this->error('请提供导入内容');
        }

        $lines = explode("\n", trim($content));
        $imported = 0;
        $errors = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // CSV格式：from_url,to_url,redirect_type,match_type,description
            $parts = str_getcsv($line);

            if (count($parts) < 2) {
                $errors[] = "行 " . ($index + 1) . ": 格式错误";
                continue;
            }

            try {
                SeoRedirect::create([
                    'from_url' => $parts[0],
                    'to_url' => $parts[1],
                    'redirect_type' => $parts[2] ?? 301,
                    'match_type' => $parts[3] ?? 'exact',
                    'description' => $parts[4] ?? '',
                    'is_enabled' => 1,
                    'hit_count' => 0
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "行 " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return $this->success([
            'imported' => $imported,
            'errors' => $errors
        ], "成功导入 {$imported} 条规则");
    }

    /**
     * 导出重定向规则（CSV格式）
     */
    public function export(Request $request)
    {
        $redirects = SeoRedirect::select();

        $csv = "源URL,目标URL,重定向类型,匹配类型,描述,命中次数,状态\n";

        foreach ($redirects as $redirect) {
            $csv .= sprintf(
                '"%s","%s",%d,%s,"%s",%d,%s' . "\n",
                $redirect->from_url,
                $redirect->to_url,
                $redirect->redirect_type,
                $redirect->match_type,
                $redirect->description,
                $redirect->hit_count,
                $redirect->is_enabled ? '启用' : '禁用'
            );
        }

        return $this->success([
            'content' => $csv,
            'filename' => 'redirects_' . date('YmdHis') . '.csv'
        ]);
    }

    /**
     * 获取配置选项
     */
    public function options()
    {
        return $this->success([
            'redirect_types' => SeoRedirect::getRedirectTypes(),
            'match_types' => SeoRedirect::getMatchTypes()
        ]);
    }
}
