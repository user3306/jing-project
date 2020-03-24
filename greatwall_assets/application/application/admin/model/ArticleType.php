<?php
/**
 * 文章分类模型
 * @author caizhuan <[<email address>]>
 * @date:20190425
 */
namespace app\admin\model;
use think\Model;

class ArticleType extends Model{
	/**
	 * 获取产品分类
	 * @param  array   $where [description]
	 * @param  integer $limit [description]
	 * @return [type]         [description]
	 */
	public function getArticleTypeList($where = array(), $limit = 0,$searchinfo = array()){
		return $this->name('articletype2019')
                    ->alias('a')
                    ->join('articletype2019 t','a.pid=t.id','left')
                    ->where($where)
                    ->field('a.id,a.typename,a.addtime,a.status,t.typename parentname')
                    ->paginate($limit,false,['query'=>$searchinfo]);
	}

	/**
	 * 获取资产分类
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getArticleTypeInfo(){
		return $this->name('articletype2019')->select()->toArray();
	}


	/**
	 * 新增
	 * @param int $id ID
     * @return bool 新增状态
	 */
    public function add($arr = array()){
        return $this->name('articletype2019')->insert($arr);
    }


    /**
     * 更新
     * @param int $id ID
     * @return bool 更新状态
     */
    public function edit($arr = array(),$id){
        $where['id']=input('post.id');
        return $this->name('articletype2019')->where($where)->update($arr);
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
        return $this->name('articletype2019')->where($map)->delete();
    }


    /**
     * 获取类型详情
     * @param  int $[id] [<description>]
     * @return array [<description>]
     */
    public function getTypeDetail($id = 0){
        return $this->name('articletype2019')->where(array('id'=>$id))->find();
    }


     /**
     * 处理类型
     * @param  [type] $menuinfo [description]
     * @return [type]           [description]
     */
    public function handleMenu($menuinfo){
        $parentMenu = array();
        $i = 0;
        if (!empty($menuinfo)) {
            foreach ($menuinfo as $key => $value) {
                if ($value['pid'] == 0) {
                    $parentMenu[$i]['parentname'] = $value['typename'];
                    $parentMenu[$i]['id'] = $value['id'];
                    $parentMenu[$i]['chilrenname'] = array();
                    $i++;
                }
            }

            $j = 0;
            foreach ($parentMenu as $key => $value) {
                foreach ($menuinfo as $k => $val) {
                    if($value['id'] == $val['pid']){
                        $parentMenu[$key]['chilrenname'][$j]['typename'] = $val['typename'];
                        $parentMenu[$key]['chilrenname'][$j]['id'] = $val['id'];
                        $j++;
                    }
                }
            }
        }
        return $parentMenu;
    }
}
?>