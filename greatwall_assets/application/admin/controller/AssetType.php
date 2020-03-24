<?php
/**
 * 资产分类
 * @auther:caizhaun
 * date:20190417
 */
namespace app\admin\controller;
use app\admin\controller\Admin;
class AssetType extends Admin{
	public function __controller(){
		parent::__controller();
	}
	/**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '资产类型管理',
                'description' => '管理网站的资产',
            ),
            'menu' => array(
                array(
                    'name' => '资产类型列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            '_info' => array(
                array(
                    'name' => '添加资产类型',
                    'url' => url('info'),
                    'icon' => 'plus',
                ),
            )
        );
    }


	/**
	 * 资产列表
	 * @param  string $[username] [<description>]
	 * @param string $[mobile] [<description>]
	 * return 
	 */
	public function index(){
		//筛选条件
        $where = array();
        $assettype = input('assettype');
        if(!empty($assettype)){
            $where[] = ['assettype','like','%'.$assettype.'%'];
        }

        $list = model('AssetType')->getAssetTypeList($where,20);

        //位置导航
        $breadCrumb=array(array('name'=>'资产分类列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$list->render());
		return $this->fetch();
	}


    /**
     * 编辑员工信息
     * @param intval $[id] [<description>]
     * @param string $[assettype] [<description>]
     * @param string $[pid] [<description>]
     * @param string $[lever] [<description>]
     * return false
     */
    public function info(){
        $id=input('post.id');

        if (input('post.')){
            $arr = $_POST;
            $arr['addtime'] = time();
            $result = model('AssetType')->getAreaDetail(array('id'=>$arr['pid']));
            if (!empty($result)) {
                $arr['pname'] = $result[0]['assettype'];
            }
            $arr['pname'] = "";
            
            if ($id){
                $status=model('AssetType')->edit($arr,$id);
            }else{
                $status=model('AssetType')->add($arr);
            }
            if($status){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $typelist = model('AssetType')->getAssetTypeInfo();
            $info = model('AssetType')->getDetailInfo(input('id'));
            $this->assign('typelist',$typelist);
            $this->assign('info',$info);
            return $this->fetch();
        }
    }


    /**
     * 删除用户
     * @param  intval $[id] [<description>]
     * return false
     */
    public function del($id){
        $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }
        if(model('AssetType')->del($id)){
            return ajaxReturn(200,'分类删除成功！');
        }else{
            return ajaxReturn(0,'分类删除失败！');
        }
    }
}
?>