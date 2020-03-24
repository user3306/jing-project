<?php
/**
 * 客户模型
 * @author caizhuan 
 * @date:20190422
 */

namespace app\admin\model;
use think\Model;

class Customer extends Model{
	//获取用户列表
	public function getCustomerList($where = array(), $limit = 0){
        return $this->name('entryinfo2019')
             ->alias('s')
             ->join('bingdinginfo2019 u','s.unionid=u.unionid','left')
             ->join('member2019 m','u.userid=m.userid','left')
             ->where($where)
             ->order('s.id desc')//根据id降序排列
             ->field(['s.id','s.cli_openid','s.cli_mobile','s.cli_name','s.cli_company','s.collectNum','s.viewNum','m.mobile mobiles'])
             ->paginate($limit);
	}


	/**
	 * 获取用户总数
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getCustomerTotal($where = array()){
		return $this->name('entryinfo2019')->where($where)->count();
	}


	/**
	 * 获取员工信息
	 * @param  intval $[id] [<description>]
	 */
	public function getCustomerInfo($id){
		$map = array();
        $map['id'] = $id;
        return $this->name('entryinfo2019')->where($map)->find();
	}


	/**
	 * 新增
	 * @param int $id ID
     * @return bool 新增状态
	 */
    public function add($arr = array()){
        return $this->name('entryinfo2019')->insert($arr);
    }


    /**
     * 更新
     * @param int $id ID
     * @return bool 更新状态
     */
    public function edit($arr = array(),$id){
        $where['id']=input('post.id');
        return $this->name('entryinfo2019')->where($where)->update($arr);
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
        return $this->name('entryinfo2019')->where($map)->delete();
    }
}
?>