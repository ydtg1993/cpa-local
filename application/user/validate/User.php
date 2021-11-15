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
namespace app\user\validate;

use think\Validate;

/**
 * 会员验证器
 * @package app\user\validate
 */
class User extends Validate
{
    //定义验证规则
    protected $rule = [
        'username|用户名'  => 'requireWith:username|checkUsername:thinkphp|unique:user',
        'email|邮箱'      => 'requireWith:email|email|checkEmail:thinkphp|unique:user',
        'mobile|手机号'    => 'requireWith:mobile|checkMobile:thinkphp|unique:user',
        'password|密码'   => 'require|length:6,32',
        'salt|密码混淆'     => 'require|length:16',
        'nick|昵称'       => 'requireWith:nick|unique:user',
        'status|状态'    => 'require|in:0,1',
        '__token__'     => 'token',
    ];

    //定义验证提示
    protected $message = [
        'password.require'  => '密码不能为空',
        'password.length'   => '密码长度6-20位',
        '__token__.token'    => '请刷新页面',
    ];

    //定义验证场景
    protected $scene = [
        'register' => [
            'username',
            'email',
            'mobile',
            'password',
            'salt',
            'nick',
        ],
        // 后台创建会员
        'adminCreate' => [
            'username',
            'email',
            'mobile',
            'password',
            'salt',
            'nick',
            'status',
        ],
    ];

    // 自定义登录场景方法
    public function sceneLogin()
    {
        return $this->only(['username', 'email', 'mobile', 'password'])
                    ->remove('username', ['unique'])
                    ->remove('email', ['unique'])
                    ->remove('mobile', ['unique']);
    }

    // 自定义登录场景方法+token验证
    public function sceneLoginToken()
    {
        return $this->only(['username', 'email', 'mobile', 'password', '__token__'])
                    ->remove('username', ['unique'])
                    ->remove('email', ['unique'])
                    ->remove('mobile', ['unique'])
                    ->append('__token__', ['require']);
    }

    // 自定义后台更新场景方法
    public function sceneAdminUpdate()
    {
        return $this->only(['username', 'email', 'mobile', 'nick', 'password', 'salt', '__token__'])
                    ->remove('password', ['require'])
                    ->append('password', ['requireWith:password'])
                    ->remove('salt', ['require'])
                    ->append('salt', ['requireWith:password'])
                    ->append('__token__', ['require']);
    }
    
    /**
     * 检查邮箱
     * @author 橘子俊 <364666827@qq.com>
     * @return stirng|array
     */
    protected function checkEmail($value, $rule, $data)
    {
        if (empty($data['username']) && empty($data['email']) && empty($data['mobile'])) {
            return '用户名、手机、邮箱至少选填一项！';
        }
        return true;
    }

    /**
     * 检查用户名
     * @author 橘子俊 <364666827@qq.com>
     * @return stirng|array
     */
    protected function checkUsername($value, $rule, $data)
    {
        if (empty($data['username']) && empty($data['email']) && empty($data['mobile'])) {
            return '用户名、手机、邮箱至少选填一项！';
        }
        
        if (!is_username($value)) {
            return '用户名必须以中文或字母开头[支持中文,字母,数字,下划线]';
        }

        return true;
    }

    /**
     * 检查手机号
     * @author 橘子俊 <364666827@qq.com>
     * @return stirng|array
     */
    protected function checkMobile($value, $rule, $data)
    {
        if (empty($data['username']) && empty($data['email']) && empty($data['mobile'])) {
            return '用户名、手机、邮箱至少选填一项！';
        }
        
        if (!is_mobile($value)) {
            return '手机号格式错误！';
        }

        return true;
    }
}
