<?php
/**
 * 筛选列表
 * @auther:caizhuan
 * @date:20190426
 */
namespace app\admin\controller;
use app\admin\controller\Admin;

class ScreendataList extends Admin{
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
                'name' => '筛选管理',
                'description' => '管理网站的筛选',
            ),
            'menu' => array(
                array(
                    'name' => '筛选列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            // '_info' => array(
            //     array(
            //         'name' => '添加资产',
            //         'url' => url('info'),
            //         'icon' => 'plus',
            //     ),
            // )
        );
    }


	/**
	 * 筛选列表
	 * @param  string $[username] [<description>]
	 * @param string $[mobile] [<description>]
	 * return 
	 */
	public function index(){
		//筛选条件
        $where = array();
        $starttime = input('starttime');    //开始时间
        $endtime = input('endtime');        //结束时间

        if (!empty($starttime)) {
            $where[] = ['s.screendate','>=',strtotime($starttime.' 00:00:00')];
        }

        if (!empty($endtime)) {
            $where[] = ['s.screendate','<=',strtotime($endtime.' 23:59:59')];
        }

        $list = model('Screendata')->getScreendataList($where,20);

        //位置导航
        $breadCrumb=array(array('name'=>'筛选列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$list->render());
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
        if(model('Asset')->del($id)){
            return ajaxReturn(200,'员工删除成功！');
        }else{
            return ajaxReturn(0,'员工删除失败！');
        }
    }
}
?>