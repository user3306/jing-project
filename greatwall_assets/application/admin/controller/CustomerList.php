<?php
/**
 * 客户管理
 * @author caizhuan <[<email address>]>
 * @date(20190422)
 */
namespace app\admin\controller;
use app\admin\controller\Admin;

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
           $where[] = ['u.userid','in',$userinfo['nicename']];
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

        $list = model('customer')->getCustomerList($where,20);
        //位置导航
        $breadCrumb=array(array('name'=>'客户列表','url'=>url('index')));
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
     * 删除用户
     * @param  intval $[id] [<description>]
     * return false
     */
    public function del($id){
        $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }
        if(model('customer')->del($id)){
            return ajaxReturn(200,'员工删除成功！');
        }else{
            return ajaxReturn(0,'员工删除失败！');
        }
    }
}
?>