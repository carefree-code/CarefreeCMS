<?php
namespace app\taglib;

use think\template\TagLib;

/**
 * Carefree 自定义标签库
 * 扩展ThinkPHP模板引擎，提供CMS常用标签
 */
class Carefree extends TagLib
{
    /**
     * 定义标签列表
     * attr: 标签属性
     * close: 是否闭合标签 1-闭合 0-不闭合
     */
    protected $tags = [
        // 文章列表标签
        'article' => ['attr' => 'typeid,tagid,userid,limit,offset,order,flag,titlelen,hascover,exclude,days,id,empty', 'close' => 1],

        // 分类列表标签
        'category' => ['attr' => 'parent,limit,id,empty', 'close' => 1],

        // 标签列表标签
        'tag' => ['attr' => 'limit,order,id,empty', 'close' => 1],

        // 网站配置标签
        'config' => ['attr' => 'name', 'close' => 0],

        // 导航菜单标签
        'nav' => ['attr' => 'limit,id', 'close' => 1],

        // 友情链接标签
        'link' => ['attr' => 'group,limit,id', 'close' => 1],

        // 面包屑导航标签
        'breadcrumb' => ['attr' => 'separator,id', 'close' => 1],

        // 单篇文章标签
        'arcinfo' => ['attr' => 'aid', 'close' => 1],

        // 单个分类标签
        'catinfo' => ['attr' => 'catid', 'close' => 1],

        // 单个标签标签
        'taginfo' => ['attr' => 'tagid', 'close' => 1],

        // 幻灯片标签
        'slider' => ['attr' => 'group,limit,id,empty', 'close' => 1],

        // 分页标签
        'pagelist' => ['attr' => 'total,pagesize,currentpage,url,style', 'close' => 0],

        // 广告标签
        'ad' => ['attr' => 'position,limit,id,empty', 'close' => 1],

        // 统计标签
        'stats' => ['attr' => 'type,catid', 'close' => 0],

        // 相关文章标签
        'related' => ['attr' => 'aid,limit,type,id,empty', 'close' => 1],

        // 标签云
        'tagcloud' => ['attr' => 'limit,orderby,minsize,maxsize,style', 'close' => 0],

        // 搜索框
        'search' => ['attr' => 'action,placeholder,button,class', 'close' => 0],

        // 评论列表
        'comment' => ['attr' => 'limit,aid,type,id,empty', 'close' => 1],

        // 用户信息
        'userinfo' => ['attr' => 'uid,id', 'close' => 1],

        // 作者列表
        'author' => ['attr' => 'limit,orderby,id,empty', 'close' => 1],

        // 归档列表
        'archive' => ['attr' => 'type,limit,format,id,empty', 'close' => 1],

        // SEO标签
        'seo' => ['attr' => 'title,keywords,description,image,type', 'close' => 0],

        // 社交分享
        'share' => ['attr' => 'platforms,size,style', 'close' => 0],

        // 前台用户列表
        'frontuser' => ['attr' => 'limit,level,isvip,status,orderby,id,empty', 'close' => 1],

        // 会员等级列表
        'memberlevel' => ['attr' => 'limit,id,empty', 'close' => 1],

        // 消息通知列表
        'notification' => ['attr' => 'limit,userid,type,isread,id,empty', 'close' => 1],

        // 投稿列表
        'contribution' => ['attr' => 'limit,status,userid,orderby,id,empty', 'close' => 1],

        // 专题列表
        'topic' => ['attr' => 'limit,status,orderby,id,empty', 'close' => 1],

        // 专题信息
        'topicinfo' => ['attr' => 'topicid', 'close' => 1],

        // 单页列表
        'page' => ['attr' => 'id,alias,limit,name,empty', 'close' => 1],

        // 单页详细信息
        'pageinfo' => ['attr' => 'id,alias', 'close' => 1],

        // 上一篇/下一篇导航
        'prevnext' => ['attr' => 'aid,catid,type', 'close' => 1],

        // 自定义字段值
        'customfield' => ['attr' => 'aid,catid,pageid,fieldname,modeltype', 'close' => 0],

        // 文章属性列表
        'articleflag' => ['attr' => 'limit,status,id,empty', 'close' => 1],

        // 排行榜标签
        'rank' => ['attr' => 'type,limit,catid,days,id,empty', 'close' => 1],

        // 通用循环标签
        'loop' => ['attr' => 'data,id,key,empty', 'close' => 1],

        // SQL查询标签
        'sql' => ['attr' => 'sql,id,empty', 'close' => 1],

        // 内容位置/区块标签
        'position' => ['attr' => 'name,id,empty', 'close' => 1],

        // 热门关键词标签
        'hotwords' => ['attr' => 'limit,days,orderby,id,empty', 'close' => 1],

        // 随机图片标签
        'randomimg' => ['attr' => 'limit,source,id,empty', 'close' => 1],
    ];

    /**
     * 文章列表标签
     * {carefree:article typeid='1' limit='10' order='create_time desc' flag='hot' titlelen='30' id='article'}
     *     <a href="/article/{$article.id}.html">{$article.title}</a>
     * {/carefree:article}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string 编译后的PHP代码
     */
    public function tagArticle($tag, $content)
    {
        // 解析属性
        $typeid = $tag['typeid'] ?? 0;
        $limit = $tag['limit'] ?? 10;
        $order = $tag['order'] ?? 'create_time desc';
        $flag = $tag['flag'] ?? '';
        $titlelen = $tag['titlelen'] ?? 0;
        $id = $tag['id'] ?? 'article';  // 循环变量名
        $empty = $tag['empty'] ?? '';  // 空数据提示

        // 生成唯一变量名避免冲突
        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $mod = !empty($tag['mod']) ? $tag['mod'] : 'mod';
        $i = 'i';

        // 构建PHP代码
        $parseStr = '<?php ';
        $parseStr .= '$__articles__ = \app\service\tag\ArticleTagService::getList([';
        $parseStr .= "'typeid' => {$typeid}, ";
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'order' => '{$order}', ";
        $parseStr .= "'flag' => '{$flag}', ";
        $parseStr .= "'titlelen' => {$titlelen}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__articles__)): ';
        $parseStr .= 'foreach($__articles__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '$' . $mod . ' = ($' . $key . ' % 2); ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        // 添加空数据处理
        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 分类列表标签
     * {carefree:category parent='0' limit='10' id='cat'}
     *     <a href="/category/{$cat.id}.html">{$cat.name}</a>
     * {/carefree:category}
     */
    public function tagCategory($tag, $content)
    {
        $parent = $tag['parent'] ?? 0;
        $limit = $tag['limit'] ?? 0;
        $id = $tag['id'] ?? 'category';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__categories__ = \app\service\tag\CategoryTagService::getList([';
        $parseStr .= "'parent' => {$parent}, ";
        $parseStr .= "'limit' => {$limit}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__categories__)): ';
        $parseStr .= 'foreach($__categories__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 标签列表标签
     * {carefree:tag limit='20' order='article_count desc' id='tag'}
     *     <a href="/tag/{$tag.id}.html">{$tag.name}</a>
     * {/carefree:tag}
     */
    public function tagTag($tag, $content)
    {
        $limit = $tag['limit'] ?? 0;
        $order = $tag['order'] ?? 'sort asc';
        $id = $tag['id'] ?? 'tag';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__tags__ = \app\service\tag\TagTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'order' => '{$order}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__tags__)): ';
        $parseStr .= 'foreach($__tags__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 网站配置标签
     * {carefree:config name='web_name' /}
     */
    public function tagConfig($tag, $content)
    {
        $name = $tag['name'] ?? '';

        $parseStr = '<?php ';
        $parseStr .= 'echo \app\service\tag\ConfigTagService::get("' . $name . '"); ';
        $parseStr .= '?>';

        return $parseStr;
    }

    /**
     * 导航菜单标签
     * {carefree:nav limit='10' id='nav'}
     *     <a href="{$nav.url}">{$nav.title}</a>
     * {/carefree:nav}
     */
    public function tagNav($tag, $content)
    {
        $limit = $tag['limit'] ?? 0;
        $id = $tag['id'] ?? 'nav';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__navs__ = \app\service\tag\NavTagService::getList([';
        $parseStr .= "'limit' => {$limit}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__navs__)): ';
        $parseStr .= 'foreach($__navs__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; endif; ?>';

        return $parseStr;
    }

    /**
     * 友情链接标签
     * {carefree:link group='1' limit='10' id='link'}
     *     <a href="{$link.url}">{$link.title}</a>
     * {/carefree:link}
     */
    public function tagLink($tag, $content)
    {
        $group = $tag['group'] ?? 1;
        $limit = $tag['limit'] ?? 0;
        $id = $tag['id'] ?? 'link';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__links__ = \app\service\tag\LinkTagService::getList([';
        $parseStr .= "'group' => {$group}, ";
        $parseStr .= "'limit' => {$limit}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__links__)): ';
        $parseStr .= 'foreach($__links__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 面包屑导航标签
     * {carefree:breadcrumb separator=' > ' id='item'}
     *     <a href="{$item.url}">{$item.title}</a>
     * {/carefree:breadcrumb}
     */
    public function tagBreadcrumb($tag, $content)
    {
        $separator = $tag['separator'] ?? ' > ';
        $id = $tag['id'] ?? 'item';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__breadcrumbs__ = \app\service\tag\BreadcrumbTagService::get(); ';

        $parseStr .= 'if(!empty($__breadcrumbs__)): ';
        $parseStr .= 'foreach($__breadcrumbs__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; endif; ?>';

        return $parseStr;
    }

    /**
     * 单篇文章标签
     * {carefree:arcinfo aid='1'}
     *     <h1>{$article.title}</h1>
     *     <div>{$article.content}</div>
     * {/carefree:arcinfo}
     */
    public function tagArcinfo($tag, $content)
    {
        $aid = $tag['aid'] ?? 0;

        $parseStr = '<?php ';
        $parseStr .= '$article = \app\service\tag\ArticleTagService::getOne(' . $aid . '); ';
        $parseStr .= 'if($article): ?>';

        $parseStr .= $content;

        $parseStr .= '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * 单个分类标签
     * {carefree:catinfo catid='1'}
     *     <h1>{$category.name}</h1>
     *     <p>{$category.description}</p>
     * {/carefree:catinfo}
     */
    public function tagCatinfo($tag, $content)
    {
        $catid = $tag['catid'] ?? 0;

        $parseStr = '<?php ';
        $parseStr .= '$category = \app\service\tag\CategoryTagService::getOne(' . $catid . '); ';
        $parseStr .= 'if($category): ?>';

        $parseStr .= $content;

        $parseStr .= '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * 单个标签标签
     * {carefree:taginfo tagid='1'}
     *     <h1>{$tag.name}</h1>
     *     <p>{$tag.description}</p>
     * {/carefree:taginfo}
     */
    public function tagTaginfo($tag, $content)
    {
        $tagid = $tag['tagid'] ?? 0;

        $parseStr = '<?php ';
        $parseStr .= '$tag = \app\service\tag\TagTagService::getOne(' . $tagid . '); ';
        $parseStr .= 'if($tag): ?>';

        $parseStr .= $content;

        $parseStr .= '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * 幻灯片标签
     * {carefree:slider group='1' limit='5' id='slide'}
     *     <div class="slide-item">
     *         <img src="{$slide.image}" alt="{$slide.title}">
     *         <h3>{$slide.title}</h3>
     *     </div>
     * {/carefree:slider}
     */
    public function tagSlider($tag, $content)
    {
        $group = $tag['group'] ?? 1;
        $limit = $tag['limit'] ?? 0;
        $id = $tag['id'] ?? 'slide';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__sliders__ = \app\service\tag\SliderTagService::getList([';
        $parseStr .= "'group' => {$group}, ";
        $parseStr .= "'limit' => {$limit}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__sliders__)): ';
        $parseStr .= 'foreach($__sliders__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 分页标签
     * {carefree:pagelist total='100' pagesize='10' currentpage='1' url='/articles/page-{page}.html' style='full' /}
     */
    public function tagPagelist($tag, $content)
    {
        $total = $tag['total'] ?? '$total';  // 允许使用变量
        $pagesize = $tag['pagesize'] ?? '$pagesize';
        $currentpage = $tag['currentpage'] ?? '$current_page';
        $url = $tag['url'] ?? '';
        $style = $tag['style'] ?? 'full';

        // 使用autoBuildVar解析变量参数
        $totalVar = $this->autoBuildVar($total);
        $pagesizeVar = $this->autoBuildVar($pagesize);
        $currentpageVar = $this->autoBuildVar($currentpage);

        $parseStr = '<?php ';
        $parseStr .= 'echo \app\service\tag\PageTagService::render([';
        $parseStr .= "'total' => {$totalVar}, ";
        $parseStr .= "'pagesize' => {$pagesizeVar}, ";
        $parseStr .= "'currentpage' => {$currentpageVar}, ";
        $parseStr .= "'url' => '{$url}', ";
        $parseStr .= "'style' => '{$style}'";
        $parseStr .= ']); ';
        $parseStr .= '?>';

        return $parseStr;
    }

    /**
     * 广告标签
     * {carefree:ad position='1' limit='3' id='ad'}
     *     <div class="ad-item">
     *         <a href="{$ad.link_url}">
     *             <img src="{$ad.images}" alt="{$ad.name}">
     *         </a>
     *     </div>
     * {/carefree:ad}
     */
    public function tagAd($tag, $content)
    {
        $position = $tag['position'] ?? 1;
        $limit = $tag['limit'] ?? 0;
        $id = $tag['id'] ?? 'ad';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        // 判断position是否为数字，如果不是数字则作为字符串处理
        $positionValue = is_numeric($position) ? $position : "'{$position}'";

        $parseStr = '<?php ';
        $parseStr .= '$__ads__ = \app\service\tag\AdTagService::getList([';
        $parseStr .= "'position' => {$positionValue}, ";
        $parseStr .= "'limit' => {$limit}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__ads__)): ';
        $parseStr .= 'foreach($__ads__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 统计标签
     * {carefree:stats type='article' /}
     * {carefree:stats type='view' catid='1' /}
     *
     * 支持的统计类型：
     * - article: 文章总数
     * - category: 分类总数
     * - tag: 标签总数
     * - view: 总浏览量
     * - todayarticle: 今日文章数
     * - todayview: 今日浏览量
     *
     * @param array $tag 标签属性
     * @return string
     */
    public function tagStats($tag)
    {
        $type = $tag['type'] ?? 'article';
        $catid = $tag['catid'] ?? 0;

        $parseStr = '<?php echo \app\service\tag\StatsTagService::get([';
        $parseStr .= "'type' => '{$type}', ";
        $parseStr .= "'catid' => {$catid}";
        $parseStr .= ']); ?>';

        return $parseStr;
    }

    /**
     * 相关文章标签
     * {carefree:related aid='$article.id' limit='5' type='auto' id='related'}
     *     <div class="related-item">
     *         <a href="/article/{$related.id}.html">{$related.title}</a>
     *     </div>
     * {/carefree:related}
     *
     * type 类型说明：
     * - auto: 自动推荐（优先同标签，不足则同分类）
     * - category: 同分类推荐
     * - tag: 同标签推荐
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagRelated($tag, $content)
    {
        $aid = $tag['aid'] ?? 0;
        $limit = $tag['limit'] ?? 5;
        $type = $tag['type'] ?? 'auto';
        $id = $tag['id'] ?? 'related';
        $empty = $tag['empty'] ?? '';

        // 使用autoBuildVar解析aid参数
        $aidVar = $this->autoBuildVar($aid);

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__related_articles__ = \app\service\tag\RelatedTagService::getList([';
        $parseStr .= "'aid' => " . $aidVar . ", ";
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'type' => '{$type}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__related_articles__)): ';
        $parseStr .= 'foreach($__related_articles__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 标签云标签
     * {carefree:tagcloud limit='30' orderby='count' minsize='12' maxsize='28' style='html' /}
     *
     * orderby 排序方式：
     * - count: 按使用次数排序（默认）
     * - name: 按标签名称排序
     * - random: 随机排序
     *
     * style 输出方式：
     * - html: 直接输出HTML（默认）
     * - data: 输出数据数组
     *
     * @param array $tag 标签属性
     * @return string
     */
    public function tagTagcloud($tag)
    {
        $limit = $tag['limit'] ?? 30;
        $orderby = $tag['orderby'] ?? 'count';
        $minsize = $tag['minsize'] ?? 12;
        $maxsize = $tag['maxsize'] ?? 28;
        $style = $tag['style'] ?? 'html';

        if ($style === 'html') {
            // 直接输出HTML
            $parseStr = '<?php echo \app\service\tag\TagCloudService::render([';
            $parseStr .= "'limit' => {$limit}, ";
            $parseStr .= "'orderby' => '{$orderby}', ";
            $parseStr .= "'minsize' => {$minsize}, ";
            $parseStr .= "'maxsize' => {$maxsize}";
            $parseStr .= ']); ?>';
        } else {
            // 输出数据数组供模板使用
            $parseStr = '<?php $__tagcloud__ = \app\service\tag\TagCloudService::get([';
            $parseStr .= "'limit' => {$limit}, ";
            $parseStr .= "'orderby' => '{$orderby}', ";
            $parseStr .= "'minsize' => {$minsize}, ";
            $parseStr .= "'maxsize' => {$maxsize}";
            $parseStr .= ']); ?>';
        }

        return $parseStr;
    }

    /**
     * 搜索框标签
     * {carefree:search action='/search' placeholder='请输入关键词' button='搜索' class='search-form' /}
     *
     * @param array $tag 标签属性
     * @return string
     */
    public function tagSearch($tag)
    {
        $action = $tag['action'] ?? '/search';
        $placeholder = $tag['placeholder'] ?? '请输入关键词...';
        $button = $tag['button'] ?? '搜索';
        $class = $tag['class'] ?? 'search-form';

        $html = '<form action="' . $action . '" method="get" class="' . $class . '">';
        $html .= '<div class="search-input-wrapper">';
        $html .= '<input type="text" name="q" class="search-input" placeholder="' . htmlspecialchars($placeholder) . '" required>';
        $html .= '<button type="submit" class="search-button">' . htmlspecialchars($button) . '</button>';
        $html .= '</div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * 评论列表标签
     * {carefree:comment limit='10' aid='0' type='latest' id='comment'}
     *     <div class="comment-item">
     *         <div class="comment-author">{$comment.display_name}</div>
     *         <div class="comment-content">{$comment.short_content}</div>
     *         <div class="comment-time">{$comment.formatted_time}</div>
     *     </div>
     * {/carefree:comment}
     *
     * type 类型说明：
     * - latest: 最新评论（默认）
     * - hot: 热门评论（按点赞数排序）
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagComment($tag, $content)
    {
        $limit = $tag['limit'] ?? 10;
        $aid = $tag['aid'] ?? 0;
        $type = $tag['type'] ?? 'latest';
        $id = $tag['id'] ?? 'comment';
        $empty = $tag['empty'] ?? '';

        // 使用autoBuildVar解析aid参数
        $aidVar = $this->autoBuildVar($aid);

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';

        if ($type === 'hot') {
            // 热门评论
            $parseStr .= '$__comments__ = \app\service\tag\CommentTagService::getHot([';
            $parseStr .= "'limit' => {$limit}";
            $parseStr .= ']); ';
        } else {
            // 最新评论
            $parseStr .= '$__comments__ = \app\service\tag\CommentTagService::getList([';
            $parseStr .= "'limit' => {$limit}, ";
            $parseStr .= "'aid' => " . $aidVar;
            $parseStr .= ']); ';
        }

        $parseStr .= 'if(!empty($__comments__)): ';
        $parseStr .= 'foreach($__comments__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 用户信息标签
     * {carefree:userinfo uid='$article.user_id'}
     *     <div class="author-info">
     *         <img src="{$userinfo.avatar}" alt="{$userinfo.display_name}">
     *         <div class="author-name">{$userinfo.display_name}</div>
     *         <div class="author-stats">
     *             文章: {$userinfo.article_count} | 浏览: {$userinfo.total_views}
     *         </div>
     *     </div>
     * {/carefree:userinfo}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagUserinfo($tag, $content)
    {
        $uid = $tag['uid'] ?? 0;
        $id = $tag['id'] ?? 'user';

        // 使用autoBuildVar解析uid参数
        $uidVar = $this->autoBuildVar($uid);

        $parseStr = '<?php ';
        $parseStr .= '$' . $id . ' = \app\service\tag\UserInfoService::get(' . $uidVar . '); ';
        $parseStr .= 'if($' . $id . '): ?>';

        $parseStr .= $content;

        $parseStr .= '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * 作者列表标签
     * {carefree:author limit='10' orderby='article' id='author'}
     *     <div class="author-item">
     *         <img src="{$author.avatar}" alt="{$author.display_name}">
     *         <div class="author-name">{$author.display_name}</div>
     *         <div class="author-stats">
     *             {$author.article_count} 篇文章 • {$author.total_views} 阅读
     *         </div>
     *     </div>
     * {/carefree:author}
     *
     * orderby 排序方式：
     * - article: 按发文数排序（默认）
     * - view: 按总浏览量排序
     * - like: 按总点赞数排序
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagAuthor($tag, $content)
    {
        $limit = $tag['limit'] ?? 10;
        $orderby = $tag['orderby'] ?? 'article';
        $id = $tag['id'] ?? 'author';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__authors__ = \app\service\tag\AuthorTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'orderby' => '{$orderby}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__authors__)): ';
        $parseStr .= 'foreach($__authors__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 归档列表标签
     * {carefree:archive type='month' limit='12' format='Y年m月' id='archive'}
     *     <div class="archive-item">
     *         <a href="{$archive.url}">
     *             {$archive.display_date} ({$archive.article_count})
     *         </a>
     *     </div>
     * {/carefree:archive}
     *
     * type 归档类型：
     * - year: 按年归档
     * - month: 按月归档（默认）
     * - day: 按日归档
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagArchive($tag, $content)
    {
        $type = $tag['type'] ?? 'month';
        $limit = $tag['limit'] ?? 12;
        $format = $tag['format'] ?? 'Y年m月';
        $id = $tag['id'] ?? 'archive';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__archives__ = \app\service\tag\ArchiveTagService::getList([';
        $parseStr .= "'type' => '{$type}', ";
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'format' => '{$format}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__archives__)): ';
        $parseStr .= 'foreach($__archives__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * SEO标签
     * {carefree:seo title='$article.seo_title' keywords='$article.seo_keywords' description='$article.seo_description' image='$article.cover_image' type='article' /}
     *
     * 自动生成完整的SEO meta标签，包括：
     * - 基础meta标签
     * - Open Graph标签
     * - Twitter Card标签
     *
     * @param array $tag 标签属性
     * @return string
     */
    public function tagSeo($tag)
    {
        $title = $tag['title'] ?? '';
        $keywords = $tag['keywords'] ?? '';
        $description = $tag['description'] ?? '';
        $image = $tag['image'] ?? '';
        $type = $tag['type'] ?? 'website';

        // 使用 autoBuildVar 方法将模板变量转换为正确的PHP语法
        $titleVar = $title ? $this->autoBuildVar($title) : '""';
        $keywordsVar = $keywords ? $this->autoBuildVar($keywords) : '""';
        $descriptionVar = $description ? $this->autoBuildVar($description) : '""';
        $imageVar = $image ? $this->autoBuildVar($image) : '""';

        $parseStr = '<?php ';
        $parseStr .= '$__seo_title = ' . $titleVar . '; ';
        $parseStr .= '$__seo_keywords = ' . $keywordsVar . '; ';
        $parseStr .= '$__seo_description = ' . $descriptionVar . '; ';
        $parseStr .= '$__seo_image = ' . $imageVar . '; ';
        $parseStr .= '$__seo_scheme = $_SERVER["REQUEST_SCHEME"] ?? (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? "https" : "http"); ';
        $parseStr .= '$__seo_url = $__seo_scheme . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost") . ($_SERVER["REQUEST_URI"] ?? "/"); ';
        $parseStr .= '?>';

        $parseStr .= '<!-- SEO Meta Tags -->';
        $parseStr .= '<meta name="keywords" content="<?php echo htmlspecialchars($__seo_keywords); ?>">';
        $parseStr .= '<meta name="description" content="<?php echo htmlspecialchars($__seo_description); ?>">';

        $parseStr .= '<!-- Open Graph / Facebook -->';
        $parseStr .= '<meta property="og:type" content="' . $type . '">';
        $parseStr .= '<meta property="og:url" content="<?php echo htmlspecialchars($__seo_url); ?>">';
        $parseStr .= '<meta property="og:title" content="<?php echo htmlspecialchars($__seo_title); ?>">';
        $parseStr .= '<meta property="og:description" content="<?php echo htmlspecialchars($__seo_description); ?>">';
        $parseStr .= '<?php if(!empty($__seo_image)): ?>';
        $parseStr .= '<meta property="og:image" content="<?php echo htmlspecialchars($__seo_image); ?>">';
        $parseStr .= '<?php endif; ?>';

        $parseStr .= '<!-- Twitter -->';
        $parseStr .= '<meta property="twitter:card" content="summary_large_image">';
        $parseStr .= '<meta property="twitter:url" content="<?php echo htmlspecialchars($__seo_url); ?>">';
        $parseStr .= '<meta property="twitter:title" content="<?php echo htmlspecialchars($__seo_title); ?>">';
        $parseStr .= '<meta property="twitter:description" content="<?php echo htmlspecialchars($__seo_description); ?>">';
        $parseStr .= '<?php if(!empty($__seo_image)): ?>';
        $parseStr .= '<meta property="twitter:image" content="<?php echo htmlspecialchars($__seo_image); ?>">';
        $parseStr .= '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * 社交分享标签
     * {carefree:share platforms='wechat,weibo,qq,twitter,facebook' size='normal' style='icon' /}
     *
     * 生成社交分享按钮
     *
     * @param array $tag 标签属性
     * @return string
     */
    public function tagShare($tag)
    {
        $platforms = $tag['platforms'] ?? 'wechat,weibo,qq,twitter,facebook';
        $size = $tag['size'] ?? 'normal';
        $style = $tag['style'] ?? 'icon';

        $platformList = explode(',', $platforms);

        $html = '<div class="social-share share-' . $style . ' share-' . $size . '">';

        foreach ($platformList as $platform) {
            $platform = trim($platform);

            switch ($platform) {
                case 'wechat':
                    $html .= '<a href="javascript:;" class="share-wechat" title="分享到微信">';
                    $html .= '<i class="icon-wechat"></i>';
                    if ($style !== 'icon') {
                        $html .= '<span>微信</span>';
                    }
                    $html .= '</a>';
                    break;

                case 'weibo':
                    $html .= '<a href="javascript:;" class="share-weibo" title="分享到微博">';
                    $html .= '<i class="icon-weibo"></i>';
                    if ($style !== 'icon') {
                        $html .= '<span>微博</span>';
                    }
                    $html .= '</a>';
                    break;

                case 'qq':
                    $html .= '<a href="javascript:;" class="share-qq" title="分享到QQ">';
                    $html .= '<i class="icon-qq"></i>';
                    if ($style !== 'icon') {
                        $html .= '<span>QQ</span>';
                    }
                    $html .= '</a>';
                    break;

                case 'twitter':
                    $html .= '<a href="javascript:;" class="share-twitter" title="分享到Twitter">';
                    $html .= '<i class="icon-twitter"></i>';
                    if ($style !== 'icon') {
                        $html .= '<span>Twitter</span>';
                    }
                    $html .= '</a>';
                    break;

                case 'facebook':
                    $html .= '<a href="javascript:;" class="share-facebook" title="分享到Facebook">';
                    $html .= '<i class="icon-facebook"></i>';
                    if ($style !== 'icon') {
                        $html .= '<span>Facebook</span>';
                    }
                    $html .= '</a>';
                    break;

                case 'linkedin':
                    $html .= '<a href="javascript:;" class="share-linkedin" title="分享到LinkedIn">';
                    $html .= '<i class="icon-linkedin"></i>';
                    if ($style !== 'icon') {
                        $html .= '<span>LinkedIn</span>';
                    }
                    $html .= '</a>';
                    break;
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * 前台用户列表标签
     * {carefree:frontuser limit='10' level='1' isvip='1' status='1' orderby='points' id='user'}
     *     <div class="user-item">
     *         <div class="user-avatar">
     *             <img src="{$user.avatar}" alt="{$user.nickname}">
     *         </div>
     *         <div class="user-info">
     *             <div class="user-name">{$user.nickname}</div>
     *             <div class="user-level">等级: {$user.level_name}</div>
     *             <div class="user-points">积分: {$user.points}</div>
     *         </div>
     *     </div>
     * {/carefree:frontuser}
     *
     * orderby 排序方式：
     * - points: 按积分排序（默认）
     * - create_time: 按注册时间排序
     * - login_time: 按最后登录时间排序
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagFrontuser($tag, $content)
    {
        $limit = $tag['limit'] ?? 10;
        $level = $tag['level'] ?? '';
        $isvip = $tag['isvip'] ?? '';
        $status = $tag['status'] ?? '';
        $orderby = $tag['orderby'] ?? 'points';
        $id = $tag['id'] ?? 'user';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__frontusers__ = \app\service\tag\FrontUserTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'level' => '{$level}', ";
        $parseStr .= "'isvip' => '{$isvip}', ";
        $parseStr .= "'status' => '{$status}', ";
        $parseStr .= "'orderby' => '{$orderby}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__frontusers__)): ';
        $parseStr .= 'foreach($__frontusers__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 会员等级列表标签
     * {carefree:memberlevel limit='10' id='level'}
     *     <div class="level-item">
     *         <div class="level-name">{$level.name}</div>
     *         <div class="level-info">
     *             升级条件: {$level.upgrade_points} 积分
     *         </div>
     *         <div class="level-benefits">{$level.description}</div>
     *     </div>
     * {/carefree:memberlevel}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagMemberlevel($tag, $content)
    {
        $limit = $tag['limit'] ?? 0;
        $id = $tag['id'] ?? 'level';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__memberlevels__ = \app\service\tag\MemberLevelTagService::getList([';
        $parseStr .= "'limit' => {$limit}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__memberlevels__)): ';
        $parseStr .= 'foreach($__memberlevels__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 消息通知列表标签
     * {carefree:notification limit='10' userid='$user.id' type='system' isread='0' id='notice'}
     *     <div class="notice-item">
     *         <div class="notice-title">{$notice.title}</div>
     *         <div class="notice-content">{$notice.content}</div>
     *         <div class="notice-time">{$notice.create_time}</div>
     *     </div>
     * {/carefree:notification}
     *
     * type 类型：
     * - system: 系统通知
     * - reply: 评论回复
     * - like: 点赞通知
     * - follow: 关注通知
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagNotification($tag, $content)
    {
        $limit = $tag['limit'] ?? 10;
        $userid = $tag['userid'] ?? '';
        $type = $tag['type'] ?? '';
        $isread = $tag['isread'] ?? '';
        $id = $tag['id'] ?? 'notice';
        $empty = $tag['empty'] ?? '';

        // 使用autoBuildVar解析userid参数
        $useridVar = $userid ? $this->autoBuildVar($userid) : '""';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__notifications__ = \app\service\tag\NotificationTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'userid' => " . $useridVar . ", ";
        $parseStr .= "'type' => '{$type}', ";
        $parseStr .= "'isread' => '{$isread}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__notifications__)): ';
        $parseStr .= 'foreach($__notifications__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 投稿列表标签
     * {carefree:contribution limit='10' status='1' userid='$user.id' orderby='create_time' id='contrib'}
     *     <div class="contrib-item">
     *         <div class="contrib-title">{$contrib.title}</div>
     *         <div class="contrib-author">{$contrib.author}</div>
     *         <div class="contrib-status">{$contrib.status_text}</div>
     *         <div class="contrib-time">{$contrib.create_time}</div>
     *     </div>
     * {/carefree:contribution}
     *
     * status 状态：
     * - 0: 待审核
     * - 1: 已通过
     * - 2: 已拒绝
     *
     * orderby 排序方式：
     * - create_time: 按创建时间排序（默认）
     * - update_time: 按更新时间排序
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagContribution($tag, $content)
    {
        $limit = $tag['limit'] ?? 10;
        $status = $tag['status'] ?? '';
        $userid = $tag['userid'] ?? '';
        $orderby = $tag['orderby'] ?? 'create_time';
        $id = $tag['id'] ?? 'contrib';
        $empty = $tag['empty'] ?? '';

        // 使用autoBuildVar解析userid参数
        $useridVar = $userid ? $this->autoBuildVar($userid) : '""';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__contributions__ = \app\service\tag\ContributionTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'status' => '{$status}', ";
        $parseStr .= "'userid' => " . $useridVar . ", ";
        $parseStr .= "'orderby' => '{$orderby}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__contributions__)): ';
        $parseStr .= 'foreach($__contributions__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 专题列表标签
     * {carefree:topic limit='10' status='1' orderby='sort' id='topic'}
     *     <div class="topic-item">
     *         <img src="{$topic.cover_image}" alt="{$topic.name}">
     *         <h3>{$topic.name}</h3>
     *         <p>{$topic.description}</p>
     *         <div class="topic-stats">
     *             文章数: {$topic.article_count} | 浏览: {$topic.view_count}
     *         </div>
     *     </div>
     * {/carefree:topic}
     *
     * orderby 排序方式：
     * - sort: 按排序号排序（默认）
     * - view_count: 按浏览量排序
     * - article_count: 按文章数排序
     * - create_time: 按创建时间排序
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagTopic($tag, $content)
    {
        $limit = $tag['limit'] ?? 10;
        $status = $tag['status'] ?? '';
        $orderby = $tag['orderby'] ?? 'sort_order';
        $id = $tag['id'] ?? 'topic';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__topics__ = \app\service\tag\TopicTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'status' => '{$status}', ";
        $parseStr .= "'orderby' => '{$orderby}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__topics__)): ';
        $parseStr .= 'foreach($__topics__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 专题信息标签
     * {carefree:topicinfo topicid='1'}
     *     <h1>{$topic.name}</h1>
     *     <p>{$topic.description}</p>
     *     <div>文章数: {$topic.article_count}</div>
     * {/carefree:topicinfo}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagTopicinfo($tag, $content)
    {
        $topicid = $tag['topicid'] ?? 0;

        // 使用autoBuildVar解析topicid参数
        $topicidVar = $this->autoBuildVar($topicid);

        $parseStr = '<?php ';
        $parseStr .= '$topic = \app\service\tag\TopicTagService::getOne(' . $topicidVar . '); ';
        $parseStr .= 'if($topic): ?>';

        $parseStr .= $content;

        $parseStr .= '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * 单页列表/单个单页标签
     * {carefree:page limit='10' name='page'}
     *     <li><a href="/page/{$page.id}.html">{$page.title}</a></li>
     * {/carefree:page}
     *
     * {carefree:page id='1' name='page'}
     *     <h1>{$page.title}</h1>
     *     <div>{$page.content}</div>
     * {/carefree:page}
     *
     * {carefree:page alias='about' name='page'}
     *     <h1>{$page.title}</h1>
     *     <div>{$page.content}</div>
     * {/carefree:page}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagPage($tag, $content)
    {
        $pageid = $tag['id'] ?? '';
        $alias = $tag['alias'] ?? '';
        $limit = $tag['limit'] ?? 0;
        $name = $tag['name'] ?? 'page';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        // 判断是查询单个还是列表
        if (!empty($pageid) || !empty($alias)) {
            // 查询单个单页
            $parseStr = '<?php ';

            if (!empty($pageid)) {
                $pageidVar = $this->autoBuildVar($pageid);
                $parseStr .= '$' . $name . ' = \app\service\tag\PageTagService::getOne(' . $pageidVar . ', "id"); ';
            } else {
                $parseStr .= '$' . $name . ' = \app\service\tag\PageTagService::getOne("' . $alias . '", "alias"); ';
            }

            $parseStr .= 'if($' . $name . '): ?>';
            $parseStr .= $content;
            $parseStr .= '<?php endif; ?>';
        } else {
            // 查询列表
            $parseStr = '<?php ';
            $parseStr .= '$__pages__ = \app\service\tag\PageTagService::getList([';
            $parseStr .= "'limit' => {$limit}";
            $parseStr .= ']); ';

            $parseStr .= 'if(!empty($__pages__)): ';
            $parseStr .= 'foreach($__pages__ as $' . $key . ' => $' . $name . '): ';
            $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
            $parseStr .= '?>';

            $parseStr .= $content;

            $parseStr .= '<?php endforeach; ';

            if (!empty($empty)) {
                $parseStr .= 'else: ?>';
                $parseStr .= '<div class="empty-state">' . $empty . '</div>';
                $parseStr .= '<?php ';
            }

            $parseStr .= 'endif; ?>';
        }

        return $parseStr;
    }

    /**
     * 单页详细信息标签
     * {carefree:pageinfo id='1'}
     *     <h1>{$page.title}</h1>
     *     <div>{$page.content}</div>
     * {/carefree:pageinfo}
     *
     * {carefree:pageinfo alias='about'}
     *     <h1>{$page.title}</h1>
     * {/carefree:pageinfo}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagPageinfo($tag, $content)
    {
        $pageid = $tag['id'] ?? '';
        $alias = $tag['alias'] ?? '';

        $parseStr = '<?php ';

        if (!empty($pageid)) {
            $pageidVar = $this->autoBuildVar($pageid);
            $parseStr .= '$page = \app\service\tag\PageTagService::getOne(' . $pageidVar . ', "id"); ';
        } elseif (!empty($alias)) {
            $parseStr .= '$page = \app\service\tag\PageTagService::getOne("' . $alias . '", "alias"); ';
        } else {
            $parseStr .= '$page = null; ';
        }

        $parseStr .= 'if($page): ?>';
        $parseStr .= $content;
        $parseStr .= '<?php endif; ?>';

        return $parseStr;
    }

    /**
     * 上一篇/下一篇导航标签
     * {carefree:prevnext aid='$article.id' catid='$article.category_id'}
     *     {if $prev}
     *         <a href="/article/{$prev.id}.html">上一篇: {$prev.title}</a>
     *     {/if}
     *     {if $next}
     *         <a href="/article/{$next.id}.html">下一篇: {$next.title}</a>
     *     {/if}
     * {/carefree:prevnext}
     *
     * type参数: all-所有分类, same-同分类（默认）
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagPrevnext($tag, $content)
    {
        $aid = $tag['aid'] ?? 0;
        $catid = $tag['catid'] ?? 0;
        $type = $tag['type'] ?? 'same';

        $aidVar = $this->autoBuildVar($aid);
        $catidVar = $this->autoBuildVar($catid);

        $parseStr = '<?php ';
        $parseStr .= '$__prevnext__ = \app\service\tag\ArticleTagService::getPrevNext(';
        $parseStr .= $aidVar . ', ' . $catidVar . ', "' . $type . '"';
        $parseStr .= '); ';
        $parseStr .= '$prev = $__prevnext__["prev"] ?? null; ';
        $parseStr .= '$next = $__prevnext__["next"] ?? null; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        return $parseStr;
    }

    /**
     * 自定义字段值标签
     * {carefree:customfield aid='$article.id' fieldname='author_intro' modeltype='article' /}
     * {carefree:customfield pageid='1' fieldname='contact_email' modeltype='page' /}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagCustomfield($tag, $content)
    {
        $aid = $tag['aid'] ?? '';
        $catid = $tag['catid'] ?? '';
        $pageid = $tag['pageid'] ?? '';
        $fieldname = $tag['fieldname'] ?? '';
        $modeltype = $tag['modeltype'] ?? 'article';

        $parseStr = '<?php ';

        // 确定关联ID
        if (!empty($aid)) {
            $idVar = $this->autoBuildVar($aid);
        } elseif (!empty($pageid)) {
            $idVar = $this->autoBuildVar($pageid);
        } elseif (!empty($catid)) {
            $idVar = $this->autoBuildVar($catid);
        } else {
            $idVar = '0';
        }

        $parseStr .= 'echo \app\service\tag\CustomFieldTagService::getValue(';
        $parseStr .= $idVar . ', "' . $fieldname . '", "' . $modeltype . '"';
        $parseStr .= '); ';
        $parseStr .= '?>';

        return $parseStr;
    }

    /**
     * 文章属性列表标签
     * {carefree:articleflag limit='10' status='1' id='flag'}
     *     <span class="badge">{$flag.name}</span>
     * {/carefree:articleflag}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagArticleflag($tag, $content)
    {
        $limit = $tag['limit'] ?? 0;
        $status = $tag['status'] ?? '';
        $id = $tag['id'] ?? 'flag';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__flags__ = \app\service\tag\ArticleFlagTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'status' => '{$status}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__flags__)): ';
        $parseStr .= 'foreach($__flags__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 排行榜标签
     * {carefree:rank type='view' limit='10' id='item'}
     *     <li>
     *         <a href="/article/{$item.id}.html">{$item.title}</a>
     *         <span>{$item.view_count} 次浏览</span>
     *     </li>
     * {/carefree:rank}
     *
     * type参数:
     * - view: 浏览量排行
     * - comment: 评论数排行
     * - like: 点赞数排行
     * - collect: 收藏数排行
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagRank($tag, $content)
    {
        $type = $tag['type'] ?? 'view';
        $limit = $tag['limit'] ?? 10;
        $catid = $tag['catid'] ?? 0;
        $days = $tag['days'] ?? 0;
        $id = $tag['id'] ?? 'item';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__rank__ = \app\service\tag\RankTagService::getRank([';
        $parseStr .= "'type' => '{$type}', ";
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'catid' => {$catid}, ";
        $parseStr .= "'days' => {$days}";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__rank__)): ';
        $parseStr .= 'foreach($__rank__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 通用循环标签
     * {carefree:loop data='$myArray' id='item' key='index'}
     *     <div>{$index}: {$item}</div>
     * {/carefree:loop}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagLoop($tag, $content)
    {
        $data = $tag['data'] ?? '';
        $id = $tag['id'] ?? 'item';
        $key = $tag['key'] ?? 'key';
        $empty = $tag['empty'] ?? '';

        if (empty($data)) {
            return '<!-- loop tag: data parameter is required -->';
        }

        // 使用autoBuildVar解析data参数
        $dataVar = $this->autoBuildVar($data);

        $parseStr = '<?php ';
        $parseStr .= 'if(!empty(' . $dataVar . ') && is_array(' . $dataVar . ')): ';
        $parseStr .= 'foreach(' . $dataVar . ' as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * SQL查询标签
     * {carefree:sql sql="SELECT * FROM articles WHERE status=1 LIMIT 10" id='row'}
     *     <div>{$row.title}</div>
     * {/carefree:sql}
     *
     * 注意：此标签具有安全风险，仅供高级用户使用
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagSql($tag, $content)
    {
        $sql = $tag['sql'] ?? '';
        $id = $tag['id'] ?? 'row';
        $empty = $tag['empty'] ?? '';

        if (empty($sql)) {
            return '<!-- sql tag: sql parameter is required -->';
        }

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__sql_result__ = \app\service\tag\SqlTagService::query("' . addslashes($sql) . '"); ';

        $parseStr .= 'if(!empty($__sql_result__)): ';
        $parseStr .= 'foreach($__sql_result__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 内容位置/区块标签
     * {carefree:position name='sidebar' id='block'}
     *     <div class="block">
     *         <h3>{$block.title}</h3>
     *         <div>{$block.content|raw}</div>
     *     </div>
     * {/carefree:position}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagPosition($tag, $content)
    {
        $name = $tag['name'] ?? '';
        $id = $tag['id'] ?? 'block';
        $empty = $tag['empty'] ?? '';

        if (empty($name)) {
            return '<!-- position tag: name parameter is required -->';
        }

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__positions__ = \app\service\tag\PositionTagService::getByPosition("' . $name . '"); ';

        $parseStr .= 'if(!empty($__positions__)): ';
        $parseStr .= 'foreach($__positions__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 热门关键词标签
     * {carefree:hotwords limit='20' days='30' id='word'}
     *     <a href="/search?q={$word.keyword}" class="tag-{$word.level}">
     *         {$word.keyword}
     *     </a>
     * {/carefree:hotwords}
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagHotwords($tag, $content)
    {
        $limit = $tag['limit'] ?? 20;
        $days = $tag['days'] ?? 30;
        $orderby = $tag['orderby'] ?? 'count';
        $id = $tag['id'] ?? 'word';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__hotwords__ = \app\service\tag\HotwordsTagService::getList([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'days' => {$days}, ";
        $parseStr .= "'orderby' => '{$orderby}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__hotwords__)): ';
        $parseStr .= 'foreach($__hotwords__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }

    /**
     * 随机图片标签
     * {carefree:randomimg limit='5' source='article' id='img'}
     *     <img src="{$img.url}" alt="{$img.title}">
     * {/carefree:randomimg}
     *
     * source参数:
     * - article: 从文章封面图随机获取
     * - media: 从媒体库随机获取
     * - slider: 从幻灯片随机获取
     *
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagRandomimg($tag, $content)
    {
        $limit = $tag['limit'] ?? 5;
        $source = $tag['source'] ?? 'article';
        $id = $tag['id'] ?? 'img';
        $empty = $tag['empty'] ?? '';

        $key = !empty($tag['key']) ? $tag['key'] : 'key';
        $i = 'i';

        $parseStr = '<?php ';
        $parseStr .= '$__randomimgs__ = \app\service\tag\RandomImgTagService::getRandom([';
        $parseStr .= "'limit' => {$limit}, ";
        $parseStr .= "'source' => '{$source}'";
        $parseStr .= ']); ';

        $parseStr .= 'if(!empty($__randomimgs__)): ';
        $parseStr .= 'foreach($__randomimgs__ as $' . $key . ' => $' . $id . '): ';
        $parseStr .= '$' . $i . ' = $' . $key . ' + 1; ';
        $parseStr .= '?>';

        $parseStr .= $content;

        $parseStr .= '<?php endforeach; ';

        if (!empty($empty)) {
            $parseStr .= 'else: ?>';
            $parseStr .= '<div class="empty-state">' . $empty . '</div>';
            $parseStr .= '<?php ';
        }

        $parseStr .= 'endif; ?>';

        return $parseStr;
    }
}
