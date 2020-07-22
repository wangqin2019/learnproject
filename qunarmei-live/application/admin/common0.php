<?php
use think\Db;
use think\Loader;

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
    // $csv_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\csv\\';
    //服务器
     $csv_path = '/home/canmay/www/live/public/csv/';

    if(is_file($csv_path.$csv_name))
    {
        unlink($csv_path.$csv_name);
    }

    $fp = fopen($csv_path.$csv_name,'a');
    // 处理头部标题
    $header = implode(',', $csv_header) . PHP_EOL;
    // 处理内容
    $content = '';
    foreach ($csv_body as $k => $v) {
        // 每条进行转码
        $v = iconv("UTF-8", "GB2312//IGNORE", $v);
        $content .= implode(',', $v) . PHP_EOL;
    }
    // 拼接
    $csv = $header.$content;
    // $csv = iconv("UTF-8", "GB2312//IGNORE", $csv);
    // 写入并关闭资源
    fwrite($fp, $csv);
    fclose($fp);
//        echo 'csv_name:'.$csv_name;exit;
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