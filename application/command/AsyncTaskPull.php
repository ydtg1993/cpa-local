<?php


namespace app\command;

use app\common\libs\CurlData;
use app\common\libs\ServerRequest;
use app\example\model\ExampleNews as NewsModel;
use GuzzleHttp\Exception\GuzzleException;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Env;
use think\facade\Log;
use app\common\libs\SwooleService;
use think\exception\ValidateException;

class AsyncTaskPull extends Command
{
    protected function configure()
    {
        $this->setName('pull:install:data')->setDescription('start task!');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("开始执行！");
        $this->start($input,$output);
        $output->writeln("拉取安装数据执行成功！");
    }

    private function start($input,$output)
    {
        $service = new SwooleService();
        $service->task($input,$output,'pull:install:data');
    }
}