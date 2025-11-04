<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\service\MemberLevelService;
use think\facade\Log;

/**
 * 会员等级自动升级命令
 * 使用方法：php think member-level:upgrade
 */
class MemberLevelUpgrade extends Command
{
    protected function configure()
    {
        $this->setName('member-level:upgrade')
            ->setDescription('自动检查并升级会员等级')
            ->addOption('limit', 'l', \think\console\input\Option::VALUE_OPTIONAL, '每次处理的用户数量', 100);
    }

    protected function execute(Input $input, Output $output)
    {
        $startTime = microtime(true);
        $limit = $input->getOption('limit');

        $output->writeln('===========================================');
        $output->writeln('开始执行会员等级自动升级任务');
        $output->writeln('执行时间：' . date('Y-m-d H:i:s'));
        $output->writeln('处理数量限制：' . $limit);
        $output->writeln('===========================================');

        try {
            // 执行批量升级
            $result = MemberLevelService::batchCheckAndUpgrade($limit);

            $output->writeln('');
            $output->writeln('执行结果：');
            $output->writeln("- 检查用户总数：{$result['total']}");
            $output->writeln("- 成功升级数量：{$result['upgraded']}");
            $output->writeln("- 失败数量：{$result['failed']}");

            if (!empty($result['details'])) {
                $output->writeln('');
                $output->writeln('升级详情：');
                foreach ($result['details'] as $detail) {
                    $output->writeln('  - ' . $detail);
                }
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $output->writeln('');
            $output->writeln("任务执行完成，耗时：{$duration}秒");
            $output->writeln('===========================================');

            // 记录日志
            Log::info('会员等级自动升级任务执行完成', [
                'total' => $result['total'],
                'upgraded' => $result['upgraded'],
                'failed' => $result['failed'],
                'duration' => $duration,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('');
            $output->error('任务执行失败：' . $e->getMessage());
            $output->writeln('===========================================');

            // 记录错误日志
            Log::error('会员等级自动升级任务执行失败：' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
