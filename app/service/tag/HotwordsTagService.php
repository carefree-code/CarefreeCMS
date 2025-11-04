<?php
namespace app\service\tag;

use app\model\Tag;
use think\facade\Db;

/**
 * 热门关键词标签服务类
 * 处理热门关键词的数据查询
 */
class HotwordsTagService
{
    /**
     * 获取热门关键词列表
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - days: 最近N天的数据
     *   - orderby: 排序方式 (count-使用次数, random-随机)
     * @return array
     */
    public static function getList($params = [])
    {
        $limit = $params['limit'] ?? 20;
        $days = $params['days'] ?? 30;
        $orderby = $params['orderby'] ?? 'count';

        // 基于标签使用频率
        $query = Tag::alias('t')
            ->field('t.id, t.name as keyword, COUNT(at.article_id) as count, t.click_count')
            ->leftJoin('article_tag at', 't.id = at.tag_id')
            ->where('t.status', 1)
            ->group('t.id');

        // 如果指定天数，筛选最近的文章
        if ($days > 0) {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $query->whereExists(function($query) use ($startDate) {
                $query->table('articles a')
                    ->whereRaw('a.id = at.article_id')
                    ->where('a.create_time', '>=', $startDate);
            });
        }

        // 排序
        if ($orderby === 'random') {
            $query->orderRaw('RAND()');
        } else {
            $query->order('count', 'desc')
                  ->order('click_count', 'desc');
        }

        // 数量限制
        if ($limit > 0) {
            $query->limit($limit);
        }

        $list = $query->select();

        if (!$list || $list->isEmpty()) {
            return [];
        }

        $result = [];
        $maxCount = 0;
        $minCount = PHP_INT_MAX;

        // 获取最大最小值用于计算热度等级
        foreach ($list as $item) {
            $count = intval($item['count']);
            if ($count > $maxCount) $maxCount = $count;
            if ($count < $minCount) $minCount = $count;
        }

        // 计算热度等级（1-5级）
        foreach ($list as $item) {
            $count = intval($item['count']);

            // 计算等级
            if ($maxCount == $minCount) {
                $level = 3;
            } else {
                $level = ceil(($count - $minCount) / ($maxCount - $minCount) * 4) + 1;
            }

            $result[] = [
                'id' => $item['id'],
                'keyword' => $item['keyword'],
                'count' => $count,
                'level' => $level, // 1-5级，可用于显示不同大小
                'url' => '/search?q=' . urlencode($item['keyword'])
            ];
        }

        return $result;
    }
}
