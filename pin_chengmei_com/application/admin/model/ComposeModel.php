<?php

namespace app\admin\model;
use think\exception\PDOException;
use think\Model;
use think\Db;

class ComposeModel extends Model
{
    protected $name = 'compose';

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
        $list= $this->alias('c')
            ->field('c.id,g.name,c.pid,c.cids,c.create_time,c.status')
            ->join('goods g','c.pid=g.id','left')
            ->where($map)
            ->page($Nowpage,$limits)
            ->order('g.id desc')
            ->select();
        if($list){
            foreach ($list as $k=>$v){
                $c_name=Db::name('goods')->where('id','in',$v['cids'])->column('name');
                $list[$k]['c_name']=implode('<br/>',$c_name);
            }
        }
        return $list;
    }



    /**
     * 根据条件获取数量
     * @param $where
     */
    public function getCount($map)
    {
        return $this->alias('c')->join('goods g','c.pid=g.id','left')->where($map)->count();
    }

    /**
     * 插入信息
     * @param $param
     */
    public function insertCompose($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '产品组合关联成功'];
            }
        }catch(\PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑信息
     * @param $param
     */
    public function updateCompose($param)
    {
        try{
            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '产品组合编辑成功'];
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
    public function delCompose($id)
    {
        try{
            $this->where('id',$id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除关联组合成功'];
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}