<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\controller\api\StaticBuild;
use think\facade\Request;

class BuildStatic extends Command
{
    protected function configure()
    {
        $this->setName('build:static')
            ->setDescription('批量生成静态页面（定时任务）');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始批量生成静态页面...');

        try {
            // 创建临时请求对象
            $request = Request::create('');

            // 创建静态生成控制器实例
            $staticBuild = new StaticBuild();
            $staticBuild->request = $request;

            // 执行批量生成
            $result = $staticBuild->buildAll();

            if ($result->getData()['code'] == 200) {
                $data = $result->getData()['data'];
                $output->info("生成完成！");
                $output->info("首页: {$data['index']} 个");
                $output->info("文章: {$data['articles']} 个");
                $output->info("分类: {$data['categories']} 个");
                $output->info("页面: {$data['pages']} 个");
                $output->info("失败: {$data['failed']} 个");
            } else {
                $output->error('批量生成失败');
            }

        } catch (\Exception $e) {
            $output->error('生成失败：' . $e->getMessage());
        }

        $output->writeln('批量生成任务结束');
    }
}
