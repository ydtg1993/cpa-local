<?php


namespace app\command;

use GuzzleHttp\Exception\GuzzleException;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

/**
 * 今日安装量脚本 php think TodayInstall
 * Class TodayInstall
 * @package app\command
 */
class RunSql extends Command
{
    protected function configure()
    {
        $this->setName('RunSql')->setDescription('sql初始化');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("开始执行！");
        Db::execute("alter table `hisi_system_user` add column `apps` varchar(500) DEFAULT '' COMMENT '渠道app权限'");
        $output->writeln("执行成功！");
    }


}