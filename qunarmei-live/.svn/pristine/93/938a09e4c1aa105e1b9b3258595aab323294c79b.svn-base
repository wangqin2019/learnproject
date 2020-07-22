<?php

namespace app\admin\controller;
use qiniu_transcoding\QnFile;
use think\Controller;
use think\File;
use think\Request;

class Upload extends Base
{
	//图片上传
    public function upload(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/images');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    //会员头像上传
    public function uploadface(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/face');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    /**
     * 文件上传至七牛云
     * @param  上传文件
     * @return string json 图片地址
     */
    public function uploadFile()
    {
        $arr['code'] = 1;
        $arr['msg'] = '上传文件失败';

        $file = request()->file('file');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/file');
        $path = ROOT_PATH.'public/uploads/file/'.$info->getSaveName();

        $qiniuSer = new QnFile();

        $res = $qiniuSer->uploadFile($path);

        if($res){
            $arr['code'] = 0;
            $arr['msg'] = '上传文件成功';
            $arr['data']['src'] = $res;
            return json($arr);
        }
        return $path;
    }

}