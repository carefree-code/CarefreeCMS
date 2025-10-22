<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Media as MediaModel;
use think\Request;
use think\facade\Filesystem;

/**
 * 媒体库控制器
 */
class Media extends BaseController
{
    /**
     * 媒体文件列表
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $type = $request->get('type', '');
        $filename = $request->get('filename', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        // 构建查询
        $query = MediaModel::with(['user'])
            ->order('create_time', 'desc');

        // 搜索条件
        if (!empty($type)) {
            $query->where('file_type', $type);
        }
        if (!empty($filename)) {
            $query->where('file_name', 'like', '%' . $filename . '%');
        }
        // 日期范围查询
        if (!empty($startDate)) {
            $query->whereTime('create_time', '>=', $startDate . ' 00:00:00');
        }
        if (!empty($endDate)) {
            $query->whereTime('create_time', '<=', $endDate . ' 23:59:59');
        }

        // 分页
        $list = $query->page($page, $pageSize)->select();

        // 计算总数（需要使用相同的查询条件）
        $total = MediaModel::when(!empty($type), function($query) use ($type) {
            $query->where('file_type', $type);
        })
        ->when(!empty($filename), function($query) use ($filename) {
            $query->where('file_name', 'like', '%' . $filename . '%');
        })
        ->when(!empty($startDate), function($query) use ($startDate) {
            $query->whereTime('create_time', '>=', $startDate . ' 00:00:00');
        })
        ->when(!empty($endDate), function($query) use ($endDate) {
            $query->whereTime('create_time', '<=', $endDate . ' 23:59:59');
        })
        ->count();

        return Response::paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 文件上传
     */
    public function upload(Request $request)
    {
        // 获取上传的文件
        $file = $request->file('file');

        if (!$file) {
            return Response::error('请选择要上传的文件');
        }

        // 验证文件
        try {
            validate([
                'file' => [
                    'fileSize' => 10 * 1024 * 1024, // 10MB
                    'fileExt'  => 'jpg,jpeg,png,gif,webp,ico,pdf,doc,docx,xls,xlsx,zip,rar',
                ]
            ])->check(['file' => $file]);
        } catch (\think\exception\ValidateException $e) {
            return Response::error($e->getMessage());
        }

        try {
            // 获取文件信息
            $originalName = $file->getOriginalName();
            $ext = strtolower($file->extension());
            $mimeType = $file->getMime();
            $fileSize = $file->getSize();

            // 判断文件类型
            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'];
            $videoExts = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'];
            $audioExts = ['mp3', 'wav', 'wma', 'ogg', 'flac'];
            $docExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];

            if (in_array($ext, $imageExts)) {
                $fileType = 'image';
            } elseif (in_array($ext, $videoExts)) {
                $fileType = 'video';
            } elseif (in_array($ext, $audioExts)) {
                $fileType = 'audio';
            } elseif (in_array($ext, $docExts)) {
                $fileType = 'document';
            } else {
                $fileType = 'other';
            }

            // 生成日期目录
            $datePath = date('Y/m/d');
            $savePath = 'uploads/' . $datePath;

            // 生成唯一文件名
            $fileName = date('YmdHis') . '_' . uniqid() . '.' . $ext;

            // 创建目录（如果不存在）- 保存到html目录
            $fullPath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . $savePath;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // 移动文件
            $file->move($fullPath, $fileName);

            // 文件相对路径（只存储相对路径，URL在读取时动态生成）
            $filePath = $savePath . '/' . $fileName;

            // 获取图片尺寸
            $width = null;
            $height = null;
            if ($fileType === 'image') {
                $imageInfo = getimagesize(app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . $filePath);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            // 保存到数据库（不保存file_url，URL将在读取时动态生成）
            $media = MediaModel::create([
                'user_id'      => $request->user['id'],
                'file_name'    => $originalName,
                'file_path'    => $filePath,
                'file_type'    => $fileType,
                'mime_type'    => $mimeType,
                'file_size'    => $fileSize,
                'width'        => $width,
                'height'       => $height,
                'storage_type' => 'local',
            ]);

            return Response::success([
                'id'        => $media->id,
                'file_name' => $originalName,
                'file_url'  => $media->file_url,  // 使用模型的获取器动态生成URL
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'width'     => $width,
                'height'    => $height,
            ], '文件上传成功');

        } catch (\Exception $e) {
            return Response::error('文件上传失败：' . $e->getMessage());
        }
    }

    /**
     * 删除文件
     */
    public function delete($id)
    {
        $media = MediaModel::find($id);
        if (!$media) {
            return Response::notFound('文件不存在');
        }

        // 检查回收站是否开启
        $recycleBinEnabled = \app\model\Config::getConfig('recycle_bin_enable', 'open');

        try {
            if ($recycleBinEnabled === 'open') {
                // 软删除：进入回收站，不删除物理文件
                $media->delete();
                $message = '文件已移入回收站';
            } else {
                // 物理删除：删除物理文件和数据库记录
                $filePath = app()->getRootPath() . 'html' . DIRECTORY_SEPARATOR . $media->file_path;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }

                // 强制删除数据库记录
                $media->force()->delete();
                $message = '文件删除成功';
            }

            return Response::success([], $message);
        } catch (\Exception $e) {
            return Response::error('文件删除失败：' . $e->getMessage());
        }
    }
}
