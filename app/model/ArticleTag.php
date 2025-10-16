<?php

namespace app\model;

use think\model\Pivot;

/**
 * 文章标签关联模型
 */
class ArticleTag extends Pivot
{
    protected $name = 'article_tags';

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'create_time';
    protected $updateTime = false;
}
