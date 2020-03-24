<?php
namespace app\weichat\model;
use think\Model;
/**
 * 用户操作
 */
class WeichatMaterialVideo extends Model {
    /**
     * 获取所有数据
     */
    public function allList($where=array(),$limit=0,$field='*'){
        $data = $this->all(function ($query) use ($field,$where,$limit){
            $query->field($field)->where($where)->limit($limit);
        });
        return $data;
    }
    /**
     * 获取列表
     * @return array 列表
     */
    public function loadList($where = array(), $limit = 0){
        $data = $this->where($where)->order('add_time DESC')->paginate($limit);
        return $data;
    }
    
    /**
     * 获取数量
     * @return int 数量
     */
    public function countList($where = array()){
        return $this->where($where)->count();
    }
    //新增数据
    public function add(){
        $_POST['add_time']=time();
        return $this->allowField(true)->save($_POST);
    }
    /**
     * 更新
     */
    public function edit(){
        $where['material_id']=input('post.material_id');
        return $this->allowField(true)->save($_POST,$where);
    }
    /**
     * 获取信息
     * @param int $materialId ID
     * @return array 信息
     */
    public function getInfo($materialId = 1){
        $map = array();
        $map['material_id'] = $materialId;
        return $this->getWhereInfo($map);
    }
    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function getWhereInfo($where){
        return $this->where($where)->find();
    }
    /**
     * 删除信息
     * @param int $materialId ID
     * @return bool 删除状态
     */
    public function del($materialId){
        $map = array();
        $map['material_id'] = $materialId;
        return $this->where($map)->delete();
    }
}
