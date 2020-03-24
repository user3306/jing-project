<?php
/**
 * 资产类型模型
 * @auther:caizhaun
 * date:20190417
 */
namespace app\admin\model;
use think\Model;

class AssetType extends Model{
	//获取资产列表
	public function getAssetTypeList($where = array(), $limit = 0){
		return $this->name('asset_type2019')->where($where)->paginate($limit);
	}


	/**
	 * 获取资产总数
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getAssetTypeTotal($where = array()){
		return $this->name('asset_type2019')->where($where)->count();
	}


	/**
	 * 新增
	 * @param int $id ID
     * @return bool 新增状态
	 */
    public function add($arr = array()){
        return $this->name('asset_type2019')->insert($arr);
    }


    /**
     * 更新
     * @param int $id ID
     * @return bool 更新状态
     */
    public function edit($arr = array(),$id){
        $where['id']=input('post.id');
        return $this->name('asset_type2019')->where($where)->update($arr);
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
        return $this->name('asset_type2019')->where($map)->delete();
    }


    /**
     * 获取资产分类
     */
    public function getAssetTypeInfo(){
    	$res = $this->name('asset_type2019')->select()->toArray();
    	return $this->handleMenu($res);
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
                    $parentMenu[$i]['parentname'] = $value['assettype'];
                    $parentMenu[$i]['id'] = $value['id'];
                    $parentMenu[$i]['lever'] = $value['lever'];
                    $parentMenu[$i]['chilrenname'] = array();
                    $i++;
                }
            }

            $j = 0;
            foreach ($parentMenu as $key => $value) {
                foreach ($menuinfo as $k => $val) {
                    if($value['id'] == $val['pid']){
                        $parentMenu[$key]['chilrenname'][$j]['assettype'] = $val['assettype'];
                        $parentMenu[$key]['chilrenname'][$j]['id'] = $val['id'];
                        $parentMenu[$key]['chilrenname'][$j]['lever'] = $value['lever'];
                        $j++;
                    }
                }
            }
        }
        return $parentMenu;
    }


    /**
     * 获取区县
     * @param  string $[name] [<description>]
     * return array
     */
    public function getAreaDetail($where = array()){
    	return $this->name('asset_type2019')->where($where)->select()->toArray();
    }


    /**
     * 获取详细信息
     * @param intval $[id] [<description>]
     */
    public function getDetailInfo($id){
        return $this->name('asset_type2019')->where(array('id'=>$id))->find();
    }


    /**
     * 获取父级名称
     * @param int $[pid] [<description>]
     * return array
     */
    public function getParentname($where = array()){
    	$res = $this->name('asset_type2019')->where($where)->find();
    	if(empty($res['pid'])){
    		return $res;
    	}
    		
    	return $this->name('asset_type2019')->where(array('id'=>$res['pid']))->find();
    }
}
?>