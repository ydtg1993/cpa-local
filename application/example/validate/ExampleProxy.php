<?php

namespace app\example\validate;

use think\Validate;

/**
 * 新闻验证器
 * @package app\example\validate
 */
class ExampleProxy extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|分组名称' => 'require',
        'appid|组长账号' => 'require',
    ];
}
