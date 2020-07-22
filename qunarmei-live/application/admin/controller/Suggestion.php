<?php

namespace app\admin\controller;
use think\Db;

/* 用户app建议反馈表
 * */
class Suggestion extends Base
{

    /**
     * 功能: 建议列表
     * 请求: key 建议搜索
     * 返回:
     */
    public function index(){

        $key = input('key');
        $map = ''; $map1='';
        if($key&&$key!=="")
        {
            $map = " and l.content like '%$key%' ";
            $map1 = "  l.content like '%$key%' ";
        }
        // start Modify by wangqin 2017-12-05

        $lists = Db::table('ims_bj_shopn_suggestion l,ims_bj_shopn_member mem,ims_bwk_branch ibb')->where('mem.mobile=l.mobile and ibb.id=mem.storeid '.$map )->field('l.*,mem.realname,ibb.title')->select();
        foreach($lists as &$v)
        {
            if($v['img_path'])
            {
                $v['img_path'] = json_decode($v['img_path']);
            }
            $v['result'] =  $v['result']==''?'':$v['result'];
            $v['updatetime'] =  $v['updatetime']==''?'':$v['updatetime'];
            if($v['status'] == 1)
            {
                $v['status'] = '已处理';
            }else
            {
                $v['status'] = '未处理';
            }
        }
//        print_r($lists);
        $p = new Page($lists,30,$key);
        //把分页后的对象$p渲染到模板
        $this->assign([
            'p' => $p,
        ]);
        // echo "<pre>";print_r($p->data);
        $this->assign('val', $key);
        $this->assign('lists', $p->data);

        // end Modify by wangqin 2017-12-05
        return $this->fetch();
    }

    /**
     * [sugEdit 处理结果]
     * @return
     * @author
     */
    public function sugEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){

            $param = input('post.');
            $data = array('result' => $param['result'],'status' => 1,'updatetime' => date('Y-m-d H:i:s'));
            $ret = Db::table('ims_bj_shopn_suggestion')->where('id', $id)->update($data);
            $flag = array('code'=>1,'data'=>'','msg'=>'修改成功');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $list = Db::table('ims_bj_shopn_suggestion')->field('id,result')->limit(1)->where(array('id'=>$id))->select();
        $this->assign('list',$list);

        return $this->fetch();
    }


    /**
     * [sugDel 删除]
     * @return
     * @author
     */
    public function sugDel()
    {
        $id = input('param.id');
        $rest = Db::table('ims_bj_shopn_suggestion')->where('id',$id)->delete();
        return $this->returnMsg(1,'','删除成功');
    }

    //返回json数据
    public function returnMsg($code=1,$data='',$msg='')
    {
        $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($ret);
    }

}