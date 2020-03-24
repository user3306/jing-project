<?php
namespace app\xjboc\controller;

/**
 * Class Enrol
 * @package app\xjboc\controller
 *  标准报名系统
 */
use think\Db;
use org\weixin\Wechat;

class Enrol extends Base{


    public function index(){
        $openid = input('openid');
        $act_id = input('act_id/d');
        $token = input('token');
        if($token !== md5(md5(TOKEN_KEY.$openid.$act_id))){
            closeWindowMsg('参数错误');
        }
        if(empty($openid) || empty($act_id)){
            closeWindowMsg('参数错误');
        }
        //获取活动配置信息
        $act_info = Db::name('boc_enrol')->where(['id'=>$act_id])->find();
        if($act_info['status'] == 0){
            closeWindowMsg('该活动未发布');
        }
        if($act_info['status'] == 2){
            closeWindowMsg('该活动已关闭');
        }
        if($act_info['start_time'] > time()){
            closeWindowMsg('该活动未开始');
        }
        if($act_info['end_time'] < time()){
            closeWindowMsg('该活动已结束');
        }

        $weObj=new Wechat(get_weichat_options());
        $signPackage = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        //记录访问信息
        $visit_info = [
            'act_id'=>$act_id,
            'openid'=>$openid,
            'createtime'=>time()
        ];
        $count = Db::name('boc_enrol_userdata_'.$act_id)->count('id');
        Db::name('boc_enrol_visit')->insert($visit_info);
        //获取banner图
        $banner = Db::name('boc_enrol_banner')->where(['act_id'=>$act_id])->select();
        $this->assign('banner',$banner);
        $this->assign('act_info',$act_info);
        $this->assign('token',$token);
        $this->assign('act_id',$act_id);
        $this->assign('openid',$openid);
        $this->assign('js_packeg',$signPackage);
        $this->assign('count',$count);
        return $this->fetch();
    }

    public function register(){
        if(input('post.')){
            $act_id = input('post.act_id');
            $openid = input('post.openid');
            $mobile = input('post.mobile');
            $realname = input('post.name');
            $white = input('post.white');
            if (!preg_match('/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])[0-9]{8}$/', $mobile)) {
                return ajaxReturn(0,'请输入正确的手机号码');die;
            }
            if(input('post.token') !== md5(md5(TOKEN_KEY.$openid.$act_id))){
                return ajaxReturn(0,'参数错误');
            }
            //检查是否有白名单
            if($white == 2){
                $check_white = Db::name('boc_enrol_white')->where(['act_id'=>$act_id,'mobile'=>$mobile])->find();
                if(!$check_white){
                    return ajaxReturn(0,'您未获得本期活动资格，请留意下期活动');
                }
            }
            $check = Db::name('boc_enrol_userdata_'.$act_id)->where(['openid'=>$openid])->field('id')->find();
            if(!$check){
                $data = [
                    'openid'=>$openid,
                    'act_id'=>$act_id,
                    'realname'=>$realname,
                    'mobile'=>$mobile,
                    'createtime'=>time()
                ];
                $res = Db::name('boc_enrol_userdata_'.$act_id)->insert($data);
                if($res){
                    return ajaxReturn(200,'报名成功');
                }else{
                    return ajaxReturn(0,'网络错误，请稍后重试');
                }
            }else{
                return ajaxReturn(0,'您已经报过名啦');
            }

        }else{
            return false;
        }
    }


}