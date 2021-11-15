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
namespace app\user\admin;

use app\system\admin\Admin;
use app\user\model\UserGroup as GroupModel;

/**
 * 会员分组控制器
 * @package app\user\admin
 */
class Group extends Admin
{
    protected $hisiModel = 'UserGroup';

    /**
     * 分组列表
     * @author 橘子俊 <364666827@qq.com>
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $data = [];
            $data['data'] = GroupModel::select();
            $data['count'] = 0;
            $data['code'] = 0;

            return json($data);

        }

        return $this->fetch();
    }
    
    /**
     * 设置默认等级
     * @author 橘子俊 <364666827@qq.com>
     * @return mixed
     */
    public function setDefault($id = 0)
    {
        GroupModel::where('id', 'neq', $id)->setField('default', 0);
        
        if (GroupModel::where('id', $id)->setField('default', 1) === false) {
            return $this->error('设置失败');
        }

        return $this->success('设置成功');
    }
}
