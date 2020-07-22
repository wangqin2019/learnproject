<?php

/**
 * @param int $length
 * @return string
 *
 * 生成唯一字符串
 */
function createToken()
{
    $str = md5(uniqid(md5(microtime(true)),true));  //生成一个不会重复的字符串
    $str = sha1($str);  //加密
    return $str;
}


/*
 * 发送post请求
 */
function sendPost($postUrl,$data)
{
    $postData = $data;
    $postData = http_build_query($postData);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $postUrl);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'application/x-www-form-urlencoded;charset=UTF-8',
        'Content-Length: ' . strlen($postData)
    ));
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    $r = curl_exec($curl);
    curl_close($curl);
    return $r;
}
