<?php

namespace app\admin\model;


use think\Model;

class GameWheelRule extends Model{

    /**
     * 增加一条规则信息
     */
    public function addRule($data){

        return $this->allowField(true)->save($data);
    }

    public function updateStatus(){
        return $this->where('status',1)->update(['status'=>0]);
    }

    public function getInfo($time){
        return $this->where('endtime','>',$time)->find();
    }


}