<?php
declare (strict_types = 1);

namespace app\service;

use think\facade\Cache;
use think\facade\Config;

/**
 * 缓存管理服务
 */
class CacheManager
{
    /**
     * 获取缓存信息
     * @return array
     */
    public function getInfo()
    {
        $cacheType = Config::get('cache.default');
        $cacheConfig = Config::get("cache.stores.{$cacheType}");

        return [
            'type' => $cacheType,
            'driver' => $cacheConfig['type'] ?? 'file',
            'config' => $cacheConfig,
            'stats' => $this->getStats($cacheType)
        ];
    }

    /**
     * 获取缓存统计信息
     * @param string $cacheType
     * @return array
     */
    private function getStats($cacheType)
    {
        switch ($cacheType) {
            case 'redis':
                return $this->getRedisStats();
            case 'file':
                return $this->getFileStats();
            case 'memcache':
            case 'memcached':
                return $this->getMemcacheStats();
            default:
                return [];
        }
    }

    /**
     * 获取Redis统计信息
     * @return array
     */
    private function getRedisStats()
    {
        try {
            $handler = Cache::store('redis')->handler();

            if ($handler instanceof \Redis) {
                $info = $handler->info();

                return [
                    'version' => $info['redis_version'] ?? 'Unknown',
                    'used_memory' => $info['used_memory'] ?? 0,
                    'used_memory_human' => $info['used_memory_human'] ?? '0B',
                    'keys_count' => $handler->dbSize(),
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
                    'uptime_in_days' => $info['uptime_in_days'] ?? 0,
                    'hit_rate' => $this->calculateHitRate($info)
                ];
            }
        } catch (\Exception $e) {
            // Redis未配置或连接失败
        }

        return [];
    }

    /**
     * 计算命中率
     * @param array $info
     * @return float
     */
    private function calculateHitRate($info)
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * 获取文件缓存统计信息
     * @return array
     */
    private function getFileStats()
    {
        $cacheDir = app()->getRuntimePath() . 'cache/';

        if (!is_dir($cacheDir)) {
            return [
                'files_count' => 0,
                'total_size' => 0,
                'total_size_human' => '0B'
            ];
        }

        $files = $this->scanDirectory($cacheDir);
        $totalSize = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }

        return [
            'files_count' => count($files),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'cache_dir' => $cacheDir
        ];
    }

    /**
     * 获取Memcache统计信息
     * @return array
     */
    private function getMemcacheStats()
    {
        try {
            $handler = Cache::store('memcache')->handler();

            if ($handler instanceof \Memcache || $handler instanceof \Memcached) {
                $stats = $handler->getStats();

                if (is_array($stats)) {
                    $firstServer = current($stats);

                    return [
                        'version' => $firstServer['version'] ?? 'Unknown',
                        'used_memory' => $firstServer['bytes'] ?? 0,
                        'used_memory_human' => $this->formatBytes($firstServer['bytes'] ?? 0),
                        'keys_count' => $firstServer['curr_items'] ?? 0,
                        'total_connections' => $firstServer['total_connections'] ?? 0,
                        'uptime' => $firstServer['uptime'] ?? 0
                    ];
                }
            }
        } catch (\Exception $e) {
            // Memcache未配置或连接失败
        }

        return [];
    }

    /**
     * 清除所有缓存
     * @return bool
     */
    public function clearAll()
    {
        try {
            return Cache::clear();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清除指定标签的缓存
     * @param string $tag
     * @return bool
     */
    public function clearTag($tag)
    {
        try {
            return Cache::tag($tag)->clear();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清除指定键的缓存
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        try {
            return Cache::delete($key);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清除模板缓存
     * @return array
     */
    public function clearTemplate()
    {
        $tempPath = app()->getRuntimePath() . 'temp/';

        if (!is_dir($tempPath)) {
            return [
                'success' => true,
                'files_deleted' => 0,
                'message' => '模板缓存目录不存在'
            ];
        }

        $files = $this->scanDirectory($tempPath);
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
                $deleted++;
            }
        }

        return [
            'success' => true,
            'files_deleted' => $deleted,
            'message' => "成功删除 {$deleted} 个模板缓存文件"
        ];
    }

    /**
     * 清除日志文件
     * @param int $days 保留天数
     * @return array
     */
    public function clearLogs($days = 7)
    {
        $logPath = app()->getRuntimePath() . 'log/';

        if (!is_dir($logPath)) {
            return [
                'success' => true,
                'files_deleted' => 0,
                'message' => '日志目录不存在'
            ];
        }

        $cutoffTime = time() - ($days * 86400);
        $files = $this->scanDirectory($logPath);
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                @unlink($file);
                $deleted++;
            }
        }

        return [
            'success' => true,
            'files_deleted' => $deleted,
            'message' => "成功删除 {$deleted} 个日志文件"
        ];
    }

    /**
     * 获取缓存键列表（仅支持Redis）
     * @param string $pattern
     * @param int $limit
     * @return array
     */
    public function getKeys($pattern = '*', $limit = 100)
    {
        try {
            $handler = Cache::store('redis')->handler();

            if ($handler instanceof \Redis) {
                $keys = $handler->keys($pattern);

                if (count($keys) > $limit) {
                    $keys = array_slice($keys, 0, $limit);
                }

                $result = [];
                foreach ($keys as $key) {
                    $ttl = $handler->ttl($key);
                    $type = $handler->type($key);

                    $result[] = [
                        'key' => $key,
                        'type' => $this->getRedisTypeName($type),
                        'ttl' => $ttl,
                        'expire_time' => $ttl > 0 ? date('Y-m-d H:i:s', time() + $ttl) : '永久'
                    ];
                }

                return $result;
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }

    /**
     * 获取Redis类型名称
     * @param int $type
     * @return string
     */
    private function getRedisTypeName($type)
    {
        $types = [
            \Redis::REDIS_STRING => 'string',
            \Redis::REDIS_SET => 'set',
            \Redis::REDIS_LIST => 'list',
            \Redis::REDIS_ZSET => 'zset',
            \Redis::REDIS_HASH => 'hash'
        ];

        return $types[$type] ?? 'unknown';
    }

    /**
     * 获取缓存值
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 设置缓存值
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return bool
     */
    public function set($key, $value, $expire = 0)
    {
        try {
            return Cache::set($key, $value, $expire);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 缓存预热（预加载常用数据）
     * @return array
     */
    public function warmup()
    {
        $warmed = [];

        try {
            // 预热系统配置
            $config = \app\model\Config::select();
            Cache::set('system_config', $config, 3600);
            $warmed[] = 'system_config';

            // 预热分类列表
            $categories = \app\model\Category::where('status', 1)->select();
            Cache::set('categories_list', $categories, 3600);
            $warmed[] = 'categories_list';

            // 预热标签列表
            $tags = \app\model\Tag::select();
            Cache::set('tags_list', $tags, 3600);
            $warmed[] = 'tags_list';

            return [
                'success' => true,
                'warmed' => $warmed,
                'count' => count($warmed),
                'message' => '缓存预热完成'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'warmed' => $warmed
            ];
        }
    }

    /**
     * 扫描目录
     * @param string $dir
     * @return array
     */
    private function scanDirectory($dir)
    {
        $files = [];

        if (!is_dir($dir)) {
            return $files;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            } else {
                $files[] = $path;
            }
        }

        return $files;
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
     * 测试缓存性能
     * @param int $iterations
     * @return array
     */
    public function testPerformance($iterations = 1000)
    {
        $testKey = 'cache_performance_test';
        $testValue = str_repeat('x', 1024); // 1KB数据

        // 测试写入性能
        $writeStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cache::set($testKey . $i, $testValue, 60);
        }
        $writeTime = microtime(true) - $writeStart;

        // 测试读取性能
        $readStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cache::get($testKey . $i);
        }
        $readTime = microtime(true) - $readStart;

        // 清理测试数据
        for ($i = 0; $i < $iterations; $i++) {
            Cache::delete($testKey . $i);
        }

        return [
            'iterations' => $iterations,
            'write_time' => round($writeTime, 4),
            'write_ops' => round($iterations / $writeTime),
            'read_time' => round($readTime, 4),
            'read_ops' => round($iterations / $readTime),
            'data_size' => '1KB per operation'
        ];
    }
}
