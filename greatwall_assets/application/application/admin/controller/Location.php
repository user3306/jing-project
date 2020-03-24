<?php
namespace app\admin\controller;

use think\Db;

class Location extends Index{

    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '网点位置',
                'description' => '网点经纬度信息',
            ),
            'menu' => array(
                array(
                    'name' => '网点列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            '_info' => array(
                array(
                    'name' => '添加信息',
                    'url' => url('info'),
                ),
            ),
        );
    }
    /**
     * 周边网点位置经纬度信息表
     */
    public function index(){

        $list = Db::name('boc_location')->order('id desc')->paginate(15);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function info(){
        $id = input('id');
        if(input('post.')){
            $id = input('post.id/d');
            unset($_POST['id']);
            if(empty($id)){//新增
                $res = Db::name('boc_location')->insert($_POST);

            }else{//修改
                $res = Db::name('boc_location')->where('id',$id)->update($_POST);
            }
            if($res){
               return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }
        $info = Db::name('boc_location')->where('id',$id)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }





}