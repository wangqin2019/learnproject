<?php
use think\Db;
use think\Loader;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * 将字符解析成数组
 * @param $str
 */
function parseParams($str)
{
    $arrParams = [];
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}


/**
 * 子孙树 用于菜单整理
 * @param $param
 * @param int $pid
 */
function subTree($param, $pid = 0)
{
    static $res = [];
    foreach($param as $key=>$vo){

        if( $pid == $vo['pid'] ){
            $res[] = $vo;
            subTree($param, $vo['id']);
        }
    }

    return $res;
}


/**
 * 记录日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writelog($uid,$username,$description,$status)
{

    $data['admin_id'] = $uid;
    $data['admin_name'] = $username;
    $data['description'] = $description;
    $data['status'] = $status;
    $data['ip'] = request()->ip();
    $data['add_time'] = time();
    $log = Db::name('Log')->insert($data);

}


/**
 * 整理菜单树方法
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
    $parent = []; //父类
    $child = [];  //子类
    $child_son = [];  //孙子类

    foreach($param as $key=>$vo){
        if($vo['pid'] == 0){
            $vo['href'] = '#';
            $vo['child'] = [];
            $parent[] = $vo;
        }else{
            if($vo['name']=='#'){
                $vo['href'] = '#';
                $vo['child_son'] = [];
                $child[] = $vo;
            }else{
                $vo['href'] = url($vo['name']);
                $child_son[] = $vo;
            }
        }
    }


    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){
            if($v['pid'] == $vo['id']){
                $parent[$key]['child'][] = $v;
            }
        }
    }


    foreach($parent as $key=>$vo){
        if(is_array($vo['child'])){
            foreach($vo['child'] as $k=>$v){
                foreach ($child_son as $kk=>$vv){
                    if($vv['pid'] == $v['id']){
                        $parent[$key]['child'][$k]['child_son'][] = $vv;
                    }
                }
            }
        }
    }

    unset($child);
    unset($child_son);
    return $parent;
}


/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }
    return $size . $delimiter . $units[$i];
}
//csv导出
function export_data($lists,$xlsCell,$title){
    foreach ( $lists as $i => $row ) {
        $str[$i]=array_iconv($row);
    }
    array_unshift($str,array_iconv($xlsCell));
    header ( "Content-type:application/vnd.ms-excel" );
    header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", $title.date('_YmdHis') ) . ".csv" );
    $fp = fopen('php://output', 'a');
    foreach ($str as $key=>$line)
    {
        fputcsv($fp,$line);
    }
    fclose($fp);
}

/**
 * UTF-8编码 GBK编码转换
 *
 * @param array $str   字符串，支持数组传递
 * @param string $in_charset 原字符串编码
 * @param string $out_charset 输出的字符串编码
 * @return array
 */
function array_iconv($str)
{
    foreach($str as $k => $v)
    {
        $str[$k] = iconv('utf-8','gbk//IGNORE',$v."\t");
    }
    return $str;
}


/**

 * excel表格导出
 * @param string $fileName 文件名称
 * @param array $headArr 表头名称
 * @param array $data 要导出的数据
 * @author static7  */
function excelExport($fileName = '', $headArr = [], $data = [], $widths=[]) {
    $fileName = iconv("UTF-8", "GB2312//IGNORE", @$fileName);
    $fileName .=".xlsx";
    Loader::import('PHPExcel.PHPExcel');
    Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->getProperties();
    $ordA = ord('A'); //65
    $key2 = ord("@"); //64
    foreach ($headArr as $v) {
        if($ordA > ord("Z"))
        {
            $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
        }else{
            $colum = chr($ordA++);
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
    }
    $column = 2;
    $objActSheet = $objPHPExcel->getActiveSheet();
    foreach ($data as $key => $rows) { // 行写入
        $ordA = ord('A');//重新从A开始
        $key2 = ord("@"); //64
        foreach ($rows as $keyName => $value) { // 列写入
            if($ordA > ord("Z"))
            {
                $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
            }else{
                $colum = chr($ordA++);
            }
            $objActSheet->setCellValue($colum . $column, $value);
        }
        $column++;
    }
    //表格宽度
    if(count($widths)){
        $ordA = ord('A');//重新从A开始
        $key2 = ord("@"); //64
        foreach ($widths as  $value) { // 列写入
            if($ordA > ord("Z"))
            {
                $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
            }else{
                $colum = chr($ordA++);
            }
            $objActSheet->getColumnDimension($colum)->setWidth($value);
        }
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
    header('Content-Disposition: attachment;filename="'.$fileName.'"');//告诉浏览器将输出文件的名称(文件下载)
    header('Cache-Control: max-age=0');//禁止缓存
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
//    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//    //$objWriter->save('./excelFile/'.$fileName);
//    header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
//    header('Content-Disposition: attachment;filename="'.$fileName.'"');//告诉浏览器将输出文件的名称(文件下载)
//    header('Cache-Control: max-age=0');//禁止缓存
//    $objWriter->save("php://output");
}

/**
 * Excel导出，TODO 可继续优化
 *
 * @param array  $datas      导出数据，格式['A1' => 'XXXX公司报表', 'B1' => '序号']
 * @param string $fileName   导出文件名称
 * @param array  $options    操作选项，例如：
 *                           bool   print       设置打印格式
 *                           string freezePane  锁定行数，例如表头为第一行，则锁定表头输入A2
 *                           array  setARGB     设置背景色，例如['A1', 'C1']
 *                           array  setWidth    设置宽度，例如['A' => 30, 'C' => 20]
 *                           bool   setBorder   设置单元格边框
 *                           array  mergeCells  设置合并单元格，例如['A1:J1' => 'A1:J1']
 *                           array  formula     设置公式，例如['F2' => '=IF(D2>0,E42/D2,0)']
 *                           array  format      设置格式，整列设置，例如['A' => 'General']
 *                           array  alignCenter 设置居中样式，例如['A1', 'A2']
 *                           array  bold        设置加粗样式，例如['A1', 'A2']
 *                           string savePath    保存路径，设置后则文件保存到服务器，不通过浏览器下载
 */
function exportExcel1($datas, $options = [], $fileName = '',$format = 'Xlsx' ){
    set_time_limit(0);
    debug('begin');
    /** @var Spreadsheet $objSpreadsheet */
    $spreadsheet  = new Spreadsheet();
    $cellNum = count($options);

    $fileName = iconv('utf-8', 'gb2312', $fileName);//文件名称
    $filename = $fileName.date('YmdHis');
    /* 设置默认文字居左，上下居中 */
    $styleArray = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
        ],
    ];
    $spreadsheet->getDefaultStyle()->applyFromArray($styleArray);
    /* 设置Excel Sheet */
    $spreadsheet->setActiveSheetIndex(0);
    $cellName = array(
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X',
        'Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR',
        'AS','AT','AU','AV','AW','AX','AY','AZ'
    );

    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1',$fileName);
    //合并单元格
    $spreadsheet->getActiveSheet()->mergeCells('A1:'.$cellName[$cellNum-1].'1');
    //设置标题
    $spreadsheet->getActiveSheet()->setTitle('sheet');
    //设置行高
    $spreadsheet->getActiveSheet()->getRowDimension('A1')->setRowHeight(30);
    //设置excel第一行数据
    foreach ($options as $key=>$val){
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($cellName[$key].'2', $val['name']);
        //设置列宽
        $spreadsheet->getActiveSheet()->getColumnDimension($cellName[$key])->setWidth($val['width']?:15);//setAutoSize(true);
        //设置默认列宽为12 $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);

        //设置字体
        $spreadsheet->getActiveSheet()->getStyle($cellName[$key].'2')->getFont()->setBold(true);

        //设置行高
        if(!empty($val['height'])){
            $spreadsheet->getActiveSheet()->getRowDimension($cellName[$key].'2')->setRowHeight($val['height']);
            //设置默认行高 $spreadsheet->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        }
    }
    $yieldData = yieldData($datas);
    $i = 0;
    foreach ($yieldData as $val){
        for($j=0;$j<$cellNum;$j++){
            $spreadsheet->setActiveSheetIndex(0)->setCellValue($cellName[$j].($i+3),' '.$val[$options[$j]['column']].' ');
        }
        $i++;
    }

    debug('end');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'.($i+4),' '.debug('begin','end',8).'s ');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'.($i+4),' '.debug('begin','end','m').' ');



    header('pragma:public');
    if($format == 'Xlsx'){
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }elseif($format == 'Xls'){
        header('Content-Type: application/vnd.ms-excel');
    }

    $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $objWriter->setPreCalculateFormulas(false);
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment;filename=". $filename . '.' . strtolower($format));
    header('Cache-Control: max-age=0');//禁止缓存
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header("Content-Transfer-Encoding:binary");
    header("Expires: 0");
    ob_clean();
    ob_start();
    //$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, $format);
    $objWriter->save('php://output');
    //$a =  \Think\Env::get('app_path').'/data/'.$filename . '.' . strtolower($format);
    //$objWriter->save($a);
    /* 释放内存 */
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    ob_end_flush();

    return true;
}
/**
 * Commit: excel导出
 * Function: exportExcel
 * @param $expTableData 数据集
 * @param $expCellName 表头数据及对应需要的字段及宽度
 * @param $expTitle 导出文件名称
 * User: stars<1014916675@qq.com>
 * DateTime: 2019-10-10 09:36:39
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 * @throws PHPExcel_Writer_Exception
 */
function exportExcel($expTableData,$expCellName,$expTitle,$exp = 'Excel2007'){
//    debug('begin');
    Loader::import('PHPExcel.PHPExcel');
    Loader::import('PHPExcel.PHPExcel.IOFactory');
    $exp = ucfirst($exp);
    if($exp = 'Excel2007'){
        Loader::import('PHPExcel.PHPExcel.Writer.Excel2007');
    }else{
        Loader::import('PHPExcel.PHPExcel.Writer.Excel5');
    }

    Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
    $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    /* //超时处理 将单元格数据序列化后保存在内存中
     //$cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
     //超时处理 将单元格序列化后再进行Gzip压缩，然后保存在内存中
     //$cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
     //if(!PHPExcel_Settings::setCacheStorageMethod($cacheMethod)){}
     //\PHPExcel_Settings::setCacheStorageMethod($cacheMethod);


     // 设置缓存方式，减少对内存的占用 保存在php://temp
     $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
    $cacheSettings = array( 'memoryCacheSize' => '512MB','cacheTime' => 300 );
    PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);
    */

    $objPHPExcel = new \PHPExcel();
    $cellName = array(
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X',
        'Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR',
        'AS','AT','AU','AV','AW','AX','AY','AZ'
    );
    //设置文档标题
    //$objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1')->setCellValue('A1', $expTitle);
    //字体大小
    //$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
    //单个单元格居中
    $objPHPExcel->getActiveSheet(0)->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    // 设置垂直居中
    $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    //行高
    $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
    //工作表标签 默认显示三个 Sheet1  Sheet2 Sheet3
    //$objPHPExcel->getActiveSheet()->setTitle('图文素材');

    //设置excel第一行数据
    foreach ($expCellName as $key=>$val){
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$key].'1', $val['name']);
        //设置所有格居中显示
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置垂直居中
        $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //设置单元格自动宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$key])->setWidth($val['width']?:15);

        //$objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$key])->setAutoSize(true);
        //第二行加粗 true false
        $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getFont()->setBold(true);
    }

    $yieldData = yieldData($expTableData);
    $i = 0;
    foreach ($yieldData as $val){
        for($j=0;$j<$cellNum;$j++){
            $column = strip_tags($val[$expCellName[$j]['column']]);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$j].($i+2),' '. $column .' ');
        }
        $i++;
        unset($val);
        unset($yieldData);
    }
    unset($expTableData);
    debug('end');

//    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($dataNum+2),' '.debug('begin','end',8).'s ');
//    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($dataNum+2),' '.debug('begin','end','m').' ');

    ob_end_clean();//清除缓冲区,避免乱码
    header('Content-Description: File Transfer');
    header('pragma:public');
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    if($exp = 'Excel2007'){
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//excel2007
        header('Content-Disposition:attachment;filename='.$fileName.'.xlsx');//attachment新窗口打印inline本窗口打印
    }else{
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');//excel5
        header('Content-Disposition:attachment;filename='.$fileName.'.xls');//attachment新窗口打印inline本窗口打印
    }
    header("Content-Transfer-Encoding:binary");
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    // header('Pragma: no-cache');
    header("Expires: 0");
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, $exp);
    $objWriter->save('php://output');
    exit;
}
function exportCsv($lists,$expCellName,$title){
    header ( "Content-type:application/vnd.ms-excel" );
    header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", $title.date('_YmdHis') ) . ".csv" );

    //打开PHP文件句柄,php://output 表示直接输出到浏览器
    $fp = fopen('php://output', 'a');

    //输出Excel列名信息
    $headlist = [];
    foreach ($expCellName as $key => $value) {
        //CSV的Excel支持GBK编码，一定要转换，否则乱码
        $headlist[$key] = iconv('utf-8', 'gbk//IGNORE', $value['name']."\t");
    }
    //将数据通过fputcsv写到文件句柄
    fputcsv($fp, $headlist);
    $yieldData = yieldData($lists);

    foreach ($yieldData as $k=>$v) {
        $row = [];
        for ($i = 0; $i < count($expCellName); $i++){
            $row[$i] = strip_tags( iconv('utf-8','gbk//IGNORE',$v[$expCellName[$i]['column']]."\t"));
        }
        fputcsv($fp,$row);
        unset($row);
        unset($yieldData);
    }
}
function yieldData($data){
    foreach ($data as $val){
        yield $val;
    }
}



/**
 * @param int $user_defined //客户自定义开头
 * @param int $no_of_codes//定义一个int类型的参数 用来确定生成多少个优惠码
 * @param array $exclude_codes_array//定义一个exclude_codes_array类型的数组
 * @param int $code_length //定义一个code_length的参数来确定优惠码的长度
 * @return array//返回数组
 */
function generate_promotion_code($user_defined,$no_of_codes,$exclude_codes_array='',$code_length = 4)
{
    $characters = "0123456789";

    $promotion_codes = array();//这个数组用来接收生成的优惠码
    for($j = 0 ; $j < $no_of_codes; $j++)
    {
        $code = "";
        $code .= $user_defined;
        for ($i = 0; $i < $code_length; $i++)
        {
            $code .= $characters[mt_rand(0, strlen($characters)-1)];
        }
        //如果生成的4位随机数不再我们定义的$promotion_codes函数里面
        if(!in_array($code,$promotion_codes))
        {
            if(is_array($exclude_codes_array))//
            {
                if(!in_array($code,$exclude_codes_array))//排除已经使用的优惠码
                {
                    $promotion_codes[$j] = $code;//将生成的新优惠码赋值给promotion_codes数组
                }
                else
                {
                    $j--;
                }
            }
            else
            {
                $promotion_codes[$j] = $code;//将优惠码赋值给数组
            }
        }
        else
        {
            $j--;
        }
    }
    return $promotion_codes;
}


/**
 * php正则提取文本中多个11位国内手机号完整实例
 *
 * @author yujianyue <admin@ewuyi.net>
 * @copyright www.echafen.com
 * @version 2.5 2017-08-28
 */

function chafenbacom($str){
    preg_match('/1[34578][0-9]{8,10}/', $str, $match);
    return $match[0];//得结果，可输出查看或调用
}


/**
 * 多个数组的笛卡尔积
 *
 * @param unknown_type $data
 */
function combineDika() {
    $data = func_get_args();
    $data = current($data);
    $cnt = count($data);
    $result = array();
    $arr1 = array_shift($data);
    foreach($arr1 as $key=>$item)
    {
        $result[] = array($item);
    }

    foreach($data as $key=>$item)
    {
        $result = combineArray($result,$item);
    }
    return $result;
}


/**
 * 两个数组的笛卡尔积
 * @param unknown_type $arr1
 * @param unknown_type $arr2
 */
function combineArray($arr1,$arr2) {
    $result = array();
    foreach ($arr1 as $item1)
    {
        foreach ($arr2 as $item2)
        {
            $temp = $item1;
            $temp[] = $item2;
            $result[] = $temp;
        }
    }
    return $result;
}
