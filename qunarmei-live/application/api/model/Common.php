<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/26
 * Time: 10:11
 */

namespace app\api\model;

/**
 * 用于一些模块公用的方法
 *
 */
// use Endroid\QrCode\ErrorCorrectionLevel;
// use Endroid\QrCode\LabelAlignment;
// use Endroid\QrCode\QrCode;
use qrcode\QrcodeImg;
//use Endroid\QrCode\Response\QrCodeResponse;
//上传图片到七牛
use qiniu_transcoding\Upimg;
class Common
{
    /*
     * 功能:生成二维码
     * */
    public function makeQrCode($msg='',$filename='qrcode.png')
    {
        $qrcode = new QrcodeImg();
        $img_url = $qrcode->scerweima($msg,$filename);
        //上传七牛
        $img_url = $this->upQiniuImg($filename);
        return $img_url;
    }
    /*
     * 功能:上传图片到七牛
     * 请求:$img=>图片的名称
     * */
    public function upQiniuImg($img)
    {
        $upimg = new Upimg();
        $img = config('images.img_path').'/'.$img;
        $img_url = $upimg->upImg($img);
        return $img_url;
    }
}