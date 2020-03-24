<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2018-2-26
 * Time: 14:05
 */

namespace app\admin\model;


use think\Model;

class XzyMessage extends Model
{
    /**
     * 获取列表
     * @return array 列表
     */
    public function loadList($where = array(), $limit = 0){
        $data = $this->name('xzy_message')
            ->field('*')
            ->where($where)
            ->order('id','desc')
            ->paginate($limit);
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
        return $this->name('xzy_message')
            ->field('*')
            ->where($where)
            ->find();
    }

    /**
     * 新增
     */
    public function add(){
        $_POST['posttime']=time();
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