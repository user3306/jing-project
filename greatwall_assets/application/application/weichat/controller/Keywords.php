<?php
namespace app\weichat\controller;
use app\admin\controller\Admin;
use think\Db;
use org\weixin\Wechat;

class Keywords extends Admin{

    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '自动回复设置',
                'description' => '设置关键字自动回复功能',
            ),
            'menu' => array(
                array(
                    'name' => '关键字列表',
                    'url' => url('index'),
                    'icon' => 'exclamation-circle',
                )
            ),
            '_info' => array(
                array(
                    'name' => '添加规则',
                    'url' => url('info'),
                ),
            ),
        );
    }


    /**
     * 关键字列表
     */
    public function index(){
        $list = Db::name('weichat_keywords')->order('id desc')->paginate(15);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 详情
     */
    public function info(){
        $id = input('id/d');
        $info = Db::name('weichat_keywords')->where('id',$id)->find();
        if(input('post.')){
            $news_info = $_POST['news_info'];

            $data = [
                'keywords_name'=>input('post.keywords_name'),
                'type'=>input('post.type'),
                'title'=>input('post.title'),
                'msg_type'=>input('post.msg_type'),
                'content'=>input('post.content'),
                'menu_key'=>input('post.menu_key'),
                'status'=>input('post.status'),
                'news_info'=>serialize($news_info),
                'createtime'=>time()
            ];
            if(input('post.id/d')){
                $res = Db::name('weichat_keywords')->where('id',input('post.id/d'))->update($data);
            }else{
                $res = Db::name('weichat_keywords')->insert($data);
            }
            if($res){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }
        if(!empty($info['news_info']) && $info['type']=='news'){
            $news = unserialize($info['news_info']);
            $this->assign('news',$news);
        }
        $news = unserialize($info['news_info']);
        $this->assign('news',$news);
        $this->assign('info',$info);
        return $this->fetch();
    }


//    public function test(){
//        $weObj=new Wechat(get_weichat_options());
//        $info = $weObj->getForeverMedia('N2UOrwOXAGV5Ivpi--cyP4QG_4J6T5xu-tS5vvqtqWw');
//        foreach ($info['news_item'] as $v){
//
//        }
//        dump($info);die;
//    }

    public function del(){
        $id = input('id/d');
        if($id){
            $res = Db::name('weichat_keywords')->delete($id);
            if($res){
                return ajaxReturn(200,'删除成功');
            }else{
                return ajaxReturn(0,'网络错误');
            }
        }else{
            return ajaxReturn(0,'系统错误');
        }

    }




}