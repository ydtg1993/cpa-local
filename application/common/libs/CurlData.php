<?php


namespace app\common\libs;


class CurlData
{
    const SIGN_KEY  = 'W_aHv_Xm5Zpb_m76ss_HqAzczanu_E5kR';
    public function curl_post($qqc_server_url,$send_data)
    {
        $ch = curl_init();//初使化init方法
        curl_setopt($ch, CURLOPT_URL, $qqc_server_url); //指定URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设定请求后返回结果
        curl_setopt($ch, CURLOPT_POST, 1);//声明使用POST方式来进行发送
        curl_setopt($ch, CURLOPT_POSTFIELDS,$send_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('application/x-www-data-urlencoded'));//设置post数据
        //忽略证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);//忽略header头信息
        curl_setopt($ch, CURLOPT_TIMEOUT, 90); //设置超时时间
        $output = curl_exec($ch);//发送请求
        curl_close($ch);//关闭curl
        return $output;//返回数据
    }

    public function createSign($paramArr)
    {
        if(isset($paramArr['sign'])){
            unset($paramArr['sign']);
        }
        ksort($paramArr);
        $sign='';
        foreach ($paramArr as $key => $val) {
            if ($key != '' && $val != '') {
                $sign .= $key."=".$val."&";
            }
        }
        $sign=rtrim($sign,"&");
        $apiKey = '&userKey='.self::SIGN_KEY;
        $sign.=$apiKey;
        $sign = md5($sign);
        return $sign;
    }
}