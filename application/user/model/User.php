<?php
// +----------------------------------------------------------------------
// | HisiPHP框架[基于ThinkPHP5开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2021 http://www.hisiphp.com
// +----------------------------------------------------------------------
// | HisiPHP承诺基础框架永久免费开源，您可用于学习和商用，但必须保留软件版权信息。
// +----------------------------------------------------------------------
// | Author: 橘子俊 <364666827@qq.com>，开发者QQ群：50304283
// +----------------------------------------------------------------------
namespace app\user\model;

use think\Model;

/**
 * 会员模型
 * @package app\user\model
 */
class User extends Model
{
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'mtime';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function init()
    {
        self::event('after_delete', function ($user) {
            runhook('user_delete', $user);
        });
    }

    // 过滤昵称里面的表情符号
    public function setNickAttr($value)
    {
        if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $value)) {
            return 'nick'.random(15, 0);
        }

        return $value;
    }

    // 密码加密
    public function setPasswordAttr($value, $data)
    {
        if (strlen($value) != 32) {
            $value = md5($value);
        }
        return md5($value.$data['salt']);
    }

    // 最后登陆ip
    public function setLastLoginIpAttr()
    {
        return get_client_ip();
    }

    // 最后登陆ip
    public function setLastLoginTimeAttr()
    {
        return request()->time();
    }

    // 有效期
    public function setExpireTimeAttr($value)
    {
        if ($value != 0) {
            return strtotime($value);
        }
        return 0;
    }

    // 有效期
    public function getExpireTimeAttr($value)
    {
        if ($value != 0) {
            return date('Y-m-d', $value);
        }
        return 0;
    }

    public function getMobileAttr($value)
    {
        if (!$value) return '';
        return $value;
    }

    public function hasGroup()
    {
        return $this->hasOne('app\user\model\UserGroup', 'id', 'group_id');
    }
    
    /**
     * 注册
     * @param array $data 参数，可传参数account,username,password,email,mobile,nick,avatar
     * @param bool $login 注册成功后自动登录
     * @author 橘子俊 <364666827@qq.com>
     * @return stirng|array
     */
    public function register($data = [], $login = true)
    {
        $map                = [];
        $map['email']       = '';
        $map['mobile']      = '';
        $map['username']    = '';
        $map['nick']        = isset($data['nick']) ? $data['nick'] : '';
        $map['avatar']      = isset($data['avatar']) ? $data['avatar'] : '';
        $map['group_id']    = 0;
        $map['salt']        = random(16, 0);
        if (isset($data['password'])) {
            $map['password'] = $data['password'];
        }
    
        if (isset($data['account'])) {
            if (is_email($data['account'])) {// 邮箱
                $map['email']       = $data['account'];
            } elseif (is_mobile($data['account'])) {// 手机号
                $map['mobile']      = $data['account'];
            } elseif (is_username($data['account'])) {// 用户名
                $map['username']    = $data['account'];
            } else {
                $this->error = '注册账号异常！';
                return false;
            }
        }

        if (isset($data['email']) && is_email($data['email'])) {
            $map['email'] = $data['email'];
        }

        if (isset($data['mobile']) && is_mobile($data['mobile'])) {
            $map['mobile'] = $data['mobile'];
        }

        if (isset($data['username']) && is_username($data['username'])) {
            $map['username'] = $data['username'];
        }
        

        $group = model('user/UserGroup')->where('default',1)->find();
        if ($group) {
            $map['group_id']    = $group['id'];
            $map['expire_time'] = $group['expire'] > 0 ? strtotime('+ '.$group['expire'].' days') : 0;
        }

        $validate = new \app\user\validate\User;
        if($validate->scene('register')->check($map) !== true) {
            $this->error = $validate->getError();
            return false;
        }

        $res = $this->isUpdate(false)->save($map);
        if (!$res) {
            return false;
        }

        $map['id'] = $this->id;
        unset($map['password']);
        runhook('user_register', $map);
        if ($login) {
            return self::autoLogin($map);
        }

        return true;
    }

    /**
     * 授权登录注册，只为了提供授权登录时绑定user_id
     * @param string $data 传入数据
     * @author 橘子俊 <364666827@qq.com>
     * @return stirng|array
     */
    public function oauthRegister($data = [])
    {
        $map                    = [];
        $map['nick']            = isset($data['nick']) ? $data['nick'] : '';
        $map['email']           = '';
        $map['mobile']          = '';
        $map['username']        = '';
        $map['avatar']          = isset($data['avatar']) ? $data['avatar'] : '';
        $map['last_login_ip']   = get_client_ip();
        $map['last_login_time'] = time();
        $map['salt']            = random(16, 0);

        $group = model('user/UserGroup')->where('default',1)->find();
        if ($group) {
            $map['group_id']    = $group['id'];
            $map['expire_time'] = $group['expire'] > 0 ? strtotime('+ '.$group['expire'].' days') : 0;
        }

        if (isset($data['email']) && is_email($data['email'])) {
            $map['email'] = $data['email'];
        }

        if (isset($data['mobile']) && is_mobile($data['mobile'])) {
            $map['mobile'] = $data['mobile'];
        }

        if (isset($data['username']) && is_username($data['username'])) {
            $map['username'] = $data['username'];
        }

        $res = $this->create($map);
        if (!$res) {
            $this->error = $this->getError() ? : '授权登录失败！';
            return false;
        }

        $map['id'] = $res->id;
        runhook('user_register', $map);

        return self::autoLogin($map);
    }

    /**
     * 登录
     * @param string $account 账号
     * @param string $password 密码
     * @param bool $remember 记住登录 TODO
     * @param string $field 登陆之后缓存的字段
     * @author 橘子俊 <364666827@qq.com>
     * @return stirng|array
     */
    public function login($account = '', $password = '', $remember = false, $field = 'nick,username,mobile,email,avatar', $token = true)
    {
        $where = $rule = [];
        $account    = trim($account);
        $password   = $rule['password'] = trim($password);
        $field      = trim($field, ',');

        // 匹配登录方式
        if (is_email($account)) {
            // 邮箱登录
            $where['email']       = $rule['email']    = $account;
        } elseif (is_mobile($account)) {
            // 手机号登录
            $where['mobile']      = $rule['mobile']   = $account;
        } elseif (is_username($account)) {
            // 用户名登录
            $where['username']    = $rule['username'] = $account;
        } else {
            $this->error = '登陆账号异常！';
            return false;
        }

        if ($token !== false) {
            $rule['__token__'] = input('param.__token__') ? : $token;
            $scene = 'loginToken';
        } else {
            $scene = 'login';
        }

        $validate = new \app\user\validate\User;
        if($validate->scene($scene)->check($rule) !== true) {
            $this->error = $validate->getError();
            return false;
        }

        $where['status'] = 1;
        $member = self::where($where)->field('id,'.$field.',group_id,password,salt,expire_time')->find();
        if (!$member) {
            $this->error = '用户不存在或被禁用！';
            return false;
        }
        
        $member = $member->toArray();

        if (strlen($password) != 32) {
            $password = md5($password);
        }

        // 密码校验
        if (md5($password.$member['salt']) != $member['password'] ) {
            $this->error = '登陆密码错误！';
            return false;
        }

        // 检查有效期
        if ($member['expire_time'] !== 0 && strtotime($member['expire_time']) < time()) {
            $this->error = '账号已过期！';
            return false;
        }

        $login              = [];
        $login['id']        = $member['id'];
        $login['group_id']  = $member['group_id'];
        $fields = explode(',', $field);

        foreach ($fields as $v) {
            if ($v == 'password' || $v == 'salt') {
                continue;
            }
            $login[$v] = $member[$v];
        }

        return self::autoLogin($login);
    }

    /**
     * 判断是否登录
     * @author 橘子俊 <364666827@qq.com>
     * @return bool|array
     */
    public function isLogin() 
    {
        $user = session('login_user');
        if (!isset($user['id'])) {
            return false;
        } else {
            return session('login_user_sign') == $this->dataSign($user) ? $user : false;
        }
    }

    /**
     * 自动登陆
     * @author 橘子俊 <364666827@qq.com>
     * @param bool $oauth 第三方授权登录
     * @return bool|array
     */
    public function autoLogin($data = [], $oauth = false)
    {
        if ($oauth) {
            $map            = [];
            $map['id']      = $data['id'];
            $map['status']  = 1;
            $data = $this->where($map)->field('id,group_id,nick,username,mobile,email,expire_time,avatar')->find();
            if (!$data) {
                $this->error = '用户不存在或被禁用！';
                return false;
            }

            $data = $data->toArray();
            // 检查有效期
            if ($data['expire_time'] !== 0 && strtotime($data['expire_time']) < time()) {
                $this->error = '账号已过期！';
                return false;
            }
        }
        $map                    = [];
        $map['last_login_ip']   = get_client_ip();
        $map['last_login_time'] = request()->time();
        $this->where('id', $data['id'])->update($map);

        session('login_user', $data);
        session('login_user_sign', $this->dataSign($data));
        runhook('user_login', $data);

        return $data;
    }

    /**
     * 管理后台重置密码
     * @author 橘子俊 <364666827@qq.com>
     * @return bool
     */
    public function adminResetPassword($id = [], $pwd = '123456')
    {
        $data = [];
        foreach ($id as $v) {
            $data[] = [
                'id' => $v,
                'salt' => random(16, 0),
                'password' => $pwd,
            ];
        }
        return $this->saveAll($data);
    }

    /**
     * 退出登陆
     * @author 橘子俊 <364666827@qq.com>
     * @return bool
     */
    public static function logout() 
    {
        session('login_user', null);
        session('login_user_sign', null);

        return true;
    }

    /**
     * 数据签名认证
     * @param array $data 被认证的数据
     * @author 橘子俊 <364666827@qq.com>
     * @return string 签名
     */
    public function dataSign($data = [])
    {
        if (!is_array($data)) {
            $data = (array) $data;
        }

        ksort($data);
        $code = http_build_query($data);
        $sign = sha1($code);

        return $sign;
    }
}
