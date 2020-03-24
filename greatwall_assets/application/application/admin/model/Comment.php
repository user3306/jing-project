<?php
/**
 * 评论模型
 * @auther:caizhuan
 * @date:20190423
 */
namespace app\admin\model;
use think\Model;

class Comment extends Model{
	//获取用户列表
	public function getCommentList($where = array(), $limit = 0,$searchinfo = array()){
        return $this->name('comment2019')
             ->alias('c')
             ->join('entryinfo2019 e','c.cli_openid=e.cli_openid','left')
             ->join('assetinfo2019 a','c.info_id=a.id','left')
             ->join('member2019 m','c.asset_userid=m.userid','left')
             ->where($where)
             ->order('c.id desc')//根据id降序排列
             ->field(['c.id','e.cli_mobile','a.asset_name','m.mobile','c.comment_content','c.reply_content','c.comment_time','c.reply_time'])
             ->paginate($limit,false,['query'=>$searchinfo]);
	}


	/**
	 * 获取评论总数
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getCommentTotal($where = array()){
		return $this->name('comment2019')->where($where)->count();
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
        return $this->name('comment2019')->where($map)->delete();
    }
}
?>