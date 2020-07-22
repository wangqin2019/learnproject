<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BargainModel extends Model
{
    protected $name = 'bargain_goods';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    public function getIsTypeAttr($value){
        return $value ? '活动产品订单' : '奖励产品订单';
    }
    /**
     * 根据搜索条件获取拼人品列表信息
     */
    public function getTuanByWhere($map, $Nowpage, $limits)
    {
        return $this->alias('pt')
            ->where($map)
            ->field('pt.*,branch.title,branch.sign')
            ->join(['ims_bwk_branch' => 'branch'],'pt.storeid=branch.id')
            ->page($Nowpage, $limits)
            ->order('pt.id desc')
            ->select();
    }
    /**
     * Commit: 获取参与门店产品及奖励产品
     * Function: getBargainGoods
     * @param $map
     * @param $Nowpage
     * @param $limits
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 10:14:14
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBargainGoods($map, $Nowpage, $limits)
    {
        return $this->alias('bargain')
            ->where($map)
            ->field('bargain.*,branch.title,branch.sign')
            ->join(['ims_bwk_branch' => 'branch'],'bargain.storeid=branch.id')
            ->page($Nowpage, $limits)
            ->order('bargain.id desc')
            ->select();
    }
    /**
     * Commit: 获取店铺产品总数
     * Function: getBargainGoodsCount
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 09:14:49
     * @return int|string
     */
    public function getBargainGoodsCount($map)
    {
        return $this->alias('bargain')
            ->where($map)
            ->join(['ims_bwk_branch' => 'branch'],'bargain.storeid=branch.id','left')
            ->count();
    }
    /**
     * Commit: 获取店铺信息
     * Function: getBargainStore
     * @param $map
     * @param $Nowpage
     * @param $limits
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 09:07:03
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getBargainStore($map, $Nowpage, $limits)
    {
        $res = Db::table('ims_bwk_branch')
            ->alias('bwk')
            ->field('bwk.id,bwk.title,bwk.sign,bwk.is_bargain,m.realname,m.mobile,bargain_plan')
            ->join(['ims_bj_shopn_member'=>'m'],'bwk.id=m.storeid','left')
            ->where($map)
            ->page($Nowpage, $limits)
            ->group('bwk.id')
            ->order('bwk.is_bargain desc')
            ->select();
        return $res;
    }
    /**
     * Commit: 获取店铺总数
     * Function: getBargainStoreCount
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 09:14:49
     * @return int|string
     */
    public function getBargainStoreCount($map)
    {
        return Db::table('ims_bwk_branch')
            ->alias('bwk')
            //->join(['ims_bj_shopn_member'=>'m'],'bwk.id=m.storeid','left')
            ->where($map)
            //->group('bwk.id')
            ->count();
    }

    /**
     * Commit: 已开通或未开通门店的数量
     * Function: getOpenStoreCount
     * @Param int $tips
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-02 14:14:17
     * @Return int|string
     */
    public function getOpenStoreCount($tips = 1) {
        $map['is_bargain'] = $tips;
        return Db::table('ims_bwk_branch')->where($map)->count();
    }



    /**
     * 根据搜索条件获取所有的拼人品数量
     * @param $where
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 插入拼人品信息
     * @param $param
     */
    public function insertUser($param)
    {
        try{
            if($param['tuisong']){
                try {
                    //获取当前参与活动的门店
                    $branchList = $this->group('storeid')->column('storeid');
                    $insertData = [];
                    foreach ($branchList as $k => $v) {
                        $param['storeid'] = $v;
                        $param['create_time'] = time();
                        $param['update_time'] = time();
                        unset($param['file']);
                        unset($param['tuisong']);
                        $insertData[$k] = $param;
                    }
                    $this->insertAll($insertData);
                    writelog(session('uid'),session('username'),'拼人品【'.$param['name'].'】批量添加成功',1);
                    return ['code' => 1, 'data' => '', 'msg' => '添加拼人品成功'];
                }catch (\Exception $e){
                    return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
                }
            }else{
                $result = $this->validate('BargainValidate')->allowField(true)->save($param);
                if(false === $result){
                    return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
                }else{
                    writelog(session('uid'),session('username'),'拼人品【'.$param['name'].'】添加成功',1);
                    return ['code' => 1, 'data' => '', 'msg' => '添加拼人品成功'];
                }
            }

        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 编辑拼人品信息
     * @param $param
     */
    public function editPt($param)
    {
        try{
            $result =  $this->validate('PintuanValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                if($param['storeid']==2) {
                    $udata['pid'] = array('eq', $param['pid']);
                    $udata['id'] = array('neq', $param['id']);
                    $udata['is_custom'] = array('eq', 0);
                    Db::name('tuan_info')->where($udata)->update(['p_pic' => $param['p_pic'], 'pt_cover' => $param['pt_cover'], 'name' => $param['name'], 'p_name' => $param['p_name'], 'order_by' => $param['order_by'], 'prizeid' => $param['prizeid'], 'pt_rule' => $param['pt_rule'], 'pt_rule1' => $param['pt_rule1']]);
                }
                writelog(session('uid'),session('username'),'拼人品【'.$param['name'].'】编辑成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '编辑拼人品成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 根据拼人品id获取角色信息
     * @param $id
     */
    public function getOnePy($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 删除拼人品
     * @param $id
     */
    public function delPt($id)
    {
        try{

            $this->where('id', $id)->delete();
            writelog(session('uid'),session('username'),'删除拼人品成功(ID='.$id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除拼人品成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}