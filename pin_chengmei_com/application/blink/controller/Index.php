<?php
/**
 * Created by PhpStorm.
 * User: houdj
 * Date: 2018/9/5
 * Time: 14:24
 */

namespace app\blink\controller;

use app\blink\model\MemberModel;
use app\blink\model\PintuanModel;
use app\blink\model\GoodsModel;
use app\blink\model\BlinkOrderBoxModel;
use app\blink\model\BlinkBoxCouponUserModel;
use think\Cache;
use think\Controller;
use think\Db;
use think\Exception;
use think\Loader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


class Index extends Controller
{

	public function _initialize() {
        parent::_initialize();
        $token = input('param.token','');

        if($token == ''){
            return true;
            $code = 400;
            $data = '';
            $msg = '非法请求';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }else{
            if(!parent::checkToken($token)) {
                $code = 400;
                $data = '';
                $msg = '用户登陆信息过期，请重新登录！';
                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
                exit;
            }else{
                return true;
            }
        }
    }
    public function index(){
    	set_time_limit(0);
        ini_set("memory_limit", "2024M");
        debug('begin');
    	$dep = ['000-000', '666-666', '888-888', '998-998', '999-999'];
    	$total = Db::name('blink_box_coupon_user')
	    	->alias('cu')
	    	->join(['pt_goods'=>'g'],'g.id=cu.goods_id','left')
	    	->join(['ims_bj_shopn_member'=>'m'],'m.id = cu.uid','left')
	    	->join(['ims_bwk_branch'=>'bwk'],'m.storeid = bwk.id','left')
	    	->join(['sys_departbeauty_relation'=>'departbeauty'],'bwk.id = departbeauty.id_beauty 
			AND bwk.sign = departbeauty.id_sign','left')
	    	->join(['sys_department'=>'depart'],'departbeauty.id_department = depart.id_department ','left')
	    	->where('cu.type','=',0)
	    	->where('cu.status','=',0)
	    	->where('cu.share_status','=',0)
	    	->where('departbeauty.id_sign','in',$dep)
	    	->count();
		$limit = 100;
        $page = ceil($total / $limit);
        $filename = "盲盒活动总部四门店发货列表";

		$header = array(
            array('column' => 'st_department', 'name' => '办事处', 'width' => 15),
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sellername', 'name' => '美容师名称', 'width' => 15),
            array('column' => 'sellermobile', 'name' => '美容师电话', 'width' => 15),

            array('column' => 'origin_department', 'name' => '发货办事处', 'width' => 15),
            array('column' => 'origin_title', 'name' => '发货门店名称', 'width' => 30),
            array('column' => 'origin_name', 'name' => '发货美容师名称', 'width' => 15),
            array('column' => 'origin_mobile', 'name' => '发货美容师电话', 'width' => 15),

            array('column' => 'realname', 'name' => '顾客姓名', 'width' => 15),
            array('column' => 'mobile', 'name' => '顾客电话', 'width' => 15),
            array('column' => 'id', 'name' => '卡券id', 'width' => 15),
            array('column' => 'ticket_code', 'name' => '卡券编号', 'width' => 30),
            array('column' => 'goods_name', 'name' => '商品', 'width' => 30),
            array('column' => 'insert_time', 'name' => '创建时间', 'width' => 20),
            array('column' => 'status', 'name' => '核销状态', 'width' => 15),
            array('column' => 'is_deliver', 'name' => '发货状态', 'width' => 15),
            array('column' => 'share_status', 'name' => '分享状态', 'width' => 15),
            array('column' => 'source', 'name' => '来源', 'width' => 15),
        );


        Loader::import('PHPExcel.PHPExcel');
        Loader::import('PHPExcel.PHPExcel.IOFactory');
        Loader::import('PHPExcel.PHPExcel.Writer.Excel2007');
        Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
        $xlsTitle = iconv('utf-8', 'gb2312', $filename);//文件名称
        $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($header);

        $objPHPExcel = new \PHPExcel();
        $cellName = array(
            'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X',
            'Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR',
            'AS','AT','AU','AV','AW','AX','AY','AZ'
        );
        //单个单元格居中
        $objPHPExcel->getActiveSheet(0)->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //行高
        $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
        //设置excel第一行数据
        foreach ($header as $key=>$val){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$key].'1', $val['name']);
            //设置所有格居中显示
            $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            // 设置垂直居中
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            //设置单元格自动宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$key])->setWidth($val['width']?:15);
            //第二行加粗 true false
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getFont()->setBold(true);
        }

        $a = 0;
        for ($i=0;$i<$page;$i++){
            $lists = $this->getList($dep,$i,$limit);
            if(!empty($lists)){
                foreach ($lists as $k=>$val){
                    for($j=0;$j<$cellNum;$j++){
                        $column = strip_tags($val[$header[$j]['column']]);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue(
                            $cellName[$j].($a+2),
                            $column ."\t"
                        );
                    }
                    $a++;
                }
                unset($lists);
            }
        }
        debug('end');

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($a+4),' '.debug('begin','end',8).'s ');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($a+4),' '.debug('begin','end','m').' ');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($a+4),$total);

        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Description: File Transfer');
        header('pragma:public');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//excel2007
        header('Content-Disposition:attachment;filename='.$fileName.'.xlsx');//attachment新窗口打印inline本窗口打印
        header("Content-Transfer-Encoding:binary");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        // header('Pragma: no-cache');
        header("Expires: 0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    //数据
    public function getList($dep,$page = 1,$limit = 1000){
    	$field = "cu.id,cu.blinkno,cu.ticket_code,cu.type,cu.uid,";
    	$field .= "(CASE WHEN cu.status = 0 THEN '未核销' WHEN cu.status = 1 THEN '已核销' ELSE '核销中' END) status,";
    	$field .= "(CASE WHEN cu.share_status = 0 THEN '未分享' WHEN cu.share_status = 1 THEN '已分享' ELSE '分享中' END) share_status,";
    	$field .= "(CASE WHEN cu.is_deliver = 0 THEN '未发货' WHEN cu.is_deliver = 1 THEN '已发货' ELSE '发货中' END) is_deliver,";
	    $field .= "(CASE WHEN cu.source = 0 THEN '拆盲盒' WHEN cu.source = 1 THEN '好友赠送' WHEN cu.source = 2 THEN '好友助力' ELSE '合成卡片' END) source,";
	    $field .= "CONCAT( g.NAME, '-', cu.goods_id ) goods_name,FROM_UNIXTIME( cu.insert_time ) insert_time,";
	    $field .= "m.mobile,m.realname,m.storeid,m.staffid,m.originfid,CONCAT( bwk.title, '- ', bwk.sign ) title,";


        $field .= "member.mobile as sellermobile,member.realname as sellername,member.storeid as sellerstoreid,";
	    $field .= "depart.st_department ";
	    $list = Db::name('blink_box_coupon_user')
	    	->alias('cu')
	    	->field($field)
	    	->join(['pt_goods'=>'g'],'g.id=cu.goods_id','left')
	    	->join(['ims_bj_shopn_member'=>'m'],'m.id = cu.uid','left')//用户信息
            ->join(['ims_bj_shopn_member' => 'member'],'m.staffid=member.id','left')//美容师信息
	    	->join(['ims_bwk_branch'=>'bwk'],'m.storeid = bwk.id','left')//用户门店
	    	->join(['sys_departbeauty_relation'=>'departbeauty'],'bwk.id = departbeauty.id_beauty 
			AND bwk.sign = departbeauty.id_sign','left')
	    	->join(['sys_department'=>'depart'],'departbeauty.id_department = depart.id_department ','left')
	    	->where('cu.type','=',0)
	    	//->where('cu.status','=',0)
	    	//->where('cu.share_status','=',0)
	    	->where('departbeauty.id_sign','in',$dep)
	    	->page($page,$limit)
	    	->select();
		if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                //查询所属美容师
                if($storeid == 1792){
                    //查询当前用户引领人的原始美容师 及门店 originfid
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('mm')
                        ->join(['ims_bwk_branch' => 'bwk'],'mm.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('mm.id',$val['originfid'])
                        ->field('mm.id,mm.storeid,mm.pid,mm.code,mm.realname,mm.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->find();

                    $list[$k]['origin_fid']     = $info['id'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $info['realname'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $info['mobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_storeid'] = $info['storeid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_title']   = $info['title'].' - '.$info['sign'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_department'] = $info['st_department'];//原始（发货）美容师手所属办事处
                }else{
                    $list[$k]['origin_fid']     = $val['staffid'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $val['sellername'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $val['sellermobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_title']   = $val['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_storeid'] = $val['sellerstoreid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $val['st_department'];//原始（发货）美容师手所属办
                }
            }
        }
	    	return $list;
    }
    public function getList1($dep,$page = 1,$limit = 1000){
    	$field = "cu.id,cu.blinkno,cu.ticket_code,cu.type,";
	    $field .= "(CASE WHEN cu.source = 0 THEN '拆盲盒' WHEN cu.source = 1 THEN '好友赠送' WHEN cu.source = 2 THEN '好友助力' ELSE '合成卡片' END) source,";
	    $field .= "CONCAT( g.NAME, '-', cu.goods_id ) NAME,FROM_UNIXTIME( cu.insert_time ) insert_time,";
	    $field .= "cu.uid,m.mobile,m.realname,m.storeid,m.staffid,m.originfid,CONCAT( bwk.title, '- ', bwk.sign ) title,";
	    $field .= "( CASE WHEN m.storeid = 1792 THEN ( SELECT storeid FROM ims_bj_shopn_member WHERE id = m.originfid ) ELSE m.storeid END ) sid,";
	    $field .= "(
		CASE
			
			WHEN m.storeid = 1792 THEN
			(
			SELECT
				CONCAT( title, '- ', sign ) 
			FROM
				ims_bwk_branch 
			WHERE
				id = ( SELECT storeid FROM ims_bj_shopn_member WHERE id = m.originfid ) 
			) ELSE CONCAT( bwk.title, '- ', bwk.sign ) 
		END  ) title1,";
	    $field .= "depart.st_department ";
	    $list = Db::name('blink_box_coupon_user')
	    	->alias('cu')
	    	->field($field)
	    	->join(['pt_goods'=>'g'],'g.id=cu.goods_id','left')
	    	->join(['ims_bj_shopn_member'=>'m'],'m.id = cu.uid','left')
	    	->join(['ims_bwk_branch'=>'bwk'],'m.storeid = bwk.id','left')
	    	->join(['sys_departbeauty_relation'=>'departbeauty'],'bwk.id = departbeauty.id_beauty 
			AND bwk.sign = departbeauty.id_sign','left')
	    	->join(['sys_department'=>'depart'],'departbeauty.id_department = depart.id_department ','left')
	    	->where('cu.type','=',0)
	    	->where('cu.status','=',0)
	    	->where('cu.share_status','=',0)
	    	->where('departbeauty.id_sign','in',$dep)
	    	->order('sid','asc')
	    	->select();

	    	return $list;
    }
}