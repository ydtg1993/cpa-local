<?php

namespace app\example\admin;
use app\example\service\dbservice;
use app\system\admin\Admin;
use app\example\model\ExampleMoney as MoneyModel;
use app\example\model\ExampleNews as NewsModel;
use app\example\model\ExampleCategory as CategoryModel;
use think\Db;
use think\Exception;
use think\Request;
use app\example\model\ExampleFormsuser as FormsuserModel;
use app\system\model\SystemMenu as MenuModel;
use app\system\model\SystemRole as RoleModel;

class Money extends Admin
{
    protected $hisiModel = 'ExampleMoney';
    protected $hisiValidate = 'ExampleMoney';

    protected function initialize()
    {
        parent::initialize();
        if ($this->request->action() != 'index' && !$this->request->isPost()) {
            $category = CategoryModel::getSelect(CategoryModel::getChilds());
            $this->assign('category', $category);
        }
        $dbservice = new dbservice();
        $app_info = $dbservice->db_start();
        if(!$app_info){
            $this->redirect('login');
        }
        $_SESSION['app_db_info'] = json_encode($app_info);
    }

    public function index()
    {
        $request   = $this->request;
        $appid     = intval($request->param("appid"));
        $startDate = $request->param("start_date");
        $endDate   = $request->param("end_date");

        if ($this->request->isAjax()) {
            $where      = $data = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 15);
            if (!empty($appid)) {
                $where[] = ['appid', '=', $appid];
            }
            if (!empty($startDate)) {
                $where[] = ['ctime', '>=', strtotime($startDate)];
            }
            if (!empty($endDate)) {
                $where[] = ['ctime', '<=', strtotime($startDate)];
            }
            $data['data']   = MoneyModel::with('hasCategory')->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            if (!empty($data['data'])) {
                foreach ($data['data'] as $key => $v) {
                    $data['data'][$key]['statusDesc'] = isset(MoneyModel::WITHDRAWAL_STATUS[$v['status']]) ? MoneyModel::WITHDRAWAL_STATUS[$v['status']] : '??????';
                }
            }
            $data['count']  = count($data['data']);
            $data['code']   = 0;

            return json($data);
        }

        //????????????
        $firstAuthMenu = MenuModel::where('url', 'first-auth')->find();
        $secondAuthMenu = MenuModel::where('url', 'second-auth')->find();
        $thirdAuthMenu = MenuModel::where('url', 'third-auth')->find();
        $payDoneMenu = MenuModel::where('url', 'pay-done')->find();
        $data['firstAuth'] = RoleModel::checkAuth($firstAuthMenu['id']);
        $data['secondAuth'] = RoleModel::checkAuth($secondAuthMenu['id']);
        $data['thirdAuth'] = RoleModel::checkAuth($thirdAuthMenu['id']);
        $data['payDoneAuth'] = RoleModel::checkAuth($payDoneMenu['id']);

        //???????????????
        $moneyModel = new MoneyModel();
        if (!empty($appid)) {
            $moneyModel = $moneyModel->where('appid', '=', $appid);
        }
        if (!empty($startDate)) {
            $moneyModel = $moneyModel->where('ctime', '>=', strtotime($startDate));
        }
        if (!empty($endDate)) {
            $moneyModel = $moneyModel->where('ctime', '<=', strtotime($endDate));
        }
        $data['drawingpass'] =$moneyModel->where('status',3)->sum('money');

        $this->assign("appid", $appid);
        $this->assign("start_date", $startDate);
        $this->assign("end_date", $endDate);
        $this->assign('data', $data);
        // ?????????????????? 0?????????????????????1???????????????2??????????????????[?????????]???3??????????????????[?????????]???????????????application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');

        return $this->fetch();
    }

    public function cha()
    {
        if ($this->request->isAjax()) {
            $where      = $data = [];
            $page       = $this->request->param('page/d', 1);
            $limit      = $this->request->param('limit/d', 15);
            $keyword    = $this->request->param('keyword/s');
            $cid        = $this->request->param('cid/d');
            if ($this->request->param('appid')){
                $appid1 = $this->request->param('appid');
                $where      = $data = ['appid' => $appid1];
            }
            if ($cid) {
                $where[] = ['cid', '=', $cid];
            }
            if ($keyword) {
                $where[] = ['title', 'like', '%'.$keyword.'%'];
            }

            $data['data']   = MoneyModel::with('hasCategory')->where($where)->page($page)->order('ctime desc')->limit($limit)->select();
            $data['count']  = MoneyModel::where($where)->count('id');
            $data['code']   = 0;
            return json($data);

        }
        $data = NewsModel::select(1);
        $appid = $this->request->param('appid');

        if ($appid){
            $where      = $data = ['appid' => $appid];
            $data = NewsModel::where($where)->select();
            $count = NewsModel::where($where)->count();
            if ($count == 0){
                return $this->error('appid?????????');
            }
        }
        // ?????????????????? 0?????????????????????1???????????????2??????????????????[?????????]???3??????????????????[?????????]???????????????application/example/view/layout.html
        $this->assign('hisiTabType', 0);
        $appid = $this->request->get('appid');
        $this->assign('hisiTabData', '');
        $this->assign('appid', $appid);
        $data = NewsModel::limit(1)->select();
        $this->assign('data', $data);
        return $this->fetch('cha');
    }
    public function form(){
        return $this->fetch('proxy');
    }

    public function check(Request $request)
    {
        $id = $request->get('id');
        $appid = MoneyModel::where('id', $id)->value('appid');
        $moneyInfo = MoneyModel::where('id', $id)->find();
        $newsInfo = NewsModel::where('appid', $appid)->find();

        $this->assign('moneyInfo', $moneyInfo);
        $this->assign('newsInfo', $newsInfo);

        return $this->fetch('money/check');
    }

    public function pass1Dialog(Request $request) {
        $id     = $request->get('id');
        $record = MoneyModel::where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('record', $record);

        return $this->fetch('money/pass1');
    }

    public function refuse1Dialog(Request $request)
    {
        $id = $request->get('id');
        $record = MoneyModel::where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('record', $record);

        return $this->fetch('money/refuse1');
    }

    public function pass2Dialog(Request $request) {
        $id     = $request->get('id');
        $record = MoneyModel::where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('record', $record);

        return $this->fetch('money/pass2');
    }

    public function refuse2Dialog(Request $request)
    {
        $id = $request->get('id');
        $record = MoneyModel::where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('record', $record);

        return $this->fetch('money/refuse2');
    }

    public function pass3Dialog(Request $request) {
        $id     = $request->get('id');
        $record = MoneyModel::where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('record', $record);

        return $this->fetch('money/pass3');
    }

    public function refuse3Dialog(Request $request)
    {
        $id = $request->get('id');
        $record = MoneyModel::where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('record', $record);

        return $this->fetch('money/refuse3');
    }

    public function payDoneDialog(Request $request) {
        $id     = $request->get('id');
        $record = MoneyModel::where('id', $id)->find();
        $this->assign('id', $id);
        $this->assign('record', $record);

        return $this->fetch('money/paydone');
    }

    /**????????????
     * @param Request $request
     * DateTime: 2021/4/15 15:45
     */
    public function pass1(Request $request)
    {
        $id = $request->get('id');
        if ($record = MoneyModel::where('id', $id)->find()) {
            if ($record['status'] != '0') {
                return $this->error('????????????????????????????????????????????????');
            }
        } else {
            return $this->error('?????????????????????????????????????????????');
        }
        $record->status = 1;
        $record->mtime = time();
        $record->first_auth_user = session('admin_user')['nick'];
        $record->first_auth_at = date('Y-m-d H:i:s');
        $record->save();

        return $this->success('????????????!');
    }

    /**????????????
     * @param Request $request
     * DateTime: 2021/4/15 15:45
     */
    public function refuse1(Request $request)
    {
        $id = $request->get('id');
        $authRemark = $request->param('auth_remark');
        if(empty($authRemark)) {
            return $this->error('?????????????????????');
        }
        if ($record = MoneyModel::where('id', $id)->find()) {
            if ($record['status'] != '0') {
                return $this->error('????????????????????????????????????????????????');
            }

            Db::startTrans();
            try {
                $record->status = 2;
                $record->auth_remark = $authRemark;
                $record->mtime = time();
                $record->first_auth_user = session('admin_user')['nick'];
                $record->first_auth_at = date('Y-m-d H:i:s');
                $record->save();

                $balance = FormsuserModel::where('appid', $record['appid'])->value('balance');
                FormsuserModel::where('appid', $record['appid'])->update(['balance' => $balance + $record['money']]);

                Db::commit();
                return $this->success('???????????????');
            } catch (Exception $e) {
                Db::rollback();
                return $this->success('??????????????????');
            }
        } else {
            return $this->error('?????????????????????????????????????????????');
        }
    }

    /**????????????
     * @param Request $request
     * DateTime: 2021/4/15 15:45
     */
    public function pass2(Request $request)
    {
        $id = $request->get('id');
        if ($record = MoneyModel::where('id', $id)->find()) {
            if ($record['status'] != '1') {
                return $this->error('????????????????????????????????????????????????');
            }
        } else {
            return $this->error('?????????????????????????????????????????????');
        }

        //??????<=5W??????????????????>5W????????????
        if ((int)$record->money > 50000) {
            $record->status = 8;
        } else {
            $record->status = 5;
        }
        $record->mtime = time();
        $record->save();

        return $this->success('????????????!');
    }

    /**????????????
     * @param Request $request
     * DateTime: 2021/4/15 15:45
     */
    public function refuse2(Request $request)
    {
        $id = $request->get('id');
        $authRemark = $request->param('auth_remark');
        if(empty($authRemark)) {
            return $this->error('?????????????????????');
        }
        if ($record = MoneyModel::where('id', $id)->find()) {
            if ($record['status'] != '1') {
                return $this->error('????????????????????????????????????????????????');
            }

            Db::startTrans();
            try {
                $record->status = 6;
                $record->auth_remark = $authRemark;
                $record->mtime = time();
                $record->save();

                $balance = FormsuserModel::where('appid', $record['appid'])->value('balance');
                FormsuserModel::where('appid', $record['appid'])->update(['balance' => $balance + $record['money']]);

                Db::commit();
                return $this->success('???????????????');
            } catch (Exception $e) {
                Db::rollback();
                return $this->success('??????????????????');
            }
        } else {
            return $this->error('?????????????????????????????????????????????');
        }
    }

    /**????????????
     * @param Request $request
     * DateTime: 2021/4/15 15:45
     */
    public function pass3(Request $request)
    {
        $id = $request->get('id');
        if ($record = MoneyModel::where('id', $id)->find()) {
            if ($record['status'] != '8') {
                return $this->error('????????????????????????????????????????????????');
            }
        } else {
            return $this->error('?????????????????????????????????????????????');
        }

        $record->status = 5;
        $record->mtime = time();
        $record->save();

        return $this->success('????????????!');
    }

    /**????????????
     * @param Request $request
     * DateTime: 2021/4/15 15:45
     */
    public function refuse3(Request $request)
    {
        $id = $request->get('id');
        $authRemark = $request->param('auth_remark');
        if(empty($authRemark)) {
            return $this->error('?????????????????????');
        }
        if ($record = MoneyModel::where('id', $id)->find()) {
            if ($record['status'] != '8') {
                return $this->error('????????????????????????????????????????????????');
            }

            Db::startTrans();
            try {
                $record->status = 9;
                $record->auth_remark = $authRemark;
                $record->mtime = time();
                $record->save();

                $balance = FormsuserModel::where('appid', $record['appid'])->value('balance');
                FormsuserModel::where('appid', $record['appid'])->update(['balance' => $balance + $record['money']]);

                Db::commit();
                return $this->success('???????????????');
            } catch (Exception $e) {
                Db::rollback();
                return $this->success('??????????????????');
            }
        } else {
            return $this->error('?????????????????????????????????????????????');
        }
    }

    /**
     * ??????????????????
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * DateTime: 2021/6/21 21:31
     */
    public function paydone(Request $request)
    {
        $id = $request->get('id');
        if ($record = MoneyModel::where('id', $id)->find()) {
            if ($record['status'] != '5') {
                return $this->error('???????????????????????????????????????????????????');
            }
        } else {
            return $this->error('?????????????????????????????????????????????');
        }

        $record->status = 3;
        $record->mtime = time();
        $record->save();

        return $this->success('????????????!');
    }

}
