<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class PintuanModel extends Model
{
    protected $name = 'tuan_info';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    /**
     * 根据搜索条件获取拼团列表信息
     */
    public function getTuanByWhere($map, $Nowpage, $limits)
    {
        return $this->alias('pt')->where($map)->field('pt.*,branch.title,branch.sign')->join(['ims_bwk_branch' => 'branch'],'pt.storeid=branch.id')->page($Nowpage, $limits)->order('pt.id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的拼团数量
     * @param $where
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入拼团信息
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
                    writelog(session('uid'),session('username'),'拼团【'.$param['pt_name'].'】批量添加成功',1);
                    return ['code' => 1, 'data' => '', 'msg' => '添加拼团成功'];
                }catch (\Exception $e){
                    return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
                }
            }else{
                $result = $this->validate('PintuanValidate')->allowField(true)->save($param);
                if(false === $result){
                    return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
                }else{
                    writelog(session('uid'),session('username'),'拼团【'.$param['pt_name'].'】添加成功',1);
                    return ['code' => 1, 'data' => '', 'msg' => '添加拼团成功'];
                }
            }

        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑拼团信息
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
                    Db::name('tuan_info')->where($udata)->update(['p_pic' => $param['p_pic'], 'pt_cover' => $param['pt_cover'], 'pt_name' => $param['pt_name'], 'p_name' => $param['p_name'], 'order_by' => $param['order_by'], 'prizeid' => $param['prizeid'], 'pt_rule' => $param['pt_rule'], 'pt_rule1' => $param['pt_rule1']]);
                }
                writelog(session('uid'),session('username'),'拼团【'.$param['pt_name'].'】编辑成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '编辑拼团成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 根据拼团id获取角色信息
     * @param $id
     */
    public function getOnePy($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除拼团
     * @param $id
     */
    public function delPt($id)
    {
        try{

            $this->where('id', $id)->delete();
            writelog(session('uid'),session('username'),'删除拼团成功(ID='.$id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除拼团成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}