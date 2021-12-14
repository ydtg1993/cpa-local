<?php
// +----------------------------------------------------------------------
// | HisiPHP框架[基于ThinkPHP5开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 http://www.hisiphp.com
// +----------------------------------------------------------------------
// | HisiPHP提供个人非商业用途免费使用，商业需授权。
// +----------------------------------------------------------------------
// | Author: 橘子俊 <364666827@qq.com>，开发者QQ群：50304283
// +----------------------------------------------------------------------
namespace app\example\model;

use app\example\model\ExampleNews as NewsModel;
use app\example\model\ExampleLogintime as LogintimeModel;
use think\facade\Session;
use think\Model;
use think\Loader;

class ExampleNews extends Model
{
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'mtime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $is_cover = true;

    public static $user = array();

    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }

    public function hasCategory()
    {
        return $this->hasOne('ExampleCategory', 'id', 'cid');
    }
    /**
     * 入库
     * @param array $data 入库数据
     * @author 橘子俊 <364666827@qq.com>
     * @return bool
     */  
    public function storage($data = [])
    {
        if (empty($data)) {
            $data = request()->post();
        }

        // 验证
        $valid = Loader::validate('ExampleNews');
        if($valid->check($data) !== true) {
            $this->error = $valid->getError();
            return false;
        }

        if (isset($data['id']) && !empty($data['id'])) {
            $res = $this->update($data);
        } else {
            $res = $this->create($data);
        }
        if (!$res) {
            $this->error = '保存失败';
            return false;
        }
        
        return $res;
    }
    public function login($username = '', $password = '', $remember = false)
    {
        $username = trim($username);
//        $rule = [
//            'email|用户名' => 'require',
//            'password|密码' => 'require|length:6,16',
//            //'code|验证码' => 'require|captcha'
//        ];
//        $msg = [
//            'email.require' => '用户名不能为空',
//            'password.require' => '密码不能为空',
//            'password.length' => '密码长度必须是6~16个字符',
//        ];
//        $result = $this->validate($rule, $msg);
//        if(true !== $result){
//            return json(['status' => 'error','msg' => '用户名或者密码格式不正确！']);
//        }
//            $password=$data['password'];
//        $password=md5(trim($password));
        $result=NewsModel::where('email',$username)->where('password',$password)->find();
//        echo $this->getLastSql();
        if($result){
            switch ($result['status']) {
                case '0':
                    return 0;
                    //
                    break;
                case '1':
//                        session_start();
//                        $_SESSION["email"]=$result['email'];
//                        session('email', 'php');
                    $login['appid'] = $result['appid'];
                    $login['email'] = $result['email'];

                    // 缓存登录信息
                    session::set('user', $login);
                    $data['appid'] = $result['appid'];
                    $data['ctime'] = time();
                    $data['data'] = date('Y-m-d', time());
                    $data['time'] = date('H:i:s', time());
                    LogintimeModel::create($data);
                    //self::$user = $login;
                    return 1;
                    //return json(['status' => 'success','msg' => '登陆成功！']);
                    break;
                case '2':
                    return 2;
                    //
                    break;
            }
        }else{
            return 3;
        }

    }
    public function isLogin()
    {
        $user = session::get('user');
        //$user = self::$user;
        var_dump($user);die;
//        return $user;
//        if (isset($user['uid'])) {
//            if (!self::where('id', $user['uid'])->find()) {
//                return false;
//            }
//            return session('admin_user_sign') == $this->dataSign($user) ? $user : false;
//        }
//        return false;
    }
}