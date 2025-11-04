<?php
namespace app\service\tag;

use app\model\Article;
use app\model\Media;
use app\model\Slider;

/**
 * 随机图片标签服务类
 * 处理随机图片的数据查询
 */
class RandomImgTagService
{
    /**
     * 获取随机图片
     *
     * @param array $params 查询参数
     *   - limit: 数量限制
     *   - source: 图片来源 (article-文章封面, media-媒体库, slider-幻灯片)
     * @return array
     */
    public static function getRandom($params = [])
    {
        $limit = $params['limit'] ?? 5;
        $source = $params['source'] ?? 'article';

        switch ($source) {
            case 'article':
                return self::getFromArticles($limit);
            case 'media':
                return self::getFromMedia($limit);
            case 'slider':
                return self::getFromSliders($limit);
            default:
                return self::getFromArticles($limit);
        }
    }

    /**
     * 从文章封面图获取
     *
     * @param int $limit
     * @return array
     */
    private static function getFromArticles($limit)
    {
        $articles = Article::where('status', 1)
            ->where('cover_image', '<>', '')
            ->whereNotNull('cover_image')
            ->orderRaw('RAND()')
            ->limit($limit)
            ->field('id, title, cover_image, create_time')
            ->select();

        if (!$articles || $articles->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($articles as $article) {
            $result[] = [
                'id' => $article->id,
                'title' => $article->title,
                'url' => $article->cover_image,
                'link' => '/article/' . $article->id . '.html',
                'type' => 'article'
            ];
        }

        return $result;
    }

    /**
     * 从媒体库获取
     *
     * @param int $limit
     * @return array
     */
    private static function getFromMedia($limit)
    {
        $medias = Media::where('file_type', 'like', 'image/%')
            ->orderRaw('RAND()')
            ->limit($limit)
            ->field('id, file_name, file_path, file_url, create_time')
            ->select();

        if (!$medias || $medias->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($medias as $media) {
            $result[] = [
                'id' => $media->id,
                'title' => $media->file_name,
                'url' => $media->file_url ?: $media->file_path,
                'link' => $media->file_url ?: $media->file_path,
                'type' => 'media'
            ];
        }

        return $result;
    }

    /**
     * 从幻灯片获取
     *
     * @param int $limit
     * @return array
     */
    private static function getFromSliders($limit)
    {
        $sliders = Slider::where('status', 1)
            ->orderRaw('RAND()')
            ->limit($limit)
            ->field('id, title, image_url, link_url, create_time')
            ->select();

        if (!$sliders || $sliders->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($sliders as $slider) {
            $result[] = [
                'id' => $slider->id,
                'title' => $slider->title,
                'url' => $slider->image_url,
                'link' => $slider->link_url ?: '#',
                'type' => 'slider'
            ];
        }

        return $result;
    }
}
