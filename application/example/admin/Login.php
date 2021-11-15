<?php

namespace app\example\admin;

use app\example\model\ExampleFormsuser;
use think\Db;
use think\Exception;
use think\facade\Session;
use think\Controller;
use think\Request;
use app\example\model\ExampleNews as NewsModel;
use app\example\model\ExampleSystem as SystemModel;
use app\example\model\ExampleForms as FormsModel;
use app\example\model\ExampleFormsuser as FormsuserModel;
use app\example\model\ExampleAds as AdsModel;

class Login extends Controller
{
    protected function initialize()
    {
        parent::initialize();
        $theme = config("database.theme_id") ?? '1';
        $this->assign('_theme_id', $theme);
    }

    /**
     * @param Request $request
     * @return false|mixed|string|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        if ($request->isPost()) {
            $session = session('user');
            $appid = $session["appid"];
            $forms = FormsuserModel::where('appid', $appid)->select();
            $ads = AdsModel::where('status', 1)->limit(3)->order("sort desc")->select();


            $startTime = date('Y-m-d 0:0:0');
            $endTime = date('Y-m-d 23:59:59');
            $where[] = ['invite_time', '>=', $startTime];
            $where[] = ['invite_time', '<=', $endTime];
            $where[] = ['uid', '=', $appid];
            $data = [];
            $data['today_install_number'] = Db::table('hisi_example_install_log')->where($where)->count(); // 总安装量
//            echo Db::table('hisi_example_install_log')->where($where)->getLastSql(); // 总安装量
            $data['today_android_install_number'] = Db::table('hisi_example_install_log')->where($where)->where('invite_user_os', '1')->count(); // 安卓装量
//            echo Db::table('hisi_example_install_log')->where($where)->where('invite_user_os', '1')->getLastSql();
            $data['today_ios_install_number'] = Db::table('hisi_example_install_log')->where($where)->where('invite_user_os', '2')->count(); // IOS装量

            $data['today_price'] = $this->getPrice($data['today_install_number'],$appid); // 单价
            $data['today_income'] = $data['today_price'] * $data['today_install_number']; // 今日收益
            $data['sum_income'] = Db::table('hisi_example_formsuser')->where('appid', $appid)->value('sum'); // 累计收益

            $result = array(
                'code' => 1,
                'message' => '数据返回成功',
                'ads' => $ads,
                'forms' => $forms,
                'data' => $data
            );
            return json($result);

        }
        $example_system = Db::table('hisi_example_system')->where('id', 1)->field('custtext, custQQ')->find();
        $custtext = explode(',', $example_system['custtext']);
        $custQQ = explode(',', $example_system['custQQ']);

        $session = session('user');
        if ($session) {
            return $this->fetch('login/index', [
                'custtext' => $custtext,
                'custQQ' => $custQQ,
            ]);
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
    }

    public function logout()
    {
        //清空session
        session('user', null);
        //跳转到登录页
        $this->redirect('login');
    }

    public function cust()
    {
        header('Access-Control-Allow-Origin:*');
        $result = SystemModel::select(1);

        $qq = $result[0]['custQQ1'];
        return json(['status' => '200', 'qq' => $qq]);
    }

    public function login(Request $request)//登陆接口
    {

//        return json(['status' => 'success', 'msg' => '456！']);
        header('Access-Control-Allow-Origin:*');
        $model = new NewsModel;
        try {
            if ($request->isPost()) {
                $data = input('post.');
                $password = md5(trim($data['password']));
                $username = trim($data['email']);
                if ($model->login($username, $password) == 1) {
//                var_dump($_SESSION);
                    return json(['status' => 'success', 'msg' => '登录成功']);
                } elseif ($model->login($username, $password) == 0) {
                    return json(['status' => '0', 'msg' => '等待审核开通代理权限']);
                } elseif ($model->login($username, $password) == 2) {
                    return json(['status' => '2', 'msg' => '用户已被驳回，请联系管理员！']);
                } else {
                    return json(['status' => '3', 'msg' => '账号或密码错误！']);
                }
            }
        }catch (\Exception $e){
            return json(['msg' => $e->getMessage()]);
        }
        return $this->fetch();
    }

    /**
     * @return object
     */
    public function getapp(Request $request)
    {
        $data = input('post.');
//        $data['email'] = '123456@qq.com';
        $data['email'] = trim($data['email']);
        $count = NewsModel::where("email='{$data['email']}'")->count();
        if ($count > 0) {
            return json(['status' => '2', 'msg' => '用户名已被占用']);
        } else {
            return json(['status' => '1', 'msg' => '注册成功！']);
        }
    }


    public function reg(Request $request)
    {
        header('Access-Control-Allow-Origin:*');

        if ($request->isPost()) {
            $data = input('post.');
            $data['email'] = trim($data['email']);
            $data['password'] = md5(trim($data['password']));
//            return json(['status' => $data,'msg' => '111']);
            $data['ctime'] = time();
            $data['mtime'] = time();
            $appid = $data['appid'];
            $data['status'] = 1;
            $count = NewsModel::where("email='{$data['email']}' || appid='" . $appid . "'")->count();
            if ($count > 0) {
                return json(['status' => '2', 'msg' => '登录账号已被占用或appid已被注册']);
            } else {
                $formuser["email"] = $data["email"];
                $formuser["appid"] = $appid;
                $formuser["status"] = "1";
                ExampleFormsuser::create($formuser);
                $mid = NewsModel::insertGetId($data);  //insertGetId注册成功返回用户id
                if ($mid > 0) {
                    //防止session污染
                    session('smscode', null);
                    session('smstime', null);
                    session('mobile', null);
                    return json(['status' => '1', 'msg' => '注册成功！']);
                } else {
                    NewsModel::where("email='{$data['email']}'")->delete();
                    return json(['status' => '0', 'msg' => "注册失败"]);
                }
            }
        }

        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $url = 'http://www.daili.com/admin.php/example/login/index';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //参数为1表示传输数据，为0表示直接输出显示。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //参数为0表示不带头文件，为1表示带头文件
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        dump($output);
        exit;
    }

    public function ads(Request $request)
    {
        $get = $request->get();
        return $this->fetch();
    }

    public function wait(Request $request)
    {

        return $this->fetch();
    }

    public function out(Request $request)
    {

        return $this->fetch();
    }
}
