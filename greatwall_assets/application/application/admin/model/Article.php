<?php
/**
 * 文章模型
 * @auther:caizhuan
 * @date:20190425
 */
namespace app\admin\model;
use think\Model;

class Article extends Model{
	//获取文章列表
	public function getArticleList($where = array(), $limit = 0,$searchinfo = array()){
		return $this->name('article2019')
                    ->alias('a')
                    ->join('articletype2019 t','a.typeid=t.id','left')
                    ->order('a.id desc')//根据id降序排列
                    ->field(['a.id,a.title,a.description,a.status,a.addtime,t.typename'])
                    ->where($where)->paginate($limit,false,['query'=>$searchinfo]);
	}


	/**
	 * 获取文章总数
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getArticleDetail($id){
		return $this->name('article2019')->where(array('id'=>$id))->find();
	}


	/**
	 * 新增
	 * @param int $id ID
     * @return bool 新增状态
	 */
    public function add($arr = array()){
        return $this->name('article2019')->insert($arr);
    }


    /**
     * 更新
     * @param int $id ID
     * @return bool 更新状态
     */
    public function edit($arr = array(),$id){
        $where['id']=$id;
        return $this->name('article2019')->where($where)->update($arr);
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
        return $this->name('article2019')->where($map)->delete();
    }


    //获取文章列表
    public function getinfo($where = array(), $page = 0){
        return $this->name('article2019')
                    ->alias('a')
                    ->join('articletype2019 t','a.typeid=t.id','left')
                    ->order('a.id desc')//根据id降序排列
                    ->field(['a.id,a.title,a.description,a.content,a.pic,a.status,a.addtime,t.typename'])
                    ->where($where)->limit($page,10)->select()->toArray();
    }
}
?>