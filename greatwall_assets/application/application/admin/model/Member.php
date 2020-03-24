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
	public function getMemberList($where = array(), $limit = 0,$searchinfo = array()){
		return $this->name('member2019')
                    ->alias('m')
                    ->join('admin_user u','m.userid = u.username','left')
                    ->join('admin_group g','m.job=g.group_id','left')
                    ->where($where)
                    ->field('m.id,m.username,m.openid,m.mobile,m.office_mobile,g.name as job_info,m.belong_to_department,m.department_head,u.last_login_time as login_time,m.is_disabled,m.disabled_time,m.is_del')->paginate($limit,false,['query'=>$searchinfo]);
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
		return $this->name('member2019')
                    ->alias('m')
                    ->join('admin_user u','m.userid=u.username','left')
                    ->join('admin_group g','u.group_id=g.group_id','left')
                    ->where($where)
                    ->field('m.id,m.username,m.mobile,m.office_mobile,u.name as job_info,m.belong_to_department,m.department_head,m.login_time,m.is_disabled,m.disabled_time,m.is_del')->count();
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
        $where['id']=$id;
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


    /**
     * 删除授权关系信息
     * @param string $[openid] [<description>]
     * @return  bool [<description>]
     */
    public function delInfo($openid){
        $map = array();
        $map['cli_openid'] = $openid;
        return $this->name('entryinfo2019')->where($map)->delete();
    }
}
?>