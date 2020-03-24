<?php
namespace app\home\model;
use think\Model;

/**
 * Class Category 用户基础信息模型
 * dingzq hatabc@qq.com
 */
class XzyRepaire extends Model
{
    /**
     * 用户列表
     * @param 条件 $where
     * @param 用户id $user_id
     * @return 数组
     */
    public function loadList($where = array()){
        $where[] = ['openid','=',cookie('userid')];
        $data = $this->name('xzy_repaire')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
    }

    /**
     * 手机品牌
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getptype()
    {
        $where[] = ['status','=',1];
        $data = $this->name('xzy_ptype')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
    }
	
	public function getpmodel($where=array())
    {
        $where[] = ['status','=',1];
        $data = $this->name('xzy_pmodel')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
    }
	
	public function getpfault($pfaultid)
    {
        $where[] = ['id','=',$pfaultid];
        $data = $this->name('xzy_pfault')
            ->field('*')
            ->where($where)
            ->find();
        return $data;
    }

    public function getallpfault($where=array())
    {
        $data = $this->name('xzy_pfault')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
    }

    public function getrprice($where=array())
    {
        //$where[] = ['status','=',1];
        $data = $this->name('xzy_rprice')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
    }
	
	public function getshop()
    {
        $where[] = ['shoptype','=',2];
        $data = $this->name('xzy_shop')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
    }

    public function getrealname()
    {
        $where[] = ['openid','=',cookie('userid')];
        $data = $this->name('xzy_wxuser')
            ->field('realname')
            ->where($where)
            ->find();
        return $data['realname'];
    }
    /**
     * 新增
     */
    public function add(){
        $_POST['posttime']=time();
		$_POST['openid'] = cookie('userid');
        return $this->allowField(true)->save($_POST);
    }
    /**
     * 更新
     */
    public function edit(){
        $openid = input('post.openid');
        if (empty($openid)){
            return false;
        }        
        $where[]=['openid','=',$openid];
        $status=$this->allowField(true)->save($_POST,$where);
        
        return $status;
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

    public function getVipData($json)
    {
        $url = 'http://113.140.3.210:8193/posInt/indexvip.php';
        return postJson($url,$json,true);
    }
    
}
