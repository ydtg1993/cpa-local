<?php

namespace app\example\model;


use think\Validate;

class ExampleSystem extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|分组名称' => 'require',
        'appid|组长账号' => 'require',
    ];
}
