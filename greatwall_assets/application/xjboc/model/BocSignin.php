<?php
namespace  app\xjboc\model;

use think\Model;

class BocSignin extends Model{

    //判断用户今日是否签到
    public function check($openid){
        $res = $this->where(['openid'=>$openid])->whereTime('addtime','today')->find();
        return $res;
    }

    //判断用户是否连续签到
    public function checkRunning($openid){
        $last_time = $this->where(['openid'=>$openid])->field('addtime,running_time')->find();
        $res = time()-$last_time['addtime'];
        if($res>=172800){
            return false;//不是连续签到
        }else{
            return $last_time['running_time']; //是连续签到
        }

    }


   


}