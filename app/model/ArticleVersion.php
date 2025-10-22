<?php

namespace app\model;

use think\Model;

/**
 * 文章版本模型
 */
class ArticleVersion extends Model
{
    protected $name = 'article_versions';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'article_id'    => 'integer',
        'version_number' => 'integer',
        'category_id'   => 'integer',
        'user_id'       => 'integer',
        'view_count'    => 'integer',
        'like_count'    => 'integer',
        'comment_count' => 'integer',
        'is_top'        => 'integer',
        'is_recommend'  => 'integer',
        'is_hot'        => 'integer',
        'sort'          => 'integer',
        'status'        => 'integer',
        'created_by'    => 'integer',
        'tags'          => 'json',
        'images'        => 'json',
    ];

    /**
     * 关联文章
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * 关联作者
     */
    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id', 'id');
    }

    /**
     * 关联创建版本的用户
     */
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by', 'id');
    }

    /**
     * 获取版本数据快照（用于对比）
     * @return array
     */
    public function getSnapshot()
    {
        return [
            'title'           => $this->title,
            'slug'            => $this->slug,
            'summary'         => $this->summary,
            'content'         => $this->content,
            'cover_image'     => $this->cover_image,
            'images'          => $this->images,
            'category_id'     => $this->category_id,
            'tags'            => $this->tags,
            'author'          => $this->author,
            'source'          => $this->source,
            'source_url'      => $this->source_url,
            'is_top'          => $this->is_top,
            'is_recommend'    => $this->is_recommend,
            'is_hot'          => $this->is_hot,
            'publish_time'    => $this->publish_time,
            'seo_title'       => $this->seo_title,
            'seo_keywords'    => $this->seo_keywords,
            'seo_description' => $this->seo_description,
            'sort'            => $this->sort,
            'status'          => $this->status,
            'flags'           => $this->flags,
        ];
    }

    /**
     * 从文章创建版本快照
     * @param Article $article 文章模型
     * @param int $createdBy 创建版本的用户ID
     * @param string|null $changeLog 修改说明
     * @return ArticleVersion
     */
    public static function createFromArticle($article, $createdBy = null, $changeLog = null)
    {
        // 获取该文章的最新版本号
        $latestVersion = self::where('article_id', $article->id)
            ->order('version_number', 'desc')
            ->value('version_number');

        $versionNumber = $latestVersion ? $latestVersion + 1 : 1;

        // 获取文章的标签
        $tags = [];
        if ($article->tags) {
            $tags = $article->tags->column('id', 'name');
        }

        $version = new self();
        $version->article_id      = $article->id;
        $version->version_number  = $versionNumber;
        $version->title           = $article->title;
        $version->slug            = $article->slug;
        $version->summary         = $article->summary;
        $version->content         = $article->content;
        $version->cover_image     = $article->cover_image;
        $version->images          = $article->images;
        $version->category_id     = $article->category_id;
        $version->user_id         = $article->user_id;
        $version->tags            = $tags;
        $version->author          = $article->author;
        $version->source          = $article->source;
        $version->source_url      = $article->source_url;
        $version->view_count      = $article->view_count;
        $version->like_count      = $article->like_count;
        $version->comment_count   = $article->comment_count;
        $version->is_top          = $article->is_top;
        $version->is_recommend    = $article->is_recommend;
        $version->is_hot          = $article->is_hot;
        $version->publish_time    = $article->publish_time;
        $version->seo_title       = $article->seo_title;
        $version->seo_keywords    = $article->seo_keywords;
        $version->seo_description = $article->seo_description;
        $version->sort            = $article->sort;
        $version->status          = $article->status;
        $version->flags           = $article->flags;
        $version->change_log      = $changeLog;
        $version->created_by      = $createdBy;

        $version->save();

        return $version;
    }

    /**
     * 对比两个版本的差异
     * @param ArticleVersion $newVersion 新版本
     * @param ArticleVersion $oldVersion 旧版本
     * @return array 差异数组
     */
    public static function compareVersions($newVersion, $oldVersion)
    {
        $newData = $newVersion->getSnapshot();
        $oldData = $oldVersion->getSnapshot();

        $diff = [];
        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;

            if ($newValue != $oldValue) {
                $diff[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $diff;
    }
}
