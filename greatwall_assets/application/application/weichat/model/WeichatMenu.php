<?php
namespace app\weichat\model;
use think\Model;

/**
 * Class Category 栏目基础信息模型
 * dingzq hatabc@qq.com
 */
class WeichatMenu extends Model
{
    /**
     * 栏目列表
     * @param 条件 $where
     * @param 栏目id $menu_id
     * @return 数组
     */
    public function loadList($where = array(), $menu_id=0){
        $data=$this->loadData($where);
        $cat = new \org\Category(array('menu_id', 'parent_id', 'name', 'cname'));
        $data = $cat->getTree($data, intval($menu_id));
        return $data;
    }
    /**
     * 栏目数据
     * @param 条件 $where
     * @param 显示数量 $limit
     * @return 数组
     */
    public function loadData($where = array(), $limit = 0){
        if (get_weichat_id()){
            $where['weichat_id']=get_weichat_id();
        }
        $pageList=$this->where($where)->order('sort ASC , menu_id ASC')->limit($limit)->select();
        return $pageList;
    }
    /**
     * 获取信息
     * @param int $classId ID
     * @return array 信息
     */
    public function getInfo($classId){
        $map = array();
        $map['menu_id'] = $classId;
        return $this->getWhereInfo($map);
    }
    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function getWhereInfo($where){
        $info = $this->where($where)->find();
        return $info;
    }

    public function getCount($where=array()){
        return $this->where($where)->count();
    }
    /**
     * 新增
     * @return  栏目id menu_id |false
     */
    public function add(){
        $_POST['weichat_id']=get_weichat_id();
        if ($this->allowField(true)->save($_POST)>0){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 修改
     * @return true|false
     */
    public function edit(){
        $menu_id=input('post.menu_id');
        if(empty($menu_id)){
            return false;
        }
        $status = $this->allowField(true)->save($_POST,array('menu_id'=>$menu_id));
        if($status === false){
            return false;
        }
        return true;
    }
    /**
     * 删除
     * @param 栏目id $menu_id
     * @return 1|0
     */
    public function del($menu_id){
        $map = array();
        $map['menu_id'] = $menu_id;
        return $this->where($map)->delete();
    }
    /**
     * 获取菜单面包屑
     * @param int $classId 菜单ID
     * @return array 菜单表列表
     */
    public function loadCrumb($classId)
    {
        $data = $this->loadData();
        $cat = new \org\Category(array('menu_id', 'parent_id', 'name', 'cname'));
        $data = $cat->getPath($data, $classId);
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $data[$key] = $value;
                $data[$key]['url'] = getUrl($value);
            }
        }
        return $data;
    }
}
