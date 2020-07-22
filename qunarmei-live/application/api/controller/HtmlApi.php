<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/14
 * Time: 15:32
 */

namespace app\api\controller\v4;

use app\api\controller\v3\Common;
use app\api\service\HtmlApiService;

header('Access-Control-Allow-Origin:*');

/**
 * H5相关接口设计
 * Class HtmlApi
 * @package app\api\controller\v4
 */
class HtmlApi extends Common
{
    /**
     * 验证码校验
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @param string $id 直播间id
     * @return array
     */
    public function codeCheck()
    {
        $mobile = input('mobile');
        $code = input('code');
        $live_id = input('id');
        $htmlser = new HtmlApiService();
        $res = $htmlser->codeCheck($mobile,$code,$live_id);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 手机号验证
     * @param string $mobile 手机号
     * @return array
     */
    public function mobileCheck()
    {
        $mobile = input('mobile');
        $live_id = input('id');

        $htmlser = new HtmlApiService();
        $res = $htmlser->mobileCheck($mobile,$live_id);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}