<?php
namespace app\service\tag;

use app\model\Article;

/**
 * 排行榜标签服务类
 * 处理排行榜标签的数据查询
 */
class RankTagService
{
    /**
     * 获取排行榜数据
     *
     * @param array $params 查询参数
     *   - type: 排行类型 (view-浏览量, comment-评论数, like-点赞数, collect-收藏数)
     *   - limit: 数量限制
     *   - catid: 分类ID
     *   - days: 最近N天的数据
     * @return array
     */
    public static function getRank($params = [])
    {
        $type = $params['type'] ?? 'view';
        $limit = $params['limit'] ?? 10;
        $catid = $params['catid'] ?? 0;
        $days = $params['days'] ?? 0;

        // 构建查询
        $query = Article::where('status', 1);

        // 按分类筛选
        if ($catid > 0) {
            $query->where('category_id', $catid);
        }

        // 最近N天的数据
        if ($days > 0) {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $query->where('create_time', '>=', $startDate);
        }

        // 根据类型排序
        switch ($type) {
            case 'view':
                $query->order('view_count', 'desc');
                break;
            case 'comment':
                $query->order('comment_count', 'desc');
                break;
            case 'like':
                $query->order('like_count', 'desc');
                break;
            case 'collect':
                $query->order('collect_count', 'desc');
                break;
            default:
                $query->order('view_count', 'desc');
        }

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $list = $query->select();

        return $list ? $list->toArray() : [];
    }
}
