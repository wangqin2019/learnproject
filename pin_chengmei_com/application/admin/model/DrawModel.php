<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class DrawModel extends Model
{
    protected $name = 'draw';

    /**
     * 根据条件获取列表信息
     * @param $where
     * @param $Nowpage
     * @param $limits
     */
    public function getDrawAll()
    {
        return $this->order('orderby')->select();
    }

    public function getDrawAllByWhere()
    {
        return $this->alias('d')->field("d.*,count(l.id) count")->join('pt_lucky_draw l','d.id=l.draw_type','left')->group('d.draw_rank')->order('orderby')->select();
    }

    /**
     * 插入信息
     * @param $param
     */
    public function insertDraw($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){       
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加奖项成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑信息
     * @param $param
     */
    public function editDraw($param)
    {
        try{

            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);

            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑奖项成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据id获取一条信息
     * @param $id
     */
    public function getOneDraw($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除信息
     * @param $id
     */
    public function delDraw($id)
    {
        try{
            $this->where(['id' => $id])->delete();
            Db::name('draw_goods')->where('fid',$id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除奖项成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}