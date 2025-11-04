<?php
namespace app\service\tag;

use app\model\ArticleFlag;

/**
 * 文章属性标签服务类
 * 处理文章属性标签的数据查询
 */
class ArticleFlagTagService
{
    /**
     * 获取文章属性列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - status: 状态（1-启用，0-禁用）
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 0;
        $status = $params['status'] ?? '';

        // 构建查询
        $query = ArticleFlag::query();

        // 按状态筛选
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 排序
        $query->order('sort_order', 'asc');

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $list = $query->select();

        return $list ? $list->toArray() : [];
    }

    /**
     * 获取单个文章属性
     *
     * @param int $id 属性ID
     * @return array|null
     */
    public static function getOne($id)
    {
        if (empty($id)) {
            return null;
        }

        $flag = ArticleFlag::find($id);

        return $flag ? $flag->toArray() : null;
    }

    /**
     * 根据标识值获取属性
     *
     * @param string $flagValue 标识值
     * @return array|null
     */
    public static function getByValue($flagValue)
    {
        if (empty($flagValue)) {
            return null;
        }

        $flag = ArticleFlag::where('flag_value', $flagValue)
            ->where('status', 1)
            ->find();

        return $flag ? $flag->toArray() : null;
    }
}
