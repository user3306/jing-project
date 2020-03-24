<?php
namespace  app\xjboc\model;
use think\Model;

class Content extends Model{

    /**
     * 获取一条信息
     */
    public function getOne($where){
//        $info = $info = $this->name("content")
//            ->alias('A')
//            ->join('category C','A.class_id = C.id')
//            ->field('A.*,C.name as class_name,C.app,C.urlname as class_urlname,C.image as class_image')
//            ->where($where)
//            ->find();
        $info = $this->where($where)->find();
        if (empty($info)){
            return false;
        }
        return $info;

    }



}