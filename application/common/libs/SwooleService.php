<?php


namespace app\common\libs;
use think\Db;
use think\facade\Log;

class SwooleService
{

    const WORK_NUM = 10;
    const ACCESS_TOKEN = '97yzi45bgp0h80go7weiqugeq1upkarq';

    public function task($input,$output,$task_name)
    {
        $workers = [];
        $app_info = DB::table('hisi_example_app_channel')->where('status','0')->select();
        $length = count($app_info);
        if(!$length){
            return false;
        }
        if($length > self::WORK_NUM){
            $data=array_chunk($app_info,ceil($length/self::WORK_NUM));
            if(is_array($data) && !empty($data)){
                foreach ($data as $item){
                    $this->TaskDo($item,$task_name,$input,$output);
                }
            }
        }else{
            $this->TaskDo($app_info,$task_name,$input,$output);
        }
    }

    private function TaskDo($app_info,$task_name,$input,$output)
    {
        for($i = 0; $i < self::WORK_NUM; $i++) {
            $process = new \swoole_process(function(\swoole_process $worker) use($i, $app_info,$task_name,$input,$output) {
                // curl
                if(isset($app_info[$i])){
                    $content = $this->processData($app_info[$i],$task_name,$input,$output);
                    //将内容写入管道
                    $worker->write($content.PHP_EOL);
                }
            }, true);
            if(isset($app_info[$i])){
                $pid = $process->start();
                $workers[$pid] = $process;
            }

        }
        //获取管道内容
        foreach($workers as $process) {
            echo $process->read();
        }
    }

    private function processData($app_info,$task_name,$input,$output)
    {
        if($task_name == 'pull:install:data'){
            $this->pull_install_data($app_info,$input,$output);
            return $task_name."do success"."\r\n";
        }elseif ($task_name == 'delete:old:data'){
            $this->delete_old_data($app_info,$input,$output);
            return $task_name."do success"."\r\n";
        }elseif ($task_name == 'insert:data:to:history'){
            $this->insert_data_to_history($app_info,$input,$output);
            return $task_name."do success"."\r\n";
        }elseif ($task_name == 'today:install:data'){
            $this->insert_today_install_data($app_info,$input,$output);
            return $task_name."do success"."\r\n";
        }elseif ($task_name == 'today:install:history'){
            $this->insert_today_history_install_data($app_info,$input,$output);
            return $task_name."do success"."\r\n";
        }
    }

    private function insert_today_history_install_data($app_info,$input,$output)
    {
        $this->parameter_check($app_info);
        $users = $this->doSqlJob($app_info)->table('hisi_example_formsuser')->select();
        foreach ($users as $user) {
            $this->doSqlJob($app_info)->table('hisi_example_formsuser')->where('appid', $user['appid'])->update(['balance'=>0,'sum'=>0]);
            $this->doSqlJob($app_info)->table('hisi_example_install_total_log')->where('appid', $user['appid'])->delete();
        }
        // 1循环查询用户 2统计当日安装数量
        $this->doSqlJob($app_info)->startTrans();
        for($i=10;$i>=1;$i--) {
            foreach ($users as $user) {
                $day = "-{$i} day";
                $startTime = date('Y-m-d 00:00:00', strtotime($day));
                $endTime = date('Y-m-d 23:59:59', strtotime($day));
                $where = [];
                $where[] = ['status', '=', 1];
                $where[] = ['invite_time', '>=', $startTime];
                $where[] = ['invite_time', '<=', $endTime];
                $where[] = ['uid', '=', $user['appid']];

                $check = $this->doSqlJob($app_info)->table('hisi_example_install_total_log')->where(['appid'=>$user['appid'],'today'=>date('Y-m-d', strtotime($day))])->find();
                if($check){
                    continue;
                }
                $today_install_number = $this->doSqlJob($app_info)->table('hisi_example_install_log')->where($where)->count(); // 总安装量
                $today_android_install_number = $this->doSqlJob($app_info)->table('hisi_example_install_log')->where($where)->where('invite_user_os', '1')->count(); // 安卓装量
                $today_ios_install_number = $this->doSqlJob($app_info)->table('hisi_example_install_log')->where($where)->where('invite_user_os', '2')->count(); // IOS装量
                if ($today_install_number != 0) {
                    $insert = [];
                    $insert['email'] = $user['email'];
                    $insert['appid'] = $user['appid'];
                    $insert['sum_install'] = $today_install_number;
                    $insert['ios_install'] = $today_ios_install_number;
                    $insert['android_install'] = $today_android_install_number;
                    $insert['price'] = $this->getPriceHistory($app_info,$today_install_number);
                    $insert['today'] = $startTime;
                    $insert['ctime'] = date("Y-m-d H:i:s");
                    $insert['mtime'] = date("Y-m-d H:i:s");

                    try {
                        $this->doSqlJob($app_info)->table('hisi_example_install_total_log')->insert($insert); // 添加今日安装日志

                        $money = $insert['price'] * $today_install_number;
                        $this->doSqlJob($app_info)->table('hisi_example_formsuser')->where('appid', $user['appid'])->setInc('balance', $money); // 增加可用金额
                        $this->doSqlJob($app_info)->table('hisi_example_formsuser')->where('appid', $user['appid'])->setInc('sum', $money); // 增加累计金额
                        $output->writeln('email：' . $user['email'] . '时间：' . $startTime . '添加成功');
                    } catch (\Exception $e) {
                        $this->doSqlJob($app_info)->rollback();
                        $output->writeln("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
                        Log::error("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
                    }
                }
            }
        }
        $this->doSqlJob($app_info)->commit();
    }

    public function getPriceHistory($app_info,$price)
    {
        $first = $this->doSqlJob($app_info)->table('hisi_example_news')->value('first');
        if ($first) {
            return $first;
        }

        $system = $this->doSqlJob($app_info)->table('hisi_example_system')->where('id', '1')->find();
        $level1 = explode(',', $system['level1']);
        if ($level1[0] < $price && $level1[1] > $price) {
            return $system['first1'];
        }

        $level2 = explode(',', $system['level2']);
        if ($level2[0] < $price && $level2[1] >= $price) {
            return $system['first2'];
        }
        $level3 = explode(',', $system['level3']);
        if ($level3[0] < $price && $level3[1] >= $price) {
            return $system['first3'];
        }
        $level4 = explode(',', $system['level4']);
        if ($level4[0] < $price && $level4[1] >= $price) {
            return $system['first4'];
        }
        $level5 = explode(',', $system['level5']);
        if ($level5[0] < $price && $level5[1] >= $price) {
            return $system['first5'];
        }
    }

    private function insert_today_install_data($app_info,$input,$output)
    {
        $this->parameter_check($app_info);
        // 1循环查询用户 2统计当日安装数量
        $users = $this->doSqlJob($app_info)->table('hisi_example_formsuser')->select();
        foreach ($users as $user) {
            $startTime = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $endTime = date('Y-m-d 23:59:59', strtotime('-1 day'));
            $where = [];
            $where[] = ['status', '=', 1];
            $where[] = ['invite_time', '>=', $startTime];
            $where[] = ['invite_time', '<=', $endTime];
            $where[] = ['uid', '=', $user['appid']];
            $today_install_number = $this->doSqlJob($app_info)->table('hisi_example_install_log')->where($where)->count(); // 总安装量
            $today_android_install_number = $this->doSqlJob($app_info)->table('hisi_example_install_log')->where($where)->where('invite_user_os', '1')->count(); // 安卓装量
            $today_ios_install_number = $this->doSqlJob($app_info)->table('hisi_example_install_log')->where($where)->where('invite_user_os', '2')->count(); // IOS装量

            $insert = [];
            $insert['email'] = $user['email'];
            $insert['appid'] = $user['appid'];
            $insert['sum_install'] = $today_install_number;
            $insert['ios_install'] = $today_ios_install_number;
            $insert['android_install'] = $today_android_install_number;
            $insert['price'] = $this->getPrice($app_info,$today_install_number,$user['appid']);
            $insert['today'] = $startTime;
            $insert['ctime'] = date("Y-m-d H:i:s");
            $insert['mtime'] = date("Y-m-d H:i:s");
            $this->doSqlJob($app_info)->startTrans();
            try {
                if($this->check_exit($app_info,$user['email'],$user['appid'],$startTime)){
                    continue;
                }
                $this->doSqlJob($app_info)->table('hisi_example_install_total_log')->insert($insert); // 添加今日安装日志
                $money = $insert['price'] * $today_install_number;
                $this->doSqlJob($app_info)->table('hisi_example_formsuser')->where('appid', $user['appid'])->setInc('balance', $money); // 增加可用金额
                $this->doSqlJob($app_info)->table('hisi_example_formsuser')->where('appid', $user['appid'])->setInc('sum', $money); // 增加累计金额
                $this->doSqlJob($app_info)->commit();
                $output->writeln('email：' . $user['email'] . '时间：' . $startTime . '添加成功');
            } catch (\Exception $e) {
                $this->doSqlJob($app_info)->rollback();
                $output->writeln("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
                Log::error("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
            }
        }
    }

    private function check_exit($app_info,$email,$appid,$today)
    {
      return $this->doSqlJob($app_info)->table('hisi_example_install_total_log')
           ->where([
               ['email', '=', $email],
               ['appid', '=', $appid],
               ['today', '=', $today],
           ])->count();
    }

    private function getPrice($app_info,$price,$appid)
    {
        $first = $this->doSqlJob($app_info)->table('hisi_example_news')->where(['appid'=>$appid])->value('first');
        if ($first) {
            return $first;
        }

        $system = $this->doSqlJob($app_info)->table('hisi_example_system')->where('id', '1')->find();
        $level1 = explode(',', $system['level1']);
        if ($level1[0] <= $price && $level1[1] > $price) {
            return $system['first1'];
        }

        $level2 = explode(',', $system['level2']);
        if ($level2[0] < $price && $level2[1] >= $price) {
            return $system['first2'];
        }
        $level3 = explode(',', $system['level3']);
        if ($level3[0] < $price && $level3[1] >= $price) {
            return $system['first3'];
        }
        $level4 = explode(',', $system['level4']);
        if ($level4[0] < $price && $level4[1] >= $price) {
            return $system['first4'];
        }
        $level5 = explode(',', $system['level5']);
        if ($level5[0] < $price && $level5[1] >= $price) {
            return $system['first5'];
        }
        return 0;
    }

    private function pull_install_data($app_info,$input,$output)
    {
        $this->parameter_check($app_info);
        $startId = $this->doSqlJob($app_info)->table("hisi_example_install_log")
            ->limit(1)
            ->order("id", "desc")
            ->value("id");
        $startId = $startId == null ? 0 : $startId;
        $limit = 100;
        $curl = new CurlData();
        $send_data  = [
            'access_token'=>self::ACCESS_TOKEN,
            'appid'=>(string)$app_info['appid'],
            'limit' => (string)$limit,
            'start_id' => (string)$startId,
            'timestamp'=>(string)time()
        ];
        $get_sign = $curl->createSign($send_data);
        $send_data['sign'] = $get_sign??'';
        if(!$app_info['app_api_domain']){
            return false;
        }
        if(substr($app_info['app_api_domain'],-1) !='/'){
            $server_url = $app_info['app_api_domain'].'/install/log';
        }else{
            $server_url = $app_info['app_api_domain'].'install/log';
        }

        $serverRequest = $curl->curl_post($server_url,$send_data);
        if(isset($serverRequest) && $serverRequest){
            $respon_arr = json_decode($serverRequest,true);
        }else{
            return false;
        }
        if($respon_arr['state'] != 0){
            $output->writeln("请求错误!" . $respon_arr['message']??'');
            Log::error("请求错误!" . $respon_arr['message']??'');
            return false;
        }else{
            $data_info = $respon_arr['data'];
            $dataCount = 0;
            if(is_array($data_info) && !empty($data_info)){
                $dataCount = count($data_info);
            }
            $output->writeln("起始数据ID：" . $startId);
            $output->writeln("取到数据 " . $dataCount . " 条!");
            if($dataCount <= 0) {
                return false;
            }
            $insrtData = [];
            foreach ($data_info as $item){
                if(isset($insrtData[$item['invitees_uid']])){
                    continue;
                }
                $check = $this->doSqlJob($app_info)->table("hisi_example_install_log")
                    ->where('invite_uid',$item['invitees_uid'])
                    ->find();
                if($check !== null){
                    continue;
                }

                $proxy = $this->doSqlJob($app_info)->table("hisi_example_news")->where('appid', $item['uid'])->find();
                $status = 1; //状态默认为分成
                //是否无效
                if ($proxy && random_int(0, 99) < (int)$proxy->install_deduction_ratio) {
                    $status = 0; //不分成
                }
                $data = [
                    'id' => $item['id']??'0',
                    'uid' => $item['uid']??'0',
                    'invite_uid' => $item['invitees_uid']??'0',
                    'invite_time' => $item['created_at']??'0',
                    'user_nickname' => $item['nickname']??'',
                    'invite_nickname' => $item['invite_nickname']??'',
                    'invite_user_phonenumber' => $item['mobile']??'',
                    'invite_user_os' => $item['os']??'',
                    'status'  => $status,
                ];
                $insrtData[$item['invitees_uid']] = $data;
            }
            $this->doSqlJob($app_info)->table("hisi_example_install_log")->insertAll($insrtData);
            $output->writeln("写入数据成功!");
            if($dataCount >= $limit) {
                $output->writeln("查询下一页数据!");
                $this->pull_install_data($app_info,$input,$output);
            }
        }
    }


    private function delete_old_data($app_info,$input,$output)
    {
        $this->parameter_check($app_info);
        $count = $this->doSqlJob($app_info)
            ->table("hisi_example_install_history_log")->count();
        if(!$count){
            return false;
        }
        $two_month_before = date("Y-m-d H:i:s",strtotime("-2 month"));
        $this->doSqlJob($app_info)
            ->table("hisi_example_install_log")
            ->where('invite_time', '<= time', $two_month_before)->delete();
    }


    private function insert_data_to_history($app_info,$input,$output)
    {
        $this->parameter_check($app_info);
        $count = $this->doSqlJob($app_info)->table('hisi_example_install_history_log')->count();
        if(!$count){
            $this->doTask(0,$app_info);
        }else{
            $max_history_id = $this->doSqlJob($app_info)->table("hisi_example_install_history_log")->max('id');
            $max_id = $this->doSqlJob($app_info)->table("hisi_example_install_log")->max('id');
            if($max_history_id == $max_id){
                return false;
            }else{
                $this->doTask($max_history_id,$app_info);
            }
        }
    }

    private function parameter_check($app_info)
    {
        if(!isset($app_info['app_db_name']) ||  !$app_info['app_db_name']){
            return false;
        }
        if(!isset($app_info['app_db_user']) || !$app_info['app_db_user']){
            return false;
        }
        if(!isset($app_info['app_db_port']) || !$app_info['app_db_port']){
            return false;
        }
        if(!isset($app_info['app_db_addr']) || !$app_info['app_db_addr']){
            return false;
        }
        if(!isset($app_info['app_db_pwd']) || !$app_info['app_db_pwd']){
            return false;
        }
    }




    function InsertData($max_history_id,$app_info)
    {
        $tableName = "hisi_example_install_log";
        if(!$max_history_id){
            $sql = "select *  from $tableName";
        }else{
            $sql = "select *  from $tableName where id > $max_history_id";
        }

        $con = new \mysqli($app_info['app_db_addr'],$app_info['app_db_user'],$app_info['app_db_pwd'],$app_info['app_db_name'],$app_info['app_db_port']);
        $con->query("SET NAMES utf8");
        if(mysqli_connect_errno()){
            echo '数据库连接错误！错误信息是：'.mysqli_connect_error();
            exit;
        }
        $rest = $con->query($sql, MYSQLI_USE_RESULT);// 该处用MYSQLI_USE_RESULT 不缓存结果集中，也是为了避免内存溢出，相当于mysql_unbuffered_query
        foreach ($rest as $row) {
            yield $row; // 使用生成器返回结果
        }
        $con->close();
    }

    private function doTask($max_history_id,$app_info)
    {
        $data = $this->InsertData($max_history_id,$app_info);
        $table_name = 'hisi_example_install_history_log';
        foreach ($data as $item) {
            if(isset($item['op_uptime']) && $item['op_uptime']){
                $sql = "insert into $table_name(`id`, `uid`, `invite_uid`, `invite_time`, `user_nickname`, `invite_nickname`,  `invite_user_phonenumber`,`invite_user_os`,`op_uptime`,`status`) values";
                $sql.="('".$item['id']."','".$item['uid']."','".$item['invite_uid']."','".$item['invite_time']."','".$item['user_nickname']."','".$item['status']."','".$item['invite_user_phonenumber']."','".$item['invite_user_os']."','".$item['op_uptime']."','".$item['status']."'),";
            }else{
                $sql = "insert into $table_name(`id`, `uid`, `invite_uid`, `invite_time`, `user_nickname`, `invite_nickname`,  `invite_user_phonenumber`,`invite_user_os`,`status`) values";
                $sql.="('".$item['id']."','".$item['uid']."','".$item['invite_uid']."','".$item['invite_time']."','".$item['user_nickname']."','".$item['status']."','".$item['invite_user_phonenumber']."','".$item['invite_user_os']."','".$item['status']."'),";
            }
            $sql = substr($sql,0,strlen($sql)-1);
            $this->insert_into_new_table($sql,$app_info);
        }
    }

    private function  insert_into_new_table($sql,$app_info)
    {

        $this->doSqlJob($app_info)->execute($sql);
    }

    private function doSqlJob($app_info)
    {
        return Db::connect([
            'type' => 'mysql',
            'dsn' => '',
            'hostname' => $app_info['app_db_addr'],
            'database' => $app_info['app_db_name'],
            'username' => $app_info['app_db_user'],
            'password' => $app_info['app_db_pwd'],
            'hostport' => $app_info['app_db_port'],
            'params' => [],
            'charset' => 'utf8',
            'prefix' => '',
            'break_reconnect'=>true
        ]);
    }
}