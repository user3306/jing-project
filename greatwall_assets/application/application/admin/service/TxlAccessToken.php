<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2017-12-6
 * Time: 10:34
 */

namespace app\admin\service;
use think\facade\Cache;

class TxlAccessToken
{


    public function token()
    {
        $config = load_config('admin/config');
        $corpid=$config['Corpid'];
        $secret=$config['TXL_Secret'];
        if(Cache::has('txlToken')&&Cache::get('txlToken')['expire_time']>time())
        {
            //直接返回token
            return Cache::get('txlToken')['access_token'];
        }
        else
        {
            $url = $config['Token_Url']."?corpid=$corpid&corpsecret=$secret";
            $res = json_decode(http_get($url)["content"]);
            $tokenArr = (array)$res;
            if (!$tokenArr || $tokenArr['errcode']!=0) {
                return false;
            }
            $tokenArr['expire_time']=time()+$tokenArr['expires_in'];
            Cache::set('txlToken',$tokenArr,3600);
            return $tokenArr['access_token'];
        }
        //return $config['TXL_Secret'];
    }
}