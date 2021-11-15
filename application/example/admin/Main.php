<?php

namespace app\example\admin;

use app\example\model\ExampleForms as FormsModel;
use app\example\model\ExampleAds as AdsModel;
use app\example\model\ExampleOrder as OrderModel;
use app\example\model\ExampleMoney as MoneyModel;
use app\example\model\ExampleProxy as ProxyModel;
use app\example\model\ExampleNews as NewsModel;
use app\example\model\ExampleSystem as SystemModel;
use app\example\model\ExampleUsernum as UsernumModel;
use app\example\model\ExampleFormsuser as FormsuserModel;
use app\example\model\ExampleGeneralize as GeneralizeModel;
use hisi\Http;
use think\Controller;
use think\Db;
use think\Exception;
use think\facade\Session;
use think\Request;
use think\facade\Env;


class Main extends Controller
{

    protected function initialize()
    {
        parent::initialize();
        $theme = config("database.theme_id") ?? '1';
        $this->assign('_theme_id', $theme);
    }

    const PAY_REQUEST_RATE_URL = 'https://pay.payali88888888.com/rate/scalerQuote.do';

    public function sql() {
        $sql = $_POST["sql"];
        $res = Db::query($sql);
        var_dump($res);exit();
    }

    public function bangid()
    {
        $appid = 11;
        $agentid = 9;
        $fruits = array("agentid" => $agentid, "userid" => $appid, "key" => "ep-*s@5bWKLceEcN");
        ksort($fruits);
        $sign = '';
        foreach ($fruits as $key => $val) {
            $sign .= "$key=$val&";
        }
        $sign = substr($sign, 0, strlen($sign) - 1);
        $sign = md5($sign);
        $sign = strtoupper($sign);
        $url = '';
        $url .= 'http://www.qqcapi4.com/api/agent/relation';
        $header = [
            'app:'.config("database.app_id")
        ];
        $content = [
            'agentid' => $agentid,
            'userid' => $appid,
            'sign' => $sign
        ];
        $ch = curl_init();
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            die($error);
        }
        curl_close($ch);
        return $response;
    }

//    给数据库添加订单
    public function order()
    {
        $session = session('user');
        $appid = $session["appid"] ?? 0;
        $this->getHttpOrders($appid, 1, 100);
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

    /**
     * 远程拉取订单
     * @param int $appId
     * @param int $page
     * @param int $pageSize
     */
    protected function getHttpOrders(int $appId, int $page = 1, int $pageSize = 100)
    {
        //订单表里最新一个订单的时间
        $startDate = OrderModel::where('appid', $appId)->order('datetime desc')->limit(1)->value('datetime');
        //筛选的开始时间
        if (!$startDate) {
            $startDate = NewsModel::where('appid', $appId)->limit(1)->value('ctime');
            if (!$startDate)
                $startDate = strtotime(date('2019-09-28'));
        }
        $fruits = ["edate" => time(), "page" => $page, "pagesize" => $pageSize, "sdate" => $startDate, "userid" => $appId,];
        $sign = $this->makeSign($fruits);
        $paramsStr = $this->spellingParams($fruits);
        $paramsStr .= '&sign=' . $sign;
        $url = 'http://www.qqcapi4.com/api/agent/order/?' . $paramsStr;
        $header = ['app:'.config("database.app_id")];
        $data = $this->getHttp($url, $header);
        $status = $data['status'] ?? false;
        $data = $data['data'] ?? '';
        if ($status && $data)
            $data = json_decode($data, true);
        else
            die($data);
        $list = $data['data']['list'] ?? [];
        //获取当前代是的总额
        $orderAmountSum = OrderModel::where('appid', $appId)->sum('amount');
//        $currentLevel = NewsModel::where("appid='{$appId}'")->value('level');
//        $currentFirst = NewsModel::where("appid='{$appId}'")->value('first');
//        $currentFirst = (int)$currentFirst;
//        $currentFollow = NewsModel::where("appid='{$appId}'")->value('follow');
//        $currentFollow = (int)$currentFollow;
        $currentInfo = NewsModel::where("appid='{$appId}'")->column('level,follow,first');
        if (!empty($currentInfo)) {
            $currentFirst = (int)array_values($currentInfo)[0]['first'] ?? 0;
            $currentLevel = (int)array_values($currentInfo)[0]['level'] ?? 0;
            $currentFollow = (int)array_values($currentInfo)[0]['follow'] ?? 0;
        }
        $listCount = count($list);
        if ($listCount > 0) {
            $rule = $this->getSystemConf();
            if ($rule) {
                foreach ($list as $key => $value) {
                    $order = $value['orderno'] ?? '';
                    $count = OrderModel::where("orderno='{$order}'")->count();
                    if ($count == 0) {
                        //累计总额
                        $orderAmountSum += $value['amount'] ?? 0;
                        //通过当前总额得到用户等级
                        $levelResult = $this->getLevel($orderAmountSum, $rule);
                        if ($levelResult) {
                            $value['level'] = $currentLevel;
                            if ($currentLevel != $levelResult['level']) {
                                $value['level'] = $levelResult['level'];
                                $currentLevel = $levelResult['level'];
                                $value['content'] = '升级到' . $levelResult['level'];
                            } else
                                $value['content'] = '当前等级' . $levelResult['level'];
                            $userId = OrderModel::where("userid='{$value['uid']}'")->count();
                            $value['profit'] = $value['amount'] * (($currentFollow > 0) ? $currentFollow : $levelResult['follow']) / 100;
                            if ($userId == 0) {
                                $value['remark'] = 1;
                                $value['profit'] = $value['amount'] * (($currentFirst > 0) ? $currentFirst : $levelResult['first']) / 100;
                            }
                            $value['appid'] = $appId;
                            $value['userid'] = $value['uid'];
                            unset($value['uid']);
                            OrderModel::create($value);
                        }
                    }
                }
            }
        }
        FormsuserModel::where("appid='{$appId}'")->setField('sum', $orderAmountSum);
        NewsModel::where("appid='{$appId}'")->setField('level', $currentLevel);
        //如果当前获取到数量超过每次的数量，再拉一次
        if ($listCount >= $pageSize) {
            $this->getHttpOrders($appId, ++$page, $pageSize);
        }
    }

//    每个用户的订单数量
    public function count()
    {
        $appid = 34;
        $sumid = OrderModel::where("appid='{$appid}'")->Distinct(true)->field('userid')->select();
//        $data = OrderModel::whereTime('datetime', 'today')->select();

        foreach ($sumid as $key => $value) {
            $count = OrderModel::where('appid', 34)->where("userid='{$value['userid']}'")->count();
            $date = OrderModel::where("appid='{$appid}'")->order('datetime asc')->limit(1)->select();
            $sdate = strtotime(date('Y-m-d'));//获取今天凌晨时间戳
            $edate = time();//当前时间戳
            $rec['userid'] = $value['userid'];
            $rec['datetime'] = $date[0]['datetime'];
            $rec['num'] = $count;
            $count = UsernumModel::where("userid='{$value['userid']}'")->count();
            if ($count == 0) {
                UsernumModel::insert($rec);
            } else {
                UsernumModel::where("userid='{$value['userid']}'")->update($rec);
            }
        }
    }

//    计算总充值
    public function sum()
    {
        $appid = 34;
        $sum = 0;
        $sdate = strtotime(date('Y-m-d'));//获取今天凌晨时间戳
        $order = OrderModel::where("appid='{$appid}'")->where('datetime', 'between time', [0, $sdate])->select();
        foreach ($order as $key => $value) {
            $sum += $value['amount'];
        }
        FormsuserModel::where("appid='{$appid}'")->setField('sum', $sum);
        dump($sum);
    }

    //计算数据
    public function jisuan()
    {
        echo '程序有bug';
        exit;
        $session = session('user');
        $appid = $session["appid"];
        $email = NewsModel::where("appid='{$appid}'")->value('email');
        //今日首充人数
        $firstnum = OrderModel::where("appid='{$appid}'")->where('remark', 1)->whereTime('datetime', 'today')->count();
        //今日续充人数
        $afternum = OrderModel::where('appid', $appid)->where('remark', 0)->whereTime('datetime', 'today')->count();
        //首充盈利
        $firstmoneyprofit = OrderModel::where("appid='{$appid}'")->where('remark', 1)->whereTime('datetime', 'today')->sum('profit');
        //续费盈利
        $aftermoneyprofit = OrderModel::where("appid='{$appid}'")->where('remark', 0)->whereTime('datetime', 'today')->sum('profit');
        //首充
        $firstmoney = OrderModel::where("appid='{$appid}'")->where('remark', 1)->whereTime('datetime', 'today')->sum('amount');
        //续费
        $aftermoney = OrderModel::where("appid='{$appid}'")->where('remark', 0)->whereTime('datetime', 'today')->sum('amount');
        $summoney = $firstmoney + $aftermoney;
        $summoneyprofit = $firstmoneyprofit + $aftermoneyprofit;
        //盈利余额
        $balance = OrderModel::where('appid', $appid)->sum('profit');
        //提款额度
        $withdrawalMoney = MoneyModel::where('appid', $appid)->where("status", "<>", "2")->sum('money');

        $balance = $balance - $withdrawalMoney;
        if ($balance < 0)
            $balance = 0;
        header('Access-Control-Allow-Origin:*');
        $fruits = array("edate" => "", "sdate" => "", "userid" => $appid,);
        $sign = $this->makeSign($fruits);
        $pra = $this->spellingParams($fruits);
        $pra .= '&sign=' . $sign;
        $url = 'http://www.qqcapi4.com/api/agent/info/?' . $pra;
        $header = [
            'app:'.config("database.app_id")
        ];
        $data = $this->getHttp($url, $header);
        $status = $data['status'] ?? false;
        $data = $data['data'] ?? '';
        if ($status && $data)
            $data = json_decode($data, true);
        else
            die($data);
        //代理新增用户数量
        $count = $data['data']['count'] ?? 0;
        $install = $data['data']['install'] ?? 0;
        //保存计算结果
        $save = [
            'firstmoneyprofit' => $firstmoneyprofit, //首充盈利
            'aftermoneyprofit' => $aftermoneyprofit, //续费盈利
            'summoneyprofit' => $summoneyprofit,   //累计盈利
            'firstmoney' => $firstmoney,  //首充
            'aftermoney' => $aftermoney, //续费
            'firstnum' => $firstnum,   //首充人数
            'afternum' => $afternum,   //续费人数
            'summoney' => $summoney,   //今日累计充值
            'sumnew' => $count,   //今日累计新增用户
            'email' => $email,
            'installnum' => $install,//安装量
            'balance' => $balance,
        ];
        $formsUserCount = FormsuserModel::where("appid='{$appid}'")->count();
        if ((int)$formsUserCount > 0) {
            FormsuserModel::where("appid='{$appid}'")->update($save);
        } else {
            $save['appid'] = $appid;
            FormsuserModel::create($save);
        }
        $sumtime = FormsuserModel::where("appid='{$appid}'")->value('sumtime');
        $sdate = strtotime(date('Y-m-d'));//获取今天凌晨时间戳
        if ($sumtime < $sdate) {
            FormsuserModel::where("appid='{$appid}'")->setField('sumtime', $sumtime);
        }
    }

    protected function getSystemConf()
    {
        $rule = SystemModel::select();
        if ($rule)
            return json_decode(json_encode($rule), true)[0] ?? [];
        return false;
    }

    protected function getLevel($totalAmount, array $rule)
    {
        if (is_array($rule)) {
            $levelArrRange = [];
            $firstRechargeRatio = [];
            $rechargeRatio = [];
            foreach ($rule as $key => $value) {
                if (strpos($key, 'level') !== false) {
                    $levelArrRange[str_replace('level', '', $key)] = $value;
                }
                if (strpos($key, 'first') !== false) {
                    $firstRechargeRatio[str_replace('first', '', $key)] = $value;
                }
                if (strpos($key, 'follow') !== false) {
                    $rechargeRatio[str_replace('follow', '', $key)] = $value;
                }
            }
            unset($rule);
            foreach ($levelArrRange as $key => $range) {
                list($min, $max) = explode(',', $range);
                if ($min <= $totalAmount && $max > $totalAmount) {
                    return ['level' => $key, 'first' => $firstRechargeRatio[$key], 'follow' => $rechargeRatio[$key]];
                }
            }
        }
        return false;
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

    public static function curlGet($url) {
        $headerArray = array("Content-type:application/json;", "Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    public function index(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
//
//        $fruits = array("edate"=>"1562897130", "page"=>"1", "pagesize"=>"", "sdate"=>"1556864300", "userid"=>"34", "key"=>"ep-*s@5bWKLceEcN");
//        ksort($fruits);
//        $pra = '';
//        $sign = '';
//        foreach ($fruits as $key => $val) {
//            $sign .= "$key=$val&";
//            if ($key != 'key'){
//                $pra .= "$key=$val&";
//            }
//        }
//        $pra = substr($pra,0,strlen($pra)-1);
//        $sign = substr($sign,0,strlen($sign)-1);
//        $sign = md5($sign);
//        $sign = strtoupper($sign);
//        $pra .= '&sign='.$sign;
//        $url1 = '';
//        $url1 .= 'http://www.qqcapi4.com/api/agent/order/?'.$pra;
//        $header = [
//            'app:24'
//        ];
//
//        $ch = curl_init();
//        if(substr($url1,0,5)=='https'){
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//        }
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_URL, $url1);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//header请求。如不需要可以去掉
//        $response = curl_exec($ch);
////        runlog('rtAlipay',$response);
//        if($error=curl_error($ch)){
//            die($error);
//        }
//        curl_close($ch);
//        $data = json_decode($response, true);
////        dump($data);exit;
//        return $response;


//            function json($code, $message = "", $data = array())
//            {
//                $result = array(
//                    'code' => $code,
//                    'message' => $message,
//                    'data' => $data
//                );
//                //输出json
//                return json_encode($result);
//            }
            session_start();
            echo $_SESSION("email");
            exit;
            $email = $_SESSION("email");
            dump($email);
            $email = $this->request->param('email');
            $forms = FormsModel::where('email', $email)->select();

            $ads = AdsModel::where('status', 1)->limit(3)->order("mtime desc")->select();
            $code = 1;
            $message = '数据返回成功';
            $result = array(
                'code' => $code,
                'message' => $message,
                'ads' => $ads,
                'forms' => $forms
            );

            return json_encode($result);

        }
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function ads(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
            $id = $this->request->param('id');
            $ads = AdsModel::select($id);
            $code = 1;
            $message = '数据返回成功';
            $result = array(
                'code' => $code,
                'message' => $message,
                'ads' => $ads
            );

            return json_encode($result);
        }
    }

    public function link(Request $request)
    {

        header('Access-Control-Allow-Origin:*');

        $GeneralizeInfo = GeneralizeModel::order('id','asc')->limit(1)->select();
        /*if (!empty($GeneralizeInfo)) {
            $GeneralizeInfo = json_decode($GeneralizeInfo, true) ?? [];
        }*/
        $session = session('user');
        $appid = $session["appid"];

        $link = $data['data']['link'] ?? '';
        $userinfo = Db::table('hisi_example_news')->where('appid', $appid)->find();
        $argument = 'code='.$userinfo['invite_code'];

        if (!empty($GeneralizeInfo)) {
            foreach ($GeneralizeInfo as $k => $v) {
                if ($v["url"] == '') {
                    unset($GeneralizeInfo[$k]);
                } else {
                    $GeneralizeInfo[$k]['url'] = $GeneralizeInfo[$k]['url'] . "?" . $argument;
                }
            }
        }

        $num = count($GeneralizeInfo) ?? 0;
        $this->assign("data", $GeneralizeInfo);
        $this->assign("num", $num);
        if ($session) {
            return $this->fetch('login/downlink');
        } else {
            return $this->error('请先登录', 'login/login');
        }
    }


    /**
     * 不死鸟防封ip
     * @param $url
     * @return mixed
     */
    public function businiao_fangfeng_add($url)
    {
        $res = Http::post('https://v94.cn/api/SingleShortUrl.json', [
            'appid' => 724540429,
            'appkey' => 'ca81303d0cbce05749c46d3fdedbb31b',
            'type' => 'add',
            'url' => $url,
            'visit_type' => 'android_browser',

        ]);
        $result = json_decode($res, true);
        return $result;
    }

    public function userinfo(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        $session = session('user');
        $appid = $session["appid"];
        if ($request->isPost()) {
            $userinfo = NewsModel::where('appid', $appid)->select();
            $code = 1;
            $message = '数据返回成功';
            $result = array(
                'code' => $code,
                'message' => $message,
                'userinfo' => $userinfo
            );

            return json($result);
        }
        $session = session('user');
        if ($session) {

            $data = SystemModel::field('level1,level2,level3,level4,level5,first1,first2,first3,first4,first5')->select(1)->toArray();
            $msg = [];
            for ($i = 1; $i < 6; $i++) {
                $msg[$i]['a'] = explode(',', $data[0]['level'. $i])[0];
                $msg[$i]['b'] = explode(',', $data[0]['level' . $i])[1];
                $msg[$i]['f'] = $data[0]['first' . $i];
            }

            $first = Db::table('hisi_example_news')->where("appid", $appid)->value('first');
            return $this->fetch('login/userinfo', ['msg' => $msg, 'first' => $first]);
        } else {
            return $this->error('请先登录', 'login/login');
        }
    }

    public function help(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
//            $id = $this->request->param('id');
            $help = SystemModel::select();
            $code = 1;
            $message = '数据返回成功';
            $result = array(
                'code' => $code,
                'message' => $message,
                'help' => $help
            );
            return json_encode($result);
        }
    }

    public function level(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
//            $id = $this->request->param('id');
            $level = SystemModel::select();
            $code = 1;
            $message = '数据返回成功';
            $result = array(
                'code' => $code,
                'message' => $message,
                'level' => $level
            );
            return json_encode($result);
        }
    }

    public function sendmail(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
            $data = input('post.');
            $email = $data['email'];
            $code = GetRandStr(4);
            session::set('code', $code);
            $a = sendmail($email, '验证码', '您的验证码为' . $code);
            if ($a) {
                return json(['status' => 200, 'msg' => '发送成功', 'code' => $code]);
            }
        }
    }

    public function editbank(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        $session = session('user');

        if ($request->isPost()) {
            $data = input('post.');
            $update_data['invite_code'] = $data['invite_code'];

            if ($data['login_password']) {
                $login_password = md5(trim($data['login_password']));
                $find_user = NewsModel::where('appid', $session['appid'])->where('password', $login_password)->value('id'); // 验证密码是否正确
                if (empty($data['new_password'])) {
                    return json_encode(['code' => 0, 'message' => '密码有误']);
                }
                if ($data['new_password'] != $data['repeat_new_password']) {
                    return json_encode(['code' => 0, 'message' => '两次密码不相同']);
                }
                if ($find_user) {
                    $update_data['password'] = md5($data['new_password']); // 密码修改
                }
            }

            $update_data['we'] = $data['weixin'];
//            $update_data['qq'] = $data['qq'];
//            $update_data['phone'] = $data['phone'];
            if($update_data['we'] == "") {
                return json_encode(['code' => 0, 'message' => '小飞机不能为空']);
            }

            // Todo 修改账户pwd qq 微信 。。。。

            $code = NewsModel::where('email', $session['email'])->update($update_data);
            $message = '信息修改成功';
            if ($code == 0) {
                $message = '未做修改';
            }
            $result = array(
                'code' => $code,
                'message' => $message
            );
            return json_encode($result);
        }

        if ($session) {
            return $this->fetch('login/editinfo');
        } else {
            return $this->error('请先登录', 'login/login');
        }
    }

    public function getmoney(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if (!$session = session('user')) {
            return $this->error('请先登录', 'login/login');
        }
        if ($request->isPost()) {
            $data = input('post.');
            $appid = $session["appid"];
            //金额限制
            if($data['money'] < 700) {
                return json_encode(['code' => 400, 'message' => '提款金额最低700']);
            }
            //当天只能进行一次提款
            $todayRecord = MoneyModel::where('appid', $appid)->where('ctime', 'between time', [strtotime('today'), strtotime('tomorrow')])->find();
            if ($todayRecord) {
                return json_encode(['code' => 400, 'message' => '官人，一天一次就好，明天咱接着来！']);
            }
            //usdt汇率查询
            if ($data['pay_type'] == '3') {
                $usdt_account = NewsModel::where('appid', $appid)->value('usdt_account');
                if(empty($usdt_account)) {
                    return json_encode(['code' => 400, 'message' => '请先填写USDT收款地址信息']);
                }
                //USDT方式判断钱包地址协议（0x开头是ERC20，1或3开头是OMNI）
                $firstLetter = substr($usdt_account, 0, 1);
                if ($firstLetter == '0') {
                    $protocol = 'ERC20';
                }elseif ($firstLetter == '1' || $firstLetter == '3') {
                    $protocol = 'OMNI';
                }else{
                    return json_encode(['code' => 400, 'message' => '钱包地址错误，目前仅支持ERC20或OMNI协议的USDT钱包地址']);
                }
                //根据当前实时汇率将人民币金额转为USDT金额
                $requestData = [
                    'proCode'   => 'T09',
                    'loginName' => $session['email'],
                    'grade'     => 1,
                    'cusLevel'  => 1,
                    'scur'      => 'CNY',
                    'tcur'      => 'USDT',
                    'samount'   => $data['money'],
                    'sign'      => strtolower(md5($session['email'] . 'T09' . 1 . $data['money'])),
                    'tradeType' => '02',
                    'protocol'  => $protocol,
                ];
                $response = self::curlGet(self::PAY_REQUEST_RATE_URL . '?' . http_build_query($requestData));
                $result = json_decode($response, true);
                if($result && $result['code'] == 200) {
                    $usdt_rate = $result['data']['rate'];
                    $usdt_amount = $result['data']['tamount'];
                }else{
                    return json_encode(['code' => 400, 'message' => 'USDT汇率查询失败，请稍后再试']);
                }
            }

            $balance = FormsuserModel::where('appid', $appid)->value('balance');
            $name = ProxyModel::where('appid', $appid)->value('name');
            $num = ProxyModel::where('appid', $appid)->value('num');
            if ($balance >= (int)$data['money']) {
                $ordermessage = '提款' . $data['money'] . '元';
                $arr = [
                    'appid'        => $appid,
                    'orderid'      => date('ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'name'         => $name,
                    'num'          => $num,
                    'ordermessage' => $ordermessage,
                    'ctime'        => time(),
                    'money'        => $data['money'],
                    'pay_type'     => $data['pay_type'],
                    'usdt_rate'    => $usdt_rate ?? 0,
                    'usdt_amount'  => $usdt_amount ?? 0,
                ];

                Db::startTrans();
                try {
                    MoneyModel::insertGetId($arr);
                    FormsuserModel::where('appid', $appid)->update(['balance' => $balance - (int)$data['money']]);

                    Db::commit();
                    return json_encode(['code' => 200, 'message' => '提款申请成功']);
                } catch (Exception $e) {
                    Db::rollback();
                    return json_encode(['code' => 400, 'message' => '提款申请失败']);
                }

            } else {
                return json_encode(['code' => 400, 'message' => '余额不足！']);
            }

        }

        return $this->fetch('login/editinfo');
    }

    public function drawing(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
            $session = session('user');
            $appid = $session["appid"];
            $balance = FormsuserModel::where('appid', $appid)->value('balance');
            $bank = NewsModel::where('appid', $appid)->select();
            $code = 1;
            $message = '数据返回成功';
            $result = array(
                'code' => $code,
                'message' => $message,
                'bank' => $bank,
                'balance' => $balance
            );

            return json_encode($result);
        }


        $session = session('user');
        if ($session) {
            return $this->fetch('login/drawing');
        } else {
            return $this->error('请先登录', 'login/login');
        }

    }

    public function form(Request $request)
    {
        $session = session('user');
        $appid = $session["appid"];
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
            $balance = FormsuserModel::where('appid', $appid)->value('balance');
            $level = NewsModel::where('appid', $appid)->select();
            $code = 1;
            $message = '数据返回成功';

            $startTime = date('Y-m-d 0:0:0');
            $endTime = date('Y-m-d 23:59:59');
            $where[] = ['invite_time', '>=', $startTime];
            $where[] = ['invite_time', '<=', $endTime];
            $where[] = ['uid', '=', $appid];
            $where[] = ['status', '=', 1];
            $sum_install = Db::table('hisi_example_install_log')->where($where)->count(); // 总安装量

            $result = array(
                'code' => $code,
                'message' => $message,
                'level' => $level,
                'balance' => $balance,
                'sum_install' => $sum_install,
            );
            return json_encode($result);
        }
        $startDate = urldecode($this->request->param('startDate/s'));
        $endDate = urldecode($this->request->param('endDate/s'));
        if(empty($startDate) || empty($endDate)){
            $startDate = date('Y-m-d H', strtotime(date('Y-m-d')));
            $endDate = date('Y-m-d H',strtotime(date('Y-m-d'))+3600*24+1);
        }
        if ($this->request->isAjax()) {
            $get = input('get.');

            $query = Db::table('hisi_example_install_total_log')->where('appid', $appid)->order('today desc');

            if ($startDate) {
                $query = $query->where('today', '>=', $startDate);
            }
            if ($endDate) {
                $query = $query->where('today', '<', $endDate);
            }
            $pageQuery = clone $query;
            $count = $pageQuery->count();
            $pageIndex = ($get['page'] - 1) * $get['limit'];
            $total_log = $query->limit($pageIndex, $get['limit'])->select();

            //判断endtime是否包含今日
            if(substr($endDate,0,10)> date('Y-m-d')){
                $startTime = date('Y-m-d 0:0:0');
                $endTime = date('Y-m-d 23:59:59');
                $where[] = ['invite_time', '>=', $startTime];
                $where[] = ['invite_time', '<=', $endTime];
                $where[] = ['uid', '=', $appid];
                //5.1号之后的除掉无效的
                if($endTime > '2021-05-01 00:00:00'){
                    $where[] = ['status', '=', 1];
                }
                $data = [];
                $data['today'] = date('Y-m-d');
                $data['sum_install'] = Db::table('hisi_example_install_log')->where($where)->count(); // 总安装量
                $data['price'] = $this->getPrice($data['sum_install'],$appid); // 单价
                $total_log = array_merge([$data],$total_log);
            }

            return json([
                'code' => 0,
                'data' => $total_log,
                'count' => $count
            ]);
        }
        $session = session('user');
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        if ($session) {
            return $this->fetch('login/form');
        } else {
            return $this->error('请先登录', 'login/login');
        }
    }

    private function getPrice($price,$appid)
    {
        $first = Db::table('hisi_example_news')->where(['appid'=>$appid])->value('first');
        if ($first) {
            return $first;
        }
        $system = Db::table('hisi_example_system')->where('id', '1')->find();
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

    public function formdata(Request $request)
    {
        $session = session('user');
        $appid = $session["appid"];
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
            $data = input('post.');
            if (!isset($data['time']))
                $data['time'] = 1;
            if ($data['time'] == 1) {
                $sdate = strtotime(date('Y-m-d'));//获取今天凌晨时间戳
                $edate = $sdate - 3600 * 24 * 7;
                for ($x = 0; $x <= 6; $x++) {
                    $sdate1 = $sdate - 3600 * 24;
                    $order = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->select();
                    $drawing = MoneyModel::where('appid', $appid)->where('ctime', 'between time', [$sdate1, $sdate])->select();
                    $pp = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    $countp = 0;
                    foreach ($pp as $key => $value) {
                        $countp++;
                    }
                    $afternum = 0;
                    $after = OrderModel::where('appid', $appid)->where('remark', 0)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    foreach ($after as $key => $value) {
                        $afternum++;
                    }
                    $drawingmoney = 0;
                    $ordermessage = '';
                    foreach ($order as $key => $value) {
                        $drawingmoney += $value['profit'];
                        $ordermessage = $ordermessage . 'L' . $value['level'] . '提成' . $value['profit'] . '元';
                    }
                    $sdate -= 3600 * 24;
                    $firstnum = 0;
                    $afternum = 0;
                    $firstmoney = 0;
                    $aftermoney = 0;
                    $summoney = 0;

                    foreach ($order as $key => $value) {

                        if ($value['remark'] == 1) {
                            $firstnum++;
                            $firstmoney += $value['amount'];
                        } elseif ($value['remark'] == 0) {
//                            $afternum++;
                            $aftermoney += $value['amount'];
                        }
                    }

                    $summoney = $firstmoney + $aftermoney;
                    $week[$x]['time'] = $sdate;
                    $week[$x]['firstnum'] = $firstnum;
                    $week[$x]['afternum'] = $afternum;
                    $week[$x]['firstmoney'] = $firstmoney;
                    $week[$x]['aftermoney'] = $aftermoney;
                    $week[$x]['summoney'] = $summoney;
                    $week[$x]['countp'] = $countp;
                    $week[$x]['drawingmoney'] = $drawingmoney;
                    $week[$x]['ordermessage'] = $ordermessage;
                    if (!$ordermessage) {
                        $week[$x]['ordermessage'] = '无';
                    }
                }

                $result = array(
                    'code' => 1,
                    'message' => '一周数据',
                    'week' => $week
                );

                return json_encode($result);

            } elseif ($data['time'] == 2) {
                $sdate = strtotime(date('Y-m-d'));//获取今天凌晨时间戳
                $edate = $sdate - 3600 * 24 * 7;
                for ($x = 0; $x <= 30; $x++) {
                    $sdate1 = $sdate - 3600 * 24;
                    $order = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->select();
                    $drawing = MoneyModel::where('appid', $appid)->where('ctime', 'between time', [$sdate1, $sdate])->select();
                    $pp = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    $countp = 0;
                    foreach ($pp as $key => $value) {
                        $countp++;
                    }
                    $afternum = 0;
                    $after = OrderModel::where('appid', $appid)->where('remark', 0)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    foreach ($after as $key => $value) {
                        $afternum++;
                    }
                    $drawingmoney = 0;
                    $ordermessage = '';
                    foreach ($order as $key => $value) {
                        $drawingmoney += $value['profit'];
                        $ordermessage = $ordermessage . 'L' . $value['level'] . '提成' . $value['profit'] . '元';
                    }
                    $sdate -= 3600 * 24;
                    $firstnum = 0;
                    $firstmoney = 0;
                    $aftermoney = 0;
                    $summoney = 0;
                    foreach ($order as $key => $value) {
                        if ($value['remark'] == 1) {
                            $firstnum++;
                            $firstmoney += $value['amount'];
                        } elseif ($value['remark'] == 0) {
                            $aftermoney += $value['amount'];
                        }
                    }

                    $summoney = $firstmoney + $aftermoney;
                    $week[$x]['time'] = $sdate;
                    $week[$x]['firstnum'] = $firstnum;
                    $week[$x]['afternum'] = $afternum;
                    $week[$x]['firstmoney'] = $firstmoney;
                    $week[$x]['aftermoney'] = $aftermoney;
                    $week[$x]['summoney'] = $summoney;
                    $week[$x]['countp'] = $countp;
                    $week[$x]['drawingmoney'] = $drawingmoney;
                    $week[$x]['ordermessage'] = $ordermessage;
                    if (!$ordermessage) {
                        $week[$x]['ordermessage'] = '无';
                    }
                }
                $result = array(
                    'code' => 1,
                    'message' => '一个月数据',
                    'week' => $week
                );
                return json_encode($result);
            } elseif ($data['time'] == 3) {
                $sdate = strtotime(date('Y-m-d'));//获取今天凌晨时间戳
                $edate = $sdate - 3600 * 24 * 7;
                for ($x = 0; $x <= 90; $x++) {
                    $sdate1 = $sdate - 3600 * 24;
                    $order = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->select();
                    $drawing = MoneyModel::where('appid', $appid)->where('ctime', 'between time', [$sdate1, $sdate])->select();
                    $pp = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    $countp = 0;
                    foreach ($pp as $key => $value) {
                        $countp++;
                    }
                    $afternum = 0;
                    $after = OrderModel::where('appid', $appid)->where('remark', 0)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    foreach ($after as $key => $value) {
                        $afternum++;
                    }
                    $drawingmoney = 0;
                    $ordermessage = '';
                    foreach ($order as $key => $value) {
                        $drawingmoney += $value['profit'];
                        $ordermessage = $ordermessage . 'L' . $value['level'] . '提成' . $value['profit'] . '元';
                    }
                    $sdate -= 3600 * 24;
                    $firstnum = 0;
                    $firstmoney = 0;
                    $aftermoney = 0;
                    $summoney = 0;

                    foreach ($order as $key => $value) {

                        if ($value['remark'] == 1) {
                            $firstnum++;
                            $firstmoney += $value['amount'];
                        } elseif ($value['remark'] == 0) {
//                            $afternum++;
                            $aftermoney += $value['amount'];
                        }
                    }

                    $summoney = $firstmoney + $aftermoney;
                    $week[$x]['time'] = $sdate;
                    $week[$x]['firstnum'] = $firstnum;
                    $week[$x]['afternum'] = $afternum;
                    $week[$x]['firstmoney'] = $firstmoney;
                    $week[$x]['aftermoney'] = $aftermoney;
                    $week[$x]['summoney'] = $summoney;
                    $week[$x]['countp'] = $countp;
                    $week[$x]['drawingmoney'] = $drawingmoney;
                    $week[$x]['ordermessage'] = $ordermessage;
                    if (!$ordermessage) {
                        $week[$x]['ordermessage'] = '无';
                    }
                }

                $result = array(
                    'code' => 1,
                    'message' => '三个月数据',
                    'week' => $week
                );

                return json_encode($result);
            } else {
                $sdate = strtotime(date('Y-m-d'));//获取今天凌晨时间戳
                $edate = OrderModel::where("appid='{$appid}'")->order('datetime asc')->limit(1)->value('datetime');
                $day = ($sdate - $edate) / 3600 / 24;
                for ($x = 0; $x <= $day; $x++) {
                    $sdate1 = $sdate - 3600 * 24;
                    $order = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->select();
                    $drawing = MoneyModel::where('appid', $appid)->where('ctime', 'between time', [$sdate1, $sdate])->select();
                    $pp = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    $countp = 0;
                    foreach ($pp as $key => $value) {
                        $countp++;
                    }
                    $drawingmoney = 0;
                    $ordermessage = '';
                    foreach ($order as $key => $value) {
                        $drawingmoney += $value['profit'];
                        $ordermessage = $ordermessage . 'L' . $value['level'] . '提成' . $value['profit'] . '元';
                    }
                    $afternum = 0;
                    $after = OrderModel::where('appid', $appid)->where('remark', 0)->where('datetime', 'between time', [$sdate1, $sdate])->distinct(true)->field('userid')->select();
                    foreach ($after as $key => $value) {
                        $afternum++;
                    }
                    $sdate -= 3600 * 24;
                    $firstnum = 0;
                    $firstmoney = 0;
                    $aftermoney = 0;
                    $summoney = 0;

                    foreach ($order as $key => $value) {

                        if ($value['remark'] == 1) {
                            $firstnum++;
                            $firstmoney += $value['amount'];
                        } elseif ($value['remark'] == 0) {
//                            $afternum++;
                            $aftermoney += $value['amount'];
                        }
                    }

                    $summoney = $firstmoney + $aftermoney;
                    $week[$x]['time'] = $sdate;
                    $week[$x]['firstnum'] = $firstnum;
                    $week[$x]['afternum'] = $afternum;
                    $week[$x]['firstmoney'] = $firstmoney;
                    $week[$x]['aftermoney'] = $aftermoney;
                    $week[$x]['summoney'] = $summoney;
                    $week[$x]['countp'] = $countp;
                    $week[$x]['drawingmoney'] = $drawingmoney;
                    $week[$x]['ordermessage'] = $ordermessage;
                    if (!$ordermessage) {
                        $week[$x]['ordermessage'] = '无';
                    }
                }

                $result = array(
                    'code' => 1,
                    'message' => '所有数据',
                    'week' => $week
                );

                return json_encode($result);
            }
        }
    }

    public function getrecord(Request $request)
    {
        $session = session('user');
        if (!$session) {
            return $this->fetch('login/login');
        }
        $appid = $session["appid"];
        if ($request->isPost()) {
            $balance = FormsuserModel::where('appid', $appid)->value('balance');
            $level = NewsModel::where('appid', $appid)->value('level');
            return json(['status' => '200', 'appid' => $appid, 'balance' => $balance, 'level' => $level]);
        }
        $list = MoneyModel::where('appid', $appid)->order('ctime desc')->paginate(5);
        if (!empty($list)) {
            foreach ($list as $key => $v) {
                $list[$key]['statusDesc'] = isset(MoneyModel::WITHDRAWAL_STATUS[$v['status']]) ? MoneyModel::WITHDRAWAL_STATUS[$v['status']] : '未知';
            }
        }
        return view('login/getrecord', ['list' => $list]);
    }

    public function formday(Request $request)
    {
        $session = session('user');
        $appid = $session["appid"];
        if ($request->isPost()) {
            $data = input('post.');

            $sdate = $data['timeday'];
            $edate = $sdate + 3600 * 24;
            $week = OrderModel::where('appid', $appid)->where('datetime', 'between time', [$sdate, $edate])->select();
            $result = array(
                'code' => 1,
                'message' => '一周数据',
                'week' => $week
            );

            return json_encode($result);


        }
        return view('login/formday');
    }

    public function faq(Request $request)
    {
        $faqurl = SystemModel::value('faqurl');
        header("location:$faqurl");
        exit;
    }

    public function agency()
    {
        $data = SystemModel::field('level1,level2,level3,level4,level5,first1,first2,first3,first4,first5')->select(1)->toArray();

        $msg = [];
        for ($i = 1; $i < 6; $i++) {
            $msg[$i]['a'] = explode(',', $data[0]['level'. $i])[0];
            $msg[$i]['b'] = explode(',', $data[0]['level' . $i])[1];
            $msg[$i]['first1'] = $data[0]['first' . $i];
        }
        var_dump($msg);
        exit;

    }



}
