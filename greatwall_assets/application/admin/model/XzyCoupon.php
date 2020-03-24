<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2018/4/16
 * Time: 21:28
 */

namespace app\admin\model;


use think\Model;

class XzyCoupon extends Model
{
    /**
     * 获取列表
     * @return array 列表
     */
    public function loadList($where = array(), $limit = 0){
        $data = $this->name('xzy_coupon')
            ->field('*')
            ->where($where)
            ->paginate($limit,false,['query'=>request()->param() ]);
        return $data;
    }


    /**
     * 获取信息
     * @param int $userId ID
     * @return array 信息
     */
    public function getInfo($id = 1){
        $map = array();
        $map[] = ['id','=',$id];
        return $this->getWhereInfo($map);
    }


    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function getWhereInfo($where){
        return $this->name('xzy_coupon')
            ->field('*')
            ->where($where)
            ->find();
    }

    /**
     * 获取card_id
     */
    public function getCard_id($id){
        if(!$id){
            return false;
        }else{
            return $this->where('id',$id)->value('card_id');
        }
    }

    /**
     * 新增
     */
    public function add(){

        return $this->allowField(true)->save($_POST);
    }
    /**
     * 更新
     */
    public function edit(){
        if (empty(input('post.id'))){
            return false;
        }

        $where[]=['id','=',input('post.id')];
        return $this->allowField(true)->save($_POST,$where);
    }

    public  function del($id)
    {
        $map = array();
        $map[] = ['id','=',$id];
        return $this->where($map)->delete();
    }
}