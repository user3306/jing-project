<?php
/**
 * 资产模型
 * @author caizhuan 
 * @date:20190417
 */

namespace app\admin\model;
use think\Model;

class Asset extends Model{
	//获取资产列表
	public function getAssetList($where = array(), $limit = 0){
		return $this->name('assetinfo2019')
                    ->alias('asset')
                    ->join('asset_type2019 type','asset.asset_bigclass=type.id','left')
                    ->join('asset_type2019 type1','asset.asset_childclass=type1.id','left')
                    ->join('asset_type2019 type2','asset.asset_dishi=type2.id','left')
                    ->join('asset_type2019 type3','asset.asset_quxian=type3.id','left')
                    ->join('asset_type2019 type4','asset.asset_leavedishi=type4.id','left')
                    ->join('asset_type2019 type5','asset.asset_leavediquxian=type5.id','left')
                    ->join('asset_type2019 type6','asset.asset_trade=type6.id','left')
                    ->order('asset.id desc')//根据id降序排列
                    ->field(['asset.id','asset.asset_name','asset.asset_class','asset.asset_trade','type2.assettype asset_dishi','type3.assettype asset_quxian','type4.assettype asset_leavedishi','type5.assettype asset_leavediquxian','type.assettype asset_bigclass','type1.assettype asset_childclass','asset.asset_basemoney','asset.asset_getmoney','asset.asset_danbaomethod','asset.asset_danbaoperson','asset.asset_diyanumber','asset.asset_manager','asset.asset_managerline','asset.collection_number','asset.discussNum','asset.status','asset.competitive','type6.assettype asset_trade'])
                    ->where($where)->paginate($limit);
	}


	/**
	 * 获取资产总数
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getAssetTotal($where = array()){
		return $this->name('assetinfo2019')->where($where)->count();
	}


	/**
	 * 获取资产信息
	 * @param  intval $[id] [<description>]
	 */
	public function getAssetInfo($id){
		$map = array();
        $map['id'] = $id;
        return $this->name('assetinfo2019')->where($map)->find();
	}


	/**
	 * 新增
	 * @param int $id ID
     * @return bool 新增状态
	 */
    public function add($arr = array()){
        return $this->name('assetinfo2019')->insert($arr);
    }


    /**
     * 更新
     * @param int $id ID
     * @return bool 更新状态
     */
    public function edit($arr = array(),$id){
        $where['id']=$id;
        return $this->name('assetinfo2019')->where($where)->update($arr);
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
        return $this->name('assetinfo2019')->where($map)->delete();
    }


    /**
     * 获取资讯信息
     * @param  array $[where] [<description>]
     * @param  intval $[id] [<description>]
     * @return  array [<description>]
     */
    public function getinfo($where = array(),$page = 0){
        return $this->name('assetinfo2019')
                    ->where($where)->limit($page,10)->select()->toArray();
    }
}
?>