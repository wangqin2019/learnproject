<?php

namespace app\admin\model;
use think\exception\PDOException;
use think\Model;
use think\Db;

class GoodsModel extends Model
{
    protected $name = 'goods';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    /**
     * 根据条件获取列表信息
     * @param $where
     * @param $Nowpage
     * @param $limits
     */
    public function getAll($map, $Nowpage, $limits)
    {
        return $this->alias('g')
            ->field('g.*,cate.name cate_name')
            ->join('pt_goods_cate cate', 'g.goods_cate = cate.id','left')
            ->where($map)
            ->page($Nowpage,$limits)
            ->order('g.id desc')
            ->select();
    }
    public function getAllPage($map, $limits)
    {
        return $this->alias('g')
            ->field('g.*,cate.name cate_name')
            ->join('pt_goods_cate cate', 'g.goods_cate = cate.id','left')
            ->where($map)
            ->order('g.storeid asc,g.goods_cate desc')
            ->paginate($limits);
    }

    public function getAllByWhere($map)
    {
        return $this->field('id,name')->where($map)->order('orderby desc')->select();
    }


    /**
     * 根据条件获取列表名
     * @param $where
     * @param $Nowpage
     * @param $limits
     */
    public function getAllGoodsInfo($map)
    {
        return $this->field('id,name')->where($map)->order('orderby desc')->select();
    }

    /**
     * 插入信息
     * @param $param
     */
    public function insertGoods($param,$item,$recommend)
    {
        try{
            $result = $this->validate('GoodsValidate')->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                $goods_id=$this->id;
                if($item)
                {
                    $dataList=[];
                    foreach($item as $k => $v)
                    {
                        // 批量添加数据
                        $v['price'] = trim($v['price']);
                        $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                        $v['sku'] = trim($v['sku']);
                        $dataList[] = ['goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'sku'=>$v['sku'],'img'=>$v['img']];
                        // 修改商品后购物车的商品价格也修改一下
//                        M('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
//                            'market_price'=>$v['price'], //市场价
//                            'goods_price'=>$v['price'], // 本店价
//                            'member_goods_price'=>$v['price'], // 会员折扣价
//                        ));
                    }
                    Db::name("goods_specs_info")->insertAll($dataList);

                }
                if($recommend)
                {
                    $dataList1=[];
                    foreach($recommend as $k => $v)
                    {
//                        $v[]=$goods_id;//是否将本商品id加到推荐列表
                        // 批量添加数据
                        $d['storeid'] = $this->storeid;
                        $d['gid'] = $goods_id;
                        $d['recommend_ids'] = implode(',',$v);
                        $dataList1[] = $d;
                    }
                    Db::name("activity_goods_recommend")->insertAll($dataList1);

                }

                //获取当前参与活动的门店
                if($param['goods_cate']==4 && $param['storeid']==0 && ($param['activity_id']==1 || $param['activity_id']==2 || $param['activity_id']==3)) {
                    $m['storeid'] = array('gt', 0);
                    $branchList = $this->where($m)->group('storeid')->column('storeid');
                    $insertData = [];
                    foreach ($branchList as $k => $v) {
                        $param['storeid'] = $v;
                        $param['create_time'] = time();
                        $param['update_time'] = time();
                        $param['given'] = '';
                        unset($param['file']);
                        $insertData[$k] = $param;
                    }
                    $this->insertAll($insertData);
                }


                return ['code' => 1, 'data' => '', 'msg' => '添加产品成功'];
            }
        }catch(\PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑信息
     * @param $param
     */
    public function editGoods($param,$item,$recommend)
    {
        try{

            $result = $this->validate('GoodsValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                Db::name('goods_specs_info')->where('goods_id = '.$this->id)->delete(); // 删除原有的价格规格对象
                if($item){
                    $dataList=[];
                    $goods_id=$this->id;
                    foreach($item as $k => $v)
                    {
                        // 批量添加数据
                        $v['price'] = trim($v['price']);
                        $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                        $v['sku'] = trim($v['sku']);
                        $dataList[] = ['goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'sku'=>$v['sku'],'img'=>$v['img']];
                        // 修改商品后购物车的商品价格也修改一下
//                        M('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
//                            'market_price'=>$v['price'], //市场价
//                            'goods_price'=>$v['price'], // 本店价
//                            'member_goods_price'=>$v['price'], // 会员折扣价
//                        ));
                    }
                    Db::name("goods_specs_info")->insertAll($dataList);
                }
                Db::name('activity_goods_recommend')->where(['gid'=>$this->id,'storeid'=>$this->storeid])->delete(); // 删除原有的推荐
                if($recommend)
                {
                    $dataList1=[];
                    $recommend=array_values($recommend);
                    foreach($recommend as $k => $v)
                    {
//                        $v[]=$goods_id;//是否将本商品id加到推荐列表
                        // 批量添加数据
                        $d['storeid'] = $this->storeid;
                        $d['gid'] = $this->id;
                        $d['recommend_ids'] = implode(',',$v);
                        $dataList1[] = $d;
                    }
                    Db::name("activity_goods_recommend")->insertAll($dataList1);

                }

//                if($param['goods_cate']==4 && $param['storeid']==0) {
//                    $udata['pid'] = array('eq', $param['id']);
//                    Db::name('goods')->where($udata)->update(['given' =>'']);
//                }

                return ['code' => 1, 'data' => '', 'msg' => '编辑产品成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据id获取一条信息
     * @param $id
     */
    public function getOneInfo($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除信息
     * @param $id
     */
    public function delGoods($id)
    {
        try{
            $this->where('id',$id)->delete();
            Db::name('goods_specs_info')->where('goods_id = '.$id)->delete(); // 删除规格
            return ['code' => 1, 'data' => '', 'msg' => '删除产品成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Commit: 设置商品是否参加砍价活动
     * Function: setBargain
     * @param $id
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 11:31:32
     * @return array|\think\response\Json
     */
    public function setBargain($id)
    {
        try{
            $status = $this->where(array('id'=>$id))->value('is_bargain');//判断当前状态情况
            if($status == 1){
                $this->where('id',$id)->setField(['is_bargain'=>0]);
                return json(['code' => 1, 'data' => '', 'msg' => '已关闭']);
            }else{
                $this->where('id',$id)->setField(['is_bargain'=>1]);
                return json(['code' => 0, 'data' => '', 'msg' => '开启成功']);
            }
        }catch (PDOException $e){
            return ['code' => 1, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}