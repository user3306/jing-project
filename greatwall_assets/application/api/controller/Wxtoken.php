<?php
namespace app\Api\controller;
use think\Controller;
use think\Db;

class Wxtoken extends Controller{
    
    /**
     * 获取access_token
     * @Author   dingzq
     * @DateTime 2018-08-15
     * @return   [type]     [description]
     */
    public function accesstoken(){

        $wx_option = get_weichat_options();

        $result = http_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wx_option['appid'].'&secret='.$wx_option['appsecret']);

        //dump($result);
        if (!empty($result['content']))
        {
            $json = json_decode($result['content'],true);
            if (!$json || empty($json['access_token'])) {
                return false;
            }
            $access_token = $json['access_token'];

            $arr = array();
            $arr['token'] = $access_token;
            $arr['expired'] = time()+3600;
            $cachename = 'wechat_access_token'.$wx_option['appid'];
            file_put_contents(dirname($_SERVER['DOCUMENT_ROOT']).'/'.$cachename.'.json', json_encode($arr));

            return $access_token;
        }
    }


    public function jsapitoken(){
        $wx_option = get_weichat_options();
        //先得到Access_token
        $accesstoken = '';
        $cachename = 'wechat_access_token'.$wx_option['appid'];
        $filename = dirname($_SERVER['DOCUMENT_ROOT']).'/'.$cachename.'.json';
        if(!file_exists($filename))
            return false;
        else{
            $json = file_get_contents($filename);
            $arr = json_decode($json,true);
            if(time()>$arr['expired'])
                return false;
            else {
                $accesstoken = $arr['token'];
            }
        }

        $result = http_get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accesstoken.'&type=jsapi');
        //dump($result);
        //dump($result);
        if (!empty($result['content']))
        {
            $json = json_decode($result['content'],true);
            //dump($json);
            if (!$json || empty($json['ticket'])) {
                //echo $json['errcode'];
                //echo $json['errmsg'];
                return false;
            }
            $jsapi_token = $json['ticket'];

            $arr = array();
            $arr['token'] = $jsapi_token;
            $arr['expired'] = time()+3600;
            $cachename = 'wechat_jsapi_ticket'.$wx_option['appid'];
            file_put_contents(dirname($_SERVER['DOCUMENT_ROOT']).'/'.$cachename.'.json', json_encode($arr));

            return $jsapi_token;
        }
    }
    
}
