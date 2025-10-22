<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\common\Logger;
use app\model\Article;
use app\model\Category;
use app\model\Tag;
use app\model\Page;
use app\model\OperationLog;
use think\Request;

/**
 * 回收站管理控制器
 */
class RecycleBin extends BaseController
{
    /**
     * 获取回收站列表
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'all'); // all, article, category, tag, page
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');

        $items = [];

        // 获取各类型已删除的数据
        if ($type === 'all' || $type === 'article') {
            $articles = Article::onlyTrashed()
                ->when($keyword, function($query) use ($keyword) {
                    $query->where('title', 'like', '%' . $keyword . '%');
                })
                ->order('deleted_at', 'desc')
                ->select()
                ->each(function($item) {
                    $item->item_type = 'article';
                    $item->item_type_text = '文章';
                    $item->item_title = $item->title;
                    return $item;
                })
                ->toArray();
            $items = array_merge($items, $articles);
        }

        if ($type === 'all' || $type === 'category') {
            $categories = Category::onlyTrashed()
                ->when($keyword, function($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                })
                ->order('deleted_at', 'desc')
                ->select()
                ->each(function($item) {
                    $item->item_type = 'category';
                    $item->item_type_text = '分类';
                    $item->item_title = $item->name;
                    return $item;
                })
                ->toArray();
            $items = array_merge($items, $categories);
        }

        if ($type === 'all' || $type === 'tag') {
            $tags = Tag::onlyTrashed()
                ->when($keyword, function($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                })
                ->order('deleted_at', 'desc')
                ->select()
                ->each(function($item) {
                    $item->item_type = 'tag';
                    $item->item_type_text = '标签';
                    $item->item_title = $item->name;
                    return $item;
                })
                ->toArray();
            $items = array_merge($items, $tags);
        }

        if ($type === 'all' || $type === 'page') {
            $pages = Page::onlyTrashed()
                ->when($keyword, function($query) use ($keyword) {
                    $query->where('title', 'like', '%' . $keyword . '%');
                })
                ->order('deleted_at', 'desc')
                ->select()
                ->each(function($item) {
                    $item->item_type = 'page';
                    $item->item_type_text = '单页';
                    $item->item_title = $item->title;
                    return $item;
                })
                ->toArray();
            $items = array_merge($items, $pages);
        }

        // 按删除时间排序
        usort($items, function($a, $b) {
            return strtotime($b['deleted_at']) - strtotime($a['deleted_at']);
        });

        // 手动分页
        $total = count($items);
        $items = array_slice($items, ($page - 1) * $pageSize, $pageSize);

        return Response::paginate($items, $total, $page, $pageSize);
    }

    /**
     * 获取回收站统计
     */
    public function statistics()
    {
        $articleCount = Article::onlyTrashed()->count();
        $categoryCount = Category::onlyTrashed()->count();
        $tagCount = Tag::onlyTrashed()->count();
        $pageCount = Page::onlyTrashed()->count();

        return Response::success([
            'article_count' => $articleCount,
            'category_count' => $categoryCount,
            'tag_count' => $tagCount,
            'page_count' => $pageCount,
            'total_count' => $articleCount + $categoryCount + $tagCount + $pageCount,
        ]);
    }

    /**
     * 恢复单个项目
     */
    public function restore(Request $request)
    {
        $type = $request->post('type');
        $id = $request->post('id');

        if (!$type || !$id) {
            return Response::error('参数错误');
        }

        try {
            $model = $this->getModel($type);
            $item = $model::onlyTrashed()->find($id);

            if (!$item) {
                return Response::notFound('项目不存在');
            }

            // 恢复
            $item->restore();

            // 记录日志
            $typeName = $this->getTypeName($type);
            Logger::update(OperationLog::MODULE_SYSTEM, "恢复{$typeName}", $id);

            return Response::success([], '恢复成功');
        } catch (\Exception $e) {
            return Response::error('恢复失败：' . $e->getMessage());
        }
    }

    /**
     * 批量恢复
     */
    public function batchRestore(Request $request)
    {
        $items = $request->post('items', []); // [{'type': 'article', 'id': 1}, ...]

        if (empty($items) || !is_array($items)) {
            return Response::error('请选择要恢复的项目');
        }

        try {
            $successCount = 0;
            foreach ($items as $item) {
                $type = $item['type'] ?? '';
                $id = $item['id'] ?? '';

                if (!$type || !$id) continue;

                $model = $this->getModel($type);
                $record = $model::onlyTrashed()->find($id);

                if ($record) {
                    $record->restore();
                    $successCount++;
                }
            }

            // 记录日志
            Logger::update(OperationLog::MODULE_SYSTEM, "批量恢复回收站项目", 0);

            return Response::success([
                'success_count' => $successCount,
            ], "成功恢复 {$successCount} 个项目");
        } catch (\Exception $e) {
            return Response::error('批量恢复失败：' . $e->getMessage());
        }
    }

    /**
     * 彻底删除单个项目
     */
    public function destroy(Request $request)
    {
        $type = $request->param('type');
        $id = $request->param('id');

        if (!$type || !$id) {
            return Response::error('参数错误');
        }

        try {
            $model = $this->getModel($type);
            $item = $model::onlyTrashed()->find($id);

            if (!$item) {
                return Response::notFound('项目不存在');
            }

            // 彻底删除
            $item->force()->delete();

            // 记录日志
            $typeName = $this->getTypeName($type);
            Logger::delete(OperationLog::MODULE_SYSTEM, "彻底删除{$typeName}", $id);

            return Response::success([], '彻底删除成功');
        } catch (\Exception $e) {
            return Response::error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 批量彻底删除
     */
    public function batchDestroy(Request $request)
    {
        $items = $request->post('items', []); // [{'type': 'article', 'id': 1}, ...]

        if (empty($items) || !is_array($items)) {
            return Response::error('请选择要删除的项目');
        }

        try {
            $successCount = 0;
            foreach ($items as $item) {
                $type = $item['type'] ?? '';
                $id = $item['id'] ?? '';

                if (!$type || !$id) continue;

                $model = $this->getModel($type);
                $record = $model::onlyTrashed()->find($id);

                if ($record) {
                    $record->force()->delete();
                    $successCount++;
                }
            }

            // 记录日志
            Logger::delete(OperationLog::MODULE_SYSTEM, "批量彻底删除回收站项目", 0);

            return Response::success([
                'success_count' => $successCount,
            ], "成功删除 {$successCount} 个项目");
        } catch (\Exception $e) {
            return Response::error('批量删除失败：' . $e->getMessage());
        }
    }

    /**
     * 清空回收站
     */
    public function clear(Request $request)
    {
        $type = $request->post('type', 'all'); // all, article, category, tag, page

        try {
            $deletedCount = 0;

            if ($type === 'all' || $type === 'article') {
                $articles = Article::onlyTrashed()->select();
                foreach ($articles as $article) {
                    $article->force()->delete();
                    $deletedCount++;
                }
            }

            if ($type === 'all' || $type === 'category') {
                $categories = Category::onlyTrashed()->select();
                foreach ($categories as $category) {
                    $category->force()->delete();
                    $deletedCount++;
                }
            }

            if ($type === 'all' || $type === 'tag') {
                $tags = Tag::onlyTrashed()->select();
                foreach ($tags as $tag) {
                    $tag->force()->delete();
                    $deletedCount++;
                }
            }

            if ($type === 'all' || $type === 'page') {
                $pages = Page::onlyTrashed()->select();
                foreach ($pages as $page) {
                    $page->force()->delete();
                    $deletedCount++;
                }
            }

            // 记录日志
            Logger::delete(OperationLog::MODULE_SYSTEM, "清空回收站", 0);

            return Response::success([
                'deleted_count' => $deletedCount,
            ], "成功清空 {$deletedCount} 个项目");
        } catch (\Exception $e) {
            return Response::error('清空回收站失败：' . $e->getMessage());
        }
    }

    /**
     * 获取模型类
     */
    private function getModel($type)
    {
        $models = [
            'article' => Article::class,
            'category' => Category::class,
            'tag' => Tag::class,
            'page' => Page::class,
        ];

        if (!isset($models[$type])) {
            throw new \Exception('不支持的类型');
        }

        return $models[$type];
    }

    /**
     * 获取类型名称
     */
    private function getTypeName($type)
    {
        $names = [
            'article' => '文章',
            'category' => '分类',
            'tag' => '标签',
            'page' => '单页',
        ];

        return $names[$type] ?? '未知';
    }
}
