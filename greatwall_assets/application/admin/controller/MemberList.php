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
        if ($userinfo['group_id'] != 1) {
           $where[] = ['department_head|username','eq',$userinfo['nicename']];
        }
        
        $username = input('username');
        $mobile = input('mobile');
        if(!empty($username)){
            $where[] = ['username','like','%'.$username.'%'];
        }
        if(!empty($mobile)){
            $where[] = ['mobile','like','%'.$mobile.'%'];
        }

        $member = model('Member');
        
        $list = $member->getMemberList($where,20);
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
            $arr = $_POST;
            if ($id){
                $status=model('Member')->edit($arr,$id);
            }else{
                $status=model('Member')->add($arr);
            }
            if($status){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $info = model('Member')->getMemberInfo(input('id'));
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
        if(model('Member')->del($id)){
            return ajaxReturn(200,'员工删除成功！');
        }else{
            return ajaxReturn(0,'员工删除失败！');
        }
    }
}
?>