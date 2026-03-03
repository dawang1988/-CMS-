<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CheckBannerTable extends Command
{
    protected function configure()
    {
        $this->setName('db:check-banner')
            ->setDescription('检查ss_banner表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('检查 ss_banner 表...');

        $result = Db::query("SHOW TABLES LIKE 'ss_banner'");

        if (empty($result)) {
            $output->writeln('✗ ss_banner 表不存在');
            return 1;
        }

        $output->writeln('✓ ss_banner 表存在');

        $count = Db::table('ss_banner')->count();
        $output->writeln("  记录数: {$count}");

        return 0;
    }
}