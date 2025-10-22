<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * SEO关键词排名追踪模型
 */
class SeoKeywordRanking extends Model
{
    protected $name = 'seo_keyword_rankings';

    // 自动时间戳
    protected $autoWriteTimestamp = 'create_time';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 搜索引擎常量
    const ENGINE_BAIDU = 'baidu';
    const ENGINE_GOOGLE = 'google';
    const ENGINE_BING = 'bing';
    const ENGINE_SOGOU = 'sogou';
    const ENGINE_360 = '360';

    /**
     * 记录关键词排名
     * @param string $keyword 关键词
     * @param string $url 目标URL
     * @param string $searchEngine 搜索引擎
     * @param int|null $ranking 排名位置（NULL表示100名之外）
     * @return SeoKeywordRanking
     */
    public static function record($keyword, $url, $searchEngine, $ranking = null)
    {
        $today = date('Y-m-d');

        // 查找今天是否已有记录
        $record = self::where('keyword', $keyword)
            ->where('search_engine', $searchEngine)
            ->where('check_date', $today)
            ->find();

        if ($record) {
            // 更新排名
            $record->ranking = $ranking;
            $record->url = $url;
            $record->save();
        } else {
            // 创建新记录
            $record = self::create([
                'keyword' => $keyword,
                'url' => $url,
                'search_engine' => $searchEngine,
                'ranking' => $ranking,
                'check_date' => $today
            ]);
        }

        return $record;
    }

    /**
     * 获取关键词排名历史
     * @param string $keyword 关键词
     * @param string $searchEngine 搜索引擎
     * @param int $days 天数
     * @return array
     */
    public static function getHistory($keyword, $searchEngine, $days = 30)
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return self::where('keyword', $keyword)
            ->where('search_engine', $searchEngine)
            ->where('check_date', '>=', $startDate)
            ->order('check_date', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取关键词排名趋势
     * @param string $keyword
     * @param string $searchEngine
     * @param int $days
     * @return array
     */
    public static function getTrend($keyword, $searchEngine, $days = 30)
    {
        $history = self::getHistory($keyword, $searchEngine, $days);

        if (count($history) < 2) {
            return [
                'trend' => 'stable',  // stable, up, down
                'change' => 0,
                'latest' => $history[0]['ranking'] ?? null,
                'previous' => null
            ];
        }

        $latest = end($history);
        $previous = prev($history);

        $latestRanking = $latest['ranking'] ?? 101;
        $previousRanking = $previous['ranking'] ?? 101;

        $change = $previousRanking - $latestRanking; // 正数表示上升，负数表示下降

        if ($change > 0) {
            $trend = 'up';
        } elseif ($change < 0) {
            $trend = 'down';
        } else {
            $trend = 'stable';
        }

        return [
            'trend' => $trend,
            'change' => $change,
            'latest' => $latest['ranking'],
            'previous' => $previous['ranking'],
            'history' => $history
        ];
    }

    /**
     * 获取所有搜索引擎列表
     */
    public static function getSearchEngines()
    {
        return [
            self::ENGINE_BAIDU => '百度',
            self::ENGINE_GOOGLE => '谷歌',
            self::ENGINE_BING => '必应',
            self::ENGINE_SOGOU => '搜狗',
            self::ENGINE_360 => '360搜索',
        ];
    }

    /**
     * 获取关键词概览（多个搜索引擎的最新排名）
     * @param string $keyword
     * @return array
     */
    public static function getKeywordOverview($keyword)
    {
        $engines = array_keys(self::getSearchEngines());
        $overview = [];

        foreach ($engines as $engine) {
            $latest = self::where('keyword', $keyword)
                ->where('search_engine', $engine)
                ->order('check_date', 'desc')
                ->find();

            $overview[$engine] = [
                'ranking' => $latest ? $latest->ranking : null,
                'check_date' => $latest ? $latest->check_date : null,
                'url' => $latest ? $latest->url : null
            ];
        }

        return $overview;
    }

    /**
     * 搜索器：关键词
     */
    public function searchKeywordAttr($query, $value)
    {
        if ($value) {
            $query->where('keyword', 'like', "%{$value}%");
        }
    }

    /**
     * 搜索器：搜索引擎
     */
    public function searchSearchEngineAttr($query, $value)
    {
        if ($value) {
            $query->where('search_engine', $value);
        }
    }

    /**
     * 搜索器：日期范围
     */
    public function searchCheckDateAttr($query, $value)
    {
        if (is_array($value) && count($value) === 2) {
            $query->whereBetween('check_date', $value);
        }
    }
}
