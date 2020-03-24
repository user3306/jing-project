<?php
namespace app\xjboc\controller;

/**
 * 标准投票模块
 * 一个微信号只能投票一次
 */

use think\Db;

class Vote extends Base{


    public function index(){
        $act_id = input('act_id/d');
        $openid = input('openid');
        $token = input('token');
        if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
            closeWindowMsg("参数错误");exit();
        }
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        if(!empty($keyword)){
            if(is_numeric($keyword)){
                $where[] = ['num','=',trim($keyword)];
            }else{
                $where[] = ['username','like','%'.trim($keyword).'%'];
            }

        }else{
            $where = [];
        }
        $list = Db::name('boc_vote_member_'.$act_id)->order('votes desc,num asc')->where($where)->select();

//        Db::name('boc_vote_visit_'.$act_id)->insert(['openid'=>$openid,'createtime'=>time()]);
        //参与人数
        $play_num = Db::name('boc_vote_member_'.$act_id)->count(1);
        //累计投票
        $vote_num = Db::name('boc_vote_data_'.$act_id)->count(1);
        //访问量
        $visit_num = Db::name('boc_vote_visit_'.$act_id)->count(1);
        $info = [
            'play_num'=>$play_num,
            'vote_num'=>$vote_num,
            'visit_num'=>$visit_num
        ];

        //获取banner
        $banner_list = Db::name('boc_vote_banner')->where(['act_id'=>$act_id])->select();
        $vote_time = Db::name('boc_vote')->where(['id'=>$act_id])->field('start_time,end_time')->find();//获取活动时间
        if($vote_time['start_time'] > time()){
            closeWindowMsg("该活动还未开始");exit();
        }
        if($vote_time['end_time'] < time()){
            closeWindowMsg("该活动已结束");exit();
        }

        $this->assign('openid',$openid);
        $this->assign('token',$token);
        $this->assign('act_id',$act_id);
        $this->assign('info',$info);
        $this->assign('list',$list);
        $this->assign('banner_list',$banner_list);
        $this->assign('vote_time',$vote_time);
        return $this->fetch();
    }

    public function index_list(){
        if(input('post.')){
            $openid = input('post.openid');
            $act_id = input('post.act_id');
            $token = input('post.token');
            if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
                return json(array('code'=>-1,'msg'=>'参数错误'));
            }
            $page = input('post.page');
            $offset = 2;
            $limit = ($page-1) * $offset;
            $list = Db::name('boc_vote_member_'.$act_id)->order('votes desc,num asc')->limit($limit,$offset)->select();
            if(count($list)> 1){
                return json(array('code'=>200,'msg'=>'success','data'=>$list));
            }else{
                return json(array('code'=>100,'msg'=>'加载完成','data'=>$list));
            }

        }else{
            return json(array('code'=>-1,'msg'=>'参数错误'));
        }

    }


    /**
     * 详情
     */
    public function info(){
        $act_id = input('act_id/d');
        $openid = input('openid');
        $token = input('token');
        $user_id = input('user_id');
        if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
            closeWindowMsg("参数错误");exit();
        }
        $vote_time = Db::name('boc_vote')->where(['id'=>$act_id])->field('start_time,end_time')->find();
        if($vote_time['start_time'] > time()){
            closeWindowMsg("该活动还未开始");exit();
        }
        if($vote_time['end_time'] < time()){
            closeWindowMsg("该活动已结束");exit();
        }

        $info = Db::name('boc_vote_member_'.$act_id)->where(['id'=>$user_id])->find();
        $this->assign('act_id',$act_id);
        $this->assign('openid',$openid);
        $this->assign('token',$token);
        $this->assign('info',$info);
        $this->assign('vote_time',$vote_time);
        return $this->fetch();
    }

    /**
     * 规则页面
     */
    public function rule(){
        $act_id = input('act_id/d');
        $openid = input('openid');
        $token = input('token');
        if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
            closeWindowMsg("参数错误");exit();
        }

        $info = Db::name('boc_vote')->where('id',$act_id)->field('title,rule,start_time,end_time')->find();
        $this->assign('info',$info);
        $this->assign('act_id',$act_id);
        $this->assign('openid',$openid);
        $this->assign('token',$token);
        return $this->fetch();
    }

    /**
     * 排行页面
     */
    public function rank(){

        if($_POST){
            $act_id = input('post.act_id');
            $openid = input('post.openid');
            $token = input('post.token');
            if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
                return json(array('code'=>0,'msg'=>'参数错误'));
            }
            $page = input('post.page');
            $start = ($page-1)*10;
            $list = Db::query("SELECT t.*, @rownum := @rownum + 1 AS rownum FROM (SELECT @rownum := 0) r, (SELECT num,username,votes FROM rn_boc_vote_member_".$act_id." ORDER BY votes DESC,num ASC) AS t limit ".$start.",10;");

            if(count($list) >0){
                return json(array('code'=>200,'data'=>$list));
            }else{
                return json(array('code'=>100,'data'=>''));
            }
        }
        $act_id = input('act_id/d');
        $openid = input('openid');
        $token = input('token');
        if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
            closeWindowMsg("参数错误");exit();
        }


        $this->assign('act_id',$act_id);
        $this->assign('openid',$openid);
        $this->assign('token',$token);
        return $this->fetch();
    }

    /**
     * 投票
     * 一个用户只有一次投票机会
     */
    public function tovote(){
        if(input('post.')){
            //验证并判断
            $openid = input('post.openid');
            $act_id = input('post.act_id');
            $token = input('post.token');
            $user_id = input('post.user_id');
            if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
                return ajaxReturn(0,'参数错误');
            }
            $check = Db::name('boc_vote_data_'.$act_id)->where(['openid'=>$openid])->field('id')->find();
            if(!empty($check)){
                return ajaxReturn(0,'您已经投过票了');
            }
            $data = [
                'openid'=>$openid,
                'userid'=>$user_id,
                'act_id'=>$act_id,
                'createtime'=>time()
            ];
            $res = Db::name('boc_vote_data_'.$act_id)->insert($data);
            if($res){
                $rs = Db::name('boc_vote_member_'.$act_id)->where(['id'=>$user_id])->setInc('votes');
                if($rs){
                    return ajaxReturn(200,'投票成功');
                }else{
                    return ajaxReturn(0,'网络错误，请稍后再试！0');
                }
            }else{
                return ajaxReturn(0,'网络错误，请稍后再试！1');
            }

        }else{
            exit('error');
        }
    }





}