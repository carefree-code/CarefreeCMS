<?php
declare (strict_types = 1);

namespace app\service;

/**
 * SEO分析服务
 */
class SeoAnalyzer
{
    /**
     * 分析内容并返回SEO分数和建议
     * @param array $data 内容数据
     * @return array
     */
    public static function analyze($data)
    {
        $results = [];
        $totalScore = 0;
        $maxScore = 0;

        // 1. 标题分析 (20分)
        $titleResult = self::analyzeTitle($data['title'] ?? '', $data['seo_title'] ?? '');
        $results['title'] = $titleResult;
        $totalScore += $titleResult['score'];
        $maxScore += 20;

        // 2. 描述分析 (15分)
        $descResult = self::analyzeDescription($data['seo_description'] ?? '', $data['summary'] ?? '');
        $results['description'] = $descResult;
        $totalScore += $descResult['score'];
        $maxScore += 15;

        // 3. 关键词分析 (15分)
        $keywordsResult = self::analyzeKeywords($data['seo_keywords'] ?? '');
        $results['keywords'] = $keywordsResult;
        $totalScore += $keywordsResult['score'];
        $maxScore += 15;

        // 4. 内容分析 (20分)
        $contentResult = self::analyzeContent($data['content'] ?? '', $data['seo_keywords'] ?? '');
        $results['content'] = $contentResult;
        $totalScore += $contentResult['score'];
        $maxScore += 20;

        // 5. 图片分析 (10分)
        $imageResult = self::analyzeImages($data['content'] ?? '', $data['cover_image'] ?? '');
        $results['images'] = $imageResult;
        $totalScore += $imageResult['score'];
        $maxScore += 10;

        // 6. 链接分析 (10分)
        $linkResult = self::analyzeLinks($data['content'] ?? '');
        $results['links'] = $linkResult;
        $totalScore += $linkResult['score'];
        $maxScore += 10;

        // 7. 可读性分析 (10分)
        $readabilityResult = self::analyzeReadability($data['content'] ?? '');
        $results['readability'] = $readabilityResult;
        $totalScore += $readabilityResult['score'];
        $maxScore += 10;

        // 计算总分
        $finalScore = $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;

        // 评级
        $grade = self::getGrade($finalScore);

        return [
            'score' => $finalScore,
            'grade' => $grade,
            'results' => $results,
            'summary' => self::generateSummary($results)
        ];
    }

    /**
     * 分析标题
     */
    private static function analyzeTitle($title, $seoTitle)
    {
        $score = 0;
        $suggestions = [];
        $issues = [];

        $titleToCheck = !empty($seoTitle) ? $seoTitle : $title;
        $titleLength = mb_strlen($titleToCheck);

        // 检查标题是否存在
        if (empty($titleToCheck)) {
            $issues[] = '标题不能为空';
            return ['score' => 0, 'suggestions' => ['请添加标题'], 'issues' => $issues];
        }
        $score += 5;

        // 检查标题长度 (30-60个字符最佳)
        if ($titleLength >= 30 && $titleLength <= 60) {
            $score += 10;
        } elseif ($titleLength < 30) {
            $issues[] = '标题太短';
            $suggestions[] = "标题当前长度{$titleLength}字符，建议30-60字符";
            $score += 5;
        } else {
            $issues[] = '标题太长';
            $suggestions[] = "标题当前长度{$titleLength}字符，超过60字符可能被截断";
            $score += 5;
        }

        // 检查是否有SEO标题
        if (!empty($seoTitle)) {
            $score += 5;
        } else {
            $suggestions[] = '建议设置独立的SEO标题以优化搜索结果';
        }

        if (empty($issues)) {
            $issues[] = '标题符合SEO规范';
        }

        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'issues' => $issues,
            'length' => $titleLength
        ];
    }

    /**
     * 分析描述
     */
    private static function analyzeDescription($seoDescription, $summary)
    {
        $score = 0;
        $suggestions = [];
        $issues = [];

        $descToCheck = !empty($seoDescription) ? $seoDescription : $summary;
        $descLength = mb_strlen($descToCheck);

        // 检查描述是否存在
        if (empty($descToCheck)) {
            $issues[] = '缺少描述';
            $suggestions[] = '请添加SEO描述或摘要';
            return ['score' => 0, 'suggestions' => $suggestions, 'issues' => $issues];
        }
        $score += 5;

        // 检查描述长度 (80-160个字符最佳)
        if ($descLength >= 80 && $descLength <= 160) {
            $score += 10;
        } elseif ($descLength < 80) {
            $issues[] = '描述太短';
            $suggestions[] = "描述当前长度{$descLength}字符，建议80-160字符";
            $score += 5;
        } else {
            $issues[] = '描述太长';
            $suggestions[] = "描述当前长度{$descLength}字符，超过160字符可能被截断";
            $score += 5;
        }

        if (empty($issues)) {
            $issues[] = '描述符合SEO规范';
        }

        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'issues' => $issues,
            'length' => $descLength
        ];
    }

    /**
     * 分析关键词
     */
    private static function analyzeKeywords($keywords)
    {
        $score = 0;
        $suggestions = [];
        $issues = [];

        if (empty($keywords)) {
            $issues[] = '未设置关键词';
            $suggestions[] = '请添加3-5个相关关键词';
            return ['score' => 0, 'suggestions' => $suggestions, 'issues' => $issues];
        }
        $score += 5;

        // 分割关键词
        $keywordArray = array_filter(array_map('trim', explode(',', $keywords)));
        $keywordCount = count($keywordArray);

        // 检查关键词数量 (3-5个最佳)
        if ($keywordCount >= 3 && $keywordCount <= 5) {
            $score += 10;
        } elseif ($keywordCount < 3) {
            $issues[] = '关键词太少';
            $suggestions[] = "当前{$keywordCount}个关键词，建议3-5个";
            $score += 5;
        } else {
            $issues[] = '关键词太多';
            $suggestions[] = "当前{$keywordCount}个关键词，建议3-5个，过多会分散权重";
            $score += 5;
        }

        if (empty($issues)) {
            $issues[] = '关键词数量合适';
        }

        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'issues' => $issues,
            'count' => $keywordCount,
            'keywords' => $keywordArray
        ];
    }

    /**
     * 分析内容
     */
    private static function analyzeContent($content, $keywords)
    {
        $score = 0;
        $suggestions = [];
        $issues = [];

        // 移除HTML标签
        $plainText = strip_tags($content);
        $contentLength = mb_strlen($plainText);

        // 检查内容长度
        if ($contentLength < 300) {
            $issues[] = '内容太短';
            $suggestions[] = "当前内容{$contentLength}字符，建议至少300字符";
            $score += 5;
        } elseif ($contentLength >= 300 && $contentLength < 1000) {
            $score += 15;
        } else {
            $score += 20;
        }

        // 关键词密度分析
        if (!empty($keywords)) {
            $keywordArray = array_filter(array_map('trim', explode(',', $keywords)));
            $densityAnalysis = self::calculateKeywordDensity($plainText, $keywordArray);

            $score += $densityAnalysis['score'];
            $suggestions = array_merge($suggestions, $densityAnalysis['suggestions']);

            if (!empty($densityAnalysis['issues'])) {
                $issues = array_merge($issues, $densityAnalysis['issues']);
            }
        }

        if (empty($issues)) {
            $issues[] = '内容质量良好';
        }

        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'issues' => $issues,
            'length' => $contentLength
        ];
    }

    /**
     * 计算关键词密度
     */
    public static function calculateKeywordDensity($content, $keywords)
    {
        $score = 0;
        $suggestions = [];
        $issues = [];
        $densities = [];

        $plainText = strip_tags($content);
        $totalWords = mb_strlen($plainText);

        if ($totalWords == 0) {
            return ['score' => 0, 'suggestions' => ['内容为空'], 'issues' => [], 'densities' => []];
        }

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) continue;

            // 计算关键词出现次数
            $count = mb_substr_count($plainText, $keyword);
            $density = $totalWords > 0 ? round(($count * mb_strlen($keyword) / $totalWords) * 100, 2) : 0;

            $densities[$keyword] = [
                'count' => $count,
                'density' => $density
            ];

            // 评估密度 (1-3%为最佳)
            if ($density >= 1 && $density <= 3) {
                $score += 3;
            } elseif ($density > 0 && $density < 1) {
                $suggestions[] = "关键词「{$keyword}」密度{$density}%偏低，建议增加使用";
            } elseif ($density > 3) {
                $issues[] = "关键词「{$keyword}」密度{$density}%过高，可能被视为堆砌";
            } else {
                $issues[] = "关键词「{$keyword}」未在内容中出现";
            }
        }

        return [
            'score' => min($score, 5),
            'suggestions' => $suggestions,
            'issues' => $issues,
            'densities' => $densities
        ];
    }

    /**
     * 分析图片
     */
    private static function analyzeImages($content, $coverImage)
    {
        $score = 0;
        $suggestions = [];
        $issues = [];

        // 检查封面图
        if (!empty($coverImage)) {
            $score += 5;
        } else {
            $issues[] = '缺少封面图';
            $suggestions[] = '建议添加封面图以提升分享效果';
        }

        // 检查内容中的图片
        preg_match_all('/<img[^>]+>/i', $content, $matches);
        $imageCount = count($matches[0]);

        if ($imageCount > 0) {
            $score += 3;

            // 检查图片alt属性
            $imagesWithAlt = 0;
            foreach ($matches[0] as $imgTag) {
                if (preg_match('/alt=["\']([^"\']*)["\']/', $imgTag, $altMatch)) {
                    if (!empty($altMatch[1])) {
                        $imagesWithAlt++;
                    }
                }
            }

            if ($imagesWithAlt == $imageCount) {
                $score += 2;
            } elseif ($imagesWithAlt > 0) {
                $suggestions[] = "有{$imagesWithAlt}/{$imageCount}张图片设置了alt属性，建议为所有图片添加";
                $score += 1;
            } else {
                $issues[] = '图片缺少alt属性';
                $suggestions[] = '建议为所有图片添加alt属性以提升SEO';
            }
        } else {
            $suggestions[] = '内容中没有图片，适当添加图片可以提升用户体验';
        }

        if (empty($issues) && $score >= 8) {
            $issues[] = '图片优化良好';
        }

        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'issues' => $issues,
            'count' => $imageCount
        ];
    }

    /**
     * 分析链接
     */
    private static function analyzeLinks($content)
    {
        $score = 5;
        $suggestions = [];
        $issues = [];

        // 检查内部链接
        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', $content, $matches);
        $linkCount = count($matches[1]);

        if ($linkCount > 0) {
            $score += 5;

            // 检查外部链接的nofollow
            $externalLinks = 0;
            $noFollowLinks = 0;
            foreach ($matches[0] as $index => $linkTag) {
                $url = $matches[1][$index];
                if (preg_match('/^https?:\/\//', $url)) {
                    $externalLinks++;
                    if (preg_match('/rel=["\'][^"\']*nofollow[^"\']*["\']/', $linkTag)) {
                        $noFollowLinks++;
                    }
                }
            }

            if ($externalLinks > 0 && $noFollowLinks < $externalLinks) {
                $suggestions[] = "有{$externalLinks}个外部链接，建议为不信任的外部链接添加nofollow";
            }
        } else {
            $suggestions[] = '内容中没有链接，适当添加相关内链可以提升SEO';
        }

        if (empty($issues)) {
            $issues[] = '链接使用合理';
        }

        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'issues' => $issues,
            'count' => $linkCount
        ];
    }

    /**
     * 分析可读性
     */
    private static function analyzeReadability($content)
    {
        $score = 0;
        $suggestions = [];
        $issues = [];

        $plainText = strip_tags($content);

        // 检查段落
        $paragraphs = preg_split('/\n\s*\n/', $plainText);
        $paragraphCount = count(array_filter($paragraphs));

        if ($paragraphCount >= 3) {
            $score += 5;
        } else {
            $suggestions[] = '建议将内容分成多个段落，提升可读性';
            $score += 2;
        }

        // 检查标题标签
        $h1Count = preg_match_all('/<h1[^>]*>/', $content);
        $h2Count = preg_match_all('/<h2[^>]*>/', $content);
        $h3Count = preg_match_all('/<h3[^>]*>/', $content);

        if ($h2Count > 0 || $h3Count > 0) {
            $score += 5;
        } else {
            $suggestions[] = '建议使用H2、H3标签组织内容结构';
            $score += 2;
        }

        if (empty($suggestions)) {
            $issues[] = '内容结构清晰';
        }

        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'issues' => $issues,
            'paragraphs' => $paragraphCount
        ];
    }

    /**
     * 根据分数获取评级
     */
    private static function getGrade($score)
    {
        if ($score >= 90) {
            return ['level' => 'A', 'label' => '优秀', 'color' => 'success'];
        } elseif ($score >= 80) {
            return ['level' => 'B', 'label' => '良好', 'color' => 'primary'];
        } elseif ($score >= 70) {
            return ['level' => 'C', 'label' => '中等', 'color' => 'warning'];
        } elseif ($score >= 60) {
            return ['level' => 'D', 'label' => '及格', 'color' => 'warning'];
        } else {
            return ['level' => 'E', 'label' => '需改进', 'color' => 'danger'];
        }
    }

    /**
     * 生成总结
     */
    private static function generateSummary($results)
    {
        $allIssues = [];
        $allSuggestions = [];

        foreach ($results as $category => $result) {
            if (!empty($result['issues'])) {
                foreach ($result['issues'] as $issue) {
                    $allIssues[] = $issue;
                }
            }
            if (!empty($result['suggestions'])) {
                foreach ($result['suggestions'] as $suggestion) {
                    $allSuggestions[] = $suggestion;
                }
            }
        }

        return [
            'issues' => $allIssues,
            'suggestions' => $allSuggestions
        ];
    }

    /**
     * 自动生成SEO标题
     */
    public static function generateSeoTitle($title, $keywords = '')
    {
        $seoTitle = $title;

        // 如果有关键词，尝试融入第一个关键词
        if (!empty($keywords)) {
            $keywordArray = array_filter(array_map('trim', explode(',', $keywords)));
            if (!empty($keywordArray)) {
                $mainKeyword = $keywordArray[0];
                if (mb_stripos($title, $mainKeyword) === false) {
                    $seoTitle = $mainKeyword . ' - ' . $title;
                }
            }
        }

        // 限制长度
        if (mb_strlen($seoTitle) > 60) {
            $seoTitle = mb_substr($seoTitle, 0, 57) . '...';
        }

        return $seoTitle;
    }

    /**
     * 自动生成SEO描述
     */
    public static function generateSeoDescription($content, $keywords = '', $maxLength = 160)
    {
        $plainText = strip_tags($content);
        $plainText = preg_replace('/\s+/', ' ', $plainText);
        $plainText = trim($plainText);

        // 如果内容太短，直接返回
        if (mb_strlen($plainText) <= $maxLength) {
            return $plainText;
        }

        // 尝试在句号处截断
        $description = mb_substr($plainText, 0, $maxLength);
        $lastPeriod = mb_strrpos($description, '。');
        if ($lastPeriod !== false && $lastPeriod > $maxLength * 0.6) {
            $description = mb_substr($description, 0, $lastPeriod + 1);
        } else {
            $description = mb_substr($description, 0, $maxLength - 3) . '...';
        }

        return $description;
    }

    /**
     * 自动提取关键词
     */
    public static function extractKeywords($content, $count = 5)
    {
        $plainText = strip_tags($content);

        // 简单的关键词提取：按词频统计
        // 移除标点符号（中文和英文标点）
        $plainText = preg_replace('/[\x{FF0C}\x{3002}\x{FF01}\x{FF1F}\x{FF1B}\x{FF1A}\x{3001}\x{FF08}\x{FF09}\x{300A}\x{300B}\x{201C}\x{201D}\x{2018}\x{2019}]/u', ' ', $plainText);

        // 分词（简单按空格和长度）
        $words = preg_split('/\s+/u', $plainText);
        $wordCount = [];

        foreach ($words as $word) {
            $word = trim($word);
            // 只统计2-10个字符的词
            if (mb_strlen($word) >= 2 && mb_strlen($word) <= 10) {
                if (!isset($wordCount[$word])) {
                    $wordCount[$word] = 0;
                }
                $wordCount[$word]++;
            }
        }

        // 排序并获取前N个
        arsort($wordCount);
        $keywords = array_slice(array_keys($wordCount), 0, $count);

        return implode(',', $keywords);
    }
}
