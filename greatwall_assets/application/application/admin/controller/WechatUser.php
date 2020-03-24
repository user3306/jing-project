<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2017-12-12
 * Time: 16:33
 */

namespace app\admin\controller;
use com\snrunning\Wechat;
use think\facade\Cache;

class WechatUser extends Admin
{
    public function index()
    {
        if(Cache::has('WechatUserlist'))
            $wechatUserList=Cache::get('WechatUserlist');
        else
        {
            $model = model('admin/TxlAccessToken','service');
            $options = array(
                'token'=>$model->token()
            );
            $wechat = new Wechat($options);
            $wechatUserList = $wechat->getUserList(1,1);
            Cache::set('WechatUserlist',$wechatUserList,600);
        }
        $arr = array('dingzq','yangshengli','lixusheng','lidingjiao','liudongwei');
        $result = array();
        foreach ($wechatUserList['userlist'] as $key=>$value)
        {
            if(in_array($value['userid'],$arr))
            {
                $result[$value['userid']] = $value['name'];
            }
        }
        //dump(array_search('dingzq',$wechatUserList['userlist']));
        dump($result);
    }
}