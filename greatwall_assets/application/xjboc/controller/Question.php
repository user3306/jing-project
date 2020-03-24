<?php
namespace app\xjboc\controller;

/**
 * Class Questionnaire
 * @package app\xjboc\controller
 * 标准问卷调查
 */

use think\Db;
use org\weixin\Wechat;

class Question extends Base{


    public function index(){
        if(input('post.')){
            $token = input('post.token');
            $act_id = input('post.act_id');
            $openid = input('post.openid');
            $username = input('post.username');
            $mobile = input('post.mobile');
            if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
                return ajaxReturn(0,' 系统参数错误');
            }
            //判断是否有白名单限制
            $white_conf = Db::name('boc_question')->where(['id'=>$act_id])->value('white');
            if($white_conf == 2){
                $check_white = Db::name('boc_question_white_'.$act_id)->where(['mobile'=>$mobile])->find();
                if(empty($check_white)){
                    return ajaxReturn(0,'您没有获得本次活动资格，请关注其他活动！');
                }
            }

            $check = Db::name('boc_question_user_'.$act_id)->where(['openid'=>$openid])->find();
            if($check){
                return ajaxReturn(0,'您已参与过本次调查，请勿重复参加');
            }

            if(!preg_match('/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])[0-9]{8}$/', $mobile)){
                return ajaxReturn(0,'请输入正确的手机号码');
            }
            if(empty($username)){
                return ajaxReturn(0,'姓名不能为空');
            }


//            $data = [
//                0=>[
//                  'openid' => 'oKEt6uFuNfU5w0SS3MOECx86PNbw',
//                   'question_id' => 1,
//                   'answer' => 'A',
//                  ],
//                1=>[
//                  'openid' => 'oKEt6uFuNfU5w0SS3MOECx86PNbw',
//                   'question_id' => 1,
//                   'answer' => 'A',
//                  ],
//                2=>[
//                  'openid' => 'oKEt6uFuNfU5w0SS3MOECx86PNbw',
//                   'question_id' => 1,
//                   'answer' => 'A',
//                  ],
//            ];
            //把post数据组成如上格式

            $data = array();
            foreach ($_POST['more'] as $k=>$v){
                foreach ($v as $val){
                    $data[] = [
                        'openid' => $openid,
                        'question_id' => $k,
                        'answer' => $val
                    ];
                }
            }
            foreach ($_POST['data'] as $k=>$v){
                $data[] = [
                    'openid' => $openid,
                    'question_id' => $k,
                    'answer' => $v
                ];
            }

            Db::name('boc_question_user_'.$act_id)->insert(['openid'=>$openid,'username'=>$username,'mobile'=>$mobile,'createtime'=>time()]);
            $res = Db::name('boc_question_answer_'.$act_id)->insertAll($data);
            if($res){
                return ajaxReturn(200,'感谢您的参与');
            }else{
                return ajaxReturn(0,'网络错误，请稍后重试');
            }

        }

        $weObj = new Wechat(get_weichat_options());
        $signPackage = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        $openid = input('openid');
        $act_id = input('act_id');
        $token = input('token');
        if($token != md5(md5(TOKEN_KEY.$openid.$act_id))){
            closeWindowMsg("参数错误");exit();
        }

        //获取配置信息，判断白名单；因为无法获取用户手机号，只能在提交的时候判断用户手机号是否为白名单
        $info = Db::name('boc_question')->where(['id'=>$act_id])->find();
        if($info['status'] == 0 || $info['status'] == 2){
            closeWindowMsg("活动未发布");exit();
        }
        if($info['start_time'] > time()){
            closeWindowMsg("活动未开始，请于".date('Y-m-d',$info['start_time'])."参与");exit();
        }
        if($info['end_time'] < time()){
            closeWindowMsg("活动已结束");exit();
        }

        //获取banner，试题等等信息
        $banner_list = Db::name('boc_question_banner')->where(['act_id'=>$act_id])->select();

        //获取问卷题目列表

        $question_one = Db::name('boc_question_question_'.$act_id)->where(['type'=>1])->order('id asc')->field('id,type,question,option_a,option_b,option_c,option_d')->select();
        $question_more = Db::name('boc_question_question_'.$act_id)->where(['type'=>2])->order('id asc')->field('id,type,question,option_a,option_b,option_c,option_d')->select();
        $question_text = Db::name('boc_question_question_'.$act_id)->where(['type'=>3])->order('id asc')->field('id,type,question,option_a,option_b,option_c,option_d')->select();
//        if($info['white'] == 2){//有白名单限制
//
//        }
        Db::name('boc_question_visit_'.$act_id)->insert(['openid'=>$openid,'createtime'=>time()]);
        $this->assign('question_one',$question_one);
        $this->assign('question_more',$question_more);
        $this->assign('question_text',$question_text);
        $this->assign('openid',$openid);
        $this->assign('act_id',$act_id);
        $this->assign('token',$token);
        $this->assign('js_packeg',$signPackage);
        $this->assign('info',$info);
        $this->assign('banner_list',$banner_list);
        return $this->fetch();
    }



}