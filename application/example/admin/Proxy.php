<?php

namespace app\example\admin;
use app\example\model\ExampleNews;
use app\example\model\ExampleProxy;
use app\example\model\ExampleProxy as ProxyModel;
use app\example\service\dbservice;
use app\system\model\SystemUser as UserModel;
use app\system\admin\Admin;
use app\example\model\ExampleCategory as CategoryModel;
use think\Db;



class Proxy extends Admin
{
    protected $hisiModel = 'ExampleProxy';
    protected $hisiValidate = 'ExampleProxy';

    protected function initialize()
    {
        parent::initialize();
        if ($this->request->action() != 'index' && !$this->request->isPost()) {
            $category = CategoryModel::getSelect(CategoryModel::getChilds());
            $this->assign('category', $category);
        }
        $dbservice = new dbservice();
        $app_info = $dbservice->db_start();
        if(!$app_info){
            $this->redirect('login');
        }
        $_SESSION['app_db_info'] = json_encode($app_info);
    }

    public function editproxy($id)
    {
        $info = ProxyModel::where('id',$id)->select();
        $this->assign('info', $info[0]);
        return $this->fetch();
    }

    public function add1()
    {
        $data = request()->post();
        $group = str_replace(PHP_EOL,';',$data['groupname']);
        $group_arr = explode(';',$group);
        $data['num'] = count($group_arr);
        if ($data['name'] == ''){
            return $this->error('组名不能为空');
        }
        if ($data['groupname'] == ''){
            return $this->error('组员账号不能为空');
        }
        $res1 = UserModel::where('username',$data['appid'])->count();
        if ($data['appid'] == ''){
            return $this->error('组长账号不能为空');
        }
        if ($data['cust'] == ''){
            return $this->error('组长客服qq不能为空');
        }
        if ($res1){
            $res = ProxyModel::create($data);
            ExampleNews::where('appid','in',$group_arr)->update(['cid'=>$data['name']]);
            if ($res){
                return $this->success('添加成功');
            }else{

            }
        }else{
            $this->error('组长账号不存在','index');
        }


    }


    public function edit1()
    {
        $data = request()->post();
        $id = $data['id'];
        unset($data['id']);
        $group = str_replace(PHP_EOL,';',$data['groupname']);
        $group_arr = explode(';',$group);
        $data['num'] = count($group_arr);
        if ($data['name'] == ''){
            return $this->error('组名不能为空');
        }
        if ($data['groupname'] == ''){
            return $this->error('组员账号不能为空');
        }
        if ($data['appid'] == ''){
            return $this->error('组长账号不能为空');
        }
        if ($data['cust'] == ''){
            return $this->error('组长客服qq不能为空');
        }
        $res = ProxyModel::where('id',$id)->update($data);

        ExampleNews::where('appid','in',$group_arr)->update(['cid'=>$data['name']]);
        if ($res){
//            return $this->success('修改成功','index');
            return $this->success('修改成功','index');
        }else{
            return $this->error('修改失败');
        }

    }
    public function index()
    {
        if ($this->request->isAjax()) {

            $where      = $data = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 15);
            $keyword    = $this->request->param('keyword/s');
            $cid        = $this->request->param('cid/d');

            if ($cid) {
                $where[] = ['cid', '=', $cid];
            }
            if ($keyword) {
                $where[] = ['title', 'like', '%'.$keyword.'%'];
            }

            $data['data']   = ProxyModel::with('hasCategory')->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            $data['count']  = ProxyModel::where($where)->count('id');
            $data['code']   = 0;
            return json($data);

        }


        // 分组切换类型 0无需分组切换，1单个分组，2多个分组切换[无链接]，3多个分组切换[有链接]，具体请看application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        // tab切换数据
        // $hisiTabData = [
        //     ['title' => '后台首页', 'url' => 'system/index/index'],
        // ];
        // current 可不传
        // $this->assign('hisiTabData', ['menu' => $hisiTabData, 'current' => 'system/index/index']);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }


}
