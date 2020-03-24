<?php

/***
 * 模板消息模块
 */

namespace app\admin\controller;

use think\Db;
use \org\weixin\Wechat;

class Pfault extends Admin{

    protected function _infoModule(){

        return array(
            'info'  => array(
                'name' => '模板消息',
                'description' => '模板消息',
            ),
            'menu' => array(
                array(
                    'name' => '模板消息列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
            ),
            '_info' => array(
                array(
                    'name' => '一键获取模板列表',
                    'url' => url('getTemplateList'),
                    'function'=>'ajax'
                ),
                array(
                    'name' => '新建模板消息',
                    'url' => url('add'),
                ),
            ),
        );


    }

    /**
     * 模板消息列表
     */
    public function index(){

        $list = Db::table('rn_pfault')->alias('a')
            ->join('rn_wx_tag b','a.tagid=b.tagid')
            ->field('a.*,b.id as bid,b.tagid,b.tagname')
            ->order('createtime desc')->paginate(15);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 新建模板消息
     */
    public function add(){
        $id = input('id/d');
        if(input('post.')){
            if($id){
                $res = model('Pfault')->edit($_POST,$id);
            }else{
                $_POST['createtime'] = time();
                $_POST['status'] = 0;
                $res = model('Pfault')->add($_POST);
            }
            if($res){
                return ajaxReturn('200','操作成功');
            }else{
                return ajaxReturn('0','操作失败');
            }

        }

        $info = model('Pfault')->getInfo($id);
        $this->assign('info',$info);
        $templist = Db::name('pfault_template_list')->field('id,template_id,title')->order('id desc')->select();
        $tags = Db::name('wx_tag')->select();
        $this->assign('tags',$tags);
        $this->assign('templist',$templist);
        return $this->fetch();
    }

    /**
     * 预览发送 测试
     */
    public function priview(){
        $id = input('id/d');
        if(input('post.')){
            $weixin = new Wechat(get_weichat_options());
            $info = model('Pfault')->getInfo(input('post.id'));

            $arr = array();
            $arr['touser']=input('post.openid');
            $arr['template_id']=$info['template_id'];
            $arr['url']=$info['url'];
            $arr['data']['first']['value']=$info['first'];
            $arr['data']['keyword1']['value']=$info['keyword1'];
            $arr['data']['keyword2']['value']=$info['keyword2'];
            $arr['data']['keyword3']['value']=$info['keyword3'];
            $arr['data']['keyword4']['value']=$info['keyword4'];
            $arr['data']['remark']['value']=$info['remark'];
            $res = $weixin->sendTemplateMessage($arr);

            if($res['errcode'] == 0){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn('0','操作失败');
            }

        }
        $info = model('pfault')->getInfo($id);
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 立即发送模板消息
     */
    public function nowsend()
    {
        if (input('post.')) {
            $weixin = new Wechat(get_weichat_options());
            $id = input('post.id/d');
            $info = model('Pfault')->getInfo($id);
            $tagid = input('post.tagid/d');
            $limit = input('post.limit');
            $len = 100;
            if ($tagid == 124) {//合伙人标签
                $openid_arr = Db::name('unicom_partner')->order('id asc')->limit($limit, $len)->column('openid');
                $all = Db::name('unicom_partner')->count();
            } elseif ($tagid == 125) {//员工标签
                $openid_arr = Db::name('unicom_member_info')->order('id asc')->limit($limit, $len)->column('openid');
                $all = Db::name('unicom_member_info')->count();
            } else {
//                $openid_arr = Db::name('wxuser')->order('id asc')->where('tagid_list', $tagid)->limit($limit, $len)->column('openid');
//                $all = Db::name('wxuser')->count();
                return ajaxReturn(2,'此标签不能直接发送');
            }

            if(empty($openid_arr)){
                return ajaxReturn(200,'全部发送完毕');
            }


            foreach ($openid_arr as $v) {
                $data = [
                    'openid'=>$v,
                    'template_id'=>$info['template_id'],
                    'pfault_id'=> $id,
                    'status'=>1,
                    'time'=>time(),
                ];
                Db::name('pfault_info')->insert($data);
                $arr = array();
                $arr['touser'] = $v;
                $arr['template_id'] = $info['template_id'];
                $arr['url'] = $info['url'];
                $arr['data']['first']['value'] = $info['first'];
                $arr['data']['keyword1']['value'] = $info['keyword1'];
                $arr['data']['keyword2']['value'] = $info['keyword2'];
                $arr['data']['keyword3']['value'] = $info['keyword3'];
                $arr['data']['keyword4']['value'] = $info['keyword4'];
                $arr['data']['remark']['value'] = $info['remark'];
                $res = $weixin->sendTemplateMessage($arr);
                if($res['errcode'] !==0){
                    return ajaxReturn(0,'发送失败');
                }

            }
            $count = Db::name('pfault_info')->where('pfault_id',$id)->count();
            $num = $count/$all*100;
            $msg = [
                'num'=>$num,
                'id'=>$id
            ];

            return ajaxReturn(1,$msg);

        }
    }




    /**
     * 筛选对象(全部粉丝)
     */
    public function selectsend(){
        if(input('post.')){
            $id = input('post.id');
            $tagid = input('post.tagid');
            $limit = input('post.limit');
            $len = 100;
            if($tagid > 0){
                return ajaxReturn(2,'此标签不能筛选');
            }
            if($tagid == -20000){
                $all = 20000;
                if ($limit==$all){
                    Db::name('pfault')->where('id',$id)->setField('status',1);
                    return ajaxReturn(200,'筛选完成');
                }
            }else{
                $all = Db::name('wxuser')->where('subscribe',1)->count();
                if($limit==$all){
                    Db::name('pfault')->where('id',$id)->setField('status',1);
                    return ajaxReturn(200,'筛选完成');
                }
            }

            //先判断此条消息是否被筛选过
            $info = Db::name('pfault')->where('id',$id)->find();
            if($info['status'] == 1){
                return ajaxReturn(3,'不能重复筛选');
            }
            $openid_arr = Db::name('wxuser')->where('subscribe',1)->order('id asc')->limit($limit,$len)->column('openid');
            foreach ($openid_arr as $v){
                $data = [
                    'openid'=>$v,
                    'template_id'=>$info['template_id'],
                    'pfault_id'=>$id,
                    'status'=>0,
                    'time'=>$info['sendtime']
                ];
                $res = Db::name('pfault_info')->insert($data);
                if(!$res){
                    return ajaxReturn(0,'筛选中断');
                }
            }
            $count = Db::name('pfault_info')->where('pfault_id',$id)->count();
            $num = $count/$all*100;
            $msg = [
                'num'=>$num,
                'id'=>$id
            ];
            return ajaxReturn(1,$msg);
        }
    }

    /**
     * 获取微信模板消息列表
     */
    public function getTemplateList(){
        $weixin = new Wechat(get_weichat_options());
        $template_list = $weixin->getAllTemplate();
        //去重
        $data = [];
        foreach ($template_list as $k=>$v){
            $info = Db::name('pfault_template_list')->where('template_id',$v['template_id'])->find();
            if(!$info){
                $data[$k]['template_id'] = $v['template_id'];
                $data[$k]['title'] = $v['title'];
                $data[$k]['primary_industry'] = $v['primary_industry'];
                $data[$k]['deputy_industry'] = $v['deputy_industry'];
                $data[$k]['content'] = $v['content'];
                $data[$k]['example'] = $v['example'];
            }
        }

        $res = model('PfaultTemplateList')->addAll($data);
        if(!$res){
            return false;
        }
        return ajaxReturn(200,'获取成功',url('index'));
    }


    public function del(){
        $id = input('id/d');
        if($id){
//            $status = Db::name('pfault')->where('id',$id)->value('status');
//            if($status == 0){
//                return ajaxReturn(0,'该消息未发送,不能删除');
//            }

            $res = Db::name('pfault')->where('id',$id)->delete();
            if($res){
                return ajaxReturn('200','删除成功');
            }else{
                return ajaxReturn('0','操作错误');
            }
        }else{
            return ajaxReturn('0','网络错误');
        }

    }




}