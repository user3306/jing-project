<?php
/**
 * 筛选模型
 * @auther:caizhuan
 * @date:20190423
 */
namespace app\admin\model;
use think\Model;

class Screendata extends Model{
	//获取筛选列表
	public function getScreendataList($where = array(), $limit = 0){
		return $this->name('screendata2019')
					->alias('s')
             		->join('asset_type2019 t1','s.areaID=t1.id','left')
             		->join('asset_type2019 t2','s.productID=t2.id','left')
             		->join('asset_type2019 t3','s.scropID=t3.id','left')
             		->join('asset_type2019 t4','s.tradeID=t4.id','left')
             		->where($where)
             		->order('s.id desc')//根据id降序排列
             		->field(['s.id','t1.assettype areatype','t2.assettype producttype','t3.assettype scroptype','t4.assettype tradetype','s.screendate'])
             		->paginate($limit);
	}


	/**
	 * 获取筛选总数
	 * @param  array $[where] [<description>]
	 * return false;
	 */
	public function getScreendataTotal($where = array()){
		return $this->name('screendata2019')->where($where)->count();
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
        return $this->name('screendata2019')->where($map)->delete();
    }
}
?>