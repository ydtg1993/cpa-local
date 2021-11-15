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
/**
 * 模块菜单
 * 字段说明
 * url 【链接地址】格式：user/控制器/方法，可填写完整外链[必须以http开头]
 * param 【扩展参数】格式：a=123&b=234555
 */
return [
  [
    'title' => '会员',
    'icon' => 'hs-icon hs-icon-vip',
    'module' => 'user',
    'url' => 'user',
    'param' => '',
    'target' => '_self',
    'debug' => 0,
    'system' => 0,
    'nav' => 1,
    'sort' => 100,
    'childs' => [
      [
        'title' => '会员管理',
        'icon' => 'hs-icon hs-icon-users',
        'module' => 'user',
        'url' => 'user/index',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 1,
        'nav' => 1,
        'sort' => 2,
        'childs' => [
          [
            'title' => '会员分组',
            'icon' => '',
            'module' => 'user',
            'url' => 'user/group/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 1,
            'nav' => 1,
            'sort' => 1,
            'childs' => [
              [
                'title' => '添加分组',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/group/add',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 0,
              ],
              [
                'title' => '修改分组',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/group/edit',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 0,
              ],
              [
                'title' => '删除分组',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/group/del',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 0,
              ],
              [
                'title' => '设置默认分组',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/group/setDefault',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 0,
              ],
            ],
          ],
          [
            'title' => '会员列表',
            'icon' => '',
            'module' => 'user',
            'url' => 'user/index/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 1,
            'nav' => 1,
            'sort' => 2,
            'childs' => [
              [
                'title' => '添加会员',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/index/add',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 1,
              ],
              [
                'title' => '修改会员',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/index/edit',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 2,
              ],
              [
                'title' => '删除会员',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/index/del',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 3,
              ],
              [
                'title' => '状态设置',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/index/status',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 4,
              ],
              [
                'title' => '[弹窗]会员选择',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/index/pop',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 5,
              ],
              [
                'title' => '重置密码',
                'icon' => '',
                'module' => 'user',
                'url' => 'user/index/resetPwd',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 1,
                'sort' => 6,
              ],
            ],
          ],
        ],
      ],
    ],
  ],
];
