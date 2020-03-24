<?php
/**
 * 资产信息管理
 * @auther:caizhaun
 * date:20190417
 */
namespace app\admin\controller;
use app\admin\controller\Admin;
use think\Db;

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
                array(
                    'name' => '批量导入',
                    'url' => url('whitelist'),
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
           $where[] = ['asset_manageruserid','like','%'.$userinfo['username'].'%'];
        }elseif($userinfo['group_name'] == "部门负责人"){
            //获取部门负责人下的所有员工
            $res = model('Member')->getAllMember(array('department_head'=>$userinfo['nicename']))->toArray();
            $allInfo = array();
            if (!empty($res)) {
                $allInfo = implode(',', array_column($res,'userid','id'));
            }
            $where[] = ['asset_manageruserid','in',$allInfo];
        }

        $username = $pageMaps['username'] = input('username');
        $mobile =  $pageMaps['mobile'] = input('mobile');
        $producttype = $pageMaps['producttype'] = input('producttype');
        $asset_leavedishi = $pageMaps['asset_leavedishi'] = input('asset_leavedishi');
        $scape = $pageMaps['scape'] = input('scape');
        $trade = $pageMaps['trade'] = input('trade');
        $assetname = $pageMaps['assetname'] = input('assetname');
        $status = $pageMaps['status'] = input('status');
        //管理者
        if(!empty($username)){
            $where[] = ['asset.asset_manager','like','%'.$username.'%'];
        }
        //资产名称
        if (!empty($assetname)) {
            $where[] = ['asset.asset_name','like','%'.$assetname.'%'];
        }
        //项目负责人手机号
        if(!empty($mobile)){
            $where[] = ['asset.asset_managerline','like','%'.$mobile.'%'];
        }
        //存放地区
        if (!empty($asset_leavedishi)) {
            # code...
            $where[] = ['asset.asset_leavediquxian|type3.pid','eq',$asset_leavedishi];
        }
        //产品类型
        if(!empty($producttype)){
            $where[] = ['asset.asset_leavediquxian|type1.pid','eq',$producttype];
        }
        //规模
        if (!empty($scape)) {
            if ($scape == 188) {
                $where[] = ['asset.asset_basemoney','elt',500];
            }

            if ($scape == 189) {
                $where[] = ['asset.asset_basemoney','gt',500];
                $where[] = ['asset.asset_basemoney','elt',1000];
            }

            if ($scape == 190) {
                $where[] = ['asset.asset_basemoney','gt',1000];
                $where[] = ['asset.asset_basemoney','elt',3000];
            }

            if ($scape == 191) {
                $where[] = ['asset.asset_basemoney','gt',3000];
                $where[] = ['asset.asset_basemoney','elt',5000];
            }

            if ($scape == 192) {
                $where[] = ['asset.asset_basemoney','gt',5000];
            }
            
        }

        //行业
        if(!empty($trade)){
            $where[] = ['asset.asset_trade','eq',$trade];
        }

        //是否审核
        if ($status == "0") {
            $where[] = ['asset.status','eq',0];
        }elseif($status == 1){
            $where[] = ['asset.status','eq',1];
        }

        $asset = model('Asset');
		$searchinfo  = array();
		$searchinfo = request()->param();
        $list = $asset->getAssetList($where,20,$searchinfo);
        //位置导航
        $breadCrumb=array(array('name'=>'资产列表','url'=>url('index')));
        //获取产品分类
        $assettype = model('AssetType')->getAssetTypeInfo();
		foreach($list as $k=>$v){
			//收藏客户数
			$sql = "select count(asset_name) as num from (SELECT c.info_id,a.asset_name,c.cli_openid FROM changcheng_collectioninfo2019 as c LEFT JOIN changcheng_assetinfo2019 as a ON c.info_id = a.id where a.asset_name = '".$v['asset_name']."' GROUP BY a.asset_name,c.cli_openid) as temp group by asset_name";
			$num = Db::query($sql);
			$list[$k]['collectNum'] = !empty($num)?$num['0']['num']:0;
			//浏览数量
			$sqls = "select count(asset_name) as num from (SELECT c.info_id,a.asset_name,c.cli_openid FROM changcheng_browse2019 as c LEFT JOIN changcheng_assetinfo2019 as a ON c.info_id = a.id where a.asset_name = '".$v['asset_name']."' GROUP BY a.asset_name,c.cli_openid) as temp group by asset_name";
			$nums = Db::query($sqls);
			$list[$k]['viewtNum'] = !empty($nums)?$nums['0']['num']:0;
			
		}
		
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('pageMaps',$pageMaps);
        $this->assign('assettype',$assettype);
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
			//dump($_POST);die;
            unset($arr['pic0']); 
            Db::startTrans();
            $newarr = array();//echo $arr['defaultasset'];die;
			$arr['act'] = isset($arr['act'])?$arr['act']:array();
			
            if($arr['defaultasset'] == 0){
				if(empty($arr['act'])){
					$newarr['asset_name'] = $arr['asset_name'];
					$newarr['defaultasset'] = 0;
					
					$newarr['asset_dishi'] = $arr['asset_dishi'];
					$newarr['asset_quxian'] = $arr['asset_quxian'];
					$newarr['asset_trade'] = $arr['asset_trade'];
					$newarr['intr'] = $arr['intr'];
					$newarr['asset_basemoney'] = $arr['asset_basemoney'];
					$newarr['asset_getmoney'] = $arr['asset_getmoney'];
					$newarr['asset_danbaomethod'] = $arr['asset_danbaomethod'];
					$newarr['asset_danbaoperson'] = $arr['asset_danbaoperson'];
					$newarr['asset_leavedishi'] ="";
					$newarr['asset_leavediquxian'] = "";
					$newarr['asset_diyanumber'] = "";
					$newarr['asset_diyanperson'] = "";
					$newarr['asset_packagename'] = "";
					$newarr['pic'] = "";
					$newarr['asset_manager'] = "";
					$newarr['asset_managerline'] = "";
					$newarr['asset_manageruserid'] = "";
					$newarr['asset_childclass']="";
					$newarr['desc'] = "";
					 
					$newarr['competitive'] = "";
					if (!empty($arr['id'])) {
						$where['id'] = $arr['id'];
						$res = Db::name('assetinfo2019')->where($where)->update($newarr);
					}else{	
						$res = Db::name('assetinfo2019')->insert($newarr);
					}
				   
					if(empty($res) || $res){
						Db::commit(); 
					}else{
						Db::rollback();
					   return ajaxReturn(0,'操作失败');
					}
				}else{
					$search_arr = implode(',',array_column($arr['act'],'id'));
					Db::name('assetinfo2019')->where([['asset_name','like','%'.$arr['asset_name'].'%'],['id','not in',$search_arr]])->delete();
					foreach ($arr['act'] as $key => $value) {
						$newarr['asset_name'] = $arr['asset_name'];
						$newarr['defaultasset'] = 0;
						
						$newarr['asset_dishi'] = $arr['asset_dishi'];
						$newarr['asset_quxian'] = $arr['asset_quxian'];
						$newarr['asset_trade'] = $arr['asset_trade'];
						$newarr['intr'] = $arr['intr'];
						$newarr['asset_basemoney'] = $arr['asset_basemoney'];
						$newarr['asset_getmoney'] = $arr['asset_getmoney'];
						$newarr['asset_danbaomethod'] = $arr['asset_danbaomethod'];
						$newarr['asset_danbaoperson'] = $arr['asset_danbaoperson'];
						$asset_bigclass = model('AssetType')->getParentname(array('id'=>$value['producttype']));
						$newarr['asset_childclass'] = $value['producttype'];
						if ($asset_bigclass['id'] == $newarr['asset_childclass']) {
							$newarr['asset_childclass'] == 0;
						}
						$newarr['asset_bigclass'] = $asset_bigclass['id'];
						$newarr['asset_leavedishi'] = $value['asset_leavedishi'];
						$newarr['asset_leavediquxian'] = $value['asset_leavediquxian'];
						$newarr['asset_diyanumber'] = $value['asset_diyanumber'];
						$newarr['asset_diyanperson'] = $value['asset_diyanperson'];
						$newarr['asset_packagename'] = $value['asset_packagename']; 
						$newarr['pic'] = $value['pic'];
						$newarr['asset_manager'] = $value['asset_manager'];
						$newarr['asset_managerline'] = $value['asset_managerline'];
						$newarr['asset_manageruserid'] = $value['asset_manageruserid'];
						$url = 'https://'.$_SERVER['SERVER_NAME'];
						$content = str_replace('<img src="'.$url, '<img src="', stripslashes($value['desc']));
						$content = str_replace('<img src="', '<img src="'.$url, stripslashes($content));
					
						$newarr['desc'] = $content;
					 
						$newarr['competitive'] = $arr['competitive'];
						if (!empty($value['id'])) {
							$where['id'] = $value['id'];
							$res = Db::name('assetinfo2019')->where($where)->update($newarr);
						}else{	
							$res = Db::name('assetinfo2019')->insert($newarr);
						}
					   
						if(empty($res) || $res){
							Db::commit(); 
						}else{
							Db::rollback();
						   return ajaxReturn(0,'操作失败');
						}
					}
				}
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                $newarr['asset_name'] = $arr['asset_name'];
                $newarr['defaultasset'] = 1;
                $newarr['pic'] = $arr['pics'];
                $newarr['asset_manager'] = $arr['asset_managers'];
                $newarr['asset_managerline'] = $arr['asset_managerlines'];
                $newarr['asset_manageruserid'] = $arr['asset_manageruserids'];
                $url = 'https://'.$_SERVER['SERVER_NAME'];
                $newarr['competitive'] = $arr['competitive'];
				$url = 'https://'.$_SERVER['SERVER_NAME'];
				$content = str_replace('<img src="'.$url, '<img src="', stripslashes($arr['descs']));
				$content = str_replace('<img src="', '<img src="'.$url, stripslashes($content));
				$newarr['desc'] = $content;
				
                if (!empty($arr['ids'])) {
                    $where['id'] = $arr['ids'];
                    $res = Db::name('assetinfo2019')->where($where)->update($newarr);
                }else{
                    $res = Db::name('assetinfo2019')->insert($newarr);
                }
               
                if(empty($res) || $res){
                    Db::commit(); 
                    return ajaxReturn(200,'操作成功',url('index'));
                }else{
                    Db::rollback();
                   return ajaxReturn(0,'操作失败');
                }
            } 
        }else{
            $newinfo = array();
            $areatype = array();
            $saveareatype = array();
            $assetname =  Db::name('assetinfo2019')->where(array('id'=>input('id')))->find();
            $i = 0;
            if (!empty($assetname)) {
                $info = Db::name('assetinfo2019')->where(array('asset_name'=>$assetname['asset_name']))->select();
                $newinfo['asset_name'] = $assetname['asset_name'];
                $newinfo['competitive'] = $assetname['competitive'];
                $newinfo['defaultasset'] = $assetname['defaultasset'];
             
                $newinfo['act'] = array();
                if ($newinfo['defaultasset'] != 0) {
                    $newinfo['asset_managers'] = $assetname['asset_manager'];
                    $newinfo['asset_managerlines'] = $assetname['asset_managerline'];
                    $newinfo['asset_manageruserids'] = $assetname['asset_manageruserid'];
                    $newinfo['pics'] = $assetname['pic'];
                    $newinfo['descs'] = $assetname['desc'];
                    $newinfo['ids'] = $assetname['id'];
                }
                $newinfo['asset_dishi'] = $assetname['asset_dishi'];
                $newinfo['asset_quxian'] = $assetname['asset_quxian'];
                $newinfo['asset_trade'] = $assetname['asset_trade'];
                $newinfo['asset_basemoney'] = $assetname['asset_basemoney'];
                $newinfo['asset_getmoney'] = $assetname['asset_getmoney'];
                $newinfo['asset_danbaomethod'] = $assetname['asset_danbaomethod'];
                $newinfo['asset_danbaoperson'] = $assetname['asset_danbaoperson'];
                $newinfo['intr'] = $assetname['intr'];
				$newinfo['act']=array();
				if(!empty($assetname['asset_bigclass']) || !empty($assetname['asset_childclass']) || !empty($assetname['asset_leavedishi']) || !empty($assetname['asset_diyanumber'])){
					foreach ($info as $key => $value) {
						$newinfo['act'][$i]['asset_bigclass'] = $value['asset_bigclass'];
						$newinfo['act'][$i]['asset_childclass'] = $value['asset_childclass'];
						$newinfo['act'][$i]['asset_leavedishi'] = $value['asset_leavedishi'];
						$newinfo['act'][$i]['asset_leavediquxian'] = $value['asset_leavediquxian'];
						$newinfo['act'][$i]['asset_diyanumber'] = $value['asset_diyanumber'];
						$newinfo['act'][$i]['asset_diyanperson'] = $value['asset_diyanperson'];
						$newinfo['act'][$i]['asset_manager'] = $value['asset_manager'];
						$newinfo['act'][$i]['asset_managerline'] = $value['asset_managerline'];
						$newinfo['act'][$i]['asset_packagename'] = $value['asset_packagename'];
						$newinfo['act'][$i]['asset_manageruserid'] = $value['asset_manageruserid'];
						$newinfo['act'][$i]['pic'] = $value['pic'];
						$newinfo['act'][$i]['desc'] = $value['desc'];
						$newinfo['act'][$i]['id'] = $value['id'];
						$i++;
					}
				}else{
					$newinfo['id'] = $assetname['id'];
				}
                
                //获取市级下的县
                $areatype = model('AssetType')->getAreaDetail(array('pid'=>$assetname['asset_dishi']));
                //存放区县
                $saveareatype = model('AssetType')->getAreaDetail(array('pid'=>$assetname['asset_leavedishi']));
            }else{
                $newinfo['asset_name'] = $assetname['asset_name'];
                $newinfo['competitive'] = 0;
                $newinfo['defaultasset'] = 0;
                if ($newinfo['defaultasset'] != 0) {
                    $newinfo['asset_managers'] = $assetname['asset_manager'];
                    $newinfo['asset_managerlines'] = $assetname['asset_managerline'];
                    $newinfo['asset_manageruserids'] = $assetname['asset_manageruserid'];
                    $newinfo['pics'] = $assetname['pic'];
                    $newinfo['descs'] = $assetname['desc'];
                    $newinfo['ids'] = $assetname['id'];
                }
                $newinfo['asset_dishi'] = $assetname['asset_dishi'];
                $newinfo['asset_quxian'] = $assetname['asset_quxian'];
                $newinfo['asset_trade'] = $assetname['asset_trade'];
                $newinfo['asset_basemoney'] = $assetname['asset_basemoney'];
                $newinfo['asset_getmoney'] = $assetname['asset_getmoney'];
                $newinfo['asset_danbaomethod'] = $assetname['asset_danbaomethod'];
                $newinfo['asset_danbaoperson'] = $assetname['asset_danbaoperson'];
                $newinfo['intr'] = $assetname['intr'];
                $newinfo['act'] = array();
                
                
            }

         
			
            //获取产品分类
            $assettype = model('AssetType')->getAssetTypeInfo();
            $this->assign('start_act',$i);
            $this->assign('assettype',$assettype);
            $this->assign('atype',json_encode($assettype));
            $this->assign('areatype',$areatype);
            $this->assign('saveareatype',$saveareatype);
            $this->assign('info',$newinfo);
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
        $check = Db::name('assetinfo2019')->where(array('id'=>$id))->find();
        $info = Db::name('assetinfo2019')->where(array('asset_name'=>$check['asset_name']))->select();
        $str = implode(',', array_column($info, 'id'));
       
        if(Db::name('assetinfo2019')->where(array('asset_name'=>$check['asset_name']))->delete()){
            Db::name('comment2019')->where([['info_id','in',$str]])->delete();
            Db::name('collectioninfo2019')->where([['info_id','in',$str]])->delete();
            return ajaxReturn(200,'资产删除成功！');
        }else{
            return ajaxReturn(0,'资产删除失败！');
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
        $check = Db::name('assetinfo2019')->where(array('id'=>$id))->find();
        $info = Db::name('assetinfo2019')->where(array('asset_name'=>$check['asset_name']))->select();
        $str = implode(',', array_column($info, 'id'));

        $where['status'] = 1;
        if(Db::name('assetinfo2019')->where([['id','in',$str]])->update($where)){
            return ajaxReturn(200,'资产审核成功！');
        }else{
            return ajaxReturn(0,'资产审核失败！');
        }
    }


    /**
     * 下架功能
     * @param  intval $[id] [<description>]
     * return false
     */
    public function saleOn(){
         $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }

        $check = Db::name('assetinfo2019')->where(array('id'=>$id))->find();
        $info = Db::name('assetinfo2019')->where(array('asset_name'=>$check['asset_name']))->select();
        $str = implode(',', array_column($info, 'id'));

        $where['isshelves'] = 1;
        if(Db::name('assetinfo2019')->where([['id','in',$str]])->update($where)){
            return ajaxReturn(200,'资产下架成功！');
        }else{
            return ajaxReturn(0,'资产下架失败！');
        }
    }


    /**
     * 
     * 上架功能
     * @param  intval $[id] [<description>]
     * return false
     */
    public function saleOut(){
         $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }

        $check = Db::name('assetinfo2019')->where(array('id'=>$id))->find();
        $info = Db::name('assetinfo2019')->where(array('asset_name'=>$check['asset_name']))->select();
        $str = implode(',', array_column($info, 'id'));

        $where['isshelves'] = 0;
        if(Db::name('assetinfo2019')->where([['id','in',$str]])->update($where)){
            return ajaxReturn(200,'资产上架成功！');
        }else{
            return ajaxReturn(0,'资产上架失败！');
        }
    }


    /**
     * 上传白名单
     */
    public function whitelist(){
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8"); 
        setlocale(LC_ALL, 'zh_CN');
        if($_FILES){
            $file = $_FILES['file'];
            if(substr($file['name'],-3) !== 'csv'){
                return ajaxReturn(0,'请上传csv文件');
            } 
            if($file['error']>0){
                return ajaxReturn(1,'上传失败');
            }
           
            $filename = date('YmdHis').'.csv';
            $dir = dirname(dirname(dirname(__DIR__)))."/public/uploads/";
            if (!move_uploaded_file($file['tmp_name'],$dir.$filename)) {
                return ajaxReturn(1,'上传文件错误');
            }
            $csv_file = fopen($dir.$filename,'r');
            $i = 0;
            while ($csv_arr = fgetcsv($csv_file)){
                $csv_arr = eval('return '.iconv('GBK','UTF-8',var_export($csv_arr,true)).';');
                $i++;
                $csv_array[] = $csv_arr;
            }   
            array_shift($csv_array);
            $i=0;  
            $num = 0;
            Db::startTrans();
            foreach ($csv_array as $k=>$v){
          
                $asset_dishi = Db::name('asset_type2019')->where([['assettype','like',$v[1]]])->find();
                $asset_quxian = Db::name('asset_type2019')->where([['assettype','like',$v[2]]])->find();
                $asset_trade = Db::name('asset_type2019')->where([['assettype','like',$v[3]]])->find();
                $asset_bigclass = Db::name('asset_type2019')->where([['assettype','like',$v[8]]])->find();
                $asset_childclass = Db::name('asset_type2019')->where([['assettype','like',$v[9]]])->find();
                $asset_leavedishi = Db::name('asset_type2019')->where([['assettype','like',$v[10]]])->find();
                $asset_leavediquxian = Db::name('asset_type2019')->where([['assettype','like',$v[11]],['pname','like',$v[10]]])->find();

                $data = [
                    'asset_name'=>$v[0],
                    'asset_dishi'=>!empty($asset_dishi)?$asset_dishi['id']:0,
                    'asset_quxian'=>!empty($asset_quxian)?$asset_quxian['id']:0,
                    'asset_trade'=>!empty($asset_trade)?$asset_trade['id']:0,
                    'asset_basemoney'=>$v[4],
                    'asset_getmoney' =>$v[5],
                    'asset_danbaomethod' =>$v[6],
                    'asset_danbaoperson' =>$v[7],
                    'asset_bigclass'=>!empty($asset_bigclass)?$asset_bigclass['id']:0,
                    'asset_childclass'=>!empty($asset_childclass)?$asset_childclass['id']:0,
                    'asset_leavedishi'=>!empty($asset_leavedishi)?$asset_leavedishi['id']:0,
                    'asset_leavediquxian'=>!empty($asset_leavediquxian)?$asset_leavediquxian['id']:0,
                    'asset_diyanumber'=>$v[12],
                    'asset_diyanperson'=>$v[13], //抵押人
                    'asset_manager'=>$v[14],  //责任项目经理姓名
                    'asset_manageruserid'=>$v[15], //员工工号
                    'asset_managerline'=>$v[16], //责任项目经理联系方式
                    'asset_packagename'=>$v[17] //备注（资产包名称）
                ];
                $check = Db::name('assetinfo2019')->where($data)->find();
                if(!$check){
                    $res = Db::name('assetinfo2019')->insert($data);
                   // echo Db::name('assetinfo2019')->getlastsql();die();
                    if($res){
                        Db::commit(); 
                        $i++;
                        continue;
                    }else{
                        Db::rollback();
                        $num = $i+1;
                        return ajaxReturn(1,'插入第'.$num.'行数据有问题',url('index'));
                    }
                }else{
                    Db::rollback();
                    return ajaxReturn(1,'第'.$num.'行数据已经存在',url('index'));
                }
            }
            fclose($csv_file);
            return ajaxReturn(200,'上传'.$i.'条成功,',url('index'));
        }
        return $this->fetch();
    }


    /**
     * 全部审核
     * @param  string $[name] [<description>]
     * return json
     */
    public function allverify(){
        $str=input('post.data');  

        if (empty($str)) {
            return ajaxReturn(1,'请先选中要审核的资产','');
        }
        $arr['status'] = 1;
        $check = Db::name('assetinfo2019')->where([['id','in',$str]])->select();
        $name = implode(',', array_column($check, 'asset_name'));
        $info = Db::name('assetinfo2019')->where([['asset_name','in',$name]])->select();
        $str = implode(',', array_column($info, 'id'));
        $res = Db::name('assetinfo2019')->where([['id','in',$str]])->update($arr);
        if (empty($res) || $res) {
           return ajaxReturn(200,'更新成功',url('index'));
        }else{
            return ajaxReturn(1,'更新失败',url('index'));
        }
    }


    /**
     * 全部上架
     * @param  string $[name] [<description>]
     * return json
     */
    public function allsaleon(){
        $str=input('post.data');  

        if (empty($str)) {
            return ajaxReturn(1,'请先选中要审核的资产','');
        }
        $arr['isshelves'] = 0;
        $check = Db::name('assetinfo2019')->where([['id','in',$str]])->select();
        $name = implode(',', array_column($check, 'asset_name'));
        $info = Db::name('assetinfo2019')->where([['asset_name','in',$name]])->select();
        $str = implode(',', array_column($info, 'id'));
        $res = Db::name('assetinfo2019')->where([['id','in',$str]])->update($arr);
        if (empty($res) || $res) {
           return ajaxReturn(200,'更新成功',url('index'));
        }else{
            return ajaxReturn(1,'更新失败',url('index'));
        }
    }

    /**
     * 全部下架
     * @param  string $[name] [<description>]
     * return json
     */
    public function allsaleout(){
        $str=input('post.data');  

        if (empty($str)) {
            return ajaxReturn(1,'请先选中要审核的资产','');
        }
        $arr['isshelves'] = 1;
        $check = Db::name('assetinfo2019')->where([['id','in',$str]])->select();
        $name = implode(',', array_column($check, 'asset_name'));
        $info = Db::name('assetinfo2019')->where([['asset_name','in',$name]])->select();
        $str = implode(',', array_column($info, 'id'));
        $res = Db::name('assetinfo2019')->where([['id','in',$str]])->update($arr);
        if (empty($res) || $res) {
           return ajaxReturn(200,'更新成功',url('index'));
        }else{
            return ajaxReturn(1,'更新失败',url('index'));
        }
    }
	
	
	/**
	 *浏览信息
	 **/
	public function viewinfo(){
        $info = Db::name('browse2019')
					->alias('c')
					->join('entryinfo2019 e','c.cli_openid=e.cli_openid','left')
					->join('assetinfo2019 b','c.info_id = b.id','left')
					->join('bingdinginfo2019 u','e.unionid=u.unionid','left')
					->join('member2019 m','u.userid=m.userid','left')
					->where(array('b.asset_name'=>input('asset_name')))
					->order('c.id desc')//根据id降序排列
					->field(['distinct e.cli_mobile','c.id','e.cli_name','e.cli_company','m.username'])
					->group('e.cli_mobile')
            		->paginate(20);
        $this->assign('list',$info);
		$_GET['page'] = isset($_GET['page'])?$_GET['page']-1:0;
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$info->render());
        return $this->fetch();
    }
	
	
	/**
	 *收藏信息
	 */
	public function collectinfo(){
        $info = Db::name('collectioninfo2019')
					->alias('c')
					->join('entryinfo2019 e','c.cli_openid=e.cli_openid','left')
					->join('assetinfo2019 b','c.info_id = b.id','left')
					->join('bingdinginfo2019 u','e.unionid=u.unionid','left')
					->join('member2019 m','u.userid=m.userid','left')
					->where(array('b.asset_name'=>input('asset_name')))
					->order('c.id desc')//根据id降序排列
					->field(['distinct e.cli_mobile','c.id','e.cli_name','e.cli_company','c.collect_time','m.username'])
					->group('e.cli_mobile')
            		->paginate(20);
        $this->assign('list',$info);
		$_GET['page'] = isset($_GET['page'])?$_GET['page']-1:0;
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$info->render());
        return $this->fetch();
    }

}
?>