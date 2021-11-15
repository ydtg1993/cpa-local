<?php
namespace app\user\model;

use think\Model;
use app\user\model\User as UserModel;

/**
 * 会员分组模型
 * @package app\user\model
 */
class UserGroup extends Model
{

    // 模型事件
    public static function init()
    {

        // 删除前
        self::event('before_delete', function ($obj) {
            $row = UserModel::where('group_id', $obj['id'])->find();
            if ($row) {
                $obj->error = '['.$obj['name'].']分组下面还有会员数据';
                return false;
            }

            return true;
        });
    }

}
