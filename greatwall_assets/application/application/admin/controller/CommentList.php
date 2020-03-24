<?php
/**
 * 评论功能
 * @auth:caizhuan
 * @date:20190422
 */
namespace app\admin\controller;
use app\admin\controller\Admin;
class CommentList extends Admin{
	public function __controller(){
		parent::__controller();
	}
	/**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '评论管理',
                'description' => '管理网站的评论',
            ),
            'menu' => array(
                array(
                    'name' => '评论列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            // '_info' => array(
            //     array(
            //         'name' => '添加评论',
            //         'url' => url('info'),
            //         'icon' => 'plus',
            //     ),
            // )
        );
    }


	/**
	 * 评论列表
	 * @param  string $[username] [<description>]
	 * @param string $[mobile] [<description>]
	 * return 
	 */
	public function index(){
		//筛选条件
        $where = array();
        $mobile = input('mobile');  //员工手机号
        $pro_mobile = input('customerName');  //项目经理手机号
        $starttime = input('starttime');    //开始时间
        $endtime = input('endtime');        //结束时间

        //判断用户登陆身份
        $info = session('admin_user');
        $userinfo = model('AdminUser')->getInfo($info['user_id'])->toArray();
    
        if ($userinfo['group_name'] != "超级管理员" && $userinfo['group_name'] != "管理层" &&  $userinfo['group_name'] != "部门负责人" ) {
           $where[] = ['a.asset_manageruserid','in',$userinfo['username']];
        }elseif($userinfo['group_name'] == "部门负责人"){
            //获取部门负责人下的所有员工
            $res = model('Member')->getAllMember(array('department_head'=>$userinfo['nicename']))->toArray();
            $allInfo = array();
            if (!empty($res)) {
                $allInfo = implode(',', array_column($res,'userid','id'));
            }
            $where[] = ['a.asset_manageruserid','in',$allInfo];
        }

        if(!empty($mobile)){
            $where[] = ['c.cli_mobile','like','%'. $mobile.'%'];
        }

        if(!empty($pro_mobile)){
            $where[] = ['a.asset_managerline','like','%'. $pro_mobile.'%'];
        }

        if (!empty($starttime)) {
            $where[] = ['c.comment_time','>=',strtotime($starttime.' 00:00:00')];
        }

        if (!empty($endtime)) {
            $where[] = ['c.comment_time','<=',strtotime($endtime.' 23:59:59')];
        }
		
		$searchinfo  = array();
		$searchinfo = request()->param();

        $list = model('Comment')->getCommentList($where,20,$searchinfo);

        //位置导航
        $breadCrumb=array(array('name'=>'资产评论列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
		$_GET['page'] = isset($_GET['page'])?$_GET['page']-1:0;
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$list->render());
		return $this->fetch();
	}


    /**
     * 查看详情
     * @param intval $[id] [<description>]
     * return false
     */
    public function info(){
        $where = [];
        $where['c.id']=input('id');
        $info = model('Comment')->getCommentList($where,1);
        $this->assign('info',$info);
        return $this->fetch();
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
        if(model('Comment')->del($id)){
            return ajaxReturn(200,'分类删除成功！');
        }else{
            return ajaxReturn(0,'分类删除失败！');
        }
    }
}
?>