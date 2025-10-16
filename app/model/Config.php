<?php

namespace app\model;

use think\Model;

/**
 * 系统配置模型
 */
class Config extends Model
{
    protected $name = 'site_config';

    protected $autoWriteTimestamp = true;

    /**
     * 获取配置值
     */
    public static function getConfig($key, $default = '')
    {
        $config = self::where('config_key', $key)->find();
        return $config ? $config->config_value : $default;
    }

    /**
     * 设置配置值
     */
    public static function setConfig($key, $value)
    {
        $config = self::where('config_key', $key)->find();
        if ($config) {
            $config->config_value = $value;
            $config->save();
        } else {
            self::create([
                'config_key' => $key,
                'config_value' => $value
            ]);
        }
        return true;
    }

    /**
     * 批量设置配置
     */
    public static function setConfigs($configs)
    {
        foreach ($configs as $key => $value) {
            self::setConfig($key, $value);
        }
        return true;
    }

    /**
     * 获取所有配置
     */
    public static function getAllConfigs()
    {
        $configs = self::select();
        $result = [];
        foreach ($configs as $config) {
            $result[$config->config_key] = $config->config_value;
        }
        return $result;
    }
}
