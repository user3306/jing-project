<?php
/**
 * 收藏模型
 * @author caizhuan <[<email address>]>
 * @date:20190423
 */
namespace app\admin\model;
use think\Model;

class Collected extends Model{
	//获取资产列表
	public function getCollectedList($limit = 0){
		
		return $this->name('collectioninfo2019')
					->alias('a')
					->join('assetinfo2019 b','a.`info_id` = b.id','left')
					->field(['count(distinct a.cli_openid) as num','a.`info_id`','a.`id`','b.asset_name'])
					->group('b.asset_name')
					->paginate($limit);
	}


	//获取资产详情
	public function getCollectedDetail($asset_name,$limit = 0){
		return $this->name('collectioninfo2019')
					->alias('c')
					->join('entryinfo2019 e','c.cli_openid=e.cli_openid','left')
					->join('assetinfo2019 b','c.info_id = b.id','left')
					->where(array('b.asset_name'=>$asset_name))
					->order('c.id desc')//根据id降序排列
					->field(['distinct e.cli_mobile','c.id','e.cli_name','c.collect_time'])
					->group('e.cli_mobile')
            		->paginate($limit);
	}


	//按分类统计数据
	public function getTypeData($lever = 0){
		//地域
		if ($lever == 1) {
			$str = "s.areaID = t.id";
		}
		//押品
		if ($lever == 2) {
			$str = "s.productID = t.id";
		}
		//规模
		if ($lever == 3) {
			$str = "s.scropID = t.id";
		}
		//行业
		if ($lever == 4) {
			$str = "s.tradeID = t.id";
		}

		if ($lever == 1 || $lever == 2) {
			return $this->name('screendata2019')
					->alias('s')
					->join('asset_type2019 t',$str,'left')
					->field('count(t.id) num,t.id')
					->group('t.id')
					->having('count(t.id) > 0')
					->select()->toArray();
		}

		if ($lever == 3 || $lever == 4) {
			return $this->name('screendata2019')
					->alias('s')
					->join('asset_type2019 t',$str,'left')
					->where('lever = '.$lever)
					->field('count(t.id) num,t.id')
					->group('t.id')
					->having('count(t.id) > 0')
					->select()->toArray();
		}
		

	}


	//获取被收藏的资产总数
	public function getCollectNum(){
		return $this->name('assetinfo2019')
					->where('collection_number > 0')
					->count('id');
	}


	//获取未被收藏的资产总数
	public function getNoCollectNum(){
		return $this->name('assetinfo2019')
					->where('collection_number = 0')
					->count('id');
	}

	//获取被评论的资产总数
	public function getDiscussNum(){
		return $this->name('assetinfo2019')
					->where('discussNum > 0')
					->count('id');
	}


	//获取未被评论的资产总数
	public function getNoDiscussNum(){
		return $this->name('assetinfo2019')
					->where('discussNum = 0')
					->count('id');
	}


	//获取分类下的名称
	public function gettypename($where = array()){
		return $this->name('asset_type2019')->where($where)->field('assettype,id')->select()->toArray();
	}

}
?>