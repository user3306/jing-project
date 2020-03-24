<?php
/**
 * 湖南行湘江新区中行业务
 */

namespace app\xjboc\controller;

use org\weixin\Wechat;
use think\Db;

class Index extends Base{


    public function initialize(){

        parent::initialize();

    }



    public function index(){

        echo "hello";

    }


    /**
     * @return mixed
     * 单一图文展示
     */
    public function show(){
        $content_id = input('content_id');
        if(empty($content_id)){
            closeWindowMsg('参数错误');die();
        }
        $where = [
            'content_id'=>$content_id,
            'status'=>1
        ];
        $info = model('Content')->getOne($where);
        if(empty($info)){
            closeWindowMsg('没有内容');die();
        }else{
            $this->assign('info',$info);
        }
        return $this->fetch();
    }


    /**
     * 每日签到
     */
    public function sign(){
        $weObj=new Wechat(get_weichat_options());
        $signPackage = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        $openid = input('openid');
        $token = input('token');
        if($token !== md5(md5(TOKEN_KEY.$openid))){
            closeWindowMsg('登录失败');die();
        }
        //检查用户是否已经绑定手机号码
        $check_mob = model('Wxuser')->checkMob($openid);
        if(empty($check_mob)){
            header("Location:".url('login',array('openid'=>$openid)));
        }
        $check_have = Db::name('boc_signin')->where(['openid'=>$openid])->field('id')->find();
        if(empty($check_have)){
            $data = [
                'openid'=>$openid
            ];
            Db::name('boc_signin')->insert($data);
        }
        //判断今日日否签到
        $check = model('BocSignin')->check($openid);
        if($check){
            $check=1;
        }else{
            $check=0;
        }
        $this->assign('check',$check);

//        $info = Db::query("SELECT ANY_VALUE(a.integral) as integral,ANY_VALUE(a.openid) as openid,ANY_VALUE(a.total_time) as total_time,ANY_VALUE(a.running_time) as running_time,ANY_VALUE(b.nickname) as nickname,ANY_VALUE(b.headimgurl) as headimgurl,ANY_VALUE(COUNT(a.id)) as count_num from rn_boc_signin a JOIN rn_wxuser b ON a.openid=b.openid WHERE `a.openid`='$openid'");
        $count_num = Db::name('boc_signin')->count('id');
        $info = Db::table('rn_boc_signin')->alias('a')
            ->field('a.openid,a.integral,a.total_time,a.running_time,b.headimgurl,b.nickname')
            ->join('rn_wxuser b','a.openid=b.openid')
            ->where(['a.openid'=>$openid])
            ->find();
        $this->assign('info',$info);
        $this->assign('count_num',$count_num);
        $this->assign('token',$token);
        $this->assign('js_packeg',$signPackage);
        return $this->fetch();
    }

    /**
     * 初次登录
     */
    public function login(){
        $weObj=new Wechat(get_weichat_options());
        $signPackage = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        $openid = input('openid');
        $token = md5(md5(TOKEN_KEY.$openid));
        $res = Db::name('wxuser')->where('openid',$openid)->find();
        if(empty($openid)){
            closeWindowMsg('登录失败');die();
        }

        if(!empty($res['mobile'])){
            header("Location:".url('sign',array('openid'=>$openid,'token'=>$token)));
        }
        $this->assign('token',$token);
        $this->assign('openid',$openid);
        $this->assign('js_packeg',$signPackage);
        return $this->fetch();
    }
    /**
     * 绑定手机号码
     */
    public function bindmob(){
        if(input('post.')){
            $openid = input('post.openid');
            $mobile = input('post.mobile');
            if (!preg_match('/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])[0-9]{8}$/', $mobile)) {
                return ajaxReturn(0,'请输入正确的手机号码');
            }
            $res = Db::name('wxuser')->where('openid',$openid)->setField('mobile',$mobile);
            $check = Db::name('boc_signin')->where(['openid'=>$openid])->field('id')->find();
            if(!$check){
                Db::name('boc_signin')->insert(['openid'=>$openid]);
            }
            if($res){
                return ajaxReturn(200,'操作成功');
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }
    }

    /**
     * 排行榜
     */
    public function ranking(){
        $weObj=new Wechat(get_weichat_options());
        $signPackage = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        $openid = input('openid');
        if(empty($openid)){
            closeWindowMsg('登录失败');die();
        }
        $token = input('token');
        if($token !== md5(md5(TOKEN_KEY.$openid))){
            closeWindowMsg('登录失败');die();
        }


        //该用户当前排名
//        $rank = Db::query("SELECT COUNT(id) as rank FROM `rn_boc_signin` where integral>(SELECT integral FROM `rn_boc_signin` WHERE openid='$openid')");
        $rank = Db::query("SELECT * FROM (SELECT openid,(@rowNum:=@rowNum+1) AS rowNo
FROM `rn_boc_signin`,(SELECT(@rowNum:=0)) b ORDER BY integral DESC,addtime desc) c 
WHERE `openid`='$openid';");
        $rank = $rank[0]['rowNo'];
        //排行榜
        $list = Db::query("SELECT t.*, @rownum := @rownum + 1 AS rownum FROM (SELECT @rownum := 0) r, (SELECT s.integral,u.nickname FROM rn_boc_signin s JOIN rn_wxuser u ON s.openid=u.openid ORDER BY s.integral DESC,s.addtime desc limit 100) AS t");

        $this->assign('list',$list);
        $this->assign('rank',$rank);
        $this->assign('js_packeg',$signPackage);
        return $this->fetch();
    }


    /**
     * 签到
     * 连续签到7日，可额外获得积分：30分；
     * 连续签到30日，可额外获得积分：50分；
     * 连续签到180日，可额外获得积分：200分；
     * 连续签到365日，可额外获得积分：666分;
     */
    public function signIn(){
        if(input('post.')){
            $openid = input('post.openid');
            if(empty($openid)){
                return ajaxReturn(0,'参数错误');
            }
            $token = input('post.token');
            if($token !== md5(md5(TOKEN_KEY.$openid))){
                return ajaxReturn(0,'参数错误');
            }

            //判断今日有没有签到
            $check = model('BocSignin')->check($openid);
            if(empty($check)){
                //判断该用户上一天是否签到
                $time = time();
                $last_sign = model('BocSignin')->checkRunning($openid);
                if(!empty($last_sign) && $last_sign>=1){//是连续签到
                    switch ($last_sign){
                        case 7:
                            $add_integral = 40;
                            break;
                        case 30:
                            $add_integral = 60;
                            break;
                        case 180:
                            $add_integral = 210;
                            break;
                        case 365:
                            $add_integral = 676;
                            break;
                        default:
                            $add_integral = 10;
                    }
                    $res = Db::execute("UPDATE `rn_boc_signin` SET `integral`=`integral`+$add_integral,`addtime`=$time,`total_time`=`total_time`+1,`running_time`=`running_time`+1 WHERE `openid`='$openid'");
                    if($res){
                        return ajaxReturn(200,'签到成功,积分+'.$add_integral);
                    }else{
                        return ajaxReturn(0,'网络错误');
                    }

                }else{
                    $res = Db::execute("UPDATE `rn_boc_signin` SET `integral`=`integral`+10,`addtime`=$time,`total_time`=`total_time`+1,`running_time`=1 WHERE `openid`='$openid'");
                    if($res){
                        return ajaxReturn(200,'签到成功,积分+10');
                    }else{
                        return ajaxReturn(0,'网络错误');
                    }
                }
            }else{
                return ajaxReturn(0,'今天已经签过到啦');
            }
        }

    }



}