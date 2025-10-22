<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * Robots.txt配置模型
 */
class SeoRobot extends Model
{
    protected $name = 'seo_robots';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 获取当前启用的配置
     * @return SeoRobot|null
     */
    public static function getActiveConfig()
    {
        return self::where('is_active', 1)->find();
    }

    /**
     * 启用此配置（会将其他配置设为禁用）
     */
    public function activate()
    {
        // 先将所有配置设为禁用
        self::where('is_active', 1)->update(['is_active' => 0]);

        // 启用当前配置
        $this->is_active = 1;
        $this->save();
    }

    /**
     * 获取预设模板列表
     * @return array
     */
    public static function getTemplates()
    {
        return [
            'default' => [
                'name' => '默认配置',
                'description' => '允许所有搜索引擎，禁止抓取管理后台和API',
                'content' => "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: *.json$\nDisallow: *.xml$\n\nSitemap: /sitemap.xml"
            ],
            'allow_all' => [
                'name' => '全部允许',
                'description' => '允许所有搜索引擎抓取所有内容',
                'content' => "User-agent: *\nDisallow:\n\nSitemap: /sitemap.xml"
            ],
            'disallow_all' => [
                'name' => '全部禁止',
                'description' => '禁止所有搜索引擎抓取（适用于开发环境）',
                'content' => "User-agent: *\nDisallow: /"
            ],
            'baidu_only' => [
                'name' => '仅百度',
                'description' => '只允许百度搜索引擎，禁止其他搜索引擎',
                'content' => "User-agent: Baiduspider\nDisallow: /admin/\nDisallow: /api/\n\nUser-agent: *\nDisallow: /\n\nSitemap: /sitemap.xml"
            ],
            'google_bing' => [
                'name' => '谷歌和必应',
                'description' => '只允许 Google 和 Bing',
                'content' => "User-agent: Googlebot\nDisallow: /admin/\nDisallow: /api/\n\nUser-agent: Bingbot\nDisallow: /admin/\nDisallow: /api/\n\nUser-agent: *\nDisallow: /\n\nSitemap: /sitemap.xml"
            ],
            'block_bad_bots' => [
                'name' => '阻止恶意爬虫',
                'description' => '阻止常见的恶意爬虫和采集器',
                'content' => "User-agent: *\nDisallow: /admin/\nDisallow: /api/\n\n# 阻止恶意爬虫\nUser-agent: SemrushBot\nUser-agent: AhrefsBot\nUser-agent: DotBot\nUser-agent: MJ12bot\nDisallow: /\n\nSitemap: /sitemap.xml"
            ],
            'crawl_delay' => [
                'name' => '限制抓取频率',
                'description' => '设置抓取延迟，减轻服务器压力',
                'content' => "User-agent: *\nCrawl-delay: 10\nDisallow: /admin/\nDisallow: /api/\n\nSitemap: /sitemap.xml"
            ]
        ];
    }

    /**
     * 验证 robots.txt 内容格式
     * @param string $content
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateRobotsContent($content)
    {
        $errors = [];
        $lines = explode("\n", $content);
        $currentAgent = null;

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            // 跳过空行和注释
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // 检查格式：应该是 "Key: Value" 的形式
            if (strpos($line, ':') === false) {
                $errors[] = "行 " . ($lineNum + 1) . ": 格式错误，应为 'Key: Value' 格式";
                continue;
            }

            list($key, $value) = explode(':', $line, 2);
            $key = trim($key);

            // 验证允许的指令
            $allowedDirectives = [
                'User-agent', 'Disallow', 'Allow', 'Crawl-delay',
                'Sitemap', 'Host', 'Request-rate', 'Visit-time'
            ];

            if (!in_array($key, $allowedDirectives)) {
                $errors[] = "行 " . ($lineNum + 1) . ": 未知指令 '$key'";
            }

            // 检查 Disallow/Allow 前必须有 User-agent
            if (in_array($key, ['Disallow', 'Allow', 'Crawl-delay'])) {
                if ($currentAgent === null) {
                    $errors[] = "行 " . ($lineNum + 1) . ": '$key' 指令前必须先定义 User-agent";
                }
            }

            if ($key === 'User-agent') {
                $currentAgent = trim($value);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 搜索器：启用状态
     */
    public function searchIsActiveAttr($query, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where('is_active', $value);
        }
    }
}
