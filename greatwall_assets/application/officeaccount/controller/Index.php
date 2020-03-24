<?php
/**
 * 公众号信息展示
 * @auther:caizhuan
 * @date:20190425
 */
namespace app\officeaccount\controller;
use think\Controller;
use app\admin\model\Article;
use app\admin\model\Asset;

class Index extends Controller{

	public function index(){
		$arr = array('最新资讯','企业动态');
		$this->assign('arr',$arr);
		return $this->fetch();
	}


	public function getinfo(){
		$page = empty(input('page'))?0:input('page');
		$info = [];
		//最新资讯
		$articleModel = new Article();
		$where = array();
		$where[] = ['t.id','eq',2];
		$info['articleInfo'] = $articleModel->getinfo($where,$page);

		//企业动态
		$where = array();
		$where[] = ['t.id','eq',3];
		$info['companyInfo'] = $articleModel->getinfo($where,$page);

		//资产信息
		$assetModel = new Asset();
		$info['assetInfo'] = $assetModel->getinfo(array(),$page);
		return json_encode(array('code'=>0,'data'=>$info));
	}


	/**
	 * 资产详情
	 * @param  intval $[id] [<description>]
	 */
	public function assetInfo(){
		$assetModel = new Asset();
		$content = $assetModel->getAssetInfo(input('id'));
		return \view('financedetail',['content'=>$content]);
	}
}

?>