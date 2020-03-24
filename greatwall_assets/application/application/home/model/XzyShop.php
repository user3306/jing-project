<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2018-2-27
 * Time: 10:53
 */

namespace app\home\model;


use think\Model;

class XzyShop extends Model
{
    /**
     * 用户列表
     * @param 条件 $where
     * @param 用户id $user_id
     * @return 数组
     */
    public function loadList($where = array()){
        $where[] = ['status','=',1];
        $data = $this->name('xzy_shop')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
    }

    public function getInfo($id)
    {
        $where[] = ['id','=',$id];
        $data = $this->name('xzy_shop')
            ->field('*')
            ->where($where)
            ->find();
        return $data;
    }
}