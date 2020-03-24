<?php
/**
 * 用户模型
 * @author caizhuan 
 * @date:20190417
 */

namespace app\admin\model;
use think\Model;

class Member extends Model{
	//获取用户列表
	public function getMemberList($where = array(), $limit = 0){
		return $this->name('member2019')->where($where)->paginate($limit);
	}


    /**
     * 获取部门负责人下的员工
     */
    public function getAllMember($where = array(), $limit = 0){
        return $this->name('member2019')->where($where)->select();
    }


	/**
	 * 获取用户总数
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getMemberTotal($where = array()){
		return $this->name('member2019')->where($where)->count();
	}


	/**
	 * 获取员工信息
	 * @param  intval $[id] [<description>]
	 */
	public function getMemberInfo($id){
		$map = array();
        $map['id'] = $id;
        return $this->name('member2019')->where($map)->find();
	}


	/**
	 * 新增
	 * @param int $id ID
     * @return bool 新增状态
	 */
    public function add($arr = array()){
        return $this->name('member2019')->insert($arr);
    }


    /**
     * 更新
     * @param int $id ID
     * @return bool 更新状态
     */
    public function edit($arr = array(),$id){
        $where['id']=input('post.id');
        return $this->name('member2019')->where($where)->update($arr);
    }


	/**
     * 删除信息
     * @param int $id ID
     * @return bool 删除状态
     */
    public function del($id)
    {
        $map = array();
        $map['id'] = $id;
        return $this->name('member2019')->where($map)->delete();
    }
}
?>