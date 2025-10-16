<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Page as PageModel;
use think\Request;

/**
 * 单页管理控制器
 */
class Page extends BaseController
{
    /**
     * 单页列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 20);
        $title = $request->get('title', '');
        $status = $request->get('status', '');

        // 构建查询
        $query = PageModel::order(['sort' => 'desc', 'create_time' => 'desc']);

        // 搜索条件
        if (!empty($title)) {
            $query->where('title', 'like', '%' . $title . '%');
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 先获取总数
        $total = $query->count();

        // 分页查询
        $list = $query->page($page, $pageSize)->select();

        return Response::paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 单页详情
     */
    public function read($id)
    {
        $page = PageModel::find($id);

        if (!$page) {
            return Response::notFound('单页不存在');
        }

        $pageData = $page->toArray();

        // 生成完整的封面图片URL
        if (!empty($pageData['cover_image'])) {
            if (!str_starts_with($pageData['cover_image'], 'http')) {
                $siteUrl = \app\model\Config::getConfig('site_url', '');
                if (!empty($siteUrl)) {
                    $pageData['cover_image'] = rtrim($siteUrl, '/') . '/' . $pageData['cover_image'];
                } else {
                    $pageData['cover_image'] = request()->domain() . '/html/' . $pageData['cover_image'];
                }
            }
        }

        return Response::success($pageData);
    }

    /**
     * 创建单页
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['title']) || empty($data['slug']) || empty($data['content'])) {
            return Response::error('标题、URL别名和内容不能为空');
        }

        // 检查slug是否已存在
        $exists = PageModel::where('slug', $data['slug'])->find();
        if ($exists) {
            return Response::error('URL别名已存在');
        }

        // 自动提取SEO信息
        $this->autoExtractSeoInfo($data);

        try {
            $page = PageModel::create($data);
            return Response::success(['id' => $page->id], '单页创建成功');
        } catch (\Exception $e) {
            return Response::error('单页创建失败：' . $e->getMessage());
        }
    }

    /**
     * 更新单页
     */
    public function update(Request $request, $id)
    {
        $page = PageModel::find($id);
        if (!$page) {
            return Response::notFound('单页不存在');
        }

        $data = $request->post();

        // 检查slug是否与其他记录冲突
        if (isset($data['slug']) && $data['slug'] !== $page->slug) {
            $exists = PageModel::where('slug', $data['slug'])->where('id', '<>', $id)->find();
            if ($exists) {
                return Response::error('URL别名已存在');
            }
        }

        // 自动提取SEO信息
        $this->autoExtractSeoInfo($data);

        try {
            $page->save($data);
            return Response::success([], '单页更新成功');
        } catch (\Exception $e) {
            return Response::error('单页更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除单页
     */
    public function delete($id)
    {
        $page = PageModel::find($id);
        if (!$page) {
            return Response::notFound('单页不存在');
        }

        try {
            $page->delete();
            return Response::success([], '单页删除成功');
        } catch (\Exception $e) {
            return Response::error('单页删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取所有已发布的单页（用于下拉选择）
     */
    public function all()
    {
        $pages = PageModel::where('status', PageModel::STATUS_PUBLISHED)
            ->order('sort', 'desc')
            ->field('id,title,slug')
            ->select();

        return Response::success($pages->toArray());
    }

    /**
     * 自动提取SEO信息
     */
    private function autoExtractSeoInfo(&$data)
    {
        // 如果没有设置SEO标题，使用页面标题
        if (empty($data['seo_title']) && !empty($data['title'])) {
            $data['seo_title'] = $data['title'];
        }

        // 如果没有设置SEO关键词，从标题中提取
        if (empty($data['seo_keywords']) && !empty($data['title'])) {
            // 从标题中提取关键词（去除HTML实体）
            $title = html_entity_decode($data['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = strip_tags($title);
            $title = preg_replace('/\s+/u', ' ', $title);
            $title = trim($title);
            $data['seo_keywords'] = $title;
        }

        // 如果没有设置SEO描述，从内容中提取
        if (empty($data['seo_description']) && !empty($data['content'])) {
            // 去除HTML标签
            $plainText = strip_tags($data['content']);
            // 解码HTML实体（如&nbsp; &amp; &lt; &gt; &quot;等）
            $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // 去除多余空白（包括不可见字符）
            $plainText = preg_replace('/\s+/u', ' ', $plainText);
            $plainText = trim($plainText);
            // 去除零宽字符和其他特殊不可见字符
            $plainText = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $plainText);

            // 截取前200个字符作为描述
            $data['seo_description'] = mb_substr($plainText, 0, 200, 'UTF-8');
        }
    }
}
