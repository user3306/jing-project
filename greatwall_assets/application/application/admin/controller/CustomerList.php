<?php
/**
 * 客户管理
 * @author caizhuan <[<email address>]>
 * @date(20190422)
 */
namespace app\admin\controller;
use app\admin\controller\Admin;
use Think\Db;
use  app\index\controller\Apismall;

class CustomerList extends Admin{
	public function __controller(){
		parent::__controller();
	}
	/**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '客户管理',
                'description' => '管理网站的客户',
            ),
            'menu' => array(
                array(
                    'name' => '客户列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            // '_info' => array(
            //     array(
            //         'name' => '添加客户',
            //         'url' => url('info'),
            //         'icon' => 'plus',
            //     ),
            // )
        );
    }


	/**
	 * 客户信息
	 * @param  string $[username] [<description>]
	 * @param string $[mobile] [<description>]
	 * return 
	 */
	public function index(){
		//筛选条件
        $where = array();
        $usermobile = input('usermobile');
        $mobile = input('mobile');
        $customerName = input('customerName');

        //判断用户登陆身份
        $info = session('admin_user');
        $userinfo = model('AdminUser')->getInfo($info['user_id'])->toArray();
    
        if ($userinfo['group_name'] != "超级管理员" && $userinfo['group_name'] != "管理层" &&  $userinfo['group_name'] != "部门负责人" ) {
           $where[] = ['u.userid','in',$userinfo['username']];
        }elseif($userinfo['group_name'] == "部门负责人"){
            //获取部门负责人下的所有员工
            $res = model('Member')->getAllMember(array('department_head'=>$userinfo['nicename']))->toArray();
            $allInfo = array();
            if (!empty($res)) {
                $allInfo = implode(',', array_column($res,'userid','id'));
            }
            $where[] = ['u.userid','in',$allInfo];
        }

        if(!empty($usermobile)){
            $where[] = ['m.mobile','like','%'.$usermobile.'%'];
        }

        if(!empty($mobile)){
            $where[] = ['s.cli_mobile','like','%'.$mobile.'%'];
        }

        if(!empty($customerName)){
            $where[] = ['s.cli_name','like','%'.$customerName.'%'];
        }

        $where[] = ['s.usertype','eq','2'];
		
		$searchinfo  = array();
		$searchinfo = request()->param();

        $list = model('customer')->getCustomerList($where,20,$searchinfo);
		$page = $list->render();
	
		$apismall = new Apismall();
		foreach($list as $k => $v){
			//收藏数
			$sql = "select count(cli_openid) as num from (SELECT c.info_id,a.asset_name,c.cli_openid FROM changcheng_collectioninfo2019 as c LEFT JOIN changcheng_assetinfo2019 as a ON c.info_id = a.id where c.cli_openid = '".$v['cli_openid']."' GROUP BY a.asset_name,c.cli_openid) as temp group by cli_openid";

			$num = Db::query($sql);
			$list[$k]['collectNum'] = !empty($num)?$num['0']['num']:0;
			
			//浏览数量
			$sqls = "select count(cli_openid) as num from (SELECT c.info_id,a.asset_name,c.cli_openid FROM changcheng_browse2019 as c LEFT JOIN changcheng_assetinfo2019 as a ON c.info_id = a.id where c.cli_openid = '".$v['cli_openid']."' GROUP BY a.asset_name,c.cli_openid) as temp group by cli_openid";
			$nums = Db::query($sqls);
			$list[$k]['viewNum'] = !empty($nums)?$nums['0']['num']:0;
			
			$list[$k]['nickName'] = $apismall->hex2str($v["nickName"]);
		}
		
        //位置导航
        $breadCrumb=array(array('name'=>'客户列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
		$_GET['page'] = isset($_GET['page'])?$_GET['page']-1:0;
        $this->assign('pageMaps',$_GET);
		
        $this->assign('_page',$page);
		return $this->fetch();
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
            if ($id){
                $status=model('customer')->edit($arr,$id);
            }else{
                $status=model('customer')->add($arr);
            }
            if($status){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $info = model('customer')->getCustomerInfo(input('id'));
            $this->assign('info',$info);
            return $this->fetch();
        }
    }
	
	/**
     * 导出报名用户信息表
     */
    public function exportuserlist(){
      
        $columns = ['openid','手机号','姓名','所在公司','推荐人','微信昵称'];
        $csvFileName = '客户信息'.date("Y-m-d").'.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="'. $csvFileName .'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		$fp = fopen('php://output', 'a');//打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        fputcsv($fp, $columns);//将数据格式化为CSV格式并写入到output流中  
		$where[] = ['s.usertype','eq','2'];
        $sendcount = Db::name('entryinfo2019')
             ->alias('s')
             ->join('bingdinginfo2019 u','s.unionid=u.unionid','left')
             ->join('member2019 m','u.userid=m.userid','left')
			 ->join('user_information2019 i','u.unionId=i.unionid','left')
			 ->where($where)
             ->order('s.id ASC')//根据id降序排列
             ->field(['s.cli_openid','s.cli_mobile','s.cli_name','s.cli_company','m.username','i.nickName'])
             ->select();
	
		$apismall = new Apismall();
		if(is_array($sendcount) && !empty($sendcount)) {
			foreach ($sendcount as $rowData) {
				$rowData['nickName'] = $apismall->hex2str($rowData["nickName"]);
				mb_convert_variables('GBK', 'UTF-8', $rowData);
				fputcsv($fp, $rowData);
			}
		}
        unset($sendcount);//释放变量的内存
		//刷新输出缓冲到浏览器
		ob_flush();
		flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。

		fclose($fp);
		exit(); 

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
        $info = model('customer')->getCustomerInfo(input('id'));
        model('customer')->delbind($info['unionid']);
        if(model('customer')->del($id)){
            return ajaxReturn(200,'员工删除成功！');
        }else{
            return ajaxReturn(0,'员工删除失败！');
        }
    }
	
	
	/**
	 *收藏资产列表
	 *@param  intval $[id] [<description>]
	 * return false
	 **/
	public function viewCollectList(){
		$openid=input('openid');
		$where['cli_openid'] = $openid;
		$list = Db::name('collectioninfo2019')
             ->alias('c')
             ->join('assetinfo2019 a','c.info_id=a.id','left')
			 ->where($where)
             ->order('c.id ASC')//根据id降序排列
             ->field(['a.asset_name','a.asset_basemoney','a.asset_getmoney','group_concat(distinct a.asset_manager) as asset_manager'])
			 ->group('a.asset_name')
             ->paginate(20);
		
		$breadCrumb=array(array('name'=>'收藏资产列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
		$_GET['page'] = isset($_GET['page'])?$_GET['page']-1:0;
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$list->render());
		return $this->fetch();
	}
	
	/**
	 *收藏资产列表
	 *@param  intval $[id] [<description>]
	 * return false
	 **/
	public function CollectList(){
		$openid=input('openid');
		$where['cli_openid'] = $openid;
		$list = Db::name('browse2019')
             ->alias('c')
             ->join('assetinfo2019 a','c.info_id=a.id','left')
			 ->where($where)
             ->order('c.id ASC')//根据id降序排列
             ->field(['a.asset_name','a.asset_basemoney','a.asset_getmoney','group_concat(distinct a.asset_manager) as asset_manager'])
			 ->group('a.asset_name')
             ->paginate(20);
		
		$breadCrumb=array(array('name'=>'收藏资产列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
		$_GET['page'] = isset($_GET['page'])?$_GET['page']-1:0;
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$list->render());
		return $this->fetch();
	}
}
?>