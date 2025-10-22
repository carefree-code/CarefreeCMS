<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\service\DatabaseBackup;
use app\service\DatabaseRestore;
use app\model\DatabaseBackupRecord;
use think\Request;
use think\response\File;

/**
 * 数据库管理控制器
 */
class DatabaseController extends BaseController
{
    /**
     * 获取数据库信息
     */
    public function getInfo()
    {
        $service = new DatabaseBackup();
        $info = $service->getDatabaseInfo();

        return $this->success($info);
    }

    /**
     * 获取所有表信息
     */
    public function getTables()
    {
        $service = new DatabaseBackup();
        $tables = $service->getTablesInfo();

        return $this->success($tables);
    }

    /**
     * 执行完整备份
     */
    public function backup(Request $request)
    {
        $description = $request->param('description', '');

        $service = new DatabaseBackup();
        $result = $service->backup($description);

        if ($result['success']) {
            // 记录备份历史
            DatabaseBackupRecord::recordBackup([
                'filename' => $result['filename'],
                'filepath' => $result['filepath'],
                'filesize' => $result['filesize'],
                'tables_count' => $result['tables_count'],
                'backup_type' => 'full',
                'description' => $description,
                'success' => true,
                'user_id' => $this->request->adminInfo['id'] ?? null
            ]);

            return $this->success($result, '备份成功');
        } else {
            // 记录失败
            DatabaseBackupRecord::recordBackup([
                'filename' => '',
                'filepath' => '',
                'filesize' => 0,
                'tables_count' => 0,
                'backup_type' => 'full',
                'description' => $description,
                'success' => false,
                'message' => $result['message'],
                'user_id' => $this->request->adminInfo['id'] ?? null
            ]);

            return $this->error($result['message']);
        }
    }

    /**
     * 备份指定表
     */
    public function backupTables(Request $request)
    {
        $tables = $request->param('tables', []);
        $description = $request->param('description', '');

        if (empty($tables)) {
            return $this->error('请选择要备份的表');
        }

        $service = new DatabaseBackup();
        $result = $service->backupTables($tables, $description);

        if ($result['success']) {
            DatabaseBackupRecord::recordBackup([
                'filename' => $result['filename'],
                'filepath' => $result['filepath'],
                'filesize' => $result['filesize'],
                'tables_count' => $result['tables_count'],
                'backup_type' => 'tables',
                'description' => $description,
                'success' => true,
                'user_id' => $this->request->adminInfo['id'] ?? null
            ]);

            return $this->success($result, '备份成功');
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 获取备份列表
     */
    public function getBackups(Request $request)
    {
        $page = $request->param('page', 1);
        $perPage = $request->param('per_page', 15);

        $where = [];
        if ($request->has('backup_type')) {
            $where['backup_type'] = $request->param('backup_type');
        }
        if ($request->has('status')) {
            $where['status'] = $request->param('status');
        }

        $result = DatabaseBackupRecord::getList($where, $page, $perPage);

        return $this->success($result);
    }

    /**
     * 恢复数据库
     */
    public function restore(Request $request)
    {
        $filename = $request->param('filename');

        if (empty($filename)) {
            return $this->error('请指定备份文件');
        }

        $service = new DatabaseRestore();

        // 先验证备份文件
        $validation = $service->validateBackup($filename);
        if (!$validation['valid']) {
            return $this->error($validation['message']);
        }

        // 执行恢复
        $result = $service->restore($filename);

        if ($result['success']) {
            return $this->success($result, '数据库恢复成功');
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 验证备份文件
     */
    public function validateBackup(Request $request)
    {
        $filename = $request->param('filename');

        if (empty($filename)) {
            return $this->error('请指定备份文件');
        }

        $service = new DatabaseRestore();
        $result = $service->validateBackup($filename);

        if ($result['valid']) {
            return $this->success($result, '备份文件有效');
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 删除备份
     */
    public function deleteBackup(Request $request, $id)
    {
        if (DatabaseBackupRecord::deleteRecord($id)) {
            return $this->success(null, '删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 下载备份文件
     */
    public function downloadBackup(Request $request)
    {
        $filename = $request->param('filename');

        if (empty($filename)) {
            return $this->error('请指定备份文件');
        }

        $service = new DatabaseBackup();
        $result = $service->downloadBackup($filename);

        if ($result['success']) {
            return download($result['filepath'], $result['filename']);
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 优化表
     */
    public function optimize(Request $request)
    {
        $tables = $request->param('tables', []);

        $service = new DatabaseBackup();
        $results = $service->optimizeTables($tables);

        $successCount = count(array_filter($results, function($r) {
            return $r['success'];
        }));

        return $this->success([
            'results' => $results,
            'total' => count($results),
            'success' => $successCount,
            'failed' => count($results) - $successCount
        ], "优化完成，成功 {$successCount} 个");
    }

    /**
     * 修复表
     */
    public function repair(Request $request)
    {
        $tables = $request->param('tables', []);

        $service = new DatabaseBackup();
        $results = $service->repairTables($tables);

        $successCount = count(array_filter($results, function($r) {
            return $r['success'];
        }));

        return $this->success([
            'results' => $results,
            'total' => count($results),
            'success' => $successCount,
            'failed' => count($results) - $successCount
        ], "修复完成，成功 {$successCount} 个");
    }

    /**
     * 清理旧备份
     */
    public function cleanOldBackups(Request $request)
    {
        $days = $request->param('days', 30);

        $count = DatabaseBackupRecord::cleanOldBackups($days);

        return $this->success([
            'deleted' => $count
        ], "成功清理 {$count} 个旧备份");
    }
}
