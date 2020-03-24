<?php
namespace  app\xjboc\model;
use think\Model;
class Wxuser extends Model{

    //检查用户是否已经绑定手机号码
    public function checkMob($openid){
        $res = $this->where(['openid'=>$openid])->value('mobile');
        return $res;

    }



}