<?php
namespace app\service\tag;

use think\facade\Cache;
use think\facade\Config;

/**
 * 内容位置/区块标签服务类
 * 处理内容区块的数据查询
 */
class PositionTagService
{
    /**
     * 根据位置名称获取内容块
     *
     * @param string $position 位置名称
     * @return array
     */
    public static function getByPosition($position)
    {
        if (empty($position)) {
            return [];
        }

        // 尝试从缓存获取
        $cacheKey = 'position_blocks_' . $position;
        $blocks = Cache::get($cacheKey);

        if ($blocks !== false) {
            return $blocks;
        }

        // 从配置文件获取位置块定义
        $positions = Config::get('positions', []);

        if (!isset($positions[$position])) {
            return [];
        }

        $blocks = $positions[$position];

        // 确保返回数组格式
        if (!is_array($blocks)) {
            return [];
        }

        // 如果是单个块，转换为数组
        if (isset($blocks['title']) || isset($blocks['content'])) {
            $blocks = [$blocks];
        }

        // 缓存1小时
        Cache::set($cacheKey, $blocks, 3600);

        return $blocks;
    }

    /**
     * 设置位置块（用于后台管理）
     *
     * @param string $position 位置名称
     * @param array $blocks 内容块数组
     * @return bool
     */
    public static function setPosition($position, $blocks)
    {
        if (empty($position)) {
            return false;
        }

        // 清除缓存
        $cacheKey = 'position_blocks_' . $position;
        Cache::delete($cacheKey);

        // 这里可以扩展为保存到数据库
        // 目前使用配置文件方式

        return true;
    }

    /**
     * 获取所有位置定义
     *
     * @return array
     */
    public static function getAllPositions()
    {
        return Config::get('positions', []);
    }
}
