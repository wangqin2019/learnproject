<?php
use think\Db;
use think\Loader;

function excel_down($table_name,$field){
    Loader::import('PHPExcel.PHPExcel');
    Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
    ini_set('max_execution_time', '0');

    $excel = new PHPExcel();
    $map=["status"=>1];
    $excel->createSheet("装修项目",$table_name,$field,$map)->downloadExcel();
    die;
}


/**
 * 节省内存,读取1行,删除1行
 * @param $data
 * @return Generator
 */
function yieldData1($data){
    foreach ($data as $datum){
        yield $datum;
    }
}
/**
 * 追加写入excel文件
 * @param array $data 追加数据写入excel
 * @param string $name 文件路径全名称
 * @param int $ls 原excel有的总列数
 */
function excel_add($data='',$name,$ls=''){
    Loader::import('PHPExcel.PHPExcel');
    Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
    ini_set('max_execution_time', '0');

    $name = iconv("UTF-8", "GB2312//IGNORE", @$name);
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);

    $cols = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    $objReader = PHPExcel_IOFactory::createReader('Excel5');
    //读取excel内容
    $objPHPExcel = $objReader->load($name); //$filename可以是上传的文件，或者是指定的文件
    $sheet = $objPHPExcel->getSheet(0);

    $highestRow = $sheet->getHighestRow(); // 取得总行数
    $highestColumn = $sheet->getHighestColumn(); // 取得总列数
    $highestColumnNum = PHPExcel_Cell::columnIndexFromString($highestColumn);//列数转换num
    if($ls)
    {
        $highestColumnNum=$ls;
    }
    $currentSheet = $objPHPExcel->getActiveSheet(); // 获取当前活动sheet
//    var_dump($data);
    $data = yieldData1($data);

    //    echo 'records<pre>';print_r($records);die;
//    foreach ($records as $key => $record ){
//        var_dump($record);die;
//        foreach ($ls as $k => $value){
//            $data[$key][$k] = $record->$k;
//        }
//    }
    if($data && is_object($data)) {
        $j = $highestRow+1;
        foreach ($data as $key => $val) {
            // 重置关联数组为索引数组
            $val = array_values($val);
//            $val = yieldData1($val);
            for ($i=0; $i < $highestColumnNum; $i++) {
                $currentSheet->setCellValue($cols[$i].$j,$val[$i]);
            }
            $j++;
        }
    }
    // 下面两行代码是写入Excel文件
    $sheeetWrite = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    // unlink($name);
    $sheeetWrite->save($name);

}
/**
 * 生成excel文件
 * @param array $header 头文件数组
 * @param string $filename 文件完整路径-带后缀
 */
function excel_header($header,$filename='simple.xls')
{
    Loader::import('PHPExcel.PHPExcel');
    Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
    ini_set('max_execution_time', '0');
    $phpexcel = new PHPExcel();
//    $phpexcel->getProperties()
//        ->setCreator("Maarten Balliauw")
//        ->setLastModifiedBy("Maarten Balliauw")
//        ->setTitle("Office 2007 XLSX Test Document")
//        ->setSubject("Office 2007 XLSX Test Document")
//        ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
//        ->setKeywords("office 2007 openxml php")
//        ->setCategory("Test result file");
    $phpexcel->getActiveSheet()->fromArray($header);
    $phpexcel->getActiveSheet()->setTitle('Sheet1');
    $phpexcel->setActiveSheetIndex(0);
    $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
    $objwriter->save($filename);
    return $filename;
    exit;
}
/**
 * 导出数据文件到csv
 * @param array $header_arr 头文件数据
 * @param array $data 数据
 * @param string $file_name 文件名称
 */
function export_csv($header_arr,$data,$file_name)
{
    ini_set('max_execution_time', 300);// 设置PHP超时时间
    ini_set('memory_limit', '2048M');// 设置PHP临时允许内存大小

    $index = 0;
    $fp = fopen($file_name, 'w'); //生成临时文件
//    chmod($filePath, 0777);//修改可执行权限
    // 将数据通过fputcsv写到文件句柄
    fputcsv($fp, $header_arr);
    //处理导出数据
    foreach ($data as $key => &$val) {
        foreach ($val as $k => $v) {
            $val[$k] = $v . "\t";
            if ($index == 10000) { //每次写入1000条数据清除内存
                $index = 0;
                ob_flush();//清除内存
                flush();
            }
            $index++;
        }
        fputcsv($fp, $val);
    }
    ob_flush();
    fclose($fp);  //关闭句柄
    header("Cache-Control: max-age=0");
    header("Content-type:application/vnd.ms-excel;charset=UTF-8");
    header("Content-Description: File Transfer");
    header('Content-disposition: attachment; filename='.$file_name);
    header("Content-Type: text/csv");
    header("Content-Transfer-Encoding: binary");
//    header('Content-Length: ' . filesize($file_name));
    @readfile($file_name);//输出文件;
//    unlink($file_name); //删除压缩包临时文件
//    echo $file_name;
    return;
}
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
    /*增加一级子孙类*/
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
                $vo['child_son'] = '';
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

/**
 * curl请求接口
 * @param  string $url      请求地址
 * @param  string $data     请求参数
 * @return string           请求返回的结果
 */
/**
 * 发送HTTP请求方法
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function http($url, $params='', $method = 'GET', $header = array(), $multi = false){
    $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header
    );

    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $rest = curlPost($url,$params);
            return $rest;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if($error) throw new Exception('请求发生错误：' . $error);
    return  $data;
}

//curl post提交方法
function curlPost($url,$data='',$authori='')
{
    $ch = curl_init();
    $headers = array();
    $headers[] = 'Authorization:Bearer '.$authori;
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //添加头文件
    if($authori)
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $output = curl_exec($ch);
    $curlInfo = curl_getinfo($ch);
    //释放curl句柄
    curl_close($ch);
    return $output;
    // $res=json_decode($output,true);
    // print_r($output);
}

// start Modify by wangqin 2017-12-05
//导出报表
function reportCsv ($csv_header,$csv_body,$csv_name)
{
    // $csv_name = dirname(__FILE__).$csv_name;
    if(!strstr($csv_name,'2018'))
    {
        $csv_name = $csv_name.date('Y-m-d').'.csv';
    }else{
        $csv_name = $csv_name.'.csv';
    }
    $file_n = $csv_name;
//        $file_n = explode('/',$file_n);
    // CSV名称转中文试下
    @$csv_name = iconv("UTF-8", "GB2312//IGNORE", @$csv_name);
    //本地
     $csv_path = 'D:\software\phpstudy\PHPTutorial\WWW\SVN\qunarmei-live\public\csv\\';
    //服务器
//     $csv_path = '/home/canmay/www/live/public/csv/';

    if(is_file($csv_path.$csv_name))
    {
        unlink($csv_path.$csv_name);
    }

    $fp = fopen($csv_path.$csv_name,'a');
    // 处理头部标题
    foreach ($csv_header as $kc => $vc) {
        $csv_header[$kc] = iconv('utf-8','gb2312//IGNORE',$vc);
    }
    $header = implode(',', $csv_header) ."\n";
    fwrite($fp, $header);
    // 处理内容
    $content = '';
    foreach ($csv_body as $k => $v) {
        foreach ($v as $k1 => $v1){
            $v[$k1] = iconv('utf-8','gb2312//IGNORE',$v[$k1]);
        }
        $content .= implode(",",$v)."\n"; //用英文逗号分开

        $k++;
        if($k%100 == 0){
            // 写入并关闭资源
            fwrite($fp, $content);
            unset($content);
            $content = '';
        }
    }
    // 不足1000未写入的内容
    if($content){
        fwrite($fp, $content);
        unset($content);
    }
    // 关闭并释放资源
    fclose($fp);
    return $file_n;
}
// end Modify by wangqin 2017-12-05

//start Modify by wangqin 2017-12-06  php实现数字格式化，数字每2位加逗号的功能函数
function num_format($num){
    if(!is_numeric($num)){
        return false;
    }
    $num = explode('.',$num);//把整数和小数分开
    $rl = @$num[1];//小数部分的值
    $j = strlen($num[0]) % 2;//整数有多少位
    $sl = substr($num[0], 0, $j);//前面不满三位的数取出来
    $sr = substr($num[0], $j);//后面的满三位的数取出来
    $i = 0;$rvalue='';
    while($i <= strlen($sr)){
        $rvalue = $rvalue.':'.substr($sr, $i, 2);//三位三位取出再合并，按逗号隔开
        $i = $i + 2;
    }
    $rvalue = $sl.$rvalue;
    $rvalue = substr($rvalue,0,strlen($rvalue)-1);//去掉最后一个逗号
    $rvalue = explode(':',$rvalue);//分解成数组
    if($rvalue[0]==0){
        array_shift($rvalue);//如果第一个元素为0，删除第一个元素
    }
    $rv = $rvalue[0];//前面不满三位的数
    for($i = 1; $i < count($rvalue); $i++){
        $rv = $rv.':'.$rvalue[$i];
    }
    if(!empty($rl)){
        $rvalue = $rv.'.'.$rl;//小数不为空，整数和小数合并
    }else{
        $rvalue = $rv;//小数为空，只有整数
    }
    return $rvalue;
}
//end Modify by wangqin 2017-12-06
//start Modify by wangqin 2018-01-18
/**
 * 获取远程图片的宽高和体积大小
 *
 * @param string $url 远程图片的链接
 * @return false|array
 */
function getImageinfo($url)
{
    $result = '';
    $imageInfo = getimagesize($url);
    if ($imageInfo) {
        $result['width'] = $imageInfo[0];
        $result['height'] = $imageInfo[1];
    }
    return $result;
}
//end Modify by wangqin 2018-01-18


/**

 * excel表格导出
 * @param string $fileName 文件名称
 * @param array $headArr 表头名称
 * @param array $data 要导出的数据
 * @author static7  */

function excelExport($fileName = '', $headArr = [], $data = [], $widths=[]) {
    $fileName = iconv("UTF-8", "GB2312//IGNORE", @$fileName);
    $fileName .=".xls";
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
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    //$objWriter->save('./excelFile/'.$fileName);
    header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
    header('Content-Disposition: attachment;filename="'.$fileName.'"');//告诉浏览器将输出文件的名称(文件下载)
    header('Cache-Control: max-age=0');//禁止缓存
    $objWriter->save("php://output");
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