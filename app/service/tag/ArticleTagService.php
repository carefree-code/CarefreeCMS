<?php
namespace app\service\tag;

use app\model\Article;
use think\facade\Db;

/**
 * 文章标签服务类
 * 处理文章列表标签的数据查询
 */
class ArticleTagService
{
    /**
     * 获取文章列表
     *
     * @param array $params 查询参数
     *   - typeid: 分类ID
     *   - tagid: 标签ID
     *   - userid: 用户ID（作者）
     *   - limit: 数量限制
     *   - offset: 偏移量
     *   - order: 排序方式
     *   - flag: 文章标识 (hot-热门, recommend-推荐, top-置顶, random-随机, updated-最近更新)
     *   - titlelen: 标题截取长度
     *   - hascover: 是否有封面图（1-有，0-无）
     *   - exclude: 排除的文章ID（逗号分隔）
     *   - days: 最近N天的文章
     * @return array
     */
    public static function getList($params = [])
    {
        $typeid = $params['typeid'] ?? 0;
        $tagid = $params['tagid'] ?? 0;
        $userid = $params['userid'] ?? 0;
        $limit = $params['limit'] ?? 10;
        $offset = $params['offset'] ?? 0;
        $order = $params['order'] ?? 'create_time desc';
        $flag = $params['flag'] ?? '';
        $titlelen = $params['titlelen'] ?? 0;
        $hascover = $params['hascover'] ?? -1;
        $exclude = $params['exclude'] ?? '';
        $days = $params['days'] ?? 0;

        // 构建查询
        $query = Article::with(['category', 'tags', 'user'])
            ->where('status', 1);  // 只查询已发布文章

        // 按分类筛选
        if ($typeid > 0) {
            $query->where('category_id', $typeid);
        }

        // 按标签筛选
        if ($tagid > 0) {
            $query->whereExists(function($query) use ($tagid) {
                $query->table('article_tag')
                    ->whereRaw('article_tag.article_id = articles.id')
                    ->where('article_tag.tag_id', $tagid);
            });
        }

        // 按作者筛选
        if ($userid > 0) {
            $query->where('user_id', $userid);
        }

        // 是否有封面图筛选
        if ($hascover === 1) {
            $query->where('cover_image', '<>', '');
            $query->whereNotNull('cover_image');
        } elseif ($hascover === 0) {
            $query->where(function($query) {
                $query->where('cover_image', '')->whereOr('cover_image', null);
            });
        }

        // 排除指定文章
        if (!empty($exclude)) {
            $excludeIds = explode(',', $exclude);
            $excludeIds = array_filter($excludeIds);
            if (!empty($excludeIds)) {
                $query->whereNotIn('id', $excludeIds);
            }
        }

        // 最近N天的文章
        if ($days > 0) {
            $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $query->where('create_time', '>=', $startDate);
        }

        // 按标识筛选
        switch ($flag) {
            case 'hot':
                // 热门文章：按浏览量排序
                $query->order('view_count', 'desc');
                break;
            case 'recommend':
                // 推荐文章
                $query->where('is_recommend', 1);
                break;
            case 'top':
                // 置顶文章
                $query->where('is_top', 1);
                break;
            case 'random':
                // 随机文章
                $query->orderRaw('RAND()');
                break;
            case 'updated':
                // 最近更新的文章
                $query->order('update_time', 'desc');
                break;
        }

        // 排序（当没有特殊flag时才应用自定义排序）
        if (!empty($order) && !in_array($flag, ['hot', 'random', 'updated'])) {
            $orderArr = explode(' ', $order);
            $orderField = $orderArr[0] ?? 'create_time';
            $orderType = $orderArr[1] ?? 'desc';
            $query->order($orderField, $orderType);
        }

        // 偏移量
        if ($offset > 0) {
            $query->limit($offset, $limit);
        } elseif ($limit > 0) {
            $query->limit($limit);
        }

        $articles = $query->select()->toArray();

        // 处理标题长度
        if ($titlelen > 0) {
            foreach ($articles as &$article) {
                if (mb_strlen($article['title'], 'utf-8') > $titlelen) {
                    $article['title'] = mb_substr($article['title'], 0, $titlelen, 'utf-8') . '...';
                }
            }
        }

        return $articles;
    }

    /**
     * 获取单篇文章
     *
     * @param int $id 文章ID
     * @return array|null
     */
    public static function getOne($id)
    {
        return Article::with(['category', 'tags', 'user'])
            ->where('id', $id)
            ->where('status', 1)
            ->find()
            ?->toArray();
    }

    /**
     * 获取上一篇/下一篇文章
     *
     * @param int $aid 当前文章ID
     * @param int $catid 分类ID
     * @param string $type 类型: same-同分类, all-所有分类
     * @return array ['prev' => array|null, 'next' => array|null]
     */
    public static function getPrevNext($aid, $catid = 0, $type = 'same')
    {
        $aid = intval($aid);
        $catid = intval($catid);

        $result = [
            'prev' => null,
            'next' => null
        ];

        // 基础查询条件
        $baseQuery = Article::where('status', 1);

        // 如果是同分类模式且指定了分类ID
        if ($type === 'same' && $catid > 0) {
            $baseQuery = clone $baseQuery;
            $baseQuery->where('category_id', $catid);
        }

        // 获取上一篇（ID比当前小的最大ID）
        $prevQuery = clone $baseQuery;
        $result['prev'] = $prevQuery->where('id', '<', $aid)
            ->order('id', 'desc')
            ->field('id,title,category_id,create_time,cover_image')
            ->find()
            ?->toArray();

        // 获取下一篇（ID比当前大的最小ID）
        $nextQuery = clone $baseQuery;
        $result['next'] = $nextQuery->where('id', '>', $aid)
            ->order('id', 'asc')
            ->field('id,title,category_id,create_time,cover_image')
            ->find()
            ?->toArray();

        return $result;
    }
}
