<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class DropGameMessageTable extends Command
{
    protected function configure()
    {
        $this->setName('db:drop-game-message')
            ->setDescription('删除ss_game_message表');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始删除 ss_game_message 表...');

        Db::execute('DROP TABLE IF EXISTS ss_game_message');

        $output->writeln('✓ ss_game_message 表已删除');

        return 0;
    }
}