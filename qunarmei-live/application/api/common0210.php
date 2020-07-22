<?php
//整合腾讯云通信扩展
use tencent_cloud\TimChat;
use think\Db;
/**
 * 生成订单号
 *
 * @param sup_id => 供应商id
 * @return string 生成20位订单号
 */
/**
 * 返回统一json格式数据
 * @param array $arr =>[string $code 状态码 ,string $data 数据,string $msg内容]
 *
 */
function return_msg($arr){
    $rest = json_encode($arr,JSON_UNESCAPED_UNICODE);
    echo $rest;
}
/**
 * 写入自定义日志文件
 * @param string $msg日志内容 ,string $name 日志文件名称
 *
 */
function write_log($msg,$name=''){
    // 默认记录支付日志
    $name = $name==''?'tasklist':$name;
    $logpath = RUNTIME_PATH.'/log/'.date('Ym').'/'.$name.'_'.date('Y-m-d').'.txt';
    file_put_contents($logpath,'时间:'.date('Y-m-d H:i:s').'--'.var_export($msg,true).PHP_EOL,FILE_APPEND);
}
// 生成9位数字的字符串,每3位加个空格
function make_str(){
    $str = mt_rand(100000000,999999999);
    return (string)$str;
}
function make_ordersn($sup_id) {
    // 供应商编号(5位，不够补0)+当前时间戳（10位）+5位随机数
    $len = strlen($sup_id);
    if($len<5){
        $str = str_pad($sup_id,5,"0",STR_PAD_RIGHT);
    }else{
        $str = substr($sup_id,5);
    }
    $str .= date('YmdH').mt_rand(11111,99999);
    return $str;
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
    //SSL证书问题
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
;

//调用外部接口 curl get方法
function curlGet($url='',$authori='')
{
    $ch = curl_init();
    $headers = array();
    $headers[] = 'Authorization:Bearer '.$authori;
    //设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //SSL证书问题
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //添加头文件
    if($authori)
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    //打印获得的数据
    return $output;
}

//数组按字段排序
function sortField($arr=array(),$zi,$type='')
{
    $sort = array(
        'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
        'field'     => $zi,       //排序字段
    );
    if($type)
    {
        $sort['direction'] = 'SORT_ASC';
    }
    $arrSort = array();
    foreach($arr AS $uniqid => $row){
        foreach($row AS $key=>$value){
            $arrSort[$key][$uniqid] = $value;
        }
    }
    if($sort['direction']){
        array_multisort($arrSort[$sort['field']], constant($sort['direction']), $arr);
        return $arr;
    }

}
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
//创建聊天室
function creChatRoom()
{
    $tent = new TimChat();
    $res1 = $tent->creChatRoom();
    //插入聊天室id到chatRoom表
    $data = array('chat_id'=>$res1,'chat_name'=>'去哪美聊天室'.$res1,'chat_owner'=>'admin','chat_cnt'=>1,'flag'=>1,'log_time'=>date('Y-m-d H:i:s'));
    $res2 = Db::name('chatroom')->insert($data);
    return $res1;
}
//end Modify by wangqin 2017-12-06
//start Modify by wangqin 2017-12-27
//二维数组求差集
function arrCha($arr1,$arr2)
{
    $arr3 = array();
    foreach ($arr1 as $key => $value) {
        if(!in_array($value,$arr2)){
            $arr3[]=$value;
        }
    }
    return $arr3;
}
//end Modify by wangqin 2017-12-27

//start Modify by wangqin 2018-03-05
//获取图片长和宽
function getImageinfo($url)
{
    $result='';
    $imageInfo = getimagesize($url);
    if($imageInfo){
        $result['width'] = $imageInfo[0];
        $result['height'] = $imageInfo[1];
    }
    return $result;
}
//获取几个随机颜色
function getColors($num=1)
{
    $data1 = [];
    $sql_c = Db::query('select colors from think_color order by rand() limit '.$num);
    foreach($sql_c as $k=>$v)
    {
        $data1[] = $sql_c[$k]['colors'];
    }
    return json_encode($data1);
}
//end Modify by wangqin 2018-03-05