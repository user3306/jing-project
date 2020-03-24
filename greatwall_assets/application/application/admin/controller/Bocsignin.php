<?php
namespace app\admin\controller;

use think\Db;
use org\weixin\Wechat;

class Bocsignin extends Admin{
    /**
     * 当前模块参数
     */
    protected function _infoModule()
    {
        return array(
            'info' => array(
                'name' => '签到管理',
                'description' => '签到积分管理',
            ),
            'menu' => array(
                array(
                    'name' => '用户列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
            ),
            '_info' => array(
                array(
                    'name' => '操作记录',
                    'url' => url('history'),
                ),
            ),
        );
    }

    public function index(){
        $keyword = input('keyword');
        if(!empty($keyword)){
            $res = is_numeric($keyword);
            if($res){
                $where = [
                    'mobile'=>$keyword
                ];
            }else{
                $nickname = base64_encode($keyword);
                $where[] = ['b.nickname','like','%'.$nickname.'%'];
            }

        }else{
            $where = [];
        }

        $list = Db::table('rn_boc_signin')->alias('a')
            ->field('b.headimgurl,b.nickname,b.mobile,a.integral,a.id,a.addtime,a.total_time,a.running_time,a.comment_integral,a.contribute_integral')
            ->join('rn_wxuser b','a.openid=b.openid')
            ->where($where)
            ->order('a.integral desc,a.addtime desc')
            ->paginate(15);

        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 操作记录/日志
     */
    public function history(){

        $list = Db::name('boc_integral_log')->alias('a')
            ->field('a.id,a.class,a.integral,a.integral_type,a.createtime,b.nickname,b.headimgurl')
            ->join('rn_wxuser b','a.openid=b.openid')
            ->order('createtime desc')
            ->paginate(15);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 修改积分操作
     */
    public function info(){
        $id = input('id/d');
        $info = Db::name('boc_signin')->where(['id'=>$id])->field('id,openid,comment_integral,contribute_integral')->find();
        if(input('post.')){
            $id = input('post.id/d');
            $type = input('post.type');
            $openid = input('post.openid');
            $integral = trim(input('post.integral'));
            if($type == 'comment_add'){
                $res = Db::name('boc_signin')->where(['id'=>$id])->inc('comment_integral',$integral)->inc('integral',$integral)->update();
                $data = [
                    'class'=>'add',
                    'integral'=>$integral,
                    'integral_type'=>1
                ];
            }elseif ($type == 'contribute_add'){
                $res = Db::name('boc_signin')->where(['id'=>$id])->inc('contribute_integral',$integral)->inc('integral',$integral)->update();
                $data = [
                    'class'=>'add',
                    'integral'=>$integral,
                    'integral_type'=>2
                ];
            }elseif ($type == 'comment_dec'){
                $res = Db::name('boc_signin')->where(['id'=>$id])->dec('comment_integral',$integral)->dec('integral',$integral)->update();
                $data = [
                    'class'=>'dec',
                    'integral'=>$integral,
                    'integral_type'=>1
                ];
            }elseif ($type == 'contribute_dec'){
                $res = Db::name('boc_signin')->where(['id'=>$id])->dec('contribute_integral',$integral)->dec('integral',$integral)->update();
                $data = [
                    'class'=>'dec',
                    'integral'=>$integral,
                    'integral_type'=>2
                ];
            }else{
                return ajaxReturn(0,'操作失败');
            }
            if($res){
                $data['createtime']=time();
                $data['openid']=$openid;
                Db::name('boc_integral_log')->insert($data);
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }


        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 解绑操作
     */
    public function unbind(){
        $id = input('id/d');
        if(empty($id)){
            return ajaxReturn(0,'参数错误');
        }
        $openid = Db::name('boc_signin')->where(['id'=>$id])->value('openid');
        $res = Db::name('wxuser')->where(['openid'=>$openid])->setField('mobile',null);
        if($res){
            return ajaxReturn(200,'操作成功',url('index'));
        }else{
            return ajaxReturn(0,'操作失败');
        }

    }


}