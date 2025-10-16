<?php

namespace app\controller\api;

use app\BaseController;
use app\common\Response;
use app\model\Config as ConfigModel;
use think\Request;

/**
 * 系统配置控制器
 */
class Config extends BaseController
{
    /**
     * 获取所有配置
     */
    public function index()
    {
        try {
            $configs = ConfigModel::getAllConfigs();
            return Response::success($configs);
        } catch (\Exception $e) {
            return Response::error('获取配置失败：' . $e->getMessage());
        }
    }

    /**
     * 保存配置
     */
    public function save(Request $request)
    {
        try {
            $data = $request->post();

            if (empty($data)) {
                return Response::error('配置数据不能为空');
            }

            ConfigModel::setConfigs($data);

            return Response::success([], '配置保存成功');
        } catch (\Exception $e) {
            return Response::error('配置保存失败：' . $e->getMessage());
        }
    }
}
