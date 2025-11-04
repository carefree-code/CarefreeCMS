<?php
namespace app\service\tag;

use think\facade\Db;
use think\facade\Cache;

/**
 * 归档标签服务类
 * 按年月归档文章
 */
class ArchiveTagService
{
    /**
     * 获取归档列表
     *
     * @param array $params 查询参数
     *   - type: 归档类型（year-按年, month-按月, day-按日）
     *   - limit: 数量限制
     *   - format: 日期格式
     * @return array
     */
    public static function getList($params = [])
    {
        $type = $params['type'] ?? 'month';
        $limit = $params['limit'] ?? 12;
        $format = $params['format'] ?? 'Y年m月';

        // 尝试从缓存获取
        $cacheKey = 'archive_type_' . $type . '_limit_' . $limit;
        $archives = Cache::get($cacheKey);

        if ($archives !== false) {
            return $archives;
        }

        // 根据类型确定日期格式
        switch ($type) {
            case 'year':
                $dateFormat = '%Y';
                $groupFormat = 'Y';
                break;
            case 'day':
                $dateFormat = '%Y-%m-%d';
                $groupFormat = 'Y-m-d';
                break;
            case 'month':
            default:
                $dateFormat = '%Y-%m';
                $groupFormat = 'Y-m';
                break;
        }

        // 查询归档数据
        $archives = Db::table('articles')
            ->field([
                "DATE_FORMAT(create_time, '{$dateFormat}') as archive_date",
                'COUNT(*) as article_count'
            ])
            ->where('status', 1)
            ->group('archive_date')
            ->order('archive_date', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        // 处理归档数据
        foreach ($archives as &$archive) {
            // 生成归档页面URL
            $archive['url'] = '/archive/' . str_replace('-', '/', $archive['archive_date']) . '.html';

            // 格式化显示日期
            $timestamp = strtotime($archive['archive_date'] . '-01');
            $archive['display_date'] = date($format, $timestamp);

            // 解析年月日
            $dateParts = explode('-', $archive['archive_date']);
            $archive['year'] = $dateParts[0] ?? '';
            $archive['month'] = $dateParts[1] ?? '';
            $archive['day'] = $dateParts[2] ?? '';
        }

        // 缓存1小时
        Cache::set($cacheKey, $archives, 3600);

        return $archives;
    }

    /**
     * 获取指定归档期的文章列表
     *
     * @param string $archiveDate 归档日期（如：2025-10）
     * @param int $limit 数量限制
     * @return array
     */
    public static function getArticles($archiveDate, $limit = 100)
    {
        $cacheKey = 'archive_articles_' . $archiveDate . '_limit_' . $limit;
        $articles = Cache::get($cacheKey);

        if ($articles !== false) {
            return $articles;
        }

        // 解析日期
        $dateParts = explode('-', $archiveDate);
        $year = $dateParts[0] ?? '';
        $month = $dateParts[1] ?? '';
        $day = $dateParts[2] ?? '';

        $query = Db::table('articles')
            ->alias('a')
            ->field('a.id, a.title, a.summary, a.cover_image, a.view_count, a.like_count, a.create_time, c.name as category_name')
            ->leftJoin('categories c', 'a.category_id = c.id')
            ->where('a.status', 1);

        // 根据日期精度构建查询条件
        if (!empty($day)) {
            // 按日归档
            $startDate = $year . '-' . $month . '-' . $day . ' 00:00:00';
            $endDate = $year . '-' . $month . '-' . $day . ' 23:59:59';
            $query->whereBetween('a.create_time', [$startDate, $endDate]);
        } elseif (!empty($month)) {
            // 按月归档
            $query->whereYear('a.create_time', $year);
            $query->whereMonth('a.create_time', $month);
        } else {
            // 按年归档
            $query->whereYear('a.create_time', $year);
        }

        $articles = $query->order('a.create_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        // 缓存30分钟
        Cache::set($cacheKey, $articles, 1800);

        return $articles;
    }

    /**
     * 获取归档统计信息
     *
     * @return array
     */
    public static function getStats()
    {
        $cacheKey = 'archive_stats';
        $stats = Cache::get($cacheKey);

        if ($stats !== false) {
            return $stats;
        }

        // 总文章数
        $totalArticles = Db::table('articles')
            ->where('status', 1)
            ->count();

        // 第一篇文章日期
        $firstArticle = Db::table('articles')
            ->where('status', 1)
            ->order('create_time', 'asc')
            ->field('create_time')
            ->find();

        // 最新文章日期
        $lastArticle = Db::table('articles')
            ->where('status', 1)
            ->order('create_time', 'desc')
            ->field('create_time')
            ->find();

        $stats = [
            'total_articles' => $totalArticles,
            'first_date' => $firstArticle ? $firstArticle['create_time'] : '',
            'last_date' => $lastArticle ? $lastArticle['create_time'] : '',
            'archive_months' => 0,
        ];

        // 计算归档月份数
        if ($firstArticle && $lastArticle) {
            $firstTimestamp = strtotime($firstArticle['create_time']);
            $lastTimestamp = strtotime($lastArticle['create_time']);

            $firstYear = date('Y', $firstTimestamp);
            $firstMonth = date('m', $firstTimestamp);
            $lastYear = date('Y', $lastTimestamp);
            $lastMonth = date('m', $lastTimestamp);

            $stats['archive_months'] = ($lastYear - $firstYear) * 12 + ($lastMonth - $firstMonth) + 1;
        }

        // 缓存1天
        Cache::set($cacheKey, $stats, 86400);

        return $stats;
    }

    /**
     * 清除归档缓存
     *
     * @return void
     */
    public static function clearCache()
    {
        Cache::tag('archive')->clear();
    }
}
