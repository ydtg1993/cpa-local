<?php

namespace app\example\model;

use think\Model;
use think\Loader;

class ExampleMoney extends Model
{
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'mtime';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $is_cover = true;

    //(0待审批，1已审批，2已驳回，3已打款)
    const  WITHDRAWAL_STATUS = [
        0 => '待审批',      //CPS是1
        2 => '一审驳回',    //CPS是4
        1 => '一审通过',    //CPS是2
        6 => '二审驳回',
        8 => '二审通过',    //二审时，>5W需要三审时改为这个状态，<=5W直接改为待付款状态
        9 => '三审驳回',

        5 => '待付款',     //二或三审通过（金额<=5W只需要二审，>5W需要三审）
        3 => '已打款',

        7  => '支付驳回',   //支付公司驳回
        -3 => '已拒绝',    //支付公司拒绝
    ];

    const APPROVED_STATUS = 5;
    const PAID_STATUS = 3;
    const REFUSED_STATUS = -3;

    const PAY_APPROVED_STATUS = 1;
    const PAY_PAID_STATUS = 2;
    const PAY_REFUSED_STATUS = -3;

    const PAY_PAYMENT_STATUS_MAP = [
        self::PAY_APPROVED_STATUS => self::APPROVED_STATUS,
        self::PAY_PAID_STATUS     => self::PAID_STATUS,
        self::PAY_REFUSED_STATUS  => self::REFUSED_STATUS,
    ];

    const PAYMENT_STATUS_MAP = [
        self::APPROVED_STATUS => self::PAY_APPROVED_STATUS,
        self::PAID_STATUS     => self::PAY_PAID_STATUS,
        self::REFUSED_STATUS  => self::PAY_REFUSED_STATUS,
    ];
    

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
