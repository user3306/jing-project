<?php

namespace app\admin\model;

use think\Model;

class PfaultInfo extends Model{

    //把选择的用户添加到数据表里
    public function addSelect($data){
        return $this->allowField(true)->save($data);

    }

    //查找这条模板消息是否已被筛选
     public function getInfo($pfault_id){
        return $this->where('pfault_id',$pfault_id)->find();
     }


}