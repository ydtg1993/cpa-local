<?php

namespace app\example\admin;
use app\system\admin\Admin;
use app\example\model\ExampleAds as AdsModel;
use app\example\model\ExampleCategory as CategoryModel;
use think\Db;

class Ads extends Admin
{
    protected $hisiModel = 'ExampleAds';
    protected $hisiValidate = 'ExampleAds';

    protected function initialize()
    {
        parent::initialize();
        if ($this->request->action() != 'index' && !$this->request->isPost()) {
            $category = CategoryModel::getSelect(CategoryModel::getChilds());
            $this->assign('category', $category);
        }
    }
    public function putstatus($id)
    {
        $data = [
            'status' => 1,
        ];
        AdsModel::update($data, ['id' => $id], true);
        $this->success('发布成功','index');
        if ($this->request->isAjax()) {

            $where      = $data = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 15);

            $data['data']   = AdsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count']  = AdsModel::where($where)->count('id');
            $data['code']   = 0;
            return json($data);

        }

        $this->assign('hisiTabType', 0);

        $this->assign('hisiTabData', '');
        return $this->fetch('index');
    }
    public function understatus($id)
    {
        $res = AdsModel::where('status',1)->count();
        if ($res <= 2){
            $this->error('至少有两条已发布公告');
        }
        $data = [
            'status' => 0,
        ];
        AdsModel::update($data, ['id' => $id], true);
        $this->success('下架成功','index');
        if ($this->request->isAjax()) {

            $where      = $data = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 15);

            $data['data']   = AdsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count']  = AdsModel::where($where)->count('id');
            $data['code']   = 0;
            return json($data);

        }

        $this->assign('hisiTabType', 0);

        $this->assign('hisiTabData', '');
        return $this->fetch('index');
    }
    public function recstatus($id)
    {
        $res = AdsModel::count();
        if ($res <= 2){
            $this->error('至少有两条已发布公告');
        }
        AdsModel::where(['id' => $id])->delete();
        $this->success('删除成功');
        if ($this->request->isAjax()) {
            $where      = $data = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 15);

            $data['data']   = AdsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count']  = AdsModel::where($where)->count('id');
            $data['code']   = 0;
            return json($data);

        }

        $this->assign('hisiTabType', 0);

        $this->assign('hisiTabData', '');
        return $this->fetch('index');
    }
    public function add1()
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

            $data['data']   = AdsModel::with('hasCategory')->where($where)->page($page)->order('sort desc')->limit($limit)->select();
            $data['count']  = AdsModel::where($where)->count('id');
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
        $data = request()->post();
        $data['ctime'] = time();
        $data['adsid'] = time();
        AdsModel::insert($data);
        $this->success('发布成功','index');
        return $this->fetch('index');

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

            $data['data']   = AdsModel::with('hasCategory')->order('sort desc')->where($where)->page($page)->limit($limit)->select();
            $data['count']  = AdsModel::where($where)->count('id');
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
