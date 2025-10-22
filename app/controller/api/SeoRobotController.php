<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\SeoRobot;
use think\Request;

/**
 * Robots.txt配置管理控制器
 */
class SeoRobotController extends BaseController
{
    /**
     * 获取robots配置列表（分页）
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->param('per_page', 15);
        $keyword = $request->param('keyword', '');
        $isActive = $request->param('is_active');

        $query = SeoRobot::order('is_active', 'desc')->order('id', 'desc');

        if ($keyword) {
            $query->where('name|description', 'like', "%{$keyword}%");
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (int) $isActive);
        }

        $list = $query->paginate($perPage);

        return $this->success($list);
    }

    /**
     * 获取当前启用的配置
     */
    public function active()
    {
        $config = SeoRobot::getActiveConfig();

        if (!$config) {
            return $this->error('未找到启用的配置');
        }

        return $this->success($config);
    }

    /**
     * 获取robots配置详情
     */
    public function read($id)
    {
        $robot = SeoRobot::find($id);

        if (!$robot) {
            return $this->error('配置不存在');
        }

        return $this->success($robot);
    }

    /**
     * 创建robots配置
     */
    public function save(Request $request)
    {
        $data = $request->only([
            'name',
            'content',
            'is_active',
            'description'
        ]);

        // 验证必填字段
        if (empty($data['name'])) {
            return $this->error('配置名称不能为空');
        }

        if (empty($data['content'])) {
            return $this->error('配置内容不能为空');
        }

        // 验证格式
        $validation = SeoRobot::validateRobotsContent($data['content']);
        if (!$validation['valid']) {
            return $this->error('配置格式错误', $validation['errors']);
        }

        // 设置默认值
        $data['is_active'] = $data['is_active'] ?? 0;

        $robot = SeoRobot::create($data);

        // 如果设为启用，禁用其他配置
        if ($robot->is_active) {
            $robot->activate();
        }

        return $this->success($robot, '创建成功');
    }

    /**
     * 更新robots配置
     */
    public function update(Request $request, $id)
    {
        $robot = SeoRobot::find($id);

        if (!$robot) {
            return $this->error('配置不存在');
        }

        $data = $request->only([
            'name',
            'content',
            'is_active',
            'description'
        ]);

        // 验证必填字段
        if (isset($data['name']) && empty($data['name'])) {
            return $this->error('配置名称不能为空');
        }

        if (isset($data['content'])) {
            if (empty($data['content'])) {
                return $this->error('配置内容不能为空');
            }

            // 验证格式
            $validation = SeoRobot::validateRobotsContent($data['content']);
            if (!$validation['valid']) {
                return $this->error('配置格式错误', $validation['errors']);
            }
        }

        $robot->save($data);

        // 如果设为启用，禁用其他配置
        if (isset($data['is_active']) && $data['is_active']) {
            $robot->activate();
        }

        return $this->success($robot, '更新成功');
    }

    /**
     * 删除robots配置
     */
    public function delete($id)
    {
        $robot = SeoRobot::find($id);

        if (!$robot) {
            return $this->error('配置不存在');
        }

        // 不允许删除启用的配置
        if ($robot->is_active) {
            return $this->error('不能删除当前启用的配置');
        }

        $robot->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 启用配置
     */
    public function activate(Request $request, $id)
    {
        $robot = SeoRobot::find($id);

        if (!$robot) {
            return $this->error('配置不存在');
        }

        $robot->activate();

        return $this->success(null, '启用成功');
    }

    /**
     * 验证robots.txt内容
     */
    public function validateContent(Request $request)
    {
        $content = $request->param('content');

        if (empty($content)) {
            return $this->error('配置内容不能为空');
        }

        $validation = SeoRobot::validateRobotsContent($content);

        return $this->success($validation);
    }

    /**
     * 获取预设模板列表
     */
    public function templates()
    {
        $templates = SeoRobot::getTemplates();

        return $this->success($templates);
    }

    /**
     * 应用预设模板
     */
    public function applyTemplate(Request $request)
    {
        $templateKey = $request->param('template');

        if (empty($templateKey)) {
            return $this->error('请选择模板');
        }

        $templates = SeoRobot::getTemplates();

        if (!isset($templates[$templateKey])) {
            return $this->error('模板不存在');
        }

        $template = $templates[$templateKey];

        return $this->success([
            'content' => $template['content'],
            'description' => $template['description']
        ]);
    }

    /**
     * 生成robots.txt文件
     */
    public function generate(Request $request)
    {
        $config = SeoRobot::getActiveConfig();

        if (!$config) {
            return $this->error('未找到启用的配置');
        }

        // 将内容写入网站根目录的robots.txt文件
        $rootPath = app()->getRootPath() . '../public/';
        $robotsFile = $rootPath . 'robots.txt';

        try {
            file_put_contents($robotsFile, $config->content);

            return $this->success([
                'file' => $robotsFile,
                'content' => $config->content
            ], 'robots.txt文件生成成功');
        } catch (\Exception $e) {
            return $this->error('生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 查看当前的robots.txt文件
     */
    public function current()
    {
        $rootPath = app()->getRootPath() . '../public/';
        $robotsFile = $rootPath . 'robots.txt';

        if (!file_exists($robotsFile)) {
            return $this->success([
                'exists' => false,
                'content' => ''
            ]);
        }

        $content = file_get_contents($robotsFile);

        return $this->success([
            'exists' => true,
            'content' => $content,
            'file' => $robotsFile,
            'modified_time' => date('Y-m-d H:i:s', filemtime($robotsFile))
        ]);
    }
}
