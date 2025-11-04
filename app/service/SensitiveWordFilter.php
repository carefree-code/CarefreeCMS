<?php

namespace app\service;

/**
 * 敏感词过滤服务
 */
class SensitiveWordFilter
{
    /**
     * 敏感词列表
     * @var array
     */
    private static $sensitiveWords = [];

    /**
     * 敏感词树（用于快速匹配）
     * @var array
     */
    private static $wordTree = [];

    /**
     * 替换字符
     * @var string
     */
    private static $replaceChar = '*';

    /**
     * 初始化敏感词库
     */
    public static function init()
    {
        if (!empty(self::$wordTree)) {
            return;
        }

        // 从配置文件加载敏感词
        $configFile = app()->getConfigPath() . 'sensitive_words.php';
        if (file_exists($configFile)) {
            self::$sensitiveWords = include $configFile;
        } else {
            // 默认敏感词列表
            self::$sensitiveWords = [
                // 政治相关
                '习近平', '胡锦涛', '江泽民', '毛泽东', '邓小平',
                '共产党', '法轮功', '六四', '天安门',

                // 色情相关
                '色情', '黄色', '裸体', '性爱', 'fuck', 'shit',

                // 赌博相关
                '赌博', '赌场', '六合彩', '博彩',

                // 暴力相关
                '杀人', '自杀', '炸弹', '枪支',

                // 其他
                '傻逼', '操你妈', '草泥马', '你妈的',
            ];
        }

        // 构建敏感词树
        self::buildWordTree();
    }

    /**
     * 构建敏感词树（DFA算法）
     */
    private static function buildWordTree()
    {
        self::$wordTree = [];

        foreach (self::$sensitiveWords as $word) {
            $word = trim($word);
            if (empty($word)) {
                continue;
            }

            $len = mb_strlen($word, 'utf-8');
            $tree = &self::$wordTree;

            for ($i = 0; $i < $len; $i++) {
                $char = mb_substr($word, $i, 1, 'utf-8');

                if (!isset($tree[$char])) {
                    $tree[$char] = [];
                }

                $tree = &$tree[$char];

                // 最后一个字符，标记为结束
                if ($i === $len - 1) {
                    $tree['end'] = true;
                }
            }
        }
    }

    /**
     * 检测文本中是否包含敏感词
     * @param string $text 待检测文本
     * @return bool
     */
    public static function check(string $text): bool
    {
        self::init();

        $len = mb_strlen($text, 'utf-8');
        $tree = self::$wordTree;

        for ($i = 0; $i < $len; $i++) {
            $matchLen = 0;
            $tempTree = $tree;

            for ($j = $i; $j < $len; $j++) {
                $char = mb_substr($text, $j, 1, 'utf-8');

                if (!isset($tempTree[$char])) {
                    break;
                }

                $matchLen++;
                $tempTree = $tempTree[$char];

                // 匹配到敏感词
                if (isset($tempTree['end']) && $tempTree['end'] === true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 过滤文本中的敏感词（替换为*）
     * @param string $text 待过滤文本
     * @param string $replaceChar 替换字符
     * @return string
     */
    public static function filter(string $text, string $replaceChar = '*'): string
    {
        self::init();
        self::$replaceChar = $replaceChar;

        $len = mb_strlen($text, 'utf-8');
        $tree = self::$wordTree;
        $result = $text;
        $matches = [];

        for ($i = 0; $i < $len; $i++) {
            $matchLen = 0;
            $tempTree = $tree;

            for ($j = $i; $j < $len; $j++) {
                $char = mb_substr($text, $j, 1, 'utf-8');

                if (!isset($tempTree[$char])) {
                    break;
                }

                $matchLen++;
                $tempTree = $tempTree[$char];

                // 匹配到敏感词
                if (isset($tempTree['end']) && $tempTree['end'] === true) {
                    $word = mb_substr($text, $i, $matchLen, 'utf-8');
                    $matches[] = [
                        'word'  => $word,
                        'start' => $i,
                        'len'   => $matchLen,
                    ];
                    break;
                }
            }
        }

        // 从后往前替换，避免位置偏移
        $matches = array_reverse($matches);
        foreach ($matches as $match) {
            $replacement = str_repeat(self::$replaceChar, $match['len']);
            $result = mb_substr($result, 0, $match['start'], 'utf-8') .
                     $replacement .
                     mb_substr($result, $match['start'] + $match['len'], null, 'utf-8');
        }

        return $result;
    }

    /**
     * 获取文本中的敏感词列表
     * @param string $text 待检测文本
     * @return array
     */
    public static function getWords(string $text): array
    {
        self::init();

        $len = mb_strlen($text, 'utf-8');
        $tree = self::$wordTree;
        $words = [];

        for ($i = 0; $i < $len; $i++) {
            $matchLen = 0;
            $tempTree = $tree;

            for ($j = $i; $j < $len; $j++) {
                $char = mb_substr($text, $j, 1, 'utf-8');

                if (!isset($tempTree[$char])) {
                    break;
                }

                $matchLen++;
                $tempTree = $tempTree[$char];

                // 匹配到敏感词
                if (isset($tempTree['end']) && $tempTree['end'] === true) {
                    $word = mb_substr($text, $i, $matchLen, 'utf-8');
                    if (!in_array($word, $words)) {
                        $words[] = $word;
                    }
                    break;
                }
            }
        }

        return $words;
    }

    /**
     * 添加敏感词
     * @param string|array $words 敏感词
     */
    public static function addWords($words)
    {
        if (is_string($words)) {
            $words = [$words];
        }

        self::$sensitiveWords = array_merge(self::$sensitiveWords, $words);
        self::$sensitiveWords = array_unique(self::$sensitiveWords);

        // 重新构建词树
        self::buildWordTree();
    }

    /**
     * 移除敏感词
     * @param string|array $words 敏感词
     */
    public static function removeWords($words)
    {
        if (is_string($words)) {
            $words = [$words];
        }

        self::$sensitiveWords = array_diff(self::$sensitiveWords, $words);

        // 重新构建词树
        self::buildWordTree();
    }

    /**
     * 获取所有敏感词
     * @return array
     */
    public static function getAllWords(): array
    {
        self::init();
        return self::$sensitiveWords;
    }
}
