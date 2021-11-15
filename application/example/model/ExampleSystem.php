<?php

namespace app\example\model;

use think\Loader;
use think\Model;

class ExampleSystem extends Model
{
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'mtime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }

    public function hasCategory()
    {
        return $this->hasOne('ExampleCategory', 'id', 'cid');
    }

    /**
     * 入库
     * @param array $data 入库数据
     * @author 橘子俊 <364666827@qq.com>
     * @return bool
     */
    public function storage($data = [])
    {
        if (empty($data)) {
            $data = request()->post();
        }

        // 验证
        $valid = Loader::validate('ExampleNews');
        if($valid->check($data) !== true) {
            $this->error = $valid->getError();
            return false;
        }

        if (isset($data['id']) && !empty($data['id'])) {
            $res = $this->update($data);
        } else {
            $res = $this->create($data);
        }
        if (!$res) {
            $this->error = '保存失败';
            return false;
        }

        return $res;
    }
}
