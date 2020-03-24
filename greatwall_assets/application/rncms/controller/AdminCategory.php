<?php
namespace app\rncms\controller;
use app\admin\controller\Admin;

/**
 * Class Article 文章栏目控制器类
 * dingzq hatabc@qq.com
 */
class AdminCategory extends Admin{
    /**
     * 当前模块参数
     */
    public function _infoModule() {
        $data = array('info' => array('name' => '栏目管理',
            'description' => '管理网站全部栏目',
        ),
            'menu' => array(
                array('name' => '栏目列表',
                    'url' => url('rncms/AdminCategory/index'),
                    'icon' => 'list',
                ),
            ),
        );
        $modelList = get_page_type();
        if (!empty($modelList)) {
            $i = 0;
            foreach ($modelList as $key => $value) {
                $data['_info'][$i]['name'] = '添加' . $value['name'] . '栏目';
                $data['_info'][$i]['url'] = url('info');
                $data['_info'][$i]['icon'] = 'plus';
                $i++;
            }
        }
        return $data;
    }
	//文章栏目列表
	public function index(){
		$where=array();
		$list=model('Category')->loadList($where);
		$this->assign('list',$list);
        $this->assign('type',array('0'=>'频道','1'=>'列表'));
		$this->assign('count',count($list));
		return $this->fetch();
	}

    /**
     * 修改栏目
     * @return mixed|\think\response\Json
     */
    public function info(){
        $class_id=input('post.id');
        $model = model('Category');
        if (input('post.')){
            $_POST['app'] = 'article'; //request()->module();
            if ($class_id){
                /*$check_status=$this->parentCheck();
                if ($check_status!==true){
                    return ajaxReturn(0,$check_status);
                }
                */
                $status=$model->edit();
            }else{
                $status=$model->add();
            }
            if($status){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $this->assign('categoryList',model('Category')->loadList());//分类
            $this->assign('tplList',model('admin/Config')->tplList());//模板文件
            //$this->assign('expandList',model('kbcms/FieldsetExpand')->loadList());//扩展字段
            $this->assign('info',$model->getInfo(input('id')));//页面信息
            return $this->fetch();
        }
    }

    /**
     * 栏目删除
     * @return \think\response\Json
     */
    public function del(){
        $class_id=input('id');
        if (empty($class_id)){
            return ajaxReturn(0,'参数不能为空');
        }
        //判断子栏目
        if(model('Category')->loadList(array(), $class_id)){
            return ajaxReturn(0,'请先删除子栏目！');
        }
        //判断栏目下内容
        /*
        $where = array();
        $where[] = ['A.class_id','=',$class_id];
        $contentNum = model('ContentArticle')->countList($where);
        if(!empty($contentNum)){
            return ajaxReturn(0,'请先删除该栏目下的内容！');
        }
        */
        //删除栏目操作
        if(model('Category')->del($class_id)){
            return ajaxReturn(200,'栏目删除成功！');
        }else{
            return ajaxReturn(0,'栏目删除失败！');
        }
    }

}
