<?php

namespace app\example\admin;

use app\example\model\ExampleNews;
use app\example\model\ExampleOrder;
use app\example\model\ExampleProxy;
use app\example\model\ExampleProxyT;
use app\system\admin\Admin;
use app\example\model\ExampleForms as FormsModel;
use app\example\model\ExampleFormsuser as Formsuser;
use app\example\model\ExampleCategory as CategoryModel;
use think\Db;

class ProxyT extends Admin
{
    protected $hisiModel = 'ExampleForms';
    protected $hisiValidate = 'ExampleForms';

    protected function initialize()
    {
        parent::initialize();
        if ($this->request->action() != 'index' && !$this->request->isPost()) {
            $category = CategoryModel::getSelect(CategoryModel::getChilds());
            $this->assign('proxyT', $category);
        }
    }

    public function index()
    {
        $where = [];
        if ($this->request->isAjax()) {
            $data = [];
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            $remark = $this->request->param('remark/d');
            $level = $this->request->param('level/d');
            $type = $this->request->param('type/d');
            $startDate = urldecode($this->request->param('startDate/s'));
            $endDate = urldecode($this->request->param('endDate/s'));
            $appid = $this->request->param('appid/d');
            if ($appid) {
                $where[] = ['appid', '=', $appid];
            }
            if ($startDate) {
                $startDate = str_replace(['+'], ' ', $startDate);
                $startTime = strtotime($startDate);
                $where[] = ['datetime', '>=', $startTime];
            }
            if ($endDate) {
                $endDate = str_replace(['+'], ' ', $endDate);
                $endTime = strtotime($endDate);
                $where[] = ['datetime', '<=', $endTime];
            }
            if ($remark >= 0) {
                $where[] = ['remark', '=', (int)$remark];
            }
            if ($level)
                $where[] = ['level', '=', (int)$level];
            if ($type == 1)
                $data['data'] = ExampleOrder::where($where)->field(['appid', 'remark', 'datetime', 'userid', 'level', 'orderno', 'amount', 'profit'])->order('datetime desc')->select();
            else
                $data['data'] = ExampleOrder::where($where)->field(['appid', 'remark', 'datetime', 'userid', 'level', 'orderno', 'amount', 'profit'])->page($page)->limit($limit)->order('datetime desc')->select();
            $d_tmp = $data['data'];
            foreach ($d_tmp as $k => &$v) {
                $v['datetime'] = date('Y-m-d H:i:s', $v['datetime']);
            }
            unset($v);
            $data['data'] = $d_tmp;
            $data['count'] = ExampleOrder::where($where)->count('id');
            $data['code'] = 0;
            return json($data);
        }
        $appid = $this->request->get('appid');
        $name = $this->request->get('name');
        $startDate = $this->request->get('startDate');
        $endDate = $this->request->get('endDate');
        $installWhere = [];
        if ($appid) {
            $where[] = ['appid', '=', $appid];
            $installWhere[] = ['appid', '=', $appid];
        }
        if ($startDate) {
            $startDate = str_replace(['+'], ' ', $startDate);
            $startTime = strtotime($startDate);
            $where[] = ['datetime', '>=', $startTime];
            $installWhere[] = ['ctime', '>=', substr($startDate, 0, 10)];
        }
        if ($endDate) {
            $endDate = str_replace(['+'], ' ', $endDate);
            $endTime = strtotime($endDate);
            $where[] = ['datetime', '<=', $endTime];
            $installWhere[] = ['ctime', '<=', substr($endDate, 0, 10)];
        }
        //首充
        $firstMoney = ExampleOrder::where($where)->where('remark', 1)->sum('amount');
        //续费
        $afterMoney = ExampleOrder::where($where)->where('remark', 0)->sum('amount');
        //安装量
        $installNum =
            Db::table("hisi_example_formuser_install_log")->where($installWhere)->sum("installnum");
        $this->assign('installNum', $installNum);
        $this->assign('remark', $this->request->get('remark'));
        $this->assign('level', $this->request->get('level'));
        $this->assign('name', $name);
        $this->assign('appid', $appid);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('firstMoney', $firstMoney);
        $this->assign('afterMoney', $afterMoney);
        // 分组切换类型 0无需分组切换，1单个分组，2多个分组切换[无链接]，3多个分组切换[有链接]，具体请看application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }

    public function new1()
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

            $data['data'] = ExampleProxyT::where($where)->page($page)->limit($limit)->select();
            $data['count'] = ExampleProxyT::where($where)->count('id');
            $data['code'] = 0;
            return json($data);

        }


        // 分组切换类型 0无需分组切换，1单个分组，2多个分组切换[无链接]，3多个分组切换[有链接]，具体请看application/example/view/layout.html
        $appid = $this->request->get('appid');
        $stime = $this->request->get('appid');
        $etime = $this->request->get('appid');
        $this->assign('hisiTabType', 0);
        $this->assign('appid', $appid);
        $this->assign('stime', $stime);
        $this->assign('etime', $etime);
        // tab切换数据
        // $hisiTabData = [
        //     ['title' => '后台首页', 'url' => 'system/index/index'],
        // ];
        // current 可不传
        // $this->assign('hisiTabData', ['menu' => $hisiTabData, 'current' => 'system/index/index']);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }

    public function proxy()
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

            $data['data'] = FormsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count'] = FormsModel::where($where)->count('id');
            $data['code'] = 0;
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
