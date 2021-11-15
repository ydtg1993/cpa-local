<?php

namespace app\example\admin;

use app\example\model\ExampleMoney as MoneyModel;
use app\example\model\ExampleNews;
use app\example\model\ExampleOrder as OrderModel;
use app\example\model\ExampleOrder;
use app\example\model\ExampleProxy;
use app\system\admin\Admin;
use app\example\model\ExampleForms as FormsModel;
use app\example\model\ExampleFormsuser as Formsuser;
use app\example\model\ExampleCategory as CategoryModel;
use app\example\model\ExampleHistory as HistoryModel;
use think\Db;

class Forms extends Admin
{
    protected $hisiModel = 'ExampleForms';
    protected $hisiValidate = 'ExampleForms';

    protected function initialize()
    {
        parent::initialize();
        if ($this->request->action() != 'index' && !$this->request->isPost()) {
            $category = CategoryModel::getSelect(CategoryModel::getChilds());
            $this->assign('category', $category);
        }
    }

    protected function spellingParams(array $arr)
    {
        ksort($arr);
        $params = [];
        foreach ($arr as $key => $val) {
            $params[] = "{$key}={$val}";
        }
        return implode('&', $params);
    }

    protected function makeSign(array $arr)
    {
        $signKeyArr = ['key' => 'ep-*s@5bWKLceEcN'];
        $arr = array_merge($arr, $signKeyArr);
        $signStr = $this->spellingParams($arr);
        return strtoupper(md5($signStr));
    }

    protected function getHttp($url, $header)
    {
        $data = [
            'status' => false,
            'data' => '',
        ];
        $ch = curl_init();
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//header请求。如不需要可以去掉
        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            $data['data'] = $error;
            return $data;
        }
        curl_close($ch);
        $data['status'] = true;
        $data['data'] = $response;
        return $data;
    }

    public function index()
    {
        $order = MoneyModel::where('status', 3)->whereTime('ctime', 'today')->select();
        $order1 = MoneyModel::whereTime('ctime', 'today')->select();
        $drawing = 0;
        $drawingpass = 0;
        $n = 0;
        foreach ($order1 as $key => $value) {
            $drawing += $value['money'];
        }
        foreach ($order as $key => $value) {
            $n++;
            $drawingpass += $value['money'];
        }
        $data['drawing'] = $drawing;
        $data['drawingpass'] = $drawingpass;
        /****************************之前老代码，今日提款申请和今日出款额************************/
        $data['today_count'] = Db::table("hisi_example_install_log eil")
            //->join("hisi_example_news en", "eil.uid = en.appid")
            ->whereTime("eil.invite_time", "today")
            ->count("eil.id");
        $data['today_android_count'] = Db::table("hisi_example_install_log eil")
            //->join("hisi_example_news en", "eil.uid = en.appid")
            ->whereTime("eil.invite_time", "today")
            ->where("eil.invite_user_os", "=", "1")
            ->count("eil.id");
        $data['today_ios_count'] = Db::table("hisi_example_install_log eil")
            //->join("hisi_example_news en", "eil.uid = en.appid")
            ->whereTime("eil.invite_time", "today")
            ->where("eil.invite_user_os", "=", "2")
            ->count("eil.id");
        $data['all_count'] = Db::table("hisi_example_install_log eil")
            //->join("hisi_example_news en", "eil.uid = en.appid")
            ->count("eil.id");
        $data['all_android_count'] = Db::table("hisi_example_install_log eil")
            //->join("hisi_example_news en", "eil.uid = en.appid")
            ->where("eil.invite_user_os", "=", "1")
            ->count("eil.id");
        $data['all_ios_count'] = Db::table("hisi_example_install_log eil")
            //->join("hisi_example_news en", "eil.uid = en.appid")
            ->where("eil.invite_user_os", "=", "2")
            ->count("eil.id");
        $this->assign('data', $data);
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
            $appid = $this->request->param('appid/d', '');
            $cid = $this->request->param('cid/d');
            $stime = $this->request->param('stime');
            $etime = $this->request->param('etime');
            $stime = strtotime($stime);
            $etime = strtotime($etime);

            $day = ($etime - $stime) / 86400 + 1;

            $sdate1 = strtotime(date('Y-m-d'));
            $edate1 = $sdate1 + 3600 * 24;
            if ($cid) {
                $where[] = ['cid', '=', $cid];
            }
            if ($keyword) {
                $where[] = ['title', 'like', '%' . $keyword . '%'];
            }
            if ($appid !== '') {
                $where[] = ['appid', '=', $appid];
            }
//            dump($where);exit;
            if ($stime && $etime) {
                for ($x = 0; $x < $day; $x++) {
                    $stime = $etime - 3600 * 24;
                    $cid = ExampleNews::where('appid', $appid)->value('cid');
                    $houtai = ExampleProxy::where('name', $cid)->value('appid');
                    $data[$x]['houtai'] = $houtai;
                    $data[$x]['date'] = $etime;
                    $pp = ExampleOrder::where($where)->where('remark', 0)->whereTime('datetime', 'today')->distinct(true)->field('userid')->select();
                    $afternum = 0;
                    foreach ($pp as $key => $value) {
                        $afternum++;
                    }
                    $data[$x]['afternum'] = $afternum;
                    $data[$x]['count'] = ExampleOrder::where($where)->where('remark', 1)->where('datetime', 'between time', [$stime, $etime])->count();
                    $etime = $etime - 3600 * 24;

                }
                $data['data'] = $data;
                $data['code'] = 0;

                return json($data);
            }
            $appids = ExampleNews::order('ctime desc')->column('appid');
//            dump($appids);exit;
            foreach ($appids as $key => $value) {
                $cid = ExampleNews::where('appid', $value)->value('cid');
                $houtai = ExampleProxy::where('name', $cid)->value('appid');
                $a['houtai'] = $houtai;
                $a['date'] = $sdate1;
                $where[] = ['appid', '=', $value];
                $a['count'] = ExampleOrder::where($where)->where('remark', 1)->where('datetime', 'between time', [$sdate1, $edate1])->count();

                $pp = ExampleOrder::where($where)->where('remark', 0)->whereTime('datetime', 'today')->distinct(true)->field('userid')->select();
                $afternum = 0;
                foreach ($pp as $key1 => $value1) {
                    $afternum++;
                }
                $a['afternum'] = $afternum;
//            dump($data);exit;
                $data[$key] = $a;
            }
            $data['data'] = $data;
//            dump($data);exit;
//            dump($appids);exit;
//            $cid  = ExampleNews::where('appid',$appid)->value('cid');
//            $houtai = ExampleProxy::where('name',$cid)->value('appid');
//            $data[0]['houtai']   = $houtai;
//            $data[0]['date']   = $sdate1;
//
//            $data[0]['count']  = ExampleOrder::where($where)->where('remark',1)->where('datetime','between time',[$sdate1,$edate1])->count();
//
//            $pp= ExampleOrder::where($where)->where('remark',0)->whereTime('datetime', 'today')->distinct(true)->field('userid')->select();
//            $afternum = 0;
//            foreach ($pp as $key => $value){
//                $afternum++;
//            }
//            $data[0]['$afternum']   = $afternum;
////            dump($data);exit;
//            $data['data'] = $data;
            $data['code'] = 0;
            return json($data);

        }
        $sdate1 = strtotime(date('Y-m-d'));
        $edate1 = $sdate1 + 3600 * 24;
        $appid = 34;
        $cid = ExampleNews::where('appid', $appid)->value('cid');
        $houtai = ExampleProxy::where('name', $cid)->value('appid');
        $data[0]['houtai'] = $houtai;
        $data[0]['date'] = $sdate1;

        $data[0]['count'] = ExampleOrder::where('remark', 1)->where('datetime', 'between time', [$sdate1, $edate1])->count();
        $data[0]['code'] = 0;
        $pp = ExampleOrder::where('remark', 0)->whereTime('datetime', 'today')->distinct(true)->field('userid')->select();
        $afternum = 0;
        foreach ($pp as $key => $value) {
            $afternum++;
        }
        $data[0]['$afternum'] = $afternum;

        $this->assign('data', $data);
        // 分组切换类型 0无需分组切换，1单个分组，2多个分组切换[无链接]，3多个分组切换[有链接]，具体请看application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        // tab切换数据
        // $hisiTabData = [
        //     ['title' => '后台首页', 'url' => 'system/index/index'],
        // ];
        // current 可不传
        // $this->assign('hisiTabData', ['menu' => $hisiTabData, 'current' => 'system/index/index']);
        $this->assign('hisiTabData', '');
        $appid = $this->request->get('appid');
        $stime = $this->request->get('stime');
        $etime = $this->request->get('etime');
        $this->assign('appid', $appid);
        $this->assign('stime', $stime);
        $this->assign('etime', $etime);
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
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }

    public function history()
    {
        $this->makeData();
        $startDate = urldecode($this->request->param('startDate/s'));
        $endDate = urldecode($this->request->param('endDate/s'));
        if ($this->request->isAjax()) {
            $where = $data = [];
            $installNumberWhere = [];
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            if ($startDate) {
                $startDate = str_replace(['+'], ' ', $startDate);
                $startTime = strtotime($startDate);
                $where[] = ['datetime', '>=', $startTime];
                $installNumberWhere[] = ['ctime', '>=', substr($startDate, 0, 10)];
            }
            if ($endDate) {
                $endDate = str_replace(['+'], ' ', $endDate);
                $endTime = strtotime($endDate);
                $where[] = ['datetime', '<=', $endTime];
                $installNumberWhere[] = ['ctime', '<=', substr($endDate, 0, 10)];
            }
            $data['data'] = $list = HistoryModel::field('from_unixtime(datetime,\'%Y-%m-%d\') as datetime,total_recharge,total_profit,add_user_count,first_recharge,continue_recharge,first_count,continue_count,apply_amount,payment_amount')->where($where)->page($page)->limit($limit)->order('datetime desc')->select();

            $installNumberArr =
                Db::table("hisi_example_formuser_install_log")
                    ->field('ctime as date, installnum')
                    ->where($installNumberWhere)
                    ->group("date")->select();
            if($installNumberArr) {
                foreach ($list as &$item) {
                    foreach ($installNumberArr as $value) {
                        if($item['datetime'] == $value['date']) {
                            $item['installnum'] = $value['installnum'];
                        }
                    }
                }
            }
            $data['count'] = HistoryModel::where($where)->count('datetime');
            $data['code'] = 0;
            return json($data);
        }
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }

    public function makeData()
    {
        //获取报表最新的时间，如果没有，就从订单表中获取有订单的最早时间
        $historyData = HistoryModel::field('datetime')->order('datetime desc')->limit(1)->find();
        //如果报表里没有找到数据
        $firstOrderTime = 0;
        if (!$historyData) {
            //从订单表里的第一条数据开始
            $orderData = OrderModel::field('unix_timestamp(from_unixtime(datetime,\'%Y-%m-%d\')) as dt')->order('datetime asc')->limit(1)->find();
            $firstOrderTime = $orderData['dt'] ?? 0;
        } else {
            $firstOrderTime = $historyData['datetime'] ?? 0;
        }
        //今天的时间
        $todayTime = strtotime(date('Y-m-d'));
        if ($firstOrderTime == 0 || $firstOrderTime + 86400 * 2 > $todayTime)
            return;
        //插入history表里的数据结构
        $data = [];
        $where = [['datetime', '>=', $firstOrderTime], ['datetime', '<=', $todayTime]];
        //当日总充值和利润
        $totalAmountArr = OrderModel::field('unix_timestamp(from_unixtime(datetime,\'%Y-%m-%d\')) as gp,sum(amount) totalAmount,sum(profit) totalProfit')->where($where)->group('gp')->select();
        if ($totalAmountArr) {
            foreach ($totalAmountArr as $key => $item) {
                $data[$item['gp']] = ['datetime' => $item['gp'], 'total_recharge' => $item['totalAmount'], 'total_profit' => $item['totalProfit'], 'first_recharge' => 0, 'continue_recharge' => 0, 'first_count' => 0, 'continue_count' => 0, 'apply_amount' => 0, 'payment_amount' => 0];
            }
        }
        //当日首充和续费
        $firstRechargeArr = OrderModel::field('unix_timestamp(from_unixtime(datetime,\'%Y-%m-%d\')) as gp,sum(amount) totalAmount')->where($where)->where('remark', 1)->group('gp')->select();
        if ($firstRechargeArr) {
            foreach ($firstRechargeArr as $key => $item) {
                if (isset($data[$item['gp']]))
                    $data[$item['gp']]['first_recharge'] = $item['totalAmount'];
            }
        }
        $continueRechargeArr = OrderModel::field('unix_timestamp(from_unixtime(datetime,\'%Y-%m-%d\')) as gp,sum(amount) totalAmount')->where($where)->where('remark', 0)->group('gp')->select();
        if ($continueRechargeArr) {
            foreach ($continueRechargeArr as $key => $item) {
                if (isset($data[$item['gp']]))
                    $data[$item['gp']]['continue_recharge'] = $item['totalAmount'];
            }
        }
        //当日首充人数和续费人数
        $firstNumArr = OrderModel::field('unix_timestamp(from_unixtime(datetime,\'%Y-%m-%d\')) as gp,count(cid) totalNum')->where($where)->where('remark', 1)->group('gp')->select();
        if ($firstNumArr) {
            foreach ($firstNumArr as $key => $item) {
                if (isset($data[$item['gp']]))
                    $data[$item['gp']]['first_count'] = $item['totalNum'];
            }
        }
        $continueNumArr = OrderModel::field('unix_timestamp(from_unixtime(datetime,\'%Y-%m-%d\')) as gp,count(userid) totalNum')->where($where)->where('remark', 0)->group('gp')->select();
        if ($continueNumArr) {
            foreach ($continueNumArr as $key => $item) {
                if (isset($data[$item['gp']]))
                    $data[$item['gp']]['continue_count'] = $item['totalNum'];
            }
        }
        //当日累计提款申请金额和当日累计出款
        $applyAmountArr = MoneyModel::field('unix_timestamp(from_unixtime(ctime,\'%Y-%m-%d\')) as gp,sum(money) totalAmount')->where('status', 1)->group('gp')->select();
        if ($applyAmountArr) {
            foreach ($applyAmountArr as $key => $item) {
                if (isset($data[$item['gp']]))
                    $data[$item['gp']]['apply_amount'] = $item['totalAmount'];
            }
        }
        $paymentAmountArr = MoneyModel::field('unix_timestamp(from_unixtime(ctime,\'%Y-%m-%d\')) as gp,sum(money) totalAmount')->where('status', 3)->group('gp')->select();
        if ($paymentAmountArr) {
            foreach ($paymentAmountArr as $key => $item) {
                if (isset($data[$item['gp']]))
                    $data[$item['gp']]['payment_amount'] = $item['totalAmount'];
            }
        }
        unset($totalAmountArr, $firstRechargeArr, $continueRechargeArr, $firstNumArr, $continueNumArr, $applyAmountArr, $paymentAmountArr);
        if (count($data) > 0) {
            $data = array_values($data);
            foreach ($data as $item) {
                $res = HistoryModel::where('datetime', $item['datetime'])->count('datetime');
                if (!$res)
                    HistoryModel::create($item);
            }
        }
        unset($data);
    }
}
