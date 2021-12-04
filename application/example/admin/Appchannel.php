<?php


namespace app\example\admin;


use app\system\admin\Admin;
use think\Db;

class Appchannel extends Admin
{
    public function list()
    {
        $request   = $this->request;
        $startDate = $request->param("start_date");
        $endDate   = $request->param("end_date");
        $status    = $request->param("status");
        $appid     = htmlspecialchars($request->param("appid"));
        if ($request->isPost()) {
            $listQuery = Db::table("hisi_example_app_channel channel");
            $countQuery = clone $listQuery;
            $page = $request->post("page");
            $limit = $request->post("limit");
            if($status != "") {
                $listQuery->where("channel.status", "=", $status);
                $countQuery->where("channel.status", "=", $status);
            }
            if($appid) {
                $listQuery->where("channel.appid", "=", $appid);
                $countQuery->where("channel.appid", "=", $appid);
            }
            $list = $listQuery
                ->page($page)
                ->limit($limit)
                ->order('channel.id desc')
                ->column("channel.*");
            $data['data'] = array_values($list);//array_values，解决表格数据无法按照id降序问题
            $data['count'] = $countQuery->count("channel.id");
            $data['code'] = 0;
            return json($data);
        }
        $this->assign("start_date", $startDate);
        $this->assign("end_date", $endDate);
        $this->assign("status", $status);
        $this->assign("appid", $appid);
        return $this->fetch();
    }

    public function xiajia($id)
    {
        Db::table("hisi_example_app_channel")->where('id', $id)
            ->update(['status' => '1']);
        $this->redirect('admin.php/example/appchannel/list');
    }

    public function editappchannel($id)
    {
        $listQuery = Db::table("hisi_example_app_channel");
        $listQuery->where("id", "=", $id);
        $list = $listQuery->find();
        $this->assign("list", $list);
        return $this->fetch('editappchannel');
    }

    public function addappchannel()
    {
        return $this->fetch('addappchannel');
    }

    public function adddatado()
    {
        $request   = $this->request;
        $appid = htmlspecialchars($request->param("appid"));
        $app_name = htmlspecialchars($request->param("app_name"));
        $app_db_name   = htmlspecialchars($request->param("app_db_name"));
        $app_db_port   = htmlspecialchars($request->param("app_db_port"));
        $app_db_user   = htmlspecialchars($request->param("app_db_user"));
        $app_db_pwd   = htmlspecialchars($request->param("app_db_pwd"));
        $app_db_addr   = htmlspecialchars($request->param("app_db_addr"));
        $app_api_domain   = htmlspecialchars($request->param("app_api_domain"));
        $app_register_domain   = htmlspecialchars($request->param("app_register_domain"));
        $status    = $request->param("status");
        if(!$appid){
            $this->error('提交appid不能为空！');
        }
        if(!$app_name){
            $this->error('提交ap名称不能为空！');
        }
        if(!$app_db_name){
            $this->error('提交appDB名称不能为空！');
        }
        if(!$app_db_user){
            $this->error('提交app数据库用户名不能为空！');
        }
        if(!$app_db_port){
            $this->error('提交app数据库端口号不能为空！');
        }
        if(!$app_db_pwd){
            $this->error('提交app数据库密码不能为空！');
        }
        if(!$app_db_addr){
            $this->error('提交app数据库请求地址不能为空！');
        }
        if(!$app_api_domain){
            $this->error('提交app库请求api地址不能为空！');
        }
        Db::table("hisi_example_app_channel")->insert(['appid'=>$appid,
            'app_name'=>$app_name,'app_db_name'=>$app_db_name,'app_db_port'=>$app_db_port,'app_db_user'=>$app_db_user,'app_db_pwd'=>$app_db_pwd,
            'app_db_addr'=>$app_db_addr,'app_api_domain'=>$app_api_domain,'app_register_domain'=>$app_register_domain??'','status'=>$status,
        ]);
        $this->redirect('admin.php/example/appchannel/list');
    }

    public function editdatado()
    {
        $request   = $this->request;
        $appid = htmlspecialchars($request->param("appid"));
        $app_name = htmlspecialchars($request->param("app_name"));
        $app_db_name   = htmlspecialchars($request->param("app_db_name"));
        $app_db_port   = htmlspecialchars($request->param("app_db_port"));
        $app_db_user   = htmlspecialchars($request->param("app_db_user"));
        $app_db_pwd   = htmlspecialchars($request->param("app_db_pwd"));
        $app_db_addr   = htmlspecialchars($request->param("app_db_addr"));
        $app_api_domain   = htmlspecialchars($request->param("app_api_domain"));
        $app_register_domain   = htmlspecialchars($request->param("app_register_domain"));
        $status    = $request->param("status");
        $hidden_id = $request->param("hidden_id");
        Db::table("hisi_example_app_channel")->where('id', $hidden_id)->update(['appid'=>$appid,
            'app_name'=>$app_name,'app_db_name'=>$app_db_name,'app_db_port'=>$app_db_port,'app_db_user'=>$app_db_user,'app_db_pwd'=>$app_db_pwd,
            'app_db_addr'=>$app_db_addr,'app_api_domain'=>$app_api_domain,'app_register_domain'=>$app_register_domain,'status'=>$status,
            ]);
        $this->redirect('admin.php/example/appchannel/list');
    }



    public function shangjia($id)
    {
        Db::table("hisi_example_app_channel")->where('id', $id)
            ->update(['status' => '0']);
        $this->redirect('admin.php/example/appchannel/list');
    }
}