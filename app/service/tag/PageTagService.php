<?php
namespace app\service\tag;

/**
 * 分页标签服务类
 * 处理分页导航的生成
 */
class PageTagService
{
    /**
     * 渲染分页HTML
     *
     * @param array $params 分页参数
     *   - total: 总记录数
     *   - pagesize: 每页数量
     *   - currentpage: 当前页码
     *   - url: URL模板（如 /articles/page-{page}.html）
     *   - style: 样式（simple/full）
     * @return string HTML代码
     */
    public static function render($params = [])
    {
        $total = $params['total'] ?? 0;
        $pagesize = $params['pagesize'] ?? 10;
        $currentPage = $params['currentpage'] ?? 1;
        $url = $params['url'] ?? '';
        $style = $params['style'] ?? 'full';

        if ($total <= 0 || $pagesize <= 0) {
            return '';
        }

        // 计算总页数
        $totalPages = ceil($total / $pagesize);

        if ($totalPages <= 1) {
            return ''; // 只有一页，不显示分页
        }

        // 确保当前页在有效范围内
        $currentPage = max(1, min($currentPage, $totalPages));

        // 根据样式生成HTML
        if ($style === 'simple') {
            return self::renderSimple($currentPage, $totalPages, $url);
        } else {
            return self::renderFull($currentPage, $totalPages, $url, $total, $pagesize);
        }
    }

    /**
     * 渲染简单分页（仅上一页/下一页）
     */
    private static function renderSimple($currentPage, $totalPages, $url)
    {
        $html = '<div class="pagination pagination-simple">';

        // 上一页
        if ($currentPage > 1) {
            $prevUrl = str_replace('{page}', $currentPage - 1, $url);
            $html .= '<a href="' . $prevUrl . '" class="prev">上一页</a>';
        } else {
            $html .= '<span class="prev disabled">上一页</span>';
        }

        // 当前页/总页数
        $html .= '<span class="current">第 ' . $currentPage . ' / ' . $totalPages . ' 页</span>';

        // 下一页
        if ($currentPage < $totalPages) {
            $nextUrl = str_replace('{page}', $currentPage + 1, $url);
            $html .= '<a href="' . $nextUrl . '" class="next">下一页</a>';
        } else {
            $html .= '<span class="next disabled">下一页</span>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * 渲染完整分页（带页码列表）
     */
    private static function renderFull($currentPage, $totalPages, $url, $total, $pagesize)
    {
        $html = '<div class="pagination pagination-full">';

        // 统计信息
        $start = ($currentPage - 1) * $pagesize + 1;
        $end = min($currentPage * $pagesize, $total);
        $html .= '<span class="pagination-info">显示 ' . $start . '-' . $end . ' 条，共 ' . $total . ' 条</span>';

        // 首页
        if ($currentPage > 1) {
            $firstUrl = str_replace('{page}', 1, $url);
            $html .= '<a href="' . $firstUrl . '" class="first">首页</a>';
        }

        // 上一页
        if ($currentPage > 1) {
            $prevUrl = str_replace('{page}', $currentPage - 1, $url);
            $html .= '<a href="' . $prevUrl . '" class="prev">«</a>';
        } else {
            $html .= '<span class="prev disabled">«</span>';
        }

        // 页码列表
        $pageList = self::getPageList($currentPage, $totalPages);
        foreach ($pageList as $page) {
            if ($page === '...') {
                $html .= '<span class="ellipsis">...</span>';
            } elseif ($page == $currentPage) {
                $html .= '<span class="current">' . $page . '</span>';
            } else {
                $pageUrl = str_replace('{page}', $page, $url);
                $html .= '<a href="' . $pageUrl . '">' . $page . '</a>';
            }
        }

        // 下一页
        if ($currentPage < $totalPages) {
            $nextUrl = str_replace('{page}', $currentPage + 1, $url);
            $html .= '<a href="' . $nextUrl . '" class="next">»</a>';
        } else {
            $html .= '<span class="next disabled">»</span>';
        }

        // 末页
        if ($currentPage < $totalPages) {
            $lastUrl = str_replace('{page}', $totalPages, $url);
            $html .= '<a href="' . $lastUrl . '" class="last">末页</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * 获取页码列表（带省略号）
     */
    private static function getPageList($currentPage, $totalPages)
    {
        $pages = [];
        $showPages = 7; // 显示的页码数量

        if ($totalPages <= $showPages) {
            // 总页数少，全部显示
            for ($i = 1; $i <= $totalPages; $i++) {
                $pages[] = $i;
            }
        } else {
            // 总页数多，显示部分+省略号
            if ($currentPage <= 4) {
                // 当前页靠前
                for ($i = 1; $i <= 5; $i++) {
                    $pages[] = $i;
                }
                $pages[] = '...';
                $pages[] = $totalPages;
            } elseif ($currentPage >= $totalPages - 3) {
                // 当前页靠后
                $pages[] = 1;
                $pages[] = '...';
                for ($i = $totalPages - 4; $i <= $totalPages; $i++) {
                    $pages[] = $i;
                }
            } else {
                // 当前页居中
                $pages[] = 1;
                $pages[] = '...';
                for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
                    $pages[] = $i;
                }
                $pages[] = '...';
                $pages[] = $totalPages;
            }
        }

        return $pages;
    }
}
