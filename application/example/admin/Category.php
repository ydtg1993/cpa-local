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
use app\system\admin\Admin;
use app\example\model\ExampleCategory as CategoryModel;

/**
 * 示例模块
 * @package app\example\controller
 */
class Category extends Admin
{
    protected $hisiModel = 'ExampleCategory';
    protected $hisiValidate = 'ExampleCategory';

    protected function initialize()
    {
        parent::initialize();
        if ($this->request->action() != 'index' && !$this->request->isPost()) {
            $category = CategoryModel::getSelect(CategoryModel::getChilds());
            $this->assign('category', $category);
        }
    }

    public function index()
    {
        $data_list = CategoryModel::getChilds(0, 0);

        $this->assign('data_list', $data_list);


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