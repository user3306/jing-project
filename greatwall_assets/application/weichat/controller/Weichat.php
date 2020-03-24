<?php
namespace app\weichat\controller;
use app\admin\controller\Admin;
/**
 * 后台公众号
 */
class Weichat extends Admin {
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '公众号管理',
                'description' => '管理网站后台管理员',
                ),
            'menu' => array(
                    array(
                        'name' => '公众号列表',
                        'url' => url('index'),
                        'icon' => 'list',
                    ),
                ),
            '_info' => array(
                    array(
                        'name' => '添加公众号',
                        'url' => url('info'),
                    ),
                ),
            );
    }
	/**
     * 列表
     */
    public function index(){
        //查询数据
        $list = model('Weichat')->loadList();
        //模板传值
        $this->assign('list',$list);
        $this->assign('_page',$list->render());
        return $this->fetch();
    }
    /**
     * 详情
     */
    public function info(){
        $weichatId = input('weichat_id');
        $model = model('Weichat');
        if (input('post.')){
            if ($weichatId){
                $status=$model->edit();
            }else{
                $status=$model->add();
            }
            if($status!==false){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $this->assign('info', $model->getInfo($weichatId));
            return $this->fetch();
        }
    }

    /**
     * 删除
     */
    public function del(){
        $weichatId = input('id');
        if(empty($weichatId)){
            return ajaxReturn(0,'参数不能为空');
        }
        if($weichatId == 1){
            return ajaxReturn(0,'保留公众号无法删除');
        }
        if(model('Weichat')->del($weichatId)){
            return ajaxReturn(200,'公众号删除成功！');
        }else{
            return ajaxReturn(0,'公众号删除失败');
        }
    }
}

