<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/21
 * Time: 13:12
 */

namespace app\api\controller\v3;

use think\Loader;
use app\api\model\Common;
set_time_limit(0);
class Other
{
    /*
     * 功能:测试生成pdf文件
    */
    public function index()
    {
        $html = $this->xt1();
        $rest = $this->makeMpdf($html);
        return $rest;
    }

    function pdf($html='<h1 style="color:red">hello word</h1>'){
        $html = $this->xt1();
        Loader::import('tcpdf.tcpdf');
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // 设置打印模式
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 001');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        // 是否显示页眉
        $pdf->setPrintHeader(false);
        // 设置页眉显示的内容
        $pdf->SetHeaderData('logo.png', 60, 'baijunyao.com', '白俊遥博客', array(0,64,255), array(0,64,128));
        // 设置页眉字体
        $pdf->setHeaderFont(Array('dejavusans', '', '12'));
        // 页眉距离顶部的距离
        $pdf->SetHeaderMargin('5');
        // 是否显示页脚
        $pdf->setPrintFooter(true);
        // 设置页脚显示的内容
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        // 设置页脚的字体
        $pdf->setFooterFont(Array('dejavusans', '', '10'));
        // 设置页脚距离底部的距离
        $pdf->SetFooterMargin('10');
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // 设置行高
        $pdf->setCellHeightRatio(1);
        // 设置左、上、右的间距
        $pdf->SetMargins('10', '10', '10');
        // 设置是否自动分页  距离底部多少距离时分页
        $pdf->SetAutoPageBreak(TRUE, '15');
        // 设置图像比例因子
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
//        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
//            require_once(dirname(__FILE__).'/lang/eng.php');
//            $pdf->setLanguageArray($l);
//        }
        $pdf->setFontSubsetting(true);
        $pdf->AddPage();
        // 设置字体
        $pdf->SetFont('stsongstdlight', '', 14, '', true);
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->Output('example_001.pdf', 'D');
    }
    public function xt1()
    {
        $rest = <<<EOF
        <!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<META HTTP-EQUIV="pragma" CONTENT="no-cache">
	<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache, must-revalidate">
	<META HTTP-EQUIV="expires" CONTENT="0">
	<title>形体分</title>
	<style type="text/css">
		.body{
			font-family: "PingFangSC";

		}
		.round_icon{
		  width: 32px;
		  /* height: 32px; */
		  /* display: flex; */
		  border-radius: 50%;
		  /* align-items: center; */
		  /* justify-content: center; */
		  /* overflow: hidden; */
		  float: left;
		  margin-left: 16pt;
		}
		.username{
			float: left;
			align: center;
			line-height: 32px;
		}
		.score{
			float: right;
			margin-right: 42pt;
		}
		.header{
			background-color: #ED3072;
			color: #FFFFFF;
		}
		.tips{
			clear: both;
			margin-left: 16pt;
			margin-top: 50pt;

		}
		.middle{
			/* background-color: #F3F3F3; */
			color: #363641;
			margin-bottom: 50px;
			text-align:center;
		}
		.dt{
			float: left;
			margin-left: 16pt;
		}
		.mrs{
			float: right;
			margin-right: 42pt;
		}
		.footer{
			margin-top: 10px;
			clear: both;
			margin-left: 16pt;
		}
		.pro_name{
			float: left;
		}
		.pro_val{
			/* float: right;
			margin-right: 42pt; */
			color: #E82F6F;
		}
		.bor{
			background-color: red;
		}
		li{
			list-style-type :none;
			width: 100%;
			margin-bottom: 15pt;
		}
		.flag_pg{

			background-color: #E82F6F;
			opacity:0.7;
			border-radius: 30%;
		}
		.flag_zc{

			background-color: #35D49B;
			opacity:0.7;
			border-radius: 30%;
		}
		.flag_pd{

			background-color: #EEB436;
			opacity:0.7;
			border-radius: 30%;
		}
		.zk{
			width: 16rem;
		}
	</style>
</head>
<body>
<div align="center">形体分</div>
<div class="body">
	<div class="header">
		<h3 align="center">2018年9月20号</h3>
		<div><span><img src="http://thirdwx.qlogo.cn/mmopen/GA7e1icawUib7v1dzcpFWhu5zJib7FDbYk9UyhkdZagAyc7GLQBRxhNuOAz7KH1VO0GlgPU81icLWdPiacORqh7uyzy9rzlxd7cqH/132" alt="" class="round_icon"></span><span class="username">用户名</span> <span class="score">88分</span></div>
		<div class="tips"><p>形体保持的不错哦~,请继续加油!!!</p></div>
	</div>
	<div class="middle">
		<table border="1" cellspacing="0" cellpadding="0" >
			<tr><th>2018年9月20号记录</th><th class="zk"></th><th>美容师:邵军</th></tr>
			<tr><td>异常项</td><td class="zk"></td><td class="pro_val">3</td></tr>
			<tr><td>BB</td><td><span class="flag_pg">偏高</span></td><td>28cm</td></tr>
			<tr><td>右BP</td><td><span class="flag_zc">正常</span></td><td>36cm</td></tr>
			<tr><td>左BP</td><td><span class="flag_pd">偏低</span></td><td>36cm</td></tr>
		</table>
	</div>
</div>
</body>
</html>
EOF;
    return $rest;

    }
    /*
     * 功能:生成pdf文件
     * 请求:$html=>网页html,$file_name=>文件名称
     * */
    public function makeMpdf($html,$file_name='')
    {
        Loader::import('Mpdf.Mpdf');

        $mpdf = new \Mpdf\Mpdf();
        //设置pdf显示方式
        $mpdf->SetDisplayMode('fullwidth');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);
        $filename= iconv("utf-8","gb2312",$file_name.".pdf");
        $path="./csv/".$filename;
        $mpdf->Output($path,'f'); //保存至当前file文件夹下
        return '1';
    }
    /*
     * 功能:生成二维码
     * 请求:$msg=>二维码内容
     * */
    public function makeQrcode($msg='')
    {
        $msg = input('msg','生成二维码测试');
        $commod = new Common();
        $res = $commod->makeQrCode($msg);
        return $res;
    }
    /*
     * 功能:生成二维码
     * 请求:$msg=>二维码内容
     * */
    public function makeErweima($msg='')
    {
        // Loader::import('Endroid.QrCode.QrCode');
        Loader::import('Endroid.QrCode.QrCode',VENDOR_PATH,'.php');
        $qrcode = new QrCode();
        echo "<pre>";;print_r($qrcode);die;
        $msg = input('msg','生成二维码测试');
        $commod = new Common();
        $res = $commod->makeQrCode($msg);
        return $res;
    }
}