<?php
/**
 * 用户信息管理
 * @auther:caizhaun
 * date:20190417
 */
namespace app\admin\controller;
use app\admin\controller\Admin;

class MemberList extends Admin{
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
                'name' => '员工管理',
                'description' => '管理网站的员工',
            ),
            'menu' => array(
                array(
                    'name' => '员工列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            '_info' => array(
                array(
                    'name' => '添加员工',
                    'url' => url('info'),
                    'icon' => 'plus',
                ),
            )
        );
    }


	/**
	 * 用户信息
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
           $where[] = ['m.username','eq',$userinfo['nicename']];
        }elseif($userinfo['group_name'] == "部门负责人"){
			$where[] = ['m.department_head','eq',$userinfo['nicename']];
        }
        
        $username = input('username');
        $mobile = input('mobile');
        if(!empty($username)){
            $where[] = ['m.username','like','%'.$username.'%'];
        }
        if(!empty($mobile)){
            $where[] = ['m.mobile','like','%'.$mobile.'%'];
        }
      //  $where[] = ['m.is_del','neq',1];
        $member = model('Member');
		
		$searchinfo  = array();
		$searchinfo = request()->param();
        
        $list = $member->getMemberList($where,20,$searchinfo);
		
        //位置导航
        $breadCrumb=array(array('name'=>'用户列表','url'=>url('index')));
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
            if (input('post.password')){
                if (empty(input('post.password2'))){
                    return ajaxReturn(0,'确认密码不能为空');
                }
                if (input('post.password2')!=input('post.password')){
                    return ajaxReturn(0,'两次密码不一致');
                }
            }
            $arr['userid'] = $_POST['userid'];
            $arr['username'] = $_POST['username'];
            $arr['mobile'] = $_POST['mobile'];
            $arr['office_mobile'] = $_POST['office_mobile'];
            $arr['job'] = $_POST['group_id'];
            $arr['belong_to_department'] = $_POST['belong_to_department'];
            $arr['department_head'] = $_POST['department_head'];
            $arr['is_disabled'] =  $_POST['status'];
            if ($arr['is_disabled'] == '2') {
                $arr['disabled_time'] = date('Y-m-d H:i:s');
            }else{
                $arr['disabled_time'] = null;
            }

            if ($id){
				unset($arr['password']);
				
				if (empty(input('post.password'))){
					unset($arr['password']);
                }
				
				if (empty(input('post.email'))){
					unset($arr['email']);
                }
				
                $status=model('Member')->edit($arr,$id);
                if (empty(input('user_id'))) {
                    $statues=model('AdminUser')->add();
                }else{
                    $statues=model('AdminUser')->edit();
                }
            }else{
                if (empty(input('post.password'))){
					unset($arr['password']);
                }
				if (empty(input('post.email'))){
					unset($arr['email']);
                }
                $status=model('Member')->add($arr);
                $statues=model('AdminUser')->add();
            }
            if($status || $statues){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $info = model('Member')->getMemberInfo(input('id'));
            if (!empty(input('id'))) {
                $info = $info->toArray();
            }
            //获取用户组
            $this->assign('groupList',model('AdminGroup')->loadList());
            $this->assign('userinfo', model('AdminUser')->getAllinfo($info['userid']));
            $this->assign('info',$info);
            return $this->fetch();
        }
    }


    /**
     * 删除用户
     * @param  intval $[id] [<description>]
     * return false
     */
    public function del(){
        $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }
        $info = model('Member')->getMemberInfo(input('id'));
        if (!empty(input('id'))) {
            $info = $info->toArray();
        }
        //删除员工绑定后台账号
        model('AdminUser')->delinfo($info['userid']);
        model('Member')->delInfo($info['openid']);
        $where['is_del'] = 1;
        $where['openid'] = null;
        $where['wx_openid'] = null;
        $where['mobile'] = null;
        if(model('Member')->edit($where,$id)){

            return ajaxReturn(200,'员工删除成功！');
        }else{
            return ajaxReturn(0,'员工删除失败！');
        }
    }


    /**
     * 解绑用户
     * @param  intval $[id] [<description>]
     * return false
     */
    public function bind(){
        $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }

        $info = model('Member')->getMemberInfo(input('id'));
	
        if (!empty(input('id'))) {
            $info = $info->toArray();
        }

        model('Member')->delInfo($info['openid']);
		
        $arr['openid'] = null;
        if (model('Member')->edit($arr,$id)) {
            return ajaxReturn(200,'员工解绑成功！');
        }else{
            return ajaxReturn(0,'员工解绑失败！');
        }
    }
	
	
	/**
	 *恢复员工
	 * @param  intval $[id] [<description>]
     * return false
	 */
	public function renew(){
        $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }
        $arr['is_disabled'] = 0;
		$arr['is_del'] = 0;
        if (model('Member')->edit($arr,$id)) {
            return ajaxReturn(200,'员工恢复成功！');
        }else{
            return ajaxReturn(0,'员工恢复失败！');
        }
    }
	 
}
?>