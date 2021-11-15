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
use app\user\model\User as UserModel;
use app\user\model\UserGroup as GroupModel;

/**
 * 会员控制器
 * @package app\user\admin
 */
class Index extends Admin
{
    protected $hisiModel = 'User';//模型名称[通用添加、修改专用]
    protected $hisiAddScene = 'adminCreate';//添加数据验证场景名
    protected $hisiEditScene = '';//更新数据验证场景名

    /**
     * 会员管理
     * @author 橘子俊 <364666827@qq.com>
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $where      = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 15);
            $keyword    = $this->request->param('keyword/s');
            $groupId    = $this->request->param('group_id/d');

            if ($keyword) {

                if (is_email($keyword)) {// 邮箱

                    $where[] = ['email', 'eq', $keyword];

                } elseif (is_mobile($keyword)) {// 手机号

                    $where[] = ['mobile', 'eq', $keyword];

                } elseif (is_numeric($keyword)) {// ID

                    $where[] = ['id', 'eq', $keyword];

                } else {// 用户名、昵称

                    $where[] = ['username', 'like', '%'.$keyword.'%'];

                }

            }

            if ($groupId) {
                $where[] = ['group_id', 'eq', $groupId];
            }

            $data['data'] = UserModel::with('hasGroup')
                            ->where($where)
                            ->page($page)
                            ->limit($limit)
                            ->order('id desc')
                            ->select();

            $data['count'] = UserModel::where($where)->count('id');
            $data['code'] = 0;

            return json($data);

        }

        $groups = GroupModel::cache(600)->column('id,name');
        $this->assign('groups', $groups);
        
        return $this->fetch();
    }

    /**
     * 重置密码
     * @author 橘子俊 <364666827@qq.com>
     * @return mixed
     */
    public function resetPwd()
    {
        $id = $this->request->param('id/a');

        $model = new UserModel;

        if (!$model->adminResetPassword($id)) {
            return $this->error('密码重置失败');
        }

        return $this->success('密码重置成功');
    }
}