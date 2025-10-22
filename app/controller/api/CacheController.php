<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\service\CacheManager;
use think\Request;

/**
 * 缓存管理控制器
 */
class CacheController extends BaseController
{
    /**
     * 获取缓存信息
     */
    public function getInfo()
    {
        $service = new CacheManager();
        $info = $service->getInfo();

        return $this->success($info);
    }

    /**
     * 清除所有缓存
     */
    public function clearAll()
    {
        $service = new CacheManager();

        if ($service->clearAll()) {
            return $this->success(null, '缓存已清空');
        } else {
            return $this->error('清空缓存失败');
        }
    }

    /**
     * 清除指定标签的缓存
     */
    public function clearTag(Request $request)
    {
        $tag = $request->param('tag');

        if (empty($tag)) {
            return $this->error('请指定标签');
        }

        $service = new CacheManager();

        if ($service->clearTag($tag)) {
            return $this->success(null, '标签缓存已清除');
        } else {
            return $this->error('清除缓存失败');
        }
    }

    /**
     * 删除指定键的缓存
     */
    public function delete(Request $request)
    {
        $key = $request->param('key');

        if (empty($key)) {
            return $this->error('请指定缓存键');
        }

        $service = new CacheManager();

        if ($service->delete($key)) {
            return $this->success(null, '缓存已删除');
        } else {
            return $this->error('删除缓存失败');
        }
    }

    /**
     * 清除模板缓存
     */
    public function clearTemplate()
    {
        $service = new CacheManager();
        $result = $service->clearTemplate();

        if ($result['success']) {
            return $this->success($result, $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 清除日志文件
     */
    public function clearLogs(Request $request)
    {
        $days = $request->param('days', 7);

        $service = new CacheManager();
        $result = $service->clearLogs($days);

        if ($result['success']) {
            return $this->success($result, $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 获取缓存键列表
     */
    public function getKeys(Request $request)
    {
        $pattern = $request->param('pattern', '*');
        $limit = $request->param('limit', 100);

        $service = new CacheManager();
        $keys = $service->getKeys($pattern, $limit);

        return $this->success([
            'keys' => $keys,
            'count' => count($keys)
        ]);
    }

    /**
     * 获取缓存值
     */
    public function get(Request $request)
    {
        $key = $request->param('key');

        if (empty($key)) {
            return $this->error('请指定缓存键');
        }

        $service = new CacheManager();
        $value = $service->get($key);

        return $this->success([
            'key' => $key,
            'value' => $value,
            'type' => gettype($value)
        ]);
    }

    /**
     * 设置缓存值
     */
    public function set(Request $request)
    {
        $key = $request->param('key');
        $value = $request->param('value');
        $expire = $request->param('expire', 0);

        if (empty($key)) {
            return $this->error('请指定缓存键');
        }

        $service = new CacheManager();

        if ($service->set($key, $value, $expire)) {
            return $this->success(null, '缓存设置成功');
        } else {
            return $this->error('缓存设置失败');
        }
    }

    /**
     * 缓存预热
     */
    public function warmup()
    {
        $service = new CacheManager();
        $result = $service->warmup();

        if ($result['success']) {
            return $this->success($result, $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 测试缓存性能
     */
    public function testPerformance(Request $request)
    {
        $iterations = $request->param('iterations', 1000);

        $service = new CacheManager();
        $result = $service->testPerformance($iterations);

        return $this->success($result);
    }

    /**
     * 获取当前缓存驱动配置
     */
    public function getDriver()
    {
        $envFile = root_path() . '.env';
        $driver = 'file'; // 默认值

        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (preg_match('/CACHE_DRIVER\s*=\s*(\w+)/', $envContent, $matches)) {
                $driver = $matches[1];
            }
        }

        // 如果是Redis，还需要返回Redis配置
        $config = [];
        if ($driver === 'redis') {
            if (preg_match('/REDIS_HOST\s*=\s*(.+)/', $envContent ?? '', $matches)) {
                $config['host'] = trim($matches[1]);
            }
            if (preg_match('/REDIS_PORT\s*=\s*(\d+)/', $envContent ?? '', $matches)) {
                $config['port'] = (int)trim($matches[1]);
            }
            if (preg_match('/REDIS_PASSWORD\s*=\s*(.*)/', $envContent ?? '', $matches)) {
                $config['password'] = trim($matches[1]);
            }
        }

        return $this->success([
            'driver' => $driver,
            'config' => $config
        ]);
    }

    /**
     * 切换缓存驱动
     */
    public function switchDriver(Request $request)
    {
        $driver = $request->param('driver');
        $config = $request->param('config', []);

        if (!in_array($driver, ['file', 'redis'])) {
            return $this->error('不支持的缓存驱动');
        }

        // 如果要切换到Redis，检查扩展是否安装
        if ($driver === 'redis' && !extension_loaded('redis')) {
            return $this->error('PHP Redis扩展未安装，无法切换到Redis驱动。请先安装Redis扩展：https://pecl.php.net/package/redis');
        }

        $envFile = root_path() . '.env';

        if (!file_exists($envFile)) {
            return $this->error('.env文件不存在');
        }

        if (!is_writable($envFile)) {
            return $this->error('.env文件不可写');
        }

        $envContent = file_get_contents($envFile);

        // 更新或添加CACHE_DRIVER配置
        if (preg_match('/CACHE_DRIVER\s*=\s*.+/', $envContent)) {
            $envContent = preg_replace('/CACHE_DRIVER\s*=\s*.+/', 'CACHE_DRIVER = ' . $driver, $envContent);
        } else {
            $envContent .= "\nCACHE_DRIVER = " . $driver;
        }

        // 如果是Redis，还需要更新Redis配置
        if ($driver === 'redis' && !empty($config)) {
            if (isset($config['host'])) {
                if (preg_match('/REDIS_HOST\s*=\s*.+/', $envContent)) {
                    $envContent = preg_replace('/REDIS_HOST\s*=\s*.+/', 'REDIS_HOST = ' . $config['host'], $envContent);
                } else {
                    $envContent .= "\nREDIS_HOST = " . $config['host'];
                }
            }

            if (isset($config['port'])) {
                if (preg_match('/REDIS_PORT\s*=\s*.+/', $envContent)) {
                    $envContent = preg_replace('/REDIS_PORT\s*=\s*.+/', 'REDIS_PORT = ' . $config['port'], $envContent);
                } else {
                    $envContent .= "\nREDIS_PORT = " . $config['port'];
                }
            }

            if (isset($config['password'])) {
                $password = $config['password'] === '' ? '' : $config['password'];
                if (preg_match('/REDIS_PASSWORD\s*=\s*.*/', $envContent)) {
                    $envContent = preg_replace('/REDIS_PASSWORD\s*=\s*.*/', 'REDIS_PASSWORD = ' . $password, $envContent);
                } else {
                    $envContent .= "\nREDIS_PASSWORD = " . $password;
                }
            }
        }

        // 写入.env文件
        if (file_put_contents($envFile, $envContent) === false) {
            return $this->error('写入.env文件失败');
        }

        // 清空所有缓存
        $service = new CacheManager();
        $service->clearAll();

        return $this->success(null, '缓存驱动已切换为 ' . $driver . '，请刷新页面');
    }

    /**
     * 测试Redis连接
     */
    public function testRedis(Request $request)
    {
        // 检查Redis扩展是否安装
        if (!extension_loaded('redis')) {
            return $this->error('PHP Redis扩展未安装。请先安装Redis扩展：https://pecl.php.net/package/redis');
        }

        $host = $request->param('host', '127.0.0.1');
        $port = $request->param('port', 6379);
        $password = $request->param('password', '');

        try {
            $redis = new \Redis();
            if (!$redis->connect($host, $port, 2)) {
                return $this->error('无法连接到Redis服务器，请检查Redis服务是否启动');
            }

            if (!empty($password)) {
                if (!$redis->auth($password)) {
                    $redis->close();
                    return $this->error('Redis密码认证失败');
                }
            }

            // 测试基本操作
            $testKey = 'test_connection_' . time();
            $redis->set($testKey, 'test', 10);
            $value = $redis->get($testKey);
            $redis->del($testKey);
            $redis->close();

            if ($value !== 'test') {
                return $this->error('Redis读写测试失败');
            }

            return $this->success(null, 'Redis连接测试成功');
        } catch (\Exception $e) {
            return $this->error('Redis连接失败：' . $e->getMessage());
        }
    }
}
