<?php

namespace app\example\admin;

use app\system\admin\Admin;
use app\example\model\ExampleCategory as CategoryModel;
use app\system\model\SystemMenu as MenuModel;
use app\system\model\SystemRole as RoleModel;
use think\Db;

class CpaReport extends Admin
{
    protected $hisiModel = 'ExampleForms';
    protected $hisiValidate = 'ExampleForms';

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
        $request   = $this->request;
        $appid     = ($request->param("appid"));
        $uid       = ($request->param("uid"));
        $startDate = $request->param("start_date");
        $endDate   = $request->param("end_date");
        $type      = $request->param("type");
        $status    = $request->param("status");
        $os        = intval($request->param("os"));
        if($request->isPost()) {
            $page = $request->post("page");
            $limit = $request->post("limit");
            $listQuery = Db::table("hisi_example_install_log eil");
                //->join("hisi_example_news en", "eil.uid = en.appid");
            $countQuery = clone $listQuery;
            if($appid) {
                $listQuery->where("eil.uid", "=", $appid);
                $countQuery->where("eil.uid", "=", $appid);
            }
            if($uid) {
                $listQuery->where("eil.invite_uid", "=", $uid);
                $countQuery->where("eil.invite_uid", "=", $uid);
            }
            if($startDate != "") {
                $listQuery->where("eil.invite_time", ">=", urldecode($startDate));
                $countQuery->where("eil.invite_time", ">=", urldecode($startDate));
            }
            if($endDate != "") {
                $listQuery->where("eil.invite_time", "<=", urldecode($endDate));
                $countQuery->where("eil.invite_time", "<=", urldecode($endDate));
            }
            if($type == 2) {
                $listQuery->whereNull("eil.invite_user_phonenumber");
                $countQuery->whereNull("eil.invite_user_phonenumber");
            }
            if($type == 1) {
                $listQuery->whereNotNull("eil.invite_user_phonenumber");
                $countQuery->whereNotNull("eil.invite_user_phonenumber");
            }
            if($status != "") {
                $listQuery->where("eil.status", "=", $status);
                $countQuery->where("eil.status", "=", $status);
            }
            if($os > 0) {
                $listQuery->where("eil.invite_user_os", "=", $os);
                $countQuery->where("eil.invite_user_os", "=", $os);
            }
            $list = $listQuery
                ->page($page)
                ->limit($limit)
                ->order("eil.id", "desc")
                ->column("eil.*");
            $data['data'] = $list;
            $data['count'] = $countQuery->count("eil.id");
            $data['code'] = 0;
            return json($data);
        }

        //无效权限
        $authMenu = MenuModel::where('url', 'install-deduction')->find();
        $installDeductionAuth = RoleModel::checkAuth($authMenu['id']);

        $this->assign("appid", $appid);
        $this->assign("uid", $uid);
        $this->assign("start_date", $startDate);
        $this->assign("end_date", $endDate);
        $this->assign("type", $type);
        $this->assign("os", $os);
        $this->assign("status", $status);
        $this->assign("installDeductionAuth", $installDeductionAuth);
        $this->assign('hisiTabType', 0);
        $this->assign('hisiTabData', '');
        return $this->fetch();
    }

    public function download()
    {
        require_once(__DIR__ . "/../../common/libs/PHPExcel/Classes/PHPExcel.php");
        require_once(__DIR__ . "/../../common/libs/PHPExcel/Classes/PHPExcel/Writer/Excel5.php");
        $objPHPExcel = new \PHPExcel();
// Set document properties
        $objPHPExcel->getProperties()->setCreator("Govinda")
            ->setLastModifiedBy("Govinda")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '序号')
            ->setCellValue('B1', '代理ID')
            ->setCellValue('C1', '用户ID')
            ->setCellValue('D1', '用户名称')
            ->setCellValue('E1', '手机号码')
            ->setCellValue('F1', '设备类型')
            ->setCellValue('G1', '注册时间');

        $appid = $_GET["appid"];
        $uid = $_GET["uid"];
        $startDate = $_GET["start_date"];
        $endDate = $_GET["end_date"];
        $type = $_GET["type"];
        $os = intval($_GET["os"]);
        $page = 1;
        $limit = 10000;
        $listQuery = Db::table("hisi_example_install_log eil")
            ->join("hisi_example_news en", "eil.uid = en.appid");
        if ($appid) {
            $listQuery->where("eil.uid", "=", $appid);
        }
        if ($uid) {
            $listQuery->where("eil.invite_uid", "=", $uid);
        }
        if ($startDate) {
            $listQuery->where("eil.invite_time", ">=", urldecode($startDate));
        }
        if ($endDate) {
            $listQuery->where("eil.invite_time", "<=", urldecode($endDate));
        }
        if ($type == 2) {
            $listQuery->whereNull("eil.invite_user_phonenumber");
        }
        if ($type == 1) {
            $listQuery->whereNotNull("eil.invite_user_phonenumber");
        }
        if ($os > 0) {
            $listQuery->where("eil.invite_user_os", "=", $os);
        }
        $list = $listQuery
            ->page($page)
            ->limit($limit)
            ->order("eil.id", "desc")
            ->column("eil.*");
        $line = 2;
        foreach ($list as $l){
            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $line,$l['id']);
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $line,$l['uid']);
            $objPHPExcel->getActiveSheet()->SetCellValue('C' . $line,$l['invite_uid']);
            $objPHPExcel->getActiveSheet()->SetCellValue('D' . $line,$l['invite_nickname']);
            $objPHPExcel->getActiveSheet()->SetCellValue('E' . $line,$l['invite_user_phonenumber']);
            $objPHPExcel->getActiveSheet()->SetCellValue('F' . $line,$l['invite_user_os']);
            $objPHPExcel->getActiveSheet()->SetCellValue('G' . $line,$l['invite_time']);
            $line++;
        }

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('代理报表');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment;filename="userList.xls"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');
    }
}
