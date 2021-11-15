<?php
return [
  [
    'title' => '后台管理',
    'icon' => 'aicon ai-shezhi',
    'module' => 'example',
    'url' => 'example',
    'param' => '',
    'target' => '_self',
    'debug' => 0,
    'system' => 0,
    'nav' => 1,
    'sort' => 1,
    'childs' => [
      [
        'title' => '代理管理',
        'icon' => 'aicon ai-shezhi',
        'module' => 'example',
        'url' => 'example/index',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 1,
        'sort' => 0,
        'childs' => [
          [
            'title' => '新闻分类',
            'icon' => 'aicon ai-shezhi',
            'module' => 'example',
            'url' => 'example/category/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
            'childs' => [
              [
                'title' => '添加分类',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/category/add',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
              [
                'title' => '修改分类',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/category/edit',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
              [
                'title' => '删除分类',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/category/del',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
              [
                'title' => '状态设置',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/category/status',
                'param' => 'table=example_category',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
            ],
          ],
          [
            'title' => '账号审核',
            'icon' => 'aicon ai-shezhi',
            'module' => 'example',
            'url' => 'example/index/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 1,
            'childs' => [
              [
                'title' => '添加新闻',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/index/add',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
              [
                'title' => '修改新闻',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/index/edit',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
              [
                'title' => '删除新闻',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/index/del',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
              [
                'title' => '状态设置',
                'icon' => '',
                'module' => 'example',
                'url' => 'example/index/status',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 1,
                'sort' => 0,
              ],
              [
                'title' => '安装无效',
                'icon' => '',
                'module' => 'example',
                'url' => 'install-deduction',
                'param' => '',
                'target' => '_self',
                'debug' => 0,
                'system' => 0,
                'nav' => 0,
                'sort' => 0,
              ],
            ],
          ],
        ],
      ],
      [
        'title' => '业绩报表',
        'icon' => 'aicon ai-xitongrizhi-tiaoshi',
        'module' => 'example',
        'url' => 'example/forms',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 1,
        'sort' => 0,
        'childs' => [
          [
            'title' => '今日报表',
            'icon' => 'aicon ai-xitongrizhi-tiaoshi',
            'module' => 'example',
            'url' => 'example/forms/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
          [
            'title' => '代理报表_bak',
            'icon' => 'aicon ai-xitongrizhi-tiaoshi',
            'module' => 'example',
            'url' => 'example/proxyT/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
          [
            'title' => '新增用户排行榜',
            'icon' => 'aicon ai-xitongrizhi-tiaoshi',
            'module' => 'example',
            'url' => 'example/forms/new1',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
          [
            'title' => '历史报表',
            'icon' => 'aicon ai-xitongrizhi-tiaoshi',
            'module' => 'example',
            'url' => 'example/forms/history',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
          [
            'title' => '代理报表',
            'icon' => 'fa fa-list-alt',
            'module' => 'example',
            'url' => 'example/CpaReport/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
        ],
      ],
      [
        'title' => '代理分组',
        'icon' => 'aicon ai-huiyuanliebiao',
        'module' => 'example',
        'url' => 'example/proxy',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 1,
        'sort' => 0,
        'childs' => [
          [
            'title' => '代理分组',
            'icon' => 'aicon ai-huiyuanliebiao',
            'module' => 'example',
            'url' => 'example/proxy/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
        ],
      ],
      [
        'title' => '提款审核',
        'icon' => 'aicon ai-success',
        'module' => 'example',
        'url' => 'example/money/check',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 1,
        'sort' => 0,
        'childs' => [
          [
            'title' => '提款审核',
            'icon' => 'aicon ai-success',
            'module' => 'example',
            'url' => 'example/money/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
          [
            'title' => '一级审核',
            'icon' => '',
            'module' => 'example',
            'url' => 'first-auth',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 0,
            'sort' => 0,
          ],
          [
            'title' => '二级审核',
            'icon' => '',
            'module' => 'example',
            'url' => 'second-auth',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 0,
            'sort' => 0,
          ],
          [
            'title' => '三级审核',
            'icon' => '',
            'module' => 'example',
            'url' => 'third-auth',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 0,
            'sort' => 0,
          ],
        ],
      ],
      [
        'title' => '公告管理',
        'icon' => 'aicon ai-xitongrizhi-tiaoshi',
        'module' => 'example',
        'url' => 'example/ads/adm',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 1,
        'sort' => 0,
        'childs' => [
          [
            'title' => '公告管理',
            'icon' => 'aicon ai-xitongrizhi-tiaoshi',
            'module' => 'example',
            'url' => 'example/ads/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
        ],
      ],
      [
        'title' => '代理信息查询',
        'icon' => 'aicon ai-chu',
        'module' => 'example',
        'url' => 'example/proxy/cha',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 1,
        'sort' => 0,
        'childs' => [
          [
            'title' => '提款记录',
            'icon' => 'aicon ai-chu',
            'module' => 'example',
            'url' => 'example/money/cha',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
        ],
      ],
      [
        'title' => '系统配置',
        'icon' => 'aicon ai-icon01',
        'module' => 'example',
        'url' => 'example/system/deploy',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 1,
        'sort' => 0,
        'childs' => [
          [
            'title' => '系统配置',
            'icon' => 'aicon ai-icon01',
            'module' => 'example',
            'url' => 'example/system/index',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
          [
            'title' => '推广配置',
            'icon' => 'aicon ai-icon01',
            'module' => 'example',
            'url' => 'example/system/generalize',
            'param' => '',
            'target' => '_self',
            'debug' => 0,
            'system' => 0,
            'nav' => 1,
            'sort' => 0,
          ],
        ],
      ],
      [
        'title' => '个人信息设置',
        'icon' => '',
        'module' => 'example',
        'url' => 'system/user/info',
        'param' => '',
        'target' => '_self',
        'debug' => 0,
        'system' => 0,
        'nav' => 0,
        'sort' => 0,
      ],
    ],
  ],
];
