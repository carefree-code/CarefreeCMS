<?php
namespace app\service\tag;

use app\model\Tag;
use think\facade\Db;

/**
 * 标签标签服务类
 * 处理标签列表标签的数据查询
 */
class TagTagService
{
    /**
     * 获取标签列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - order: 排序方式 (sort asc, article_count desc)
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 0;
        $order = $params['order'] ?? 'sort asc';

        $query = Tag::where('status', 1);

        // 解析排序
        $orderArr = explode(' ', $order);
        $orderField = $orderArr[0] ?? 'sort';
        $orderType = $orderArr[1] ?? 'asc';

        // 如果按文章数量排序，需要关联统计
        if ($orderField === 'article_count') {
            $query->withCount('articles');
            $query->order('articles_count', $orderType);
        } else {
            $query->order($orderField, $orderType);
        }

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->select()->toArray();
    }

    /**
     * 获取单个标签
     *
     * @param int $id 标签ID
     * @return array|null
     */
    public static function getOne($id)
    {
        return Tag::where('id', $id)
            ->where('status', 1)
            ->find()
            ?->toArray();
    }

    /**
     * 获取热门标签
     *
     * @param int $limit 数量限制
     * @return array
     */
    public static function getHot($limit = 10)
    {
        return self::getList([
            'limit' => $limit,
            'order' => 'article_count desc'
        ]);
    }
}
