<?php
namespace app\service\tag;

use app\model\Topic;

/**
 * 专题标签服务类
 * 处理专题列表标签的数据查询
 */
class TopicTagService
{
    /**
     * 获取专题列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - status: 状态（1-启用，0-禁用）
     *   - orderby: 排序方式（sort_order, view_count, article_count, create_time）
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 10;
        $status = $params['status'] ?? '';
        $orderby = $params['orderby'] ?? 'sort_order';

        // 构建查询
        $query = Topic::query();

        // 按状态筛选
        if ($status !== '') {
            $query->where('status', $status);
        }

        // 排序
        $orderMapping = [
            'sort_order' => 'sort_order asc',
            'sort' => 'sort_order asc',
            'view_count' => 'view_count desc',
            'article_count' => 'article_count desc',
            'create_time' => 'create_time desc',
        ];

        $orderBy = $orderMapping[$orderby] ?? 'sort_order asc';
        $query->order($orderBy);

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $list = $query->select();

        return $list ? $list->toArray() : [];
    }

    /**
     * 获取单个专题信息
     *
     * @param int $topicid 专题ID
     * @return array|null
     */
    public static function getOne($topicid)
    {
        if (empty($topicid)) {
            return null;
        }

        $topic = Topic::find($topicid);

        return $topic ? $topic->toArray() : null;
    }
}
