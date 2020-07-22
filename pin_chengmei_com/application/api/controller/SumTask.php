<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/9/5
 * Time: 13:20
 */

namespace app\api\controller;
use think\Db;
/**
 * swagger: 统计计划任务
 */
class SumTask extends Base
{
    private $code = 0;
    private $data = [];
    private $msg = '暂无数据';
    // 从redis取出用户行为数据存入mysql
    public function LogToMysql(){
        // 每次取出多少条存入
        $num = input('param.num',1000);// 每次默认取出1000条
        for($i=0;$i<$num;$i++){
            $data_redis = $this->logOutRedis();
            if($data_redis){
                $data[] = json_decode($data_redis,true);
            }
        }
        if(!empty($data)){
            $data_all=[];
            foreach($data as $v){
                $data_v['uid'] = $v['id'];
                $data_v['uname'] = $v['name'];
                $data_v['avatar'] = $v['avatar'];
                $data_v['mobile'] = $v['mobile'];
                $data_v['storeid'] = $v['storeid'];
                $data_v['storename'] = $v['title'];
                $data_v['sellerid'] = $v['pid'];
                $data_v['sellername'] = $v['sellername'];
                $data_v['action'] = $v['action'];
                $data_v['first_login'] = $v['first_login'];
                $data_v['remark'] = $v['remark'];
                $data_v['insert_time'] = isset($v['insert_time'])?$v['insert_time']:time();
                $data_all[] = $data_v;
            }
            $res = Db::name('data_logs')->insertAll($data_all);
            if($res){
                $this->code = 1;
                $this->msg = 'redis数据同步mysql成功';
                $this->data = $data;
            }
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    // 存入用户行为数据到redis
    public function TestRedis(){
        $data = '{"id":20306,"storeid":2,"mobile":"13918527001","pid":20296,"isadmin":0,"staffid":20296,"id_regsource":7,"title":"\u8bda\u7f8e\u603b\u90e8\u95e8\u5e97\u3010\u6280\u672f\u90e8\u3011","name":"\u5434\u9971\u9971","avatar":"https:\/\/wx.qlogo.cn\/mmopen\/vi_32\/DYAIOgq83epfn4Bu8AzuzbqTuUYHjjguenKScJCQaBJQLPhhibclsm2icPibeiaqc4hNWfwoCtQJ3NhgxHE6aCHnew\/132","first_login":1,"sellername":"\u8bda\u7f8eIT","action":1,"remark":"\u8bbf\u95ee\u62fc\u56e2\u5217\u8868","insert_time":1536118469}';
//        $data = '{"id":20299,"storeid":2,"mobile":"18717884032","pid":16263,"isadmin":0,"staffid":16263,"id_regsoursce":7,"title":"\u8bda\u7f8e\u603b\u90e8\u95e8\u5e97\u3010\u6280\u672f\u90e8\u3011","name":"\u5434\u67ab","avatar":"https:\/\/wx.qlogo.cn\/mmopen\/vi_32\/Q0j4TwGTfTKJPAb46dO1iaMkuqiagicic0Qr7ADDKVTX0j7CaiaP79LvXfwzj9g5fuibZZ5VB4Via7ssmxNSia3uDSGDzQ\/132","first_login":1,"sellername":"\u5f20\u857e","action":6,"remark":"\u8ba2\u5355\u5931\u6548"}';
        $data1 = json_decode($data,true);
//        print_r($data1);die;
        $res = $this->logToRedis($data1);

        $this->code = 1;
        $this->msg = '存入redis成功';
        $this->data = $data;

        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
}