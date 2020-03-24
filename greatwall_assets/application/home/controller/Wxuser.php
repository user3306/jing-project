<?php
namespace app\home\controller;
use think\Controller;
use org\weixin\Wechat;
use think\Db;
use think\facade\Cookie;
use org\SignatureHelper;

class Wxuser extends Controller{

    public function tmpgetstore()
    {
        $model = model('XzyWxuser');
        $data = Db::table('rn_xzy_wxuser')->select();
        foreach ($data as $key=>$val)
        {
            $mobile = $val['mobile'];
            $result = $model->getVipData('{"nType":"1514101","vipCode":"'.$mobile.'"}');
            if(!empty($result))
            {
                $store =$result['Store'];
                Db::table('rn_xzy_wxuser')->where('mobile',$mobile)->data(['store' => $store])->update();
            }
            echo $mobile;
        }

    }
    //bind user
	public function binduser(){
		$model = model('XzyWxuser');
        if (input('post.')){

            $mobile = input('post.mobile');
            $openid = input("post.openid");
            $cryptstr = input("post.chknum");

            if($cryptstr!=session('cryptstr'))
            {
                return ajaxReturn(0,'验证码错误');
            }else{
                session('cryptstr','');
            }


            $where =  array();
            $where[] = ['mobile','=',$mobile];
            $info = $model->getWhereInfo($where);
            if($info)
            {
                $model->delInfo($where);
                //return ajaxReturn(0,'手机已被绑定');
            }
            $where = null;
            $where[] = ['openid','=',$openid];
            $info = $model->getWhereInfo($where);
            if($info)
            {
                $model->delInfo($where);
                //return ajaxReturn(0,'您已被绑定');
            }

            //用户数据存入redis db2 数据库中
            $redis = new \Redis;
            $redis->pconnect('127.0.0.1',6379);
            $redis->select(2);
            $redis->set($mobile,$openid);

            $result = $model->getVipData('{"nType":"1514101","vipCode":"'.$mobile.'"}');
            if(!empty($result))
            {
                $_POST['username'] =$result['Title'];
                $_POST['userindex'] =$result['SearchCode1'];
                $_POST['cardcode'] =$result['CardCode'];
                $_POST['store'] =$result['Store'];
            }
			

			$status=$model->add();
			
			
			
			//将用户数据存入DB1 数据库中
			$redis->select(1);
			$_POST['remark']=$_POST['realname'].'#'.$_POST['sex'].'#'.$_POST['mobile'];
			$redis->set($openid,json_encode($_POST));

            if($status!==false){
                Cookie::set('userid',$openid,60480000);
                return ajaxReturn(200,'注册成功',url('userinfo',['openid'=>$openid]));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            //找到手机品牌
            $ptype = $model->getptype();
            $this->assign('ptype',$ptype);
            $this->assign('openid',input('openid'));
            return $this->fetch();
        }
	}

	public function reginfo(){
        $model = model('XzyWxuser');
        $openid = input("openid");
        if (input('post.')) {
            $status=$model->edit();

            if($status!==false){
                return ajaxReturn(200,'操作成功',url('userinfo',['openid'=>$openid]));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            //找到手机品牌
            $ptype = $model->getptype();
            $this->assign('ptype',$ptype);
            $this->assign('openid',input('openid'));
            return $this->fetch();
        }
    }
	


    public function userinfo(){

        $weObj=new Wechat(get_weichat_options());
        if(Cookie::has('userid'))
            $openid = Cookie::get('userid');
        else{
            $rurl = $weObj->getOauthRedirect('http://vip.xinziyou.com/weichat/index/reurl.html','binduser');
            header('Location: ' . $rurl);
			exit;
        }
        //not reg
        $model = model('XzyWxuser');
        $where = array();
        $where[] = ['openid','=',$openid];
        $info = $model->getWhereInfo($where);
        if(empty($info))
        {
            header('Location: ' . url('binduser',['openid'=>$openid]));
        }

        $userarr = $weObj->getUserInfo($openid);

        $this->assign('headimgurl',$userarr['headimgurl']);
        $this->assign('nickname',$userarr['nickname']);
        //dump($userarr);
        return $this->fetch();

    }

    public function buyrecord(){
        $model = model('XzyWxuser');
        $info = $model->getInfo(cookie("userid"));
        
        $dataArr = Db::name('xzy_userpoints')->where('mobile',$info['mobile'])->select();        //
        $points = 0;
        if(empty($dataArr))
        {
           
        }else{
            foreach ($dataArr as $value)
            {
                $points=$points+$value['points'];
            }
        }
        $this->assign('userpoint',$points);
        $this->assign('result',$dataArr);
        return $this->fetch();
    }

    public function usercard(){
    	/*
        $model = model('XzyWxuser');
        $info = $model->getInfo(cookie("userid"));
        $result = $model->getVipData('{"nType":"1514103","vipCode":"'.$info['mobile'].'"}');
        if(empty($result['DisCount']))
        {
            $result['DisCount'][0]['Amount'] = '0';
            $result['DisCount'][0]['ProductSerial'] = '去门店消费就会获得哦';
            $result['DisCount'][0]['Title'] = '没有任何优惠券';
        }
        $this->assign('result',$result['DisCount']);
    	*/
        //修改为从本地数据库中获取


    	$cardDb = array();
    	$mcardDb = array();
    	$weObj=new Wechat(get_weichat_options());
    	//$cardlist = $weObj->getUserCardList(cookie("userid"),'');
        $cardlist = Db::name('xzy_cardgetrecord')->field('cardid,cardcode')->where('openid',cookie("userid"))->order('id','desc')->select();
    	if(!empty($cardlist))
    	{			
    		foreach($cardlist as $cardinfo)
    		{
    			$tmpcard = array();
    			$tmpcard['card_id']=$cardinfo['cardid'];
    			//找出卡券的价格
    			$carddata = Db::name('xzy_tickets')->field('amount')->where('card_id',$cardinfo['cardid'])->find();
    			$tmpcard['amount']=$carddata['amount'];
    			$tmpcard['bg']=$tmpcard['amount']/10;
    			//查询原始卡券信息
    			$tmpcardinfo = $weObj->getCardInfo($cardinfo['cardid']);
    			$cardtype = strtolower($tmpcardinfo['card']['card_type']);
    			$tmpcard['card_name']=$tmpcardinfo['card'][$cardtype]['base_info']['title'];
    			$tmpcard['notice']=$tmpcardinfo['card'][$cardtype]['base_info']['notice'];
    			//查询当前卡券有效期
    			$mycard = $weObj->checkCardCode($cardinfo['cardcode']);
    			$tmpcard['begin_time']=date('Y-m-d',$mycard['card']['begin_time']);
    			$tmpcard['end_time']=date('Y-m-d',$mycard['card']['end_time']);
    			$tmpcard['user_card_status']=$mycard['user_card_status'];
                $tmpcard['can_consume']=$mycard['can_consume'];
    			$tmpcard['code']=$cardinfo['cardcode'];
    			//$tmpcard['card_id']=$cardinfo['card_id'];
    			if($cardtype=='member_card'){
    				$mcardDb['cardinfo'][]=$tmpcard;
    			}else{
    				$cardDb['cardinfo'][]=$tmpcard;
    			}
    			
    		}
    	}else{

        }
    	
    	$sign = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        //dump($sign);
        $this->assign('sign',$sign);

        $cardSign = $weObj->getCardSign('',$card_id);      //GENERAL_COUPON
        //dump($cardSign);
        $this->assign('cardSign',$cardSign);
    	
    	//dump($mcardDb);
    	//dump($cardDb);
    	$this->assign('result',$cardDb['cardinfo']);
        return $this->fetch();
    }


    public function membercard(){
        /*
        $model = model('XzyWxuser');
        $info = $model->getInfo(cookie("userid"));
        $result = $model->getVipData('{"nType":"1514103","vipCode":"'.$info['mobile'].'"}');
        if(empty($result['DisCount']))
        {
            $result['DisCount'][0]['Amount'] = '0';
            $result['DisCount'][0]['ProductSerial'] = '去门店消费就会获得哦';
            $result['DisCount'][0]['Title'] = '没有任何优惠券';
        }
        $this->assign('result',$result['DisCount']);
        */
        $cardDb = array();
        $mcardDb = array();
        $weObj=new Wechat(get_weichat_options());
        $cardlist = $weObj->getUserCardList(cookie("userid"),'');
        if(!empty($cardlist['card_list']))
        {           
            foreach($cardlist['card_list'] as $cardinfo)
            {
                $tmpcard = array();
                $tmpcard['card_id']=$cardinfo['card_id'];
                //找出卡券的价格
                $carddata = Db::name('xzy_tickets')->field('amount')->where('card_id',$cardinfo['card_id'])->find();
                $tmpcard['amount']=$carddata['amount'];
                $tmpcard['bg']=$tmpcard['amount']/10;
                //查询原始卡券信息
                $tmpcardinfo = $weObj->getCardInfo($cardinfo['card_id']);
                $cardtype = strtolower($tmpcardinfo['card']['card_type']);
                $tmpcard['card_name']=$tmpcardinfo['card'][$cardtype]['base_info']['title'];
                $tmpcard['notice']=$tmpcardinfo['card'][$cardtype]['base_info']['notice'];

                if(strpos($tmpcard['card_name'],'至尊保') > 0){
                    $tmpcard['background_pic_url']='/static/home/images/mcard_zzb.png?ver=2';
                    $tmpcard['color']="#ffffff";
                }elseif(strpos($tmpcard['card_name'],'青春保') > 0){
                    $tmpcard['background_pic_url']='/static/home/images/mcard_qcb.png?ver=2';
                    $tmpcard['color']="#ffffff";
                }else{
                    $tmpcard['background_pic_url']='/static/home/images/mcard_wyb.png?ver=2';
                    $tmpcard['color']="#ffffff";
                }


                //查询当前卡券有效期
                $mycard = $weObj->checkCardCode($cardinfo['code']);
                $tmpcard['begin_time']=date('Y-m-d',$mycard['card']['begin_time']);
                $tmpcard['end_time']=date('Y-m-d',$mycard['card']['end_time']);
                $tmpcard['user_card_status']=$mycard['user_card_status'];
                $tmpcard['code']=$cardinfo['code'];
                //$tmpcard['card_id']=$cardinfo['card_id'];
                if($cardtype=='member_card'){
                    $mcardDb['cardinfo'][]=$tmpcard;
                }else{
                    $cardDb['cardinfo'][]=$tmpcard;
                }
                
            }
        }
        
        $sign = $weObj->getJsSign($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        //dump($sign);
        $this->assign('sign',$sign);

        $cardSign = $weObj->getCardSign('',$card_id);      //GENERAL_COUPON
        //dump($cardSign);
        $this->assign('cardSign',$cardSign);
        
        //dump($mcardDb);
        //dump($cardDb);
        $this->assign('result',$mcardDb['cardinfo']);
        return $this->fetch();
    }

    public function sendsms()
    {
        $mobile = input('mobile');
        $cryptstr = rand(1000, 9999);
        session('cryptstr', $cryptstr);
        //存储临时验证码，设置有效期为2个小时
        $redis = new \Redis;
        $redis->pconnect('127.0.0.1',6379);
        $redis->select(9);
        $redis->set($mobile, $cryptstr);
        $redis->expire($mobile,3600);

        $params = array ();

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAI9YCyrCh4EZEG";
        $accessKeySecret = "RBWaUiIrBOrVaNJIXXvCAs3ylc29G9";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $mobile;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "新自由通讯";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_126352367";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array (
            "code" => $cryptstr
        );

        // fixme 可选: 设置发送短信流水号
        //$params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        //$params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );

        return $content;
    }
	
    
}
