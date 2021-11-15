<?php
namespace app\example\home;
use think\facade\Session;
use think\Request;
use app\example\model\ExampleMoney as MoneyModel;
use app\example\model\ExampleNews as NewsModel;
use app\example\model\ExampleFormsuser as FormsuserModel;
use think\facade\Env;

class Pay
{
    const PAY_USER_ACCOUNT = 'CPAAPI';
    const PAY_USER_PWD = 'x0Pxo2Ss5uGP1hKHx5ga';


    /**支付公司获取token
     * @return false|string
     * DateTime: 2021/4/16 13:50
     */
    public function token(Request $request) {
        $req = $request->post();
        if (empty($req['pid']) || empty($req['account']) || empty($req['pwd'])) {
            return json_encode(['code' => 10001, 'message' => 'error params']);
        }
        if($req['pid'] != Env::get('PAY_PRODUCT_ID')) {
            return json_encode(['code' => 10001, 'message' => '产品编号错误']);
        }
        if($req['account'] != self::PAY_USER_ACCOUNT || $req['pwd'] != self::PAY_USER_PWD){
            return json_encode(['code' => 10001, 'message' => '账号或密码错误']);
        }
        $login['pid'] = $req['pid'];
        $login['account'] = $req['account'];
        $login['pwd'] = $req['pwd'];
        $login['token'] = md5($req['pid'] . $req['account'] . $req['pwd']);
        // 缓存信息
        session::set('pay-user', $login);

        return json_encode([
            'ip'    => $_SERVER['REMOTE_ADDR'],
            'iat'   => time(),
            'exp'   => strtotime(date("Y-m-d h:i:s", strtotime("+1 week"))),
            'token' => $login['token']
        ]);
    }

    /**支付公司获取待付款数据
     * @param Request $request
     * @return false|string
     * DateTime: 2021/4/16 15:02
     */
    public function getApprovedRecords(Request $request) {
        if (empty($request->header('Authorization')) ||
            $request->header('Authorization') != md5(Env::get('PAY_PRODUCT_ID') . self::PAY_USER_ACCOUNT . self::PAY_USER_PWD)) {
            return json_encode(['code' => 10001, 'message' => 'token无效,请重新登录']);
        }
        $req = $request->post();
        if($req['pid'] != Env::get('PAY_PRODUCT_ID')) {
            return json_encode(['code' => 10001, 'message' => '产品编号错误']);
        }
        $limit = $req['pageSize'] ?? 10;
        $page  = $req['pageNum'] ?? 1;
        if ($page < 1) {
            $page = 1;
        }
        $offset = $limit * ($page - 1);
        $returnData = [];
        $list = MoneyModel::where('status', 5)->limit($limit, $offset)->order("ctime desc")->select();
        if (!empty($list)) {
            foreach ($list as $key => $v) {
                $proxy = NewsModel::where('appid', $v['appid'])->find();

                if ($v->pay_type == '3') {
                    //USDT方式判断钱包地址协议（0x开头是ERC20，1或3开头是OMNI）
                    $firstLetter = substr($proxy->usdt_account, 0, 1);
                    if ($firstLetter == '0') {
                        $bank = 'other(ERC20)';
                    }elseif ($firstLetter == '1' || $firstLetter == '3') {
                        $bank = 'other(OMNI)';
                    }else{
                        $bank = '未知';
                    }
                }

                $returnData[] = [
                    "amount"            => $v->pay_type == '3' ? $v->usdt_amount : (string)$v->money,
                    "bank_account_name" => $v->pay_type == '3' ? 'USDT' : $proxy->moneyname,
                    "bank_account_no"   => $v->pay_type == '3' ? $proxy->usdt_account : $proxy->cird,
                    "bank_account_type" => "1",
                    "bank_province"     => '',
                    "bank_city"         => '',
                    "bank_name"         => $v->pay_type == '3' ? $bank : $proxy->bank, //银行卡方式：银行名称；USDT方式:地址协议（0x开头是ERC20，1或3开头是OMNI）
                    "branch_name"       => '',
                    "created_date"      => $v->ctime,
                    "currency"          => $v->pay_type == '3' ? 'USDT' : 'CNY',
                    "customer_type"     => "2",
                    "login_name"        => $proxy->email,
                    "flag"              => "" . MoneyModel::PAYMENT_STATUS_MAP[$v->status] . "",
                    "last_update"       => $v->mtime,
                    "processed_date"    => $v->first_auth_at ?? $v->mtime,
                    "remarks"           => $v->remark ?? "",
                    "request_id"        => $req['pid'] . $v->orderid,
                    "customer_level"    => "1",
                    "deposit_level"     => "1",
                    "priority_level"    => "1"
                ];
            }
        }
        return json_encode(['records'=>$returnData]);
    }

    /**支付公司通知业务系统批准或拒绝付款
     * @param Request $request
     * @return false|string
     * DateTime: 2021/4/16 16:03
     */
    public function approveRecord(Request $request) {
        if (empty($request->header('Authorization')) ||
            $request->header('Authorization') != md5(Env::get('PAY_PRODUCT_ID') . self::PAY_USER_ACCOUNT . self::PAY_USER_PWD)) {
            return json_encode(['code' => 10001, 'message' => 'token无效,请重新登录']);
        }
        $req = $request->put();
        if($req['pid'] != Env::get('PAY_PRODUCT_ID')) {
            return json_encode(['code' => 10001, 'message' => '产品编号错误']);
        }
        if ($req['request_type'] == 2) {
            $orderid = substr($req['request_id'],strlen($req['pid']));
            if (!$record = MoneyModel::where('orderid', $orderid)->where('status', 5)->find()) {
                return json_encode(['code' => 10001, 'message' => '该提款请求不存在或非待付款状态']);
            }
            $record->status = MoneyModel::PAY_PAYMENT_STATUS_MAP[$req['new_flag']];
            $record->remark = (isset($req['remarks']) && $req['remarks']) ? $req['remarks'] : '';
            $record->mtime = time();

            if($record->save()) {
                //如果拒绝，将提现金额返到代理账户余额
                if ($record->status == -3) {
                    $balance = FormsuserModel::where('appid', $record['appid'])->value('balance');
                    FormsuserModel::where('appid', $record['appid'])->update(['balance' => $balance + $record['money']]);
                }

                return json_encode(['code'=>'200', 'message'=> 'success']);
            }else{
                return json_encode(['code' => 10001, 'message' => 'error']);
            }
        }else{
            return json_encode(['code' => 10001, 'message' => '请求类型错误']);
        }
    }

}