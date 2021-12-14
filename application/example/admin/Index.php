<?php
// +----------------------------------------------------------------------
// | HisiPHP框架[基于ThinkPHP5开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 http://www.hisiphp.com
// +----------------------------------------------------------------------
// | HisiPHP承诺基础框架永久免费开源，您可用于学习和商用，但必须保留软件版权信息。
// +----------------------------------------------------------------------
// | Author: 橘子俊 <364666827@qq.com>，开发者QQ群：50304283
// +----------------------------------------------------------------------

namespace app\example\admin;

use app\example\model\ExampleNews;
use app\example\service\dbservice;
use app\system\admin\Admin;
use app\example\model\ExampleNews as NewsModel;
use app\example\model\ExampleLogintime as LogintimeModel;
use app\example\model\ExampleCategory as CategoryModel;
use think\Db;
use think\Request;
use app\system\model\SystemMenu as MenuModel;
use app\system\model\SystemRole as RoleModel;

/**
 * 示例模块
 * @package app\example\controller
 */
class Index extends Admin
{
    protected $hisiModel = 'ExampleNews';
    protected $hisiValidate = 'ExampleNews';

    protected function initialize()
    {
        parent::initialize();
        if ($this->request->action() != 'index' && !$this->request->isPost()) {
            $category = CategoryModel::getSelect(CategoryModel::getChilds());
            $this->assign('category', $category);
        }
    }

    public function recstatus($id)
    {
        $data = [
            'status' => 2,
        ];
        NewsModel::update($data, ['id' => $id], true);
        $this->success('暂停用户成功', 'index');
        if ($this->request->isAjax()) {

            $where = $data = ['status' => [0, 1]];
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);

            $data['data'] = NewsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count'] = NewsModel::where($where)->count('id');
            $data['code'] = 0;
            return json($data);
        }
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch('index');
    }

    public function backstatus($id)
    {
        $data = [
            'status' => 1,
        ];
        NewsModel::update($data, ['id' => $id], true);
        $this->success('恢复成功', 'index');
        if ($this->request->isAjax()) {

            $where = $data = ['status' => [0, 1]];
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);

            $data['data'] = NewsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count'] = NewsModel::where($where)->count('id');
            $data['code'] = 0;
            return json($data);
        }
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch('index');
    }

    public function editpwd(Request $request)
    {
        $id = $this->request->get('id');
        $password = $this->request->param('password');
        $password2 = $this->request->param('password2');
        if ($password == $password2) {
            $password = md5(trim($password));
            $res = NewsModel::where('id', $id)->update(['password' => $password]);
            if ($res) {
                $this->success('修改密码成功');
            } else {
                $this->error('修改密码失败');
            }
        } else {
            $this->error('两次密码不一致');
        }
    }

    public function setProfitScale()
    {
        $id = (int)$this->request->get('id');
        $newsData = NewsModel::field('first,follow')->where('id', $id)->limit(1)->find();
        $this->assign('data', $newsData);
        $this->assign('id', $id);
        return $this->fetch();
    }

    public function submitSetProfitScale()
    {
        $id = $this->request->get('id');
        $first = $this->request->param('first');
//        $follow = $this->request->param('follow');
//        if (NewsModel::where('id', $id)->update(['first' => (int)$first, 'follow' => (int)$follow])) {
        if (NewsModel::where('id', $id)->update(['first' => (float)$first])) {
            $this->success('设置安装单价成功');
        }
        $this->error('设置安装单价失败');
    }

    public function rec()
    {
        if ($this->request->isAjax()) {
            $where = $data = ['status' => 2];
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
            $data['data'] = NewsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count'] = NewsModel::where($where)->count('id');
            $data['code'] = 0;
            return json($data);
        }
        // 分组切换类型 0无需分组切换，1单个分组，2多个分组切换[无链接]，3多个分组切换[有链接]，具体请看application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }

    public function show($id)
    {
        $list = NewsModel::where('appid', $id)->select();
        $login1 = LogintimeModel::where('appid', $id)->select();
        $this->assign('list', $list);
        $this->assign('login1', $login1);
        return $this->fetch();
    }

    public function index()
    {
        $dbservice = new dbservice();
        $app_info = $dbservice->db_start();
        if(!$app_info){
            $this->redirect('login');
        }
        $_SESSION['app_db_info'] = json_encode($app_info);
        $email = $this->request->param('email/s');
        $level = $this->request->param('level/d');
        if ($this->request->isAjax()) {
            $where = $data = [];
            $where[] = ['status', 'in', [0, 1]];
            $page = $this->request->param('page/d', 1);
            $limit = $this->request->param('limit/d', 15);
            if ($level) {
                $where[] = ['level', '=', $level];
            }
            if ($email) {
                $where[] = ['email', 'like', '%' . $email . '%'];
            }
            $data['data'] = NewsModel::with('hasCategory')->where($where)->page($page)->limit($limit)->select();
            $data['count'] = NewsModel::where($where)->count('id');
            $data['code'] = 0;
            return json($data);
        }
        //无效权限
        $authMenu = MenuModel::where('url', 'install-deduction')->find();
        $installDeductionAuth = RoleModel::checkAuth($authMenu['id']);

        $this->assign('email', $email);
        $this->assign('level', $level);
        $this->assign('installDeductionAuth', $installDeductionAuth);
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }

    /**设置无效比例
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * DateTime: 2021/4/20 13:26
     */
    public function setDeductionRatio()
    {
        $request = $this->request;
        $id = (int)$request->get('id');
        $newsData = NewsModel::where('id', $id)->find();
        if($request->isPost()) {
            $install_deduction_ratio = (int)$request->post('install_deduction_ratio');
            if ($install_deduction_ratio < 0 || $install_deduction_ratio > 100) {
                $this->error('无效比例请设置在0-100之间');
            }
            $newsData->install_deduction_ratio = $install_deduction_ratio;
            $newsData->save();
            $this->success('设置成功');
        }
        $this->assign('data', $newsData);
        $this->assign('id', $id);

        return $this->fetch();
    }

}