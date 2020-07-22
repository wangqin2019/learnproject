<?php

namespace app\admin\controller;
use app\admin\model\BannerModel;
use app\admin\model\BannerPositionModel;
use app\admin\model\ComposeModel;
use app\admin\model\GoodsCateModel;
use app\admin\model\GoodsModel;
use org\leftnav;
use think\Db;
use think\Debug;

class Goods extends Base{

    //*********************************************商品列表*********************************************//
    /**
     * [index 商品列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function index(){
        header("Cache-control: private");
        $key = input('key');
        $cate_id = input('cate_id');
        $map = [];
        if($key&&$key!==""){
            $map['g.name'] = ['like',"%" . $key . "%"];
        }
        if($cate_id && $cate_id!==""){
            $map['g.goods_cate'] = ['eq',$cate_id];
        }
        $map['g.storeid'] = ['eq',0];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('goods')->alias('g')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $ad = new GoodsModel();
        $lists = $ad->getAll($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('cate_id', $cate_id);
        if(input('get.page')){
            return json($lists);
        }
        $nav = new \org\Leftnav;
        $cate=Db::name('goods_cate')->field('id,pid,name')->order('orderby')->select();
        $cate = $nav::rule($cate);
        $this->assign('cate',$cate);

        return $this->fetch();
    }


    /**
     * [add_ad 添加商品]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function add(){
        if(request()->isAjax()){
            $param = input('post.');
            $item = input('param.item/a');
            $recommend = input('param.recommend/a');
            if($param['is_fenqi']){
                $param['fenqi']=implode(',',$param['fenqi']);
            }
            if(isset($param['given'])){
                $param['given']=implode(',',$param['given']);
            }
            if(isset($param['buy_type'])){
                $param['buy_type']=implode(',',$param['buy_type']);
            }
            if($param['images']){
             $param['images']=implode(',',$param['images']);
            }
            $goods = new GoodsModel();
            $flag = $goods->insertGoods($param,$item,$recommend);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $storeid = input('storeid',0);
        $this->assign('storeid',$storeid);
        $cate_id = input('cate_id','');
        $this->assign('cate_id',$cate_id);
        $source = input('source','');
        $this->assign('source',$source);
        //增加无限分类
        $nav = new \org\Leftnav;
        $categroyList=Db::name('GoodsCate')->order('orderby')->select();
        $categroyList = $nav::rule($categroyList);
        $this->assign('categroyList', $categroyList); //全部分类

        //读取分期
        $bank=Db::name('bank')->field('id_bank,st_abbre_bankname')->where(['is_period'=>1,'id_status'=>1])->order('no_displayorder')->select();
        if(is_array($bank) && count($bank)){
            foreach ($bank as $k=>$v){
                $bank[$k]['list']=Db::name('bank_interestrate')->field('id,no_period')->where(['status'=>0,'id_bank'=>$v['id_bank']])->select();
            }
        }
        $this->assign('bankFenqi', $bank);

        $modelList=Db::name('goods_model')->where('model_status',1)->select();
        $this->assign('modelList', $modelList); //全部模型

        $branch=Db::table('ims_bwk_branch')->field('id,title,sign')->select();
        $this->assign('branch',$branch);
        $draw=Db::name('draw_scene')->field('id,scene_name,scene_prefix')->where('id','between',['12','19'])->select();
        $this->assign('draw',$draw);
        //买赠
        $given_m['status']=array('eq',1);
        $given_m['goods_cate']=array('in',['4','9','13','15']);
        $given_m['storeid']=array('in',['0',$storeid]);
        $given=Db::name('goods')->field('id,name')->where($given_m)->select();
        $this->assign('given',$given);

        //读取活动列表 剔除同享
        $activity_list =Db::name('activity_list')->field('id,name')->where('activity_switch',1)->select();
        foreach ($activity_list as $k=>$v){
            if($v['id']==4){
                unset($activity_list[$k]);
            }
        }
        $this->assign('activityList',$activity_list);
        return $this->fetch();
     }


    /**
     * [edit_ad 编辑商品]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function edit(){
        $goods = new GoodsModel();
        if(request()->isPost()){
            $param = input('post.');
            $item = input('param.item/a');
            $recommend = input('param.recommend/a');
            if($param['is_fenqi']){
                $param['fenqi']=implode(',',$param['fenqi']);
            }else{
                $param['fenqi']='';
            }
            if(isset($param['given'])){
                $param['given']=implode(',',$param['given']);
            }else{
                $param['given']='';
            }
            if($param['buy_type']){
                $param['buy_type']=implode(',',$param['buy_type']);
            }else{
                $param['buy_type']='';
            }
            if(isset($param['images'])) {
                $param['images'] = implode(',', $param['images']);
            }
            $flag = $goods->editGoods($param,$item,$recommend);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info=$goods->getOneInfo($id);
        $info['images']=$info['images']?explode(',',$info['images']):'';
       // $info['fenqi']=$info['fenqi']?explode(',',$info['fenqi']):'';
        $info['buy_type']=$info['buy_type']?explode(',',$info['buy_type']):'';
        $recommend=Db::name('activity_goods_recommend')->where(['storeid'=>$info['storeid'],'gid'=>$id])->select();
        if($recommend){
            foreach ($recommend as $k=>$v){
                $re=Db::name('goods')->field('id,name,activity_price,images')->where('id','in',$v['recommend_ids'])->select();
                $recommend[$k]['goods']=$re;
                $recommend[$k]['count']=count($re);
            }
        }
        $info['recommend']=$recommend;
        $this->assign('goods',$info);
        $source = input('source','');
        $this->assign('source',$source);
        //增加无限分类
        $nav = new \org\Leftnav;
        $categroyList=Db::name('GoodsCate')->order('orderby')->select();
        $categroyList = $nav::rule($categroyList);
        $this->assign('categroyList', $categroyList); //全部分类
        //读取分期
        $bank=Db::name('bank')->field('id_bank,st_abbre_bankname')->where(['is_period'=>1,'id_status'=>1])->order('no_displayorder')->select();
        if(is_array($bank) && count($bank)){
            foreach ($bank as $k=>$v){
                $bank[$k]['list']=Db::name('bank_interestrate')->field('id,no_period')->where(['status'=>0,'id_bank'=>$v['id_bank']])->select();
            }
        }
        $this->assign('bankFenqi', $bank);
        $modelList=Db::name('goods_model')->where('model_status',1)->select();
        $this->assign('modelList', $modelList); //全部模型
        $branch=Db::table('ims_bwk_branch')->field('id,title,sign')->select();
        $this->assign('branch',$branch);
        $draw=Db::name('draw_scene')->field('id,scene_name,scene_prefix')->where('id','between',['12','19'])->select();
        $this->assign('draw',$draw);
        //买赠
        $given_m['status']=array('eq',1);
        $given_m['goods_cate']=array('in',['4','9','13','15']);
        $given_m['storeid']=array('in',['0',$info['storeid']]);
        $given=Db::name('goods')->field('id,name')->where($given_m)->select();
        $this->assign('given',$given);
        //读取活动列表
        $activity_list =Db::name('activity_list')->field('id,name')->where('activity_switch',1)->select();
        foreach ($activity_list as $k=>$v){
            if($v['id']==4){
                unset($activity_list[$k]);
            }
        }
        $this->assign('activityList',$activity_list);
        return $this->fetch();
    }

    public function recommend(){
        if(request()->isAjax()){
            $ids= input('post.ids');
            $list=Db::name('goods')->field('id,name,price,activity_price,images')->where('id','in',$ids)->select();
            return json(['code' => 1, 'data' => $list, 'msg' => '获取成功']);
        }
        $storeid = input('param.storeid',0);
        $given_m['g.status']=array('eq',1);
        $given_m['g.goods_cate']=array('eq',4);
        $given_m['g.storeid']=array('eq',$storeid);
        $given_m['g.id']=array('not in',['47','79']);
        $recommend=Db::name('goods')->alias('g')->join('goods_model m','g.model_id=m.id','left')->field('g.id,g.name,m.model_specs')->where($given_m)->select();
//        if($recommend){
//            foreach ($recommend as $k=>$v){
//                $getSpecs=Db::name('goods_specs')->where('id','in',$v['model_specs'])->field('id,specs_name,specs_item')->select();
//                $recommend[$k]['model_specs']=$getSpecs;
//            }
//        }
        $this->assign('recommend',$recommend);
        return $this->fetch();
    }




    /**
     * [del_ad 删除商品]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function del(){
        $id = input('param.id');
        $ad = new GoodsModel();
        $flag = $ad->delGoods($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    /**
     * [ad_state 商品状态]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function state(){
        $id=input('param.id');
        $status = Db::name('goods')->where(array('id'=>$id))->value('status');//判断当前状态情况
        $map['id|pid']=array('eq',$id);
        if($status==1)
        {
            $flag = Db::name('goods')->where($map)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $flag = Db::name('goods')->where($map)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    }



    //*********************************************商品位*********************************************//
    /**
     * [index_position 商品位列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function index_cate(){
        $goods = new GoodsCateModel();
        $nav = new \org\Leftnav;
        $categroyList = $goods->getAllPosition();
        $list = $nav::rule($categroyList);
        $this->assign('list', $list); //全部分类
        return $this->fetch();
    }


    /**
     * [add_position 添加商品位]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function add_cate(){
        if(request()->isAjax()){
            $param = input('post.');
            $goods = new GoodsCateModel();
            $flag = $goods->insertGoodsCate($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        //增加无限分类
        $nav = new \org\Leftnav;
        $categroyList=Db::name('GoodsCate')->order('orderby')->select();
        $categroyList = $nav::rule($categroyList);
        $this->assign('categroyList', $categroyList); //全部分类
        return $this->fetch();
    }


    /**
     * [edit_position 编辑商品位]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function edit_cate(){
        $goods = new GoodsCateModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $goods->editGoodsCate($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign('goods',$goods->getOne($id));
        //增加无限分类
        $nav = new \org\Leftnav;
        $categroyList=Db::name('GoodsCate')->order('orderby')->select();
        $categroyList = $nav::rule($categroyList);
        $this->assign('categroyList', $categroyList); //全部分类
        return $this->fetch();
    }


    /**
     * [del_position 删除商品位]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function del_cate(){
        $id = input('param.id');
        $ad = new GoodsCateModel();
        $flag = $ad->delGoodsCate($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [position_state 商品位状态]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function cate_state(){
        $id=input('param.id');
        $status = Db::name('goods_cate')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1){
            $flag = Db::name('goods_cate')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $flag = Db::name('goods_cate')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    }

    /**
     * [position_state 商品规格]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function specs(){
        $nowpage = input('get.page') ? input('get.page'):1;
        $limits = 10;// 获取总条数
        $count = Db::name('goods_specs')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $list =Db::name('goods_specs')->page($nowpage, $limits)->order('id asc')->select();
        $this->assign('nowpage', $nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('list', $list);
        return $this->fetch();
    }

    //新增产品规格
    public function add_specs(){
        if(request()->isAjax()){
            $param = input('post.');
            $param['create_time']=time();
            $param['update_time']=time();
            $result =  Db::name('goods_specs')->insertGetId($param);
            if(false === $result){
                $flag= ['code' => -1, 'data' => '', 'msg' => ''];
            }else{
                if(strlen($param['specs_item'])){
                    $specs=explode(',',$param['specs_item']);
                    $specsData=[];
                    foreach ($specs as $k=>$v){
                        $specsData[$k]['spec_id']=$result;
                        $specsData[$k]['item']=$v;
                    }
                    Db::name('goods_specs_item')->insertAll($specsData);
                }
                $flag= ['code' => 1, 'data' => '', 'msg' => '产品规格新增成功'];
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }

    //编辑产品规格
    public function edit_specs(){
        if(request()->isAjax()){
            $param = input('post.');
            $param['update_time']=time();
            $result =  Db::name('goods_specs')->update($param);
            if(false === $result){
                $flag= ['code' => -1, 'data' => '', 'msg' => ''];
            }else{
                if(strlen($param['specs_item'])){
                    $specs=explode(',',$param['specs_item']);
                    $db_items = Db::name('goods_specs_item')->where("spec_id =".$param['id'])->column('id,item');
                    // 两边 比较两次
                    /* 提交过来的 跟数据库中比较 不存在 插入*/
                    foreach($specs as $key => $val)
                    {
                        if(!in_array($val, $db_items))
                            $dataList[] = array('spec_id'=>$param['id'],'item'=>$val);
                    }
                    // 批量添加数据
                    if(isset($dataList)){
                        Db::name('goods_specs_item')->insertAll($dataList);
                    }
                    /* 数据库中的 跟提交过来的比较 不存在删除*/
                    foreach($db_items as $key => $val)
                    {
                        if(!in_array($val, $specs))
                        {
                            //  SELECT * FROM `tp_spec_goods_price` WHERE `key` REGEXP '^11_' OR `key` REGEXP '_13_' OR `key` REGEXP '_21$'
                            //M("SpecGoodsPrice")->where("`key` REGEXP '^{$key}_' OR `key` REGEXP '_{$key}_' OR `key` REGEXP '_{$key}$' or `key` = '{$key}'")->delete(); // 删除规格项价格表
                            Db::name('goods_specs_item')->where('id='.$key)->delete(); // 删除规格项
                        }
                    }
                }
                $flag= ['code' => 1, 'data' => '', 'msg' => '产品规格修改成功'];
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info=Db::name('goods_specs')->where('id', $id)->find();
        $this->assign('specs',$info);
        return $this->fetch();
    }


    /**
     * [position_state 商品模型]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function goods_model(){
        $nowpage = input('get.page') ? input('get.page'):1;
        $limits = 10;// 获取总条数
        $count = Db::name('goods_model')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $list =Db::name('goods_model')->page($nowpage, $limits)->order('id asc')->select();
        if($list){
            foreach ($list as $k=>$v){
                $specs=Db::name('goods_specs')->where('id','in',$v['model_specs'])->column('specs_name');
                $list[$k]['model_specs']=implode('<br/>',$specs);
            }
        }
        $this->assign('nowpage', $nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('list', $list);
        return $this->fetch();
    }

    //新增产品模型
    public function add_model(){
        if(request()->isAjax()){
            $param = input('post.');
            $param['create_time']=time();
            $param['update_time']=time();
            $result =  Db::name('goods_model')->insert($param);
            if(false === $result){
                $flag= ['code' => -1, 'data' => '', 'msg' => ''];
            }else{
                $flag= ['code' => 1, 'data' => '', 'msg' => '产品模型新增成功'];
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }

    //编辑产品模型
    public function edit_model(){
        if(request()->isAjax()){
            $param = input('post.');
            $param['update_time']=time();
            if(isset($param['model_specs'])){
                $param['model_specs']=implode(',',$param['model_specs']);
            }else{
                $param['model_specs']='';
            }
            $result =  Db::name('goods_model')->update($param);
            if(false === $result){
                $flag= ['code' => -1, 'data' => '', 'msg' => ''];
            }else{
                $flag= ['code' => 1, 'data' => '', 'msg' => '产品模型修改成功'];
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info=Db::name('goods_model')->where('id', $id)->find();
        $this->assign('model',$info);
        $specsList=Db::name('goods_specs')->where('specs_status',1)->order('specs_order')->select();
        $this->assign('specsList', $specsList); //全部规格
        return $this->fetch();
    }


    /**
     * [del_position 删除商品模型]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function del_model(){
        $id = input('param.id');
        try{
            Db::name('goods_model')->where('id', $id)->delete();
            $flag= ['code' => 1, 'data' => '', 'msg' => '删除产品模型成功'];
        }catch( \PDOException $e){
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [del_position 删除商品规格]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function del_specs(){
        $id = input('param.id');
        try{
            Db::name('goods_specs')->where('id', $id)->delete();
            Db::name('goods_specs_item')->where('spec_id', $id)->delete();
            $flag= ['code' => 1, 'data' => '', 'msg' => '删除产品规格成功'];
        }catch( \PDOException $e){
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    public function ajax_get_spec_select(){
        $goods_id = input('get.goods_id') ? input('get.goods_id') : 0;
        $modelId=input('param.modelId');
        $specs = Db::name('goods_model')->where('id',$modelId)->value('model_specs');
        if($specs){
            $arr=explode(',',$specs);
            $specList=[];
            foreach ($arr as $k=>$v){
                $specList[$k] = Db::name('goods_specs')->field('id,specs_name,specs_item')->where('id',$v)->find(); // 获取规格项
                $specList[$k]['specs_item'] = Db::name('goods_specs_item')->where("spec_id",$v)->order('id')->column('id,item'); // 获取规格项
            }
        }
        $items_id = Db::name('goods_specs_info')->where('goods_id',$goods_id)->value("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);

        $this->assign('specsItems', $items_ids); //全部规格
        $this->assign('specList', $specList); //全部规格
        return $this->fetch('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){
        $goods_id = input('param.goods_id') ? input('param.goods_id') : 0;
        $str = $this->getSpecInput($goods_id ,input('param.spec_arr/a',[[]]));
        exit($str);
    }



    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr)
    {

        // 排序
        foreach ($spec_arr as $k => $v)
        {
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val)
        {
            $spec_arr2[$key] = $spec_arr[$key];
        }

        $clo_name = array_keys($spec_arr2);
       // print_r($clo_name);
        $spec_arr2 = combineDika($spec_arr2); //  获取 规格的 笛卡尔积

        $spec = Db::name('goods_specs')->column('id,specs_name'); // 规格表

        $specItem = Db::name('goods_specs_item')->column('id,item,spec_id');//规格项
        $keySpecGoodsPrice = Db::name('goods_specs_info')->where('goods_id',$goods_id)->column('key,key_name,price,store_count,bar_code,sku,img');//规格项
        //print_r($keySpecGoodsPrice);
        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        // 显示第一行的数据
        if($clo_name[0]<>0){
            foreach ($clo_name as $k => $v)
            {
                $str .=" <td><b>{$spec[$v]}</b></td>";
            }
        }
        $str .="<td><b>价格</b></td>
               <td><b>库存</b></td>
               <td><b>SKU</b></td>
               <td><b>图片</b></td>
             </tr>";
        // 显示第二行开始
        foreach ($spec_arr2 as $k => $v)
        {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2]['item']}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

           if($keySpecGoodsPrice){
               if(isset($keySpecGoodsPrice[$item_key])) {
                   $price = $keySpecGoodsPrice[$item_key]['price'];
                   $store_count = $keySpecGoodsPrice[$item_key]['store_count'];
                   $sku = $keySpecGoodsPrice[$item_key]['sku'];
                   $img = $keySpecGoodsPrice[$item_key]['img'];
               }else{
                   $price = 0;
                   $store_count = 0;
                   $sku = '';
                   $img= '';
               }
           }else{
               $price = 0; // 价格默认为0
               $store_count= 0; //库存默认为0
               $sku= '';
               $img= '';
           }
            $imgText=$img?'重新上传':'上传';
            $str .="<td><input name='item[$item_key][price]' value='$price' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='$store_count' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][sku]' value='$sku' /></td>";
            $str .="<td>
                <img src='".$img."' id='up_img$item_key' width='50' height='50' onerror=\"this.src='/static/admin/images/bg.png'\"/>
                <input type='hidden' name='item[$item_key][img]' id='up_img_url$item_key' value='".$img."'>　
                <button type='button' id='$item_key'  class='layui-btn  layui-btn-xs demoMore' ><i class='layui-icon'>&#xe64a;</i>$imgText</button>                      
               <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";
        }
        $str .= "</table>";
        return $str;
    }


    public function compose(){
        header("Cache-control: private");
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['g.name'] = ['like',"%" . $key . "%"];
        }
        $compose = new ComposeModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $compose->getCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = $compose->getAll($map,$Nowpage,$limits);///计算总页面
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    /*
     * 新增组合
     */
    public function composeAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            if($param['cids']){
                $param['cids']=implode(',',$param['cids']);
            }
            $compose = new ComposeModel();
            $flag = $compose->insertCompose($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $goods=Db::name('goods')->where(['is_compose'=>1])->field('id,name')->select();
        $this->assign('goods', $goods); //可能需要组合的产品
        $composeGoods=Db::name('goods')->where(['goods_cate'=>14])->field('id,name')->select();
        $this->assign('composeGoods', $composeGoods); //被组合的产品
        return $this->fetch('add_compose');
    }

    /*
     * 编辑组合
     */
    public function composeEdit(){
        $compose = new ComposeModel();
        if(request()->isAjax()){
            $param = input('post.');
            if($param['cids']){
                $param['cids']=implode(',',$param['cids']);
            }
            $flag = $compose->updateCompose($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id=input('param.id');
        $goods=Db::name('goods')->where(['is_compose'=>1])->field('id,name')->select();
        $this->assign('goods', $goods); //可能需要组合的产品
        $composeGoods=Db::name('goods')->where(['goods_cate'=>14])->field('id,name')->select();
        $this->assign('composeGoods', $composeGoods); //被组合的产品
        $info=$compose->getOneInfo($id);
        $this->assign('info', $info); //组合信息
        return $this->fetch('edit_compose');
    }


    /**
     * 删除组合
     */
    public function composeDel(){
        $id = input('param.id');
        $ad = new ComposeModel();
        $flag = $ad->delCompose($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}
