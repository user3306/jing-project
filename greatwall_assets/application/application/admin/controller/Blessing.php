<?php
namespace app\admin\controller;

use think\Db;
class Blessing extends Admin{

    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '祝福管理',
                'description' => '祝福管理',
            ),
            'menu' => array(
                array(
                    'name' => '祝福列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),

        );
    }

    public function index(){

        $list = Db::name('blessing')->order('createtime desc')->paginate(20);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function del(){
        $id = input('id/d');
        $res = Db::name('blessing')->delete($id);
        if($res){
            return ajaxReturn('200','操作成功');
        }else{
            return ajaxReturn('0','操作失败');
        }

    }

}
