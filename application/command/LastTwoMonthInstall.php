<?php


namespace app\command;


use app\common\libs\SwooleService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class LastTwoMonthInstall extends Command
{
    protected function configure()
    {
        $this->setName('delete:old:data')->setDescription('start task!');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("开始执行！");
        $this->start($input,$output);
        $output->writeln("近2月安装数据整理任务执行成功！");
    }

    private function start($input,$output)
    {
        $service = new SwooleService();
        $service->task($input,$output,'delete:old:data');
    }
}