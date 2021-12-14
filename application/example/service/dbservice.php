<?php


namespace app\example\service;


use think\Db;

class dbservice
{
    public function db_start()
    {
        if(isset($_SESSION['app_selected']) && $_SESSION['app_selected']){
            $app_selected = $_SESSION['app_selected'];
            $db_info = Db::table("hisi_example_app_channel")->where('id',$app_selected)
                ->where('status',0)
                ->find();
            if(is_array($db_info) && !empty($db_info)){
                return $db_info;
            }
            return false;
        }
        return false;
    }

    public function doSqlJob($app_info)
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