<?php

namespace app\admin\model;

use think\Model;

class Pfault extends Model{

    public function add($data){
        return $this->allowField(true)->save($data);

    }
    public function edit($data,$id){
        return $this->allowField(true)->save($data,['id'=>$id]);
    }

    public function getInfo($id){
        return $this->where('id',$id)->find();
    }
    

}