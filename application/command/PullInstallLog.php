<?php


namespace app\command;


use app\common\libs\ServerRequest;
use app\example\model\ExampleNews as NewsModel;
use GuzzleHttp\Exception\GuzzleException;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class PullInstallLog extends Command
{
    protected function configure(){
        $this->setName('PullInstallLog')->setDescription('拉取安装量数据');
    }

    protected function execute(Input $input, Output $output){
        $output->writeln("开始执行！");
        $this->saveInstallLog($input, $output);
        $output->writeln("执行成功！");
    }

    private function saveInstallLog(Input $input, Output $output) {
        $startId = Db::table("hisi_example_install_log")
            ->limit(1)
            ->order("id", "desc")
            ->value("id");
        $startId = $startId == null ? 0 : $startId;
//        $startId = 2006879;
        $limit = 100;
        $body = [
            'start_id' => $startId,
            'limit' => $limit
        ];
        $serverRequest = new ServerRequest();
        try{
            $respone = $serverRequest->request(ServerRequest::$_POST, "install/log", $body);
            if($respone == false) {
                $output->writeln("返回值错误！");
                $output->writeln($serverRequest->getResponseContent());
                Log::error("返回值错误!");
                Log::error($serverRequest->getResponseContent());
                return;
            }
            if($respone->getState() != 0) {
                $output->writeln("请求错误!" . $respone->getMessage());
                Log::error("请求错误!" . $respone->getMessage());
                return;
            }
            $dataCount = count($respone->getData());
            $output->writeln("起始数据ID：" . $startId);
            $output->writeln("取到数据 " . $dataCount . " 条!");
            if($dataCount <= 0) {
                return;
            }
            $insrtData = [];
            foreach ($respone->getData() as $item) {
                if(isset($insrtData[$item['invitees_uid']])){
                    continue;
                }
                $check = Db::table("hisi_example_install_log")
                    ->where('invite_uid',$item['invitees_uid'])
                    ->find();
                if($check !== null){
                    continue;
                }

                $proxy = NewsModel::where('appid', $item['uid'])->find();
                $status = 1; //状态默认为分成
                //是否无效
                if ($proxy && random_int(0, 99) < (int)$proxy->install_deduction_ratio) {
                    $status = 0; //不分成
                }

                $data = [
                    'id' => $item['id'],
                    'uid' => $item['uid'],
                    'invite_uid' => $item['invitees_uid'],
                    'invite_time' => $item['created_at'],
                    'user_nickname' => $item['nickname'],
                    'invite_nickname' => $item['invite_nickname'],
                    'invite_user_phonenumber' => $item['mobile'],
                    'invite_user_os' => $item['os'],
                    'status'                  => $status,
                ];
                $insrtData[$item['invitees_uid']] = $data;
            }
            Db::name("example_install_log")->insertAll($insrtData);
            $output->writeln("写入数据成功!");
            if($dataCount >= $limit) {
                $output->writeln("查询下一页数据!");
                $this->saveInstallLog($input, $output);
            }
        }catch (GuzzleException $e) {
            $output->writeln("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
            Log::error("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
        }
    }

}