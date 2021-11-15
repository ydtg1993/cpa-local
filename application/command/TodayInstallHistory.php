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
class TodayInstallHistory extends Command
{
    protected function configure()
    {
        $this->setName('TodayInstallHistory')->setDescription('历史安装量统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("开始执行！");
        $this->saveInstallLog($input, $output);
        $output->writeln("执行成功！");
    }

    private function saveInstallLog(Input $input, Output $output)
    {
        $users = Db::table('hisi_example_formsuser')->select();
        foreach ($users as $user) {
            Db::table('hisi_example_formsuser')->where('appid', $user['appid'])->update(['balance'=>0,'sum'=>0]);
            Db::table('hisi_example_install_total_log')->where('appid', $user['appid'])->delete();
        }
        // 1循环查询用户 2统计当日安装数量
        Db::startTrans();
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

                $check = Db::table('hisi_example_install_total_log')->where(['appid'=>$user['appid'],'today'=>date('Y-m-d', strtotime($day))])->find();
                if($check){
                    continue;
                }
                $today_install_number = Db::table('hisi_example_install_log')->where($where)->count(); // 总安装量
                $today_android_install_number = Db::table('hisi_example_install_log')->where($where)->where('invite_user_os', '1')->count(); // 安卓装量
                $today_ios_install_number = Db::table('hisi_example_install_log')->where($where)->where('invite_user_os', '2')->count(); // IOS装量

                if ($today_install_number != 0) {
                    $insert = [];
                    $insert['email'] = $user['email'];
                    $insert['appid'] = $user['appid'];
                    $insert['sum_install'] = $today_install_number;
                    $insert['ios_install'] = $today_ios_install_number;
                    $insert['android_install'] = $today_android_install_number;
                    $insert['price'] = $this->getPrice($today_install_number);
                    $insert['today'] = $startTime;
                    $insert['ctime'] = date("Y-m-d H:i:s");
                    $insert['mtime'] = date("Y-m-d H:i:s");

                    try {
                        Db::table('hisi_example_install_total_log')->insert($insert); // 添加今日安装日志

                        $money = $insert['price'] * $today_install_number;
                        Db::table('hisi_example_formsuser')->where('appid', $user['appid'])->setInc('balance', $money); // 增加可用金额
                        Db::table('hisi_example_formsuser')->where('appid', $user['appid'])->setInc('sum', $money); // 增加累计金额
                        $output->writeln('email：' . $user['email'] . '时间：' . $startTime . '添加成功');
                    } catch (\Exception $e) {
                        Db::rollback();
                        $output->writeln("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
                        Log::error("请求错误" . $e->getMessage() . "--" . $e->getFile() . $e->getLine());
                    }
                }
            }
        }
        Db::commit();
    }

    /**
     * @param $price
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPrice($price)
    {
        $first = Db::table('hisi_example_news')->value('first');
        if ($first) {
            return $first;
        }

        $system = Db::table('hisi_example_system')->where('id', '1')->find();
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

}