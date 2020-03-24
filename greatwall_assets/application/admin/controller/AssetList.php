<?php
/**
 * 资产信息管理
 * @auther:caizhaun
 * date:20190417
 */
namespace app\admin\controller;
use app\admin\controller\Admin;

class AssetList extends Admin{
	protected $member;
	public function __controller(){
		parent::__controller();
	}
	/**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '资产管理',
                'description' => '管理网站的资产',
            ),
            'menu' => array(
                array(
                    'name' => '资产列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            '_info' => array(
                array(
                    'name' => '添加资产',
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
        $info = session('admin_user');
        $userinfo = model('AdminUser')->getInfo($info['user_id'])->toArray();
       
        if ($userinfo['group_name'] != "超级管理员" && $userinfo['group_name'] != "管理层" &&  $userinfo['group_name'] != "部门负责人" ) {
           $where[] = ['asset_manager','eq',$userinfo['nicename']];
        }elseif($userinfo['group_name'] == "部门负责人"){
            //获取部门负责人下的所有员工
            $res = model('Member')->getAllMember(array('department_head'=>$userinfo['nicename']))->toArray();
            $allInfo = array();
            if (!empty($res)) {
                $allInfo = implode(',', array_column($res,'username','id'));
            }
            $where[] = ['asset_manager','in',$allInfo];
        }

        $username = input('username');
        $mobile = input('mobile');
        if(!empty($username)){
            $where[] = ['asset_manager','like','%'.$username.'%'];
        }
        if(!empty($mobile)){
            $where[] = ['asset_managerline','like','%'.$mobile.'%'];
        }
        $asset = model('Asset');

        $list = $asset->getAssetList($where,20);

        //位置导航
        $breadCrumb=array(array('name'=>'资产列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$list->render());
		return $this->fetch();
	}


    /**
     * 获取员工信息
     * @param  intval $[id] [<description>]
     * return false
     */
    public function memberInfo(){
        $id=input('post.id');
        model('Member')->getMemberInfo($id);
    }


    /**
     * 编辑员工信息
     * @param intval $[id] [<description>]
     * @param string $[job_info] [<description>]
     * @param string $[job] [<description>]
     * @param string $[office_mobile] [<description>]
     * @param string $[job_info] [<description>]
     * @param string $[mobile] [<description>]
     * @param string $[belong_to_department] [<description>]
     * @param string $[department_head] [<description>]
     * @param string $[is_disabled] [<description>]
     * return false
     */
    public function info(){
        $id=input('post.id');

        if (input('post.')){
            $arr = $_POST;
            $asset_bigclass = model('AssetType')->getParentname(array('id'=>$_POST['producttype']));
            $arr['asset_childclass'] = $_POST['producttype'];  
            if ($asset_bigclass['id'] == $arr['asset_childclass']) {
                $arr['asset_childclass'] == 0;
            }
            unset($arr["producttype"] );  
            $arr['asset_bigclass'] = $asset_bigclass['id'];
            $url = 'https://'.$_SERVER['SERVER_NAME'];
            $content = str_replace('<img src="'.$url, '<img src="', stripslashes($_POST['desc']));
            $content = str_replace('<img src="', '<img src="'.$url, stripslashes($_POST['desc']));
            unset($arr['id']); 
            $arr['desc'] = $content;
            if ($id){
                $status=model('Asset')->edit($arr,$id);
            }else{
                $status=model('Asset')->add($arr);
            }
        
            if($status){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $info = model('Asset')->getAssetInfo(input('id'));
            //获取产品分类
            $assettype = model('AssetType')->getAssetTypeInfo();
            //获取市级下的县
            $areatype = model('AssetType')->getAreaDetail(array('pid'=>$info['asset_dishi']));
            //存放区县
            $saveareatype = model('AssetType')->getAreaDetail(array('pid'=>$info['asset_leavedishi']));
            
            $this->assign('assettype',$assettype);
            $this->assign('areatype',$areatype);
            $this->assign('saveareatype',$saveareatype);
            $this->assign('info',$info);
            return $this->fetch();
        }
    }


    /**
     * 获取地区
     * @param  string $[areaname] [<description>]
     */
    public function getArea(){
        $areaid=input('post.areaid');
        if (!empty($areaid)) {
            $where[] = ['pid','eq',$areaid];
        }
        $res = model('AssetType')->getAreaDetail($where);
        return ajaxReturn(100,'成功','',$res);
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
        if(model('Asset')->del($id)){
            return ajaxReturn(200,'员工删除成功！');
        }else{
            return ajaxReturn(0,'员工删除失败！');
        }
    }


    /**
     * 审核用户
     * @param intval $[id] [<description>]
     * return false
     */
    public function verify(){
        $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }
        $where['status'] = 1;
        if(model('Asset')->edit($where,$id)){
            return ajaxReturn(200,'员工审核成功！');
        }else{
            return ajaxReturn(0,'员工审核失败！');
        }
    }
}
?>