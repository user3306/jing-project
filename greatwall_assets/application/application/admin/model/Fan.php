<?php
/**
 * 客户模型
 * @author caizhuan 
 * @date:20190422
 */

namespace app\admin\model;
use think\Model;

class Fan extends Model{
	//获取用户列表
	public function getCustomerList($where = array(), $limit = 0,$searchinfo = array()){
        return $this->name('user_information2019')
             ->alias('i')
             ->join('bingdinginfo2019 u','i.unionId=u.unionid','left')
			 ->join('entryinfo2019 c','i.unionId=c.unionid','left')
			 ->join('member2019 m','u.userid=m.userid','left')
             ->where($where)
             ->order('i.id ASC')//根据id降序排列
             ->field(['i.openId','i.nickName','i.gender','i.avatarUrl','i.addtime','i.province','i.country','u.userid','c.cli_name','c.cli_company','c.write_mobile','m.username','c.cli_mobile'])
             ->paginate($limit,false,['query'=>$searchinfo]);
	}
}
?>