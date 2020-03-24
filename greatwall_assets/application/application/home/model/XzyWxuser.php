<?php
namespace app\home\model;
use think\Model;

/**
 * Class Category 用户基础信息模型
 * dingzq hatabc@qq.com
 */
class XzyWxuser extends Model
{
    /**
     * 用户列表
     * @param 条件 $where
     * @param 用户id $user_id
     * @return 数组
     */
    public function loadList($where = array(), $user_id=0){
        
    }

    /**
     * 手机品牌
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getptype()
    {
        $where[] = ['status','=',1];
        $data = $this->name('xzy_mobiletype')
            ->field('*')
            ->where($where)
            ->select();
        return $data;
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


    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function delInfo($where){
        $info = $this->where($where)->delete();
        return $info;
    }

    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function getInfo($openid){
        $where = array();
        $where[] = ['openid','=',$openid];
        $info = $this->where($where)->find();
        return $info;
    }

    public function getVipData($json)
    {
        $url = 'http://113.140.3.210:2013/posInt/indexvip.php';
        return postJson($url,$json,true);
    }
    
}
