<?php
declare (strict_types = 1);

namespace app\service;

use think\facade\Db;

/**
 * 数据库恢复服务
 */
class DatabaseRestore
{
    private $backupPath;

    public function __construct()
    {
        $this->backupPath = app()->getRuntimePath() . 'backup/database/';
    }

    /**
     * 恢复数据库
     * @param string $filename 备份文件名
     * @return array
     */
    public function restore($filename)
    {
        try {
            $filepath = $this->backupPath . $filename;

            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => '备份文件不存在'
                ];
            }

            // 如果是zip文件，先解压
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'zip') {
                $sqlFile = $this->extractZip($filepath);
                if (!$sqlFile) {
                    return [
                        'success' => false,
                        'message' => 'ZIP文件解压失败'
                    ];
                }
                $filepath = $sqlFile;
            }

            // 读取SQL文件
            $sql = file_get_contents($filepath);

            if (empty($sql)) {
                return [
                    'success' => false,
                    'message' => '备份文件内容为空'
                ];
            }

            // 执行SQL
            $result = $this->executeSql($sql);

            // 如果解压了临时文件，删除它
            if (isset($sqlFile) && file_exists($sqlFile)) {
                unlink($sqlFile);
            }

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => '数据库恢复成功',
                    'executed_statements' => $result['executed']
                ];
            } else {
                return $result;
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '恢复失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 解压ZIP文件
     * @param string $zipFile
     * @return string|false SQL文件路径
     */
    private function extractZip($zipFile)
    {
        if (!extension_loaded('zip')) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === true) {
            // 获取第一个SQL文件
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                    $sqlFile = $this->backupPath . 'temp_' . time() . '.sql';
                    $content = $zip->getFromIndex($i);
                    file_put_contents($sqlFile, $content);
                    $zip->close();
                    return $sqlFile;
                }
            }
            $zip->close();
        }

        return false;
    }

    /**
     * 执行SQL语句
     * @param string $sql
     * @return array
     */
    private function executeSql($sql)
    {
        try {
            // 分割SQL语句
            $statements = $this->splitSql($sql);

            $executed = 0;
            $failed = 0;
            $errors = [];

            // 开始事务
            Db::startTrans();

            try {
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement)) {
                        continue;
                    }

                    try {
                        Db::execute($statement);
                        $executed++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = [
                            'statement' => substr($statement, 0, 100) . '...',
                            'error' => $e->getMessage()
                        ];

                        // 如果失败太多，中止
                        if ($failed > 10) {
                            throw new \Exception('执行失败的语句过多，已中止恢复');
                        }
                    }
                }

                // 提交事务
                Db::commit();

                return [
                    'success' => true,
                    'executed' => $executed,
                    'failed' => $failed,
                    'errors' => $errors
                ];
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'executed' => $executed ?? 0,
                'failed' => $failed ?? 0,
                'errors' => $errors ?? []
            ];
        }
    }

    /**
     * 分割SQL语句
     * @param string $sql
     * @return array
     */
    private function splitSql($sql)
    {
        // 移除注释
        $sql = preg_replace('/--[^\n]*\n/', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // 按分号分割，但要注意字符串中的分号
        $statements = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';

        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            // 处理字符串
            if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i - 1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }

            // 处理分号
            if ($char === ';' && !$inString) {
                $statements[] = $buffer;
                $buffer = '';
            } else {
                $buffer .= $char;
            }
        }

        // 添加最后一条语句
        if (!empty(trim($buffer))) {
            $statements[] = $buffer;
        }

        return $statements;
    }

    /**
     * 验证备份文件
     * @param string $filename
     * @return array
     */
    public function validateBackup($filename)
    {
        try {
            $filepath = $this->backupPath . $filename;

            if (!file_exists($filepath)) {
                return [
                    'valid' => false,
                    'message' => '备份文件不存在'
                ];
            }

            // 检查文件大小
            $filesize = filesize($filepath);
            if ($filesize === 0) {
                return [
                    'valid' => false,
                    'message' => '备份文件为空'
                ];
            }

            // 如果是ZIP文件，检查是否能解压
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'zip') {
                if (!extension_loaded('zip')) {
                    return [
                        'valid' => false,
                        'message' => 'ZIP扩展未安装'
                    ];
                }

                $zip = new \ZipArchive();
                if ($zip->open($filepath) !== true) {
                    return [
                        'valid' => false,
                        'message' => 'ZIP文件损坏'
                    ];
                }

                // 检查是否包含SQL文件
                $hasSql = false;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    if (pathinfo($zip->getNameIndex($i), PATHINFO_EXTENSION) === 'sql') {
                        $hasSql = true;
                        break;
                    }
                }

                $zip->close();

                if (!$hasSql) {
                    return [
                        'valid' => false,
                        'message' => 'ZIP文件中没有SQL文件'
                    ];
                }
            } else {
                // 检查SQL文件格式
                $content = file_get_contents($filepath, false, null, 0, 1000);
                if (stripos($content, 'MySQL') === false && stripos($content, 'CREATE') === false) {
                    return [
                        'valid' => false,
                        'message' => '不是有效的MySQL备份文件'
                    ];
                }
            }

            return [
                'valid' => true,
                'message' => '备份文件有效',
                'filesize' => $filesize,
                'filesize_format' => $this->formatBytes($filesize)
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 格式化字节大小
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}
