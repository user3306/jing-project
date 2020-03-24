<?php
namespace app\home\controller;
use org\weixin\Wechat;
use think\Db;

class Index extends Site{
    //首页
    public function index(){


//        $redis = new \Redis;
//        $redis->pconnect('127.0.0.1',6379);
//        $redis->select(1);
//
//        $redis->set('test_redis',date('Y-m-d H:i:s'));
//        $str = $redis->get('test_redis');
//        $info=Db::name('xzy_wxuser')->where('id',22)->find();
//        dump($info);

		return 'Resources can not be found.';
		exit;
        //MEDIA信息
        $media=$this->getMedia();
        $this->assign('media', $media);
        $this->assign('crumb', array());
        //给模版给以一个当前时间戳的值
        $this->assign('demo_time',$this->request->time());
        return $this->siteFetch(get_site('tpl_index'));
    }

    public function testjs()
    {
        $weObj=new Wechat(get_weichat_options());

        $redis = new \Redis;
        $redis->pconnect('127.0.0.1',6379);
        $redis->select(1);

        $info = json_decode($redis->get('oUOag03qN5rjIVp_G10vEN_xpenY'),true);
        dump($info);

       
        //return $this->fetch();
    }

    public function addtoredis(){
        set_time_limit(0);

        $redis = new \Redis;
        $redis->pconnect('127.0.0.1',6379);
        $redis->select(10);

        $weObj=new Wechat(get_weichat_options());
        $userlist = $weObj->getUserList();

        echo $userlist['count'];
        echo '--';

        foreach ($userlist['data']['openid'] as $openid){
            $redis->lPush('openidlist',$openid);
        }


        $next_openid = $userlist['next_openid'];
        for($i=0;$i<4;$i++){
            $userlist = $weObj->getUserList($next_openid);
            if(!empty($userlist)){
                foreach ($userlist['data']['openid'] as $openid){
                    $redis->lPush('openidlist',$openid);
                }
                $next_openid = $userlist['next_openid'];
                echo $userlist['count'];
                echo '--';
            }

        }
        echo 'success';

    }

    /**
     * 用户领取卡券
     * 记录领取记录，同步更新本地卡券数量
     */
    public function getcard(){
        $openid = input('openid');
        $card_id = input('card_id');
        $cryptstr = input('token');
        if ($cryptstr !== md5('unicom'.$openid.$card_id.$openid)){
            die('参数错误');
        }
        $weObj=new Wechat(get_weichat_options());
        $sign = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        //ajax签名
        $token = md5('unicom'.$openid.$card_id);
        $this->assign('token',$token);
        $this->assign('openid',$openid);
        $this->assign('sign',$sign);
        $cardSign = $weObj->getCardSign('',$card_id);
        $this->assign('cardSign',$cardSign);
        $this->assign('card_id',$card_id);
        return $this->fetch();
    }

    /**
     * 领取卡券的回调，发送模板消息
     * ajax发送，需验证
     */
    public function sendTemp(){
        if($_POST){
            $token = input('post.token');
            $openid = input('post.openid');
            $card_id = input('post.card_id');
            if($token !==md5('unicom'.$openid.$card_id)){
                return false;
            }
            //发送模板消息
            $weObj=new Wechat(get_weichat_options());
            $url = WEBURL.'/home/index/cardinfo/openid/'.$openid.'/card_id/'.$card_id.'/token/'.$token;
            $arr = array();
            $arr['touser']=$openid;
            $arr['template_id']='ZX-4Go0ZrFW1S9USGdxTux_STneWZl0aFj30hoH_RWk';
            $arr['url']=$url;
            $arr['data']['first']['value']='卡券领取成功';
            $arr['data']['keyword1']['value']='卡券下发';
            $arr['data']['keyword2']['value']=date('Y-m-d H:i:s');
            $arr['data']['remark']['value']='您领取的卡券已经存入您的卡包，请注意查看';
            $weObj->sendTemplateMessage($arr);
            return ajaxReturn(200,'操作成功');
        }
    }


    /**
     * 获取单一卡券信息
     */
    public function cardinfo(){
        $openid = input('openid');
        $card_id = input('card_id');
        $cryptstr = input('token');
        if ($cryptstr !== md5('unicom'.$openid.$card_id)){
            die('参数错误');
        }
        $weObj=new Wechat(get_weichat_options());
        $sign = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        $this->assign('sign',$sign);
        $cardSign = $weObj->getCardSign('',$card_id);
        $this->assign('cardSign',$cardSign);
        $this->assign('card_id',$card_id);

        return $this->fetch();
    }





    public function taguser()
    {
        set_time_limit(0);
        $weObj=new Wechat(get_weichat_options());
        //$userlist = $weObj->getUserList();

        //dump($userlist);
        //echo $userlist['count'];
        //echo '--';
        /*
        foreach ($userlist['data']['openid'] as $openid){
            $redis->lPush('openidlist',$openid);
        }


        $next_openid = $userlist['next_openid'];
        for($i=0;$i<4;$i++){
            $userlist = $weObj->getUserList($next_openid);
            if(!empty($userlist)){
                foreach ($userlist['data']['openid'] as $openid){
                    $redis->lPush('openidlist',$openid);
                }
                $next_openid = $userlist['next_openid'];
                echo $userlist['count'];
                echo '--';
            }

        }
        */

        $redis = new \Redis;
        $redis->pconnect('127.0.0.1',6379);
        $redis->select(10);

        $allnum = $redis->lLen('openidlist');
        $count=0;
        $openid = array();
        for($i=0;$i<$redis->lLen('openidlist');$i++){
            //50个一个循环
            $openid[] = $redis->rPop('openidlist');
            $count++;
            if($count==50||$i==$allnum-1){
                $weObj->batchUpdateTagsMembers(131,$openid);

                if($i!=$allnum-1){
                    echo '<script>window.location="/home/index/taguser.html";</script>';
                }
                break;

            }

        }

        echo 'ok';
    }



    //注册
    public function reg(){
        if (input('post.')){
            $status=model('User')->add();
            if ($status>0){
                return ajaxReturn(200,'注册成功',url('login'));
            }else{
                return ajaxReturn(0,'注册失败');
            }
        }else{
            return $this->siteFetch('reg');
        }
    }
    //登录
    public function login(){
        if (input('post.')){
            $email = input('post.email');
            $passWord = input('post.password');
            if(empty($email)||empty($passWord)){
                return ajaxReturn(0,'用户名或密码未填写！');
            }
            //查询用户
            $model = model('User');
            $map = array();
            $map['email'] = $email;
            $userInfo = $model->getWhereInfo($map);
            if(empty($userInfo)){
                return ajaxReturn(0,'登录用户不存在！');
            }
            if($userInfo['status']!=1){
                return ajaxReturn(0,'该用户已被禁止登录！');
            }
            if($userInfo['password']<>md5($passWord)){
                return ajaxReturn(0,'您输入的密码不正确！');
            }
            if($model->setLogin($userInfo)){
                return ajaxReturn(200,'登录成功',url('user/userHome'));
            }else{
                return ajaxReturn(0,'登录失败！');
            }
        }else{
            return $this->siteFetch('login');
        }
    }
    //退出登录
    public function loginOut(){
        model('User')->logout();
        return redirect('index/index');
    }
}
