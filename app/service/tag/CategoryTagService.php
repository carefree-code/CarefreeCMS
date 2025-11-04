<?php
namespace app\service\tag;

use app\model\Category;

/**
 * 分类标签服务类
 * 处理分类列表标签的数据查询
 */
class CategoryTagService
{
    /**
     * 获取分类列表
     *
     * @param array $params 查询参数
     *   - parent: 父分类ID，0表示顶级分类
     *   - limit: 数量限制
     * @return array
     */
    public static function getList($params = [])
    {
        $parent = $params['parent'] ?? 0;
        $limit = $params['limit'] ?? 0;

        $query = Category::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc');

        // 按父分类筛选
        if ($parent >= 0) {
            $query->where('parent_id', $parent);
        }

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $categories = $query->select()->toArray();

        // 添加文章数量统计
        foreach ($categories as &$category) {
            $category['article_count'] = \app\model\Article::where('category_id', $category['id'])
                ->where('status', 1)
                ->count();
        }

        return $categories;
    }

    /**
     * 获取单个分类
     *
     * @param int $id 分类ID
     * @return array|null
     */
    public static function getOne($id)
    {
        return Category::where('id', $id)
            ->where('status', 1)
            ->find()
            ?->toArray();
    }

    /**
     * 获取分类树
     *
     * @param int $parent 父分类ID
     * @return array
     */
    public static function getTree($parent = 0)
    {
        $categories = self::getList(['parent' => $parent]);

        foreach ($categories as &$category) {
            $category['children'] = self::getTree($category['id']);
        }

        return $categories;
    }
}
