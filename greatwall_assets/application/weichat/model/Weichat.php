<?php
namespace app\weichat\model;
use think\Model;
/**
 * 用户操作
 */
class Weichat extends Model {
    /**
     * 获取列表
     * @return array 列表
     */
    public function loadList($where = array(), $limit = 0){
        $data = $this->where($where)->paginate($limit);
        return $data;
    }
    /**
     * 获取数量
     * @return int 数量
     */
    public function countList($where = array()){
        return $this->where($where)->count();
    }
    /**
     * 获取信息
     * @param int $weichatId ID
     * @return array 信息
     */
    public function getInfo($weichatId = 1){
        $map = array();
        $map['weichat_id'] = $weichatId;
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
     * 新增
     */
    public function add(){
        return $this->allowField(true)->save($_POST);
    }
    /**
     * 更新
     */
    public function edit(){
        $where['weichat_id']=input('post.weichat_id');
        return $this->allowField(true)->save($_POST,$where);
    }
    /**
     * 删除信息
     * @param int $weichatId ID
     * @return bool 删除状态
     */
    public function del($weichatId){
        $map = array();
        $map['weichat_id'] = $weichatId;
        return $this->where($map)->delete();
    }
}
