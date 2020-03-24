<?php
namespace app\admin\controller;

use think\Db;

class Banner extends Admin{

    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '轮播管理',
                'description' => '轮播管理',
            ),
            'menu' => array(
                array(
                    'name' => '轮播列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            '_info' => array(
                array(
                    'name' => '添加图片',
                    'url' => url('info'),
                ),
            ),
        );
    }

    public function index(){

        $list = Db::name('banner')->order('id desc')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function info(){
        $id = input('id/d');
        $info = Db::name('banner')->where('id',$id)->find();
        if($_POST){
            $data = [
                'title'=>input('post.title'),
                'description'=>input('post.description'),
                'image'=>input('post.image'),
                'url'=>input('post.url'),
                'listorder'=>input('post.listorder'),
                'status'=>input('post.status'),
            ];
            if(input('post.id')){
                $res = Db::name('banner')->where('id',input('post.id'))->update($data);
            }else{
                $res = Db::name('banner')->insert($data);
            }
            if($res){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function del(){
        $id = input('id/d');
        if($id){
            $res = Db::name('banner')->delete($id);
            if($res){
                return ajaxReturn('200','操作成功');
            }else{
                return ajaxReturn('0','操作失败');
            }
        }else{
            return ajaxReturn(0,'参数错误');
        }
    }

}