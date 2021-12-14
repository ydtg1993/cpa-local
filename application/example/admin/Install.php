<?php


namespace app\example\admin;


use app\example\service\dbservice;
use app\system\admin\Admin;
use think\Db;

class Install extends Admin
{
    public function history()
    {
        $dbservice = new dbservice();
        $app_info = $dbservice->db_start();
        if(!$app_info){
            $this->redirect('login');
        }
        $request   = $this->request;
        $startDate = $request->param("start_date");
        $endDate   = $request->param("end_date");
        if ($request->isPost()) {
            $listQuery = $dbservice->doSqlJob($app_info)->table("hisi_example_install_history_log eil");
            $countQuery = clone $listQuery;
            $page = $request->post("page");
            $limit = $request->post("limit");
            $list = $listQuery
                ->page($page)
                ->limit($limit)
                ->order('eil.id desc')
                ->column("eil.*");
            $data['data'] = array_values($list);//array_values，解决表格数据无法按照id降序问题
            $data['count'] = $countQuery->count("eil.id");
            $data['code'] = 0;
            return json($data);
        }
        $this->assign("start_date", $startDate);
        $this->assign("end_date", $endDate);
        return $this->fetch();
    }

    public function two_month()
    {
        $dbservice = new dbservice();
        $app_info = $dbservice->db_start();
        if(!$app_info){
            $this->redirect('login');
        }
        $request   = $this->request;
        $startDate = $request->param("start_date");
        $endDate   = $request->param("end_date");
        if ($request->isPost()) {
            $listQuery = $dbservice->doSqlJob($app_info)->table("hisi_example_install_log eil");
            $countQuery = clone $listQuery;
            $page = $request->post("page");
            $limit = $request->post("limit");
            $list = $listQuery
                ->page($page)
                ->limit($limit)
                ->order('eil.id desc')
                ->column("eil.*");
            $data['data'] = array_values($list);//array_values，解决表格数据无法按照id降序问题
            $data['count'] = $countQuery->count("eil.id");
            $data['code'] = 0;
            return json($data);
        }
        $this->assign("start_date", $startDate);
        $this->assign("end_date", $endDate);
        return $this->fetch();
    }
}