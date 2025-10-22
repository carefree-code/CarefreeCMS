<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * URL重定向规则模型
 */
class SeoRedirect extends Model
{
    protected $name = 'seo_redirects';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 重定向类型常量
    const REDIRECT_301 = 301;  // 永久重定向
    const REDIRECT_302 = 302;  // 临时重定向

    // 匹配类型常量
    const MATCH_EXACT = 'exact';        // 精确匹配
    const MATCH_WILDCARD = 'wildcard';  // 通配符匹配
    const MATCH_REGEX = 'regex';        // 正则表达式

    /**
     * 增加命中次数
     */
    public function incrementHitCount()
    {
        $this->hit_count++;
        $this->last_hit_time = date('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * 根据URL查找匹配的重定向规则
     * @param string $url 请求的URL
     * @return SeoRedirect|null
     */
    public static function findMatchingRule($url)
    {
        // 获取所有启用的重定向规则，按命中次数排序（常用的规则优先）
        $rules = self::where('is_enabled', 1)
            ->order('hit_count', 'desc')
            ->select();

        foreach ($rules as $rule) {
            if (self::matchUrl($url, $rule)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * 检查URL是否匹配规则
     * @param string $url 待匹配的URL
     * @param SeoRedirect $rule 重定向规则
     * @return bool
     */
    private static function matchUrl($url, $rule)
    {
        switch ($rule->match_type) {
            case self::MATCH_EXACT:
                // 精确匹配
                return $url === $rule->from_url;

            case self::MATCH_WILDCARD:
                // 通配符匹配（* 代表任意字符）
                $pattern = str_replace('*', '.*', preg_quote($rule->from_url, '/'));
                return preg_match('/^' . $pattern . '$/', $url);

            case self::MATCH_REGEX:
                // 正则表达式匹配
                return @preg_match($rule->from_url, $url);

            default:
                return false;
        }
    }

    /**
     * 应用重定向规则，生成目标URL
     * @param string $url 原始URL
     * @return string
     */
    public function applyRedirect($url)
    {
        switch ($this->match_type) {
            case self::MATCH_WILDCARD:
                // 通配符替换
                $pattern = str_replace('*', '(.*)', preg_quote($this->from_url, '/'));
                $replacement = str_replace('*', '$1', $this->to_url);
                return preg_replace('/^' . $pattern . '$/', $replacement, $url);

            case self::MATCH_REGEX:
                // 正则表达式替换
                return preg_replace($this->from_url, $this->to_url, $url);

            default:
                // 精确匹配直接返回目标URL
                return $this->to_url;
        }
    }

    /**
     * 获取所有重定向类型
     */
    public static function getRedirectTypes()
    {
        return [
            self::REDIRECT_301 => '301 永久重定向',
            self::REDIRECT_302 => '302 临时重定向',
        ];
    }

    /**
     * 获取所有匹配类型
     */
    public static function getMatchTypes()
    {
        return [
            self::MATCH_EXACT => '精确匹配',
            self::MATCH_WILDCARD => '通配符（*）',
            self::MATCH_REGEX => '正则表达式',
        ];
    }

    /**
     * 搜索器：启用状态
     */
    public function searchIsEnabledAttr($query, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where('is_enabled', $value);
        }
    }

    /**
     * 搜索器：重定向类型
     */
    public function searchRedirectTypeAttr($query, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where('redirect_type', $value);
        }
    }

    /**
     * 搜索器：匹配类型
     */
    public function searchMatchTypeAttr($query, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where('match_type', $value);
        }
    }
}
