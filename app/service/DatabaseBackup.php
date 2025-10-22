<?php
declare (strict_types = 1);

namespace app\service;

use think\facade\Db;
use think\facade\Config;

/**
 * 数据库备份服务
 */
class DatabaseBackup
{
    private $backupPath;
    private $dbConfig;

    public function __construct()
    {
        $this->backupPath = app()->getRuntimePath() . 'backup/database/';
        $this->dbConfig = config('database.connections.mysql');

        // 确保备份目录存在
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * 执行完整数据库备份（改进版：添加文件锁和异常处理）
     * @param string $description 备份描述
     * @return array
     */
    public function backup($description = '')
    {
        $fp = null;
        $filepath = null;

        try {
            $filename = 'backup_' . date('YmdHis') . '.sql';
            $filepath = $this->backupPath . $filename;

            // 获取所有表
            $tables = $this->getTables();

            // 打开文件并加锁
            $fp = fopen($filepath, 'w');
            if ($fp === false) {
                throw new \Exception("无法创建备份文件：{$filepath}");
            }

            // 获取独占锁，防止并发写入
            if (!flock($fp, LOCK_EX)) {
                throw new \Exception("无法锁定备份文件");
            }

            // 写入备份头信息
            $this->writeHeader($fp);

            // 备份每个表
            foreach ($tables as $table) {
                $this->backupTable($fp, $table);
            }

            // 写入备份尾信息
            $this->writeFooter($fp);

            // 释放锁并关闭文件
            flock($fp, LOCK_UN);
            fclose($fp);
            $fp = null;

            // 压缩备份文件
            $zipFile = $this->compress($filepath);

            // 删除原始SQL文件
            if ($zipFile && file_exists($zipFile)) {
                unlink($filepath);
                $filepath = $zipFile;
                $filename = basename($zipFile);
            }

            $filesize = filesize($filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'filesize' => $filesize,
                'filesize_format' => $this->formatBytes($filesize),
                'tables_count' => count($tables),
                'description' => $description,
                'backup_time' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            // 出错时清理不完整的文件
            if ($filepath && file_exists($filepath)) {
                @unlink($filepath);
            }

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } finally {
            // 确保文件资源被正确释放
            if ($fp !== null && is_resource($fp)) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }

    /**
     * 备份指定表（改进版：添加文件锁和异常处理）
     * @param array $tables 表名数组
     * @param string $description 备份描述
     * @return array
     */
    public function backupTables($tables, $description = '')
    {
        $fp = null;
        $filepath = null;

        try {
            $filename = 'backup_tables_' . date('YmdHis') . '.sql';
            $filepath = $this->backupPath . $filename;

            // 打开文件并加锁
            $fp = fopen($filepath, 'w');
            if ($fp === false) {
                throw new \Exception("无法创建备份文件：{$filepath}");
            }

            // 获取独占锁
            if (!flock($fp, LOCK_EX)) {
                throw new \Exception("无法锁定备份文件");
            }

            $this->writeHeader($fp);

            foreach ($tables as $table) {
                $this->backupTable($fp, $table);
            }

            $this->writeFooter($fp);

            // 释放锁并关闭文件
            flock($fp, LOCK_UN);
            fclose($fp);
            $fp = null;

            // 压缩
            $zipFile = $this->compress($filepath);
            if ($zipFile && file_exists($zipFile)) {
                unlink($filepath);
                $filepath = $zipFile;
                $filename = basename($zipFile);
            }

            $filesize = filesize($filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'filesize' => $filesize,
                'filesize_format' => $this->formatBytes($filesize),
                'tables_count' => count($tables),
                'description' => $description,
                'backup_time' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            // 出错时清理不完整的文件
            if ($filepath && file_exists($filepath)) {
                @unlink($filepath);
            }

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } finally {
            // 确保文件资源被正确释放
            if ($fp !== null && is_resource($fp)) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }

    /**
     * 获取所有表名
     * @return array
     */
    public function getTables()
    {
        $database = $this->dbConfig['database'];
        $result = Db::query("SHOW TABLES FROM `{$database}`");

        $tables = [];
        foreach ($result as $row) {
            $tables[] = current($row);
        }

        return $tables;
    }

    /**
     * 获取表信息
     * @return array
     */
    public function getTablesInfo()
    {
        $tables = $this->getTables();
        $info = [];

        foreach ($tables as $table) {
            $status = Db::query("SHOW TABLE STATUS LIKE '{$table}'")[0];

            $info[] = [
                'name' => $table,
                'rows' => $status['Rows'] ?? 0,
                'data_length' => $status['Data_length'] ?? 0,
                'data_length_format' => $this->formatBytes($status['Data_length'] ?? 0),
                'index_length' => $status['Index_length'] ?? 0,
                'auto_increment' => $status['Auto_increment'] ?? null,
                'create_time' => $status['Create_time'] ?? null,
                'update_time' => $status['Update_time'] ?? null,
                'engine' => $status['Engine'] ?? null,
                'collation' => $status['Collation'] ?? null,
                'comment' => $status['Comment'] ?? ''
            ];
        }

        return $info;
    }

    /**
     * 备份单个表
     * @param resource $fp 文件句柄
     * @param string $table 表名
     */
    private function backupTable($fp, $table)
    {
        // 写入表注释
        fwrite($fp, "\n\n-- ----------------------------\n");
        fwrite($fp, "-- Table structure for {$table}\n");
        fwrite($fp, "-- ----------------------------\n");

        // 删除表（如果存在）
        fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

        // 获取建表语句
        $createTable = Db::query("SHOW CREATE TABLE `{$table}`")[0];
        $createSql = $createTable['Create Table'] ?? '';
        fwrite($fp, $createSql . ";\n");

        // 写入表数据
        fwrite($fp, "\n-- ----------------------------\n");
        fwrite($fp, "-- Records of {$table}\n");
        fwrite($fp, "-- ----------------------------\n");

        // 获取字段信息（包括类型）
        $columns = Db::query("SHOW FULL COLUMNS FROM `{$table}`");
        $columnTypes = [];
        foreach ($columns as $column) {
            $columnTypes[$column['Field']] = strtolower($column['Type']);
        }

        // 查询所有数据
        $data = Db::table($table)->select();

        if (!empty($data)) {
            // 分批写入，每100条一个INSERT语句
            $batchSize = 100;
            $batches = array_chunk($data->toArray(), $batchSize);

            foreach ($batches as $batch) {
                $values = [];
                foreach ($batch as $row) {
                    $rowValues = [];
                    foreach ($row as $field => $value) {
                        $rowValues[] = $this->formatValue($value, $columnTypes[$field] ?? 'varchar');
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }

                if (!empty($values)) {
                    $fields = array_keys($batch[0]);
                    $fieldList = '`' . implode('`, `', $fields) . '`';
                    $sql = "INSERT INTO `{$table}` ({$fieldList}) VALUES\n";
                    $sql .= implode(",\n", $values) . ";\n";
                    fwrite($fp, $sql);
                }
            }
        }
    }

    /**
     * 根据字段类型格式化值
     * @param mixed $value 值
     * @param string $type 字段类型
     * @return string
     */
    private function formatValue($value, $type)
    {
        // NULL值处理
        if (is_null($value)) {
            return 'NULL';
        }

        // 去除类型后面的长度等参数，只保留类型名称
        $baseType = preg_replace('/\(.*?\)/', '', $type);
        $baseType = preg_replace('/\s+.*$/', '', $baseType);

        // 二进制类型（BLOB, BINARY, VARBINARY等）
        if (in_array($baseType, ['blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary'])) {
            if ($value === '') {
                return "''";
            }
            return "0x" . bin2hex($value);
        }

        // 数字类型（整数和浮点数）
        if (in_array($baseType, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'float', 'double', 'decimal'])) {
            // 对于数字类型，直接返回数值，不加引号
            if (is_numeric($value)) {
                return $value;
            }
            // 如果不是数字，可能是NULL或者异常值，返回0
            return '0';
        }

        // JSON类型
        if ($baseType === 'json') {
            // JSON需要转义后作为字符串
            return "'" . addslashes($value) . "'";
        }

        // 几何类型
        if (in_array($baseType, ['geometry', 'point', 'linestring', 'polygon', 'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection'])) {
            if ($value === '') {
                return 'NULL';
            }
            // 几何类型使用ST_GeomFromText函数
            return "ST_GeomFromText('" . addslashes($value) . "')";
        }

        // BIT类型
        if ($baseType === 'bit') {
            return "b'" . decbin(ord($value)) . "'";
        }

        // 日期时间类型
        if (in_array($baseType, ['date', 'datetime', 'timestamp', 'time', 'year'])) {
            if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return 'NULL';
            }
            return "'" . addslashes($value) . "'";
        }

        // 默认：字符串类型（VARCHAR, CHAR, TEXT等）
        // 使用MySQL的转义函数更安全
        $escaped = addslashes($value);
        return "'" . $escaped . "'";
    }

    /**
     * 写入备份头信息
     * @param resource $fp
     */
    private function writeHeader($fp)
    {
        $header = "-- ----------------------------\n";
        $header .= "-- MySQL Database Backup\n";
        $header .= "-- ----------------------------\n";
        $header .= "-- Host: {$this->dbConfig['hostname']}\n";
        $header .= "-- Database: {$this->dbConfig['database']}\n";
        $header .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- ----------------------------\n\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        fwrite($fp, $header);
    }

    /**
     * 写入备份尾信息
     * @param resource $fp
     */
    private function writeFooter($fp)
    {
        $footer = "\n\nSET FOREIGN_KEY_CHECKS=1;\n";
        fwrite($fp, $footer);
    }

    /**
     * 压缩备份文件
     * @param string $filepath
     * @return string|false
     */
    private function compress($filepath)
    {
        if (!extension_loaded('zip')) {
            return false;
        }

        $zipFile = $filepath . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            $zip->addFile($filepath, basename($filepath));
            $zip->close();
            return $zipFile;
        }

        return false;
    }

    /**
     * 获取备份文件列表
     * @return array
     */
    public function getBackupFiles()
    {
        $files = [];

        if (!is_dir($this->backupPath)) {
            return $files;
        }

        $dir = opendir($this->backupPath);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..' && (strpos($file, '.sql') !== false || strpos($file, '.zip') !== false)) {
                $filepath = $this->backupPath . $file;
                $files[] = [
                    'filename' => $file,
                    'filepath' => $filepath,
                    'filesize' => filesize($filepath),
                    'filesize_format' => $this->formatBytes(filesize($filepath)),
                    'create_time' => date('Y-m-d H:i:s', filectime($filepath)),
                    'modify_time' => date('Y-m-d H:i:s', filemtime($filepath))
                ];
            }
        }
        closedir($dir);

        // 按修改时间倒序排序
        usort($files, function($a, $b) {
            return $b['modify_time'] <=> $a['modify_time'];
        });

        return $files;
    }

    /**
     * 删除备份文件
     * @param string $filename
     * @return bool
     */
    public function deleteBackup($filename)
    {
        $filepath = $this->backupPath . $filename;

        if (file_exists($filepath)) {
            return unlink($filepath);
        }

        return false;
    }

    /**
     * 下载备份文件
     * @param string $filename
     * @return array
     */
    public function downloadBackup($filename)
    {
        $filepath = $this->backupPath . $filename;

        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'message' => '备份文件不存在'
            ];
        }

        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }

    /**
     * 优化数据库表
     * @param array $tables 要优化的表，空数组表示所有表
     * @return array
     */
    public function optimizeTables($tables = [])
    {
        if (empty($tables)) {
            $tables = $this->getTables();
        }

        $results = [];
        foreach ($tables as $table) {
            try {
                Db::execute("OPTIMIZE TABLE `{$table}`");
                $results[] = [
                    'table' => $table,
                    'success' => true,
                    'message' => '优化成功'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'table' => $table,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * 修复数据库表
     * @param array $tables
     * @return array
     */
    public function repairTables($tables = [])
    {
        if (empty($tables)) {
            $tables = $this->getTables();
        }

        $results = [];
        foreach ($tables as $table) {
            try {
                Db::execute("REPAIR TABLE `{$table}`");
                $results[] = [
                    'table' => $table,
                    'success' => true,
                    'message' => '修复成功'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'table' => $table,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
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

    /**
     * 获取数据库信息
     * @return array
     */
    public function getDatabaseInfo()
    {
        $info = Db::query("SELECT
            SUM(DATA_LENGTH) as data_size,
            SUM(INDEX_LENGTH) as index_size,
            SUM(DATA_LENGTH + INDEX_LENGTH) as total_size,
            COUNT(*) as tables_count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?", [$this->dbConfig['database']])[0];

        return [
            'database' => $this->dbConfig['database'],
            'host' => $this->dbConfig['hostname'],
            'tables_count' => $info['tables_count'] ?? 0,
            'data_size' => $info['data_size'] ?? 0,
            'data_size_format' => $this->formatBytes($info['data_size'] ?? 0),
            'index_size' => $info['index_size'] ?? 0,
            'index_size_format' => $this->formatBytes($info['index_size'] ?? 0),
            'total_size' => $info['total_size'] ?? 0,
            'total_size_format' => $this->formatBytes($info['total_size'] ?? 0)
        ];
    }
}
