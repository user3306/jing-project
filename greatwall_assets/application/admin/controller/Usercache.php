<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2018/5/27
 * Time: 20:37
 */

namespace app\admin\controller;

use think\Db;

class Usercache extends Admin
{
    public function index()
    {
        return $this->fetch();
    }

    public function updateudata()
    {
        $redis = new \Redis;
        $redis->pconnect('127.0.0.1',6379);
        $redis->select(1);
        $uinfo = Db::table('rn_xzy_wxuser')->field('*')->select();
        $count = 0;
        foreach ($uinfo as $user)
        {
            $user['remark']= $user['realname'].'#'.$user['sex'].'#'.$user['mobile'];
            $redis->set($user['openid'],json_encode($user));
            $count++;
        }
        echo '合计共更新：'.$count.'条数据。';
    }

    public function updateumobile()
    {
        $redis = new \Redis;
        $redis->pconnect('127.0.0.1',6379);
        $redis->select(2);
        $uinfo = Db::table('rn_xzy_wxuser')->field('openid,mobile')->select();
        $count = 0;
        foreach ($uinfo as $ukey=>$value)
        {
            $redis->set($value['mobile'],$value['openid']);
            $count++;
        }
        echo '合计共更新：'.$count.'条数据。';
    }
}