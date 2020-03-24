<?php
namespace app\plug\model;
use think\Model;

/**
 * 用户组操作
 */
class Plugin extends Model {
    /**
     *
     * @param array $where
     * @param int $limit
     * @param string $field
     * @return false|static[]
     */
    public function allList($where=array(),$limit=0,$field='*'){
        $data = $this->all(function ($query) use ($field,$where,$limit){
            $query->field($field)->where($where)->limit($limit);
        });
        return $data;
    }
    /**
     * 获取分页列表
     * @return array 列表
     */
    public function loadList($where=array(),$limit=0,$field='*'){
        $data   = $this->field($field)->where($where)->order('`sort` asc')->paginate($limit);
        return $data;
    }

    /**
     * 把id作为key的分类数据
     * @param array $where
     * @param string $column
     * @return array
     */
    public function loadKeyList($where=array(),$column='id'){
        $brr = $this->where($where)->column($column);
        return $brr;
    }

    /**
     * 统计数量
     */
    public function getSum($where=array()){
        return $this->where($where)->count();
    }

    /**
     * 获取信息
     * @param int $id ID
     * @return array 信息
     */
    public function getInfo($id = 1){
        $map = array();
        $map['id'] = $id;
        return $this->where($map)->find();
    }
    /**
     * 获取where数据
     * @param 条件$where
     * @return 一维数组
     */
    public function getWhereInfo($where){
        $info = $this->where($where)->find();
        return $info;
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
    public function edit($data,$where){
        return $this->allowField(true)->save($data,$where);
    }

    /**
     * 删除信息
     * @param int $id ID
     * @return bool 删除状态
     */
    public function del($id){
        $map = array();
        $map['id'] = $id;
        return $this->where($map)->delete();
    }

}
