<?php

namespace app\example\admin;

use app\example\service\dbservice;
use app\system\admin\Admin;
use app\example\model\ExampleSystem as SystemModel;
use app\example\model\ExampleGeneralize as GeneralizeModel;
use app\example\model\ExampleCategory as CategoryModel;
use think\Db;

class System extends Admin
{
    protected $hisiModel = 'ExampleSystem';
    protected $hisiValidate = 'ExampleSystem';

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

    public function save()
    {
        $data = request()->post();
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (in_array($key, ['level1', 'level2', 'level3', 'level4', 'level5']) && is_array($val)) {
                    list($min, $max) = $val;
                    $data[$key] = round(floatval($min), 1) . ',' . round(floatval($max), 1);
                }
            }
        }
        $res = SystemModel::where('id', 1)->update($data);

        if ($res) {
            $data = SystemModel::select(1);
            if (is_array($data)) {
                foreach ($data as $key => $val) {
                    if (in_array($key, ['level1', 'level2', 'level3', 'level4', 'level5']) && $val) {
                        $data[$key] = explode(',', $val);
                    }
                }
            }
            $this->assign('data', $data);
            $this->assign('hisiTabType', 0);
            $this->assign('hisiTabData', '');
            return $this->fetch('index');
        } else {
            return $this->error('未做修改', 'index');
        }

    }

    public function index()
    {
        if ($this->request->isAjax()) {
            $where = $data = [];
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            $keyword = $this->request->param('keyword/s');
            $cid = $this->request->param('cid/d');

            if ($cid) {
                $where[] = ['cid', '=', $cid];
            }
            if ($keyword) {
                $where[] = ['title', 'like', '%' . $keyword . '%'];
            }

            $data['data'] = SystemModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count'] = SystemModel::where($where)->count('id');
            $data['code'] = 0;
            return json($data);
        }
        if(request()->post()){
            $data = request()->post();
            if (is_array($data)) {
                foreach ($data as $key => $val) {
                    if (in_array($key, ['level1', 'level2', 'level3', 'level4', 'level5']) && is_array($val)) {
                        list($min, $max) = $val;
                        $data[$key] = round(floatval($min), 1) . ',' . round(floatval($max), 1);
                    }
                }
            }
            $data['custtext'] = implode(',', $data['custtext']);
            $data['custQQ'] = implode(',', $data['custQQ']);
            SystemModel::where('id', 1)->update($data);
        }
        $data = SystemModel::select(1);
        if ($data) {
            $data = json_decode(json_encode($data), true)[0] ?? [];
        }
        if (is_array($data)) {
            foreach ($data as $key => $val) {

                if (in_array($key, ['level1', 'level2', 'level3', 'level4', 'level5']) && $val) {
                    $data[$key] = explode(',', $val);
                }
            }

            $custtext = explode(',', $data['custtext']);
            $custQQ = explode(',', $data['custQQ']);
        }
        $this->assign('data', $data);
        $this->assign('custtext', $custtext);
        $this->assign('custQQ', $custQQ);
        // 分组切换类型 0无需分组切换，1单个分组，2多个分组切换[无链接]，3多个分组切换[有链接]，具体请看application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }
    /*
  *  推广配置
  * */
    public function generalize(){
        if(request()->post()){
            $data = request()->post();
            if (is_array($data['date'])) {
                foreach ($data['date'] as $key => $val) {
                    $Isinf=GeneralizeModel::where("id",$key)->count();
                    $da=['title'=>$val["title"],'content'=>$val["content"],'url'=>$val["url"]];
                    if($Isinf>0){
                        GeneralizeModel::where('id',$key)->update($da);
                    }else{
                        $da['addtime']=time();
                        GeneralizeModel::create($da);
                    }
                }
            }
        }
        $data = GeneralizeModel::select();
        if ($data) {
            $data = json_decode(json_encode($data), true) ?? [];
            if(!empty($data)){
                $data=array_column(array_map(function($v){
                    return ['id'=>$v['id'],'info'=>$v];
                },$data),'info','id');
            }
        }
        $this->assign('data', $data);
        // 分组切换类型 0无需分组切换，1单个分组，2多个分组切换[无链接]，3多个分组切换[有链接]，具体请看application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch('generalize');
    }
}
