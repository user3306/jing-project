<?php
namespace app\admin\model;

use think\Model;

class PfaultTemplateList extends Model{

    public function addAll($data){
        return $this->allowField(true)->saveAll($data);
    }


    public function getList(){
        return $this->order('id desc')->select();
    }


}