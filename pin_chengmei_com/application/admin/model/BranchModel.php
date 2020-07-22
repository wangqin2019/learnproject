<?php

namespace app\admin\model;
use think\exception\PDOException;
use think\Model;
use think\Db;

class BranchModel extends Model
{

    /**
     * 根据搜索条件获取拼团列表信息
     */
    public function getBranchByWhere($map, $Nowpage, $limits)
    {
        return Db::table('ims_bwk_branch')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    /**
     * 获取全部门店信息
     */
    public function getAllBranch()
    {
        $map['isshow']=array('eq',1);
        return Db::table('ims_bwk_branch')->where($map)->field('id,title,sign')->order('id desc')->select();
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
            $result = $this->validate('PintuanValidate')->allowField(true)->save($param);
            if(false === $result){            
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'拼团【'.$param['p_name'].'】添加成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '添加拼团成功'];
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
                writelog(session('uid'),session('username'),'拼团【'.$param['p_name'].'】编辑成功',1);
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
    public function delUser($id)
    {
        try{

            $this->where('id', $id)->delete();
            writelog(session('uid'),session('username'),'拼团【'.session('username').'】删除拼团成功(ID='.$id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除拼团成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * Commit: 设置门店是否参与砍价活动
     * Function: setBargainState
     * @param $id
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 09:49:34
     * @return array|\think\response\Json
     */
    public function setBargainState($id){
        try{
            $status = Db::table('ims_bwk_branch')->where('id',$id)->value('is_bargain');//判断当前状态情况
            if($status == 1){
                Db::table('ims_bwk_branch')->where('id',$id)->setField(['is_bargain'=>0]);
                return json(['code' => 1, 'data' => '', 'msg' => '已取消参与']);
            }else{
                Db::table('ims_bwk_branch')->where('id',$id)->setField(['is_bargain'=>1]);
                return json(['code' => 0, 'data' => '', 'msg' => '参与成功']);
            }
        }catch (PDOException $e){
            return ['code' => 1, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}