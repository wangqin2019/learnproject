<?php

namespace app\admin\controller;
use app\admin\model\AdModel;
use app\admin\model\AdPositionModel;
use think\Db;
// 商品库存配置
class Goodsstock extends Base
{

    //*********************************************广告列表*********************************************//
    /**
     * [index 列表]
     * @return 
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['goods_title'] = ['like',"%" . $key . "%"];          
        }
        $map['delete_time'] = 0;
        $map['type'] = 1;             
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = Db::table('ims_bj_shopn_goods_stock')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        
        $lists = Db::table('ims_bj_shopn_goods_stock')->where($map)->order('create_time desc')->select();
        // 计算库存
        if ($lists) {
            // 查询包含单品库存及数量
            $mapd['type'] = 2;
            $mapd['delete_time'] = 0;
            $res_dp = Db::table('ims_bj_shopn_goods_stock')->field('min(goods_stock/goods_num) as stock,pid')->where($mapd)->order('pid desc')->group('pid')->select();
            // var_dump($res_dp);die;
            if ($res_dp) {
                foreach ($res_dp as $k1 => $v1) {
                    foreach ($lists as $k => $v) {
                        if ($v1['pid'] == $v['id']) {
                            $lists[$k]['goods_stock'] = floor($v1['stock']);//
                            // 更新套盒库存
                            $mapupd['id'] = $v['id'];
                            $data['goods_stock'] = floor($v1['stock']);
                            $res_upd = Db::table('ims_bj_shopn_goods_stock')->where($mapupd)->update($data);
                        }
                    }
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [add_ad 添加]
     */
    public function add_ad()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $data['goods_id'] = $param['goods_id'];
            $data['goods_code'] = $param['goods_code'];
            $data['goods_title'] = $param['goods_title'];
            $data['goods_price'] = $param['goods_price'];
            $data['goods_stock'] = $param['goods_stock'];
            $data['goods_spec'] = $param['goods_spec'];
            $data['type'] = 1;
            $data['pid'] = 0;
            $data['create_time'] = time();
            $flag = Db::table('ims_bj_shopn_goods_stock')->insert($data);
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        return $this->fetch();
    }


    /**
     * [edit_ad 编辑]
     */
    public function edit_ad()
    {
        $map['id'] = input('id');
        if(request()->isAjax()){
            $param = input('post.');
            $data['goods_id'] = $param['goods_id'];
            $data['goods_code'] = $param['goods_code'];
            $data['goods_title'] = $param['goods_title'];
            $data['goods_price'] = $param['goods_price'];
            $data['goods_stock'] = $param['goods_stock'];
            $data['goods_spec'] = $param['goods_spec'];
            $data['update_time'] = time();
            $map['id'] = $param['id'];
            $flag = Db::table('ims_bj_shopn_goods_stock')->where($map)->update($data);
            return json(['code' => 1, 'msg' => '修改成功']);
        }
        $list = Db::table('ims_bj_shopn_goods_stock')->where($map)->limit(1)->find();
        $this->assign('id', input('id'));
        $this->assign('list', $list);
        return $this->fetch();
    }


    /**
     * [del_ad 删除]
     */
    public function del_ad()
    {
        $id = input('param.id');
        $map['id'] = $id;
        $data['delete_time'] = time();
        $flag = Db::table('ims_bj_shopn_goods_stock')->where($map)->update($data);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    /**
     * [库存商品列表]
     * @return 
     */
    public function kcindex(){
        $pid = input('pid');
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['goods_title'] = ['like',"%" . $key . "%"];          
        }
        $map['pid'] = $pid;
        $map['delete_time'] = 0;
        $map['type'] = 2;             
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::table('ims_bj_shopn_goods_stock')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        
        $lists = Db::table('ims_bj_shopn_goods_stock')->where($map)->order('create_time desc')->select();

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        $this->assign('pid', $pid);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    /**
     * [kc_add_ad 添加库存商品]
     */
    public function kc_add_ad()
    {
        $pid = input('pid');
        if(request()->isAjax()){
            $param = input('post.');
            $data['goods_id'] = $param['goods_id'];
            $data['goods_code'] = $param['goods_code'];
            $data['goods_title'] = $param['goods_title'];
            $data['goods_price'] = $param['goods_price'];
            $data['goods_num'] = $param['goods_num'];
            $data['goods_stock'] = $param['goods_stock'];
            $data['goods_spec'] = $param['goods_spec'];
            $data['type'] = 2;
            $data['pid'] = $param['pid'];
            $data['create_time'] = time();
            $flag = Db::table('ims_bj_shopn_goods_stock')->insert($data);
            return json(['code' => 1, 'msg' => '添加成功']);
        }
        $this->assign('pid', $pid);
        return $this->fetch();
    }
    /**
     * [edit_ad 库存商品编辑]
     */
    public function kc_edit_ad()
    {
        $pid = input('pid');
        $map['id'] = input('id');
        if(request()->isAjax()){
            $param = input('post.');
            $data['goods_id'] = $param['goods_id'];
            $data['goods_code'] = $param['goods_code'];
            $data['goods_title'] = $param['goods_title'];
            $data['goods_price'] = $param['goods_price'];
            $data['goods_num'] = $param['goods_num'];
            $data['goods_stock'] = $param['goods_stock'];
            $data['goods_spec'] = $param['goods_spec'];
            $data['update_time'] = time();
            $map['id'] = $param['id'];
            $flag = Db::table('ims_bj_shopn_goods_stock')->where($map)->update($data);
            return json(['code' => 1, 'msg' => '修改成功']);
        }
        $list = Db::table('ims_bj_shopn_goods_stock')->where($map)->limit(1)->find();
        $this->assign('id', input('id'));
        $this->assign('list', $list);
        $this->assign('pid', $pid);
        return $this->fetch();
    }
    /**
     * [kc_del_ad 库存商品删除]
     */
    public function kc_del_ad()
    {
        $id = input('param.id');
        $map['id'] = $id;
        $data['delete_time'] = time();
        $flag = Db::table('ims_bj_shopn_goods_stock')->where($map)->update($data);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    /**
     * [erp_stock erp库存更小]
     */
    public function erp_stock()
    {
        set_time_limit(0);
        // 查询单品产品编码
        $map['type'] = 2;
        $map['delete_time'] = 0;
        $res = Db::table('ims_bj_shopn_goods_stock')->where($map)->select();
        if ($res) {
            $codes = '';
            foreach ($res as $k => $v) {
                $codes .= $v['goods_code'].',';
            }
            if ($codes) {
                $codes = rtrim($codes,',');
            }
            // 查询接口库存
            $url = 'http://erpapi2.chengmei.com:7779/app/get_stock.php?codes='.$codes;
            // var_dump($url);
            $rest = $this->curlGet($url);
            // print_r($rest);die;
            if ($rest) {
                $resp = json_decode($rest,true);
                // 根据产品编码更新单品库存
                if ($resp['code'] == 1) {
                    $arr = [];
                    foreach ($resp['data'] as $k1 => $v1) {
                        $maps['goods_code'] = $v1['code'];
                        $datas['goods_stock'] = $v1['stock'];
                        $resp2 = Db::table('ims_bj_shopn_goods_stock')->where($maps)->update($datas);
                    }
                }
            }
        }
        $ret['code'] = 1;
        $ret['msg'] = '库存更新成功!';
        return json($ret);
    }

    public function curlGet($url)
    {
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);         
        curl_setopt($curl, CURLOPT_HEADER, 0);        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//设置获取的信息以文件流的形式返回，而不是直接输出
        $data = curl_exec($curl);                     //执行命令
        curl_close($curl);                            //关闭URL请求
        return  ($data);                              //显示获得的数据
    }
}