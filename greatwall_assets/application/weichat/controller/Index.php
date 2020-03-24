<?php
namespace app\weichat\controller;
use org\weixin\Wechat;
use think\Controller;
use think\Db;
use think\facade\Cookie;
use app\index\controller\Personal;
use think\facade\Log;
use think\Request;
class Index extends Controller
{

    //首页
    public function index(){
		
		//echo '123';
		//get_weichat_options();
       //$weiObj=new Wechat(get_weichat_options());
      //return $weiObj->valid(true);//明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
        $this->autoReply();//请求自动回复
        //\think\facade\Log::write('########');
    }

    public function clearuser()
    {
        cookie('userid','');
        echo 'clear ok';
    }
	
	//公众号菜单跳转：
	public function redirecturl(){
		$url = input("nurl");
//        $weObj=new Wechat(get_weichat_options());
//        $tokenArr = $weObj->getOauthAccessToken();
//        $openid = '0';
//        \think\facade\Log::write($openid);
		header("location:$url");
	}

    public function reurl(){
        $weObj=new Wechat(get_weichat_options());
        $tokenArr = $weObj->getOauthAccessToken();
        $state = input('state');
        $openid = $tokenArr['openid'];
        Cookie::set('userid',$openid,604800);
        //if($state=='getopenid') {
            header('Location: http://vip.xinziyou.com/home/wxuser/binduser.html?openid=' . $openid);
        //}elseif($state=='userinfo')
        //{
        //    header('Location: http://vip.xinziyou.com/home/wxuser/userinfo.html?openid=' . $openid);
        //}
        exit;
    }
    /**
     * 自动回复
     */
    public function autoReply()
    {
        $redis = new \Redis;
        $request = new Request();
        $personal = new Personal($request);
        $redis->pconnect('127.0.0.1',6379);
        $redis->select(1);
        //回复
        $weObj=new Wechat(get_weichat_options());
        $type = $weObj->getRev()->getRevType();
        switch($type) {
            case Wechat::MSGTYPE_TEXT:
                //临时测试
//                if( $weObj->getRev()->getRevContent()=='link')
//                {
//                    $rurl = $weObj->getOauthRedirect('http://dongxian.test.snrunning.cn/weichat/index/reurl.html','getopenid');
//                    $weObj->text($rurl)->reply();
//                    break;
//                }

                if( $weObj->getRev()->getRevContent()=='getopenid')
                {                    
                    $weObj->text($weObj->getRev()->getRevFrom())->reply();
                    break;
                }





                //查询数据
                $openid = $weObj->getRev()->getRevFrom();
                $where[]=['openid','=',$openid];
//                $redisdb = $redis->get($openid);
                //####################################################################################################
                if( $weObj->getRev()->getRevContent()=='签到测试')
                {
                    $msg = '点击签到测试<a href="http://xjboc.wx.snrunning.cn/xjboc/index/login?openid='.$openid.'">签到</a>。';
                    $weObj->text($msg)->reply();
                    exit;
                }
                if($weObj->getRev()->getRevContent()=='长城陕西'){

                    //据openid获取用户unionid;
                    $userinfo = $weObj->getUserInfo($openid);
                    $unionid = $userinfo['unionid'];
                    $membeinfo = Db::name('entryinfo2019')
                                ->alias('e')
                                ->leftJoin(' member2019 m',' e.cli_openid=m.openid')
                                ->where('e.unionid',$unionid)
                                ->field(['userid','openid'])
                                ->find();
                    
                    if(empty($membeinfo['userid'])){
                        $weObj->text('查询不到员工相关信息，请核实！')->reply();
                        exit;
                    }
                    $userid = $membeinfo['userid'];
                    //根据userid更新员工微信公众号openid先查询用户是否更新过 无  更新 ，有 不进行操作

                    $staffinfo = Db::name('member2019')->where('userid',$userid)->field('wx_openid')->find();
                    if(empty($staffinfo['wx_openid'])){
                        Db::name('member2019')->where('userid',$userid)->update(['wx_openid'=>$openid]);
                    }
                    //根据用户openid获取员工的专属二维码

                    $filename = './Uploads/staff_two/chancheng_'.$userid.'.png';
                    if (file_exists($filename)) {

                        $ercodeimg = "https://cczc.snrunning.com/Uploads/staff_two/chancheng_".$userid.".png";
                        $Medianame = '@Uploads/staff_two/chancheng_'.$userid.'.png';

                        $media_id = '';
                        //根据userid查询media_id 是否存在,并判断是否过期
                        $mediainfo = Db::name('bingdingmedia2019')->where('userid',$userid)->find();
                        if(empty($mediainfo)){
                            $result = self::uploadMedia($Medianame);
                            if (!empty($result['media_id'])){
                                //根据media_id查询图片是否存在
                                Db::name('bingdingmedia2019')->insert(['userid'=>$userid,'media_id'=>$result['media_id'],'create_time'=>$result['created_at']]);
                                $media_id = $result['media_id'];
                            }
                        }else{
                            $time = time();
                            if($time-$mediainfo['create_time']>=259200){
                                $result = self::uploadMedia($Medianame);
                                if (!empty($result['media_id'])){
                                    //根据media_id查询图片是否存在
                                    Db::name('bingdingmedia2019')->where('userid',$userid)->update(['media_id'=>$result['media_id'],'create_time'=>$result['created_at']]);
                                    $media_id = $result['media_id'];

                                }
                            }else{
                                $media_id = $mediainfo['media_id'];
                            }
                        }

                        $weObj->image($media_id)->reply();
                        exit();

                    } else {
                        $weObj->text('您的专属二维码不存在，请核实！')->reply();
                        exit();
                    }

                }

                //查找关键字列表
                $keyword = $weObj->getRev()->getRevContent();
                $keywordinfo = Db::name('weichat_keywords')->where(['keywords_name'=>$keyword,'msg_type'=>'text','status'=>1])->find();
                if($keywordinfo){
                    if($keywordinfo['type'] == 'text'){ //文本回复
                        $weObj->text($keywordinfo['content'])->reply();
                    }else if($keywordinfo['type']=='news'){//图文回复
                        if(empty($keywordinfo['news_info'])){//调用微信图文回复
                            $info = $weObj->getForeverMedia(trim($keywordinfo['content']));
                            $result = array();
                            foreach ($info['news_item'] as $key=>$val){
                                $result[$key]['Title'] =$val['title'];
                                $result[$key]['Description'] =$val['digest'];
                                $result[$key]['PicUrl'] =$val['thumb_url'];
                                $result[$key]['Url'] =$val['url'];
                                if(!empty($val['source_url'])){
                                    $result[$key]['Url'] =$val['content_source_url'];
                                }
                            }
                        }else{//自定义图文回复
                            $info = unserialize($keywordinfo['news_info']);
                            $result = array();
                            foreach ($info as $key=>$value){
                                $result[$key]['Title'] =$value['title'];
                                $result[$key]['Description'] =$value['digest'];
                                $result[$key]['PicUrl'] =WEBURL.$value['cover_url'];
                                $result[$key]['Url'] =$value['content_url'];
                                if(!empty($value['source_url'])){
                                    $result[$key]['Url'] =$value['source_url'];
                                }
                            }

                        }
                        $weObj->news($result)->reply();

                    }
                }

                //先从Redis中查找

//                if(!$redis->exists($openid)){
//                    $info=Db::name('xzy_wxuser')->where($where)->find();
//                    if(!empty($info))
//                    {
//                        $name = empty($info['username'])?$info['realname']:$info['username'];
//                        $info['remark']=$name.'#'.$info['sex'].'#'.$info['mobile'];
//                        //设置用户备注
//                        $weObj->updateUserRemark($openid,$info['remark']);
//                        $redis->set($openid,json_encode($info));
//                    }else{
//                        $msg = '为了更好的为您提供服务，请您点击“个人中心”进行注册，或可直接点击此链接<a href="http://dongxian.test.snrunning.cn/home/wxuser/binduser.html?openid='.$openid.'">注册</a>。';
//                        $weObj->text($msg)->reply();
//                        exit;
//                    }
//                }else{
//                    $info = json_decode($redis->get($openid),true);
//                    $weObj->updateUserRemark($openid,$info['remark']);
//                }

                //加上工作时间判断：
//                if(date('H')>=9&&date('H')<18){
//                    //什么都不做
//                }else{
//                    //找出自动回复的问题内容
//                    $content = '';
//

//
//                    $msgArr = array();
//                    $msgArr['touser']=$openid;
//                    $msgArr['msgtype']='text';
//                    $msgArr['text']=['content'=>" 当前非工作时间，您可以留言，客服人员上班后回复。\n\n".$content];
//                    $weObj->sendCustomMessage($msgArr);
//
//                }
                $weObj->transfer_customer_service()->reply();

                break;
            case Wechat::MSGTYPE_EVENT:  //事件推送
                $info=$weObj->getRevEvent();
                $openid = $weObj->getRev()->getRevFrom();
                //判断是否点击事件
                if ($info['event']==Wechat::EVENT_MENU_CLICK){ //菜单点击事件
//                    \think\facade\Log::write($info);

                    $where = [
                        'createtime'=>date('Y-m-d',time()),
                        'menu_key'=>$info['key']
                    ];
                    $res = Db::name('weichat_menuhits')->where($where)->find();
                    if($res){
                        Db::name('weichat_menuhits')->where($where)->setInc('hits');
                    }else{
                        $data = [
                            'menu_key'=>$info['key'],
                            'createtime'=>date('Y-m-d',time()),
                            'hits'=>1
                        ];
                        Db::name('weichat_menuhits')->insert($data);
                    }
                    if($info['key'] == 'signineveryday'){
                        $keyword = Db::name('weichat_keywords')->where(['id'=>3])->find();
                        $result = array();
                        $msg = unserialize($keyword['news_info']);
                        foreach ($msg as $k=>$v){
                            $result[$k]['Title']=$v['title'];
                            $result[$k]['Description']=$v['digest'];
                            $result[$k]['PicUrl']=WEBURL.$v['cover_url'];
                            $result[$k]['Url']=WEBURL.'/xjboc/index/login?openid='.$openid;
                        }
                        $weObj->news($result)->reply();

                    }
                    if($info['key'] == 'yuyuefuwu'){
                        //找出栏目表中的对应的key，然后回复图文
                        $catwhere[] = ['menukey','=',$info['key']];
                        $cat = model('rncms/Category')->getWhereInfo($catwhere);
                        if($cat){
                            //这里找出这个绑定的栏目的类型，如果是文字，就直接推送文字
                            if(!empty($cat['description']))
                            {
                                $weObj->text($cat['description'])->reply();
                            }else{
                                $infowhere[] = ['class_id','=',$cat['id']];
                                $infowhere[] = ['status','=',1];
                                $newslist = model('rncms/Content')->loadData($infowhere,8);
                                $result = array();
                                foreach ($newslist as $key=>$value){
                                    $result[$key]['Title'] =$value['title'];
                                    $result[$key]['Description'] =$value['description'];
                                    $result[$key]['PicUrl'] ='http://'.$_SERVER['SERVER_NAME'].$value['image'];
                                    $result[$key]['Url'] =$_SERVER['SERVER_NAME'].url('xjboc/index/show',['content_id'=>$value['content_id']]);
                                    if(!empty($value['url'])){
                                        $result[$key]['Url'] =$value['url'];
                                    }
                                }
                                $weObj->news($result)->reply();
                            }
                        }else{ //找出click事件的图文推送
                            $keyword = Db::name('weichat_keywords')->where(['menu_key'=>$info['key'],'status'=>1,'msg_type'=>'menu_click'])->find();
                            if($keyword['type']=='text'){
                                $weObj->text($keyword['content'])->reply();
                            }else if($keyword['type']=='news'){
                                if(empty($keyword['news_info'])){
                                    $info = $weObj->getForeverMedia($keyword['content']);
                                    $result = array();
                                    foreach ($info['news_item'] as $key=>$val){
                                        $result[$key]['Title'] =$val['title'];
                                        $result[$key]['Description'] =$val['digest'];
                                        $result[$key]['PicUrl'] =$val['thumb_url'];
                                        $result[$key]['Url'] =$val['url'];
                                        if(!empty($val['source_url'])){
                                            $result[$key]['Url'] =$val['content_source_url'];
                                        }
                                    }
                                }else{
                                    $result = array();
                                    $msg = unserialize($keyword['news_info']);
                                    foreach ($msg as $k=>$v){
                                        $result[$k]['Title']=$v['title'];
                                        $result[$k]['Description']=$v['digest'];
                                        $result[$k]['PicUrl']=WEBURL.$v['cover_url'];
                                        $result[$k]['Url']=$v['content_url'];
                                    }
                                }

                                $weObj->news($result)->reply();
                            }

                        }
                    }else{
                        $data = "平台栏目正在建设中，敬请期待";
                        $weObj->text($data)->reply();exit;
                    }





                }else if($info['event']==Wechat::EVENT_MENU_VIEW){//点击跳转事件推送，保存当日点击次数
//                    \think\facade\Log::write($info);

                    $key_arr = explode('=',$info['key']);
                    $menu_key = urldecode($key_arr[1]);
                    $where = [
                        'createtime'=>date('Y-m-d',time()),
                        'menu_key'=>$menu_key
                    ];
                    $res = Db::name('weichat_menuhits')->where($where)->find();
                    if($res){
                        Db::name('weichat_menuhits')->where($where)->setInc('hits');
                    }else{
                        $data = [
                            'menu_key'=>$menu_key,
                            'createtime'=>date('Y-m-d',time()),
                            'hits'=>1
                        ];
                        Db::name('weichat_menuhits')->insert($data);
                    }
                }else if($info['event']==Wechat::EVENT_MENU_SCAN_WAITMSG){//扫描二维码返回二维码内容
                    $weObj->text($weObj->getRevScanInfo()['ScanResult'])->reply();
                }else if($info['event'] == Wechat::EVENT_SCAN){// 已关注 扫码事件推送 发送图文消息
                    $arr = explode('_',$info['key']);
//                    file_put_contents("/data/wwwroot/unicom.wx.snrunning.cn/res.log",$mobile);
                    /*** 扫描特制二维码事件推送，内容在关键字列表，Event参数为关键字名称***/
                    /*$dataa = Db::name('weichat_keywords')->where(['keywords_name'=>$info['key'],'status'=>1,'msg_type'=>'qrscene'])->find();
                    if($dataa){
                        $data = unserialize($dataa['news_info']);
                        $result = array();
                        foreach ($data as $k=>$v){
                            $result[$k]['Title']=$v['title'];
                            $result[$k]['Description']=$v['digest'];
                            $result[$k]['PicUrl']=WEBURL.$v['cover_url'];
                            $result[$k]['Url']=$v['content_url'];
                        }
                        $weObj->news($result)->reply();
                    }*/

                    if($arr[0] == 'chancheng'){
                        $userinfo = $weObj->getUserInfo($openid);
                        $unionid = $userinfo['unionid'];
                        $userid = $arr[1];
                        $result = $personal->bingdingcli($userid,$openid,$unionid);
                        $json_result = json_decode($result,true);
                        if($json_result['ret'] == 1){
                            //根据unionid判断用户信息是否存在
                            $info = Db::name('user_information2019')->where('unionId',$userinfo['unionid'])->find();
                            if(!empty($info)){
                                Db::name('user_information2019')->where('unionId',$userinfo['unionid'])->update(['wx_openId'=>$userinfo['openid']]);
                            }else{
                                $save = array(
                                    'nickName'=>$userinfo['nickname'],
                                    'gender'=>$userinfo['sex'],
                                    'language'=>$userinfo['language'],
                                    'city'=>$userinfo['city'],
                                    'province'=>$userinfo['province'],
                                    'country'=>$userinfo['country'],
                                    'avatarUrl'=>$userinfo['headimgurl'],
                                    'unionId'=>$userinfo['unionid'],
                                    'addtime'=>time()
                                );
                                Db::name('user_information2019')->insert($save);
                            }
                            $weObj->text($json_result['msg'])->reply();exit;
                        }else{
                            $weObj->text($json_result['msg'])->reply();exit;
                        }
                    }
//                    else if($info['key'] == 'someactivity'){//记录扫描该二维码次数
//                        Db::name('unicom_qrcode_hits')->where('EventKey',$info['key'])->setInc('scan');
//                    }
                }
                else if($info['event']==Wechat::EVENT_LOCATION)  {//上报地理位置
                    $locationArr = $weObj->getRevEventGeo();
                    $wherestr = array();
                    $wherestr[] = ['openid','=',$weObj->getRevFrom()];
                    Db::name('xzy_wxuser')->where($wherestr)->update(['lat'=>$locationArr['x'],'lng'=>$locationArr['y']]);
                }else if($info['event']==Wechat::EVENT_SUBSCRIBE){ //订阅事件推送
                    $userinfo = $weObj->getUserInfo($openid);

                    $info_arr = array(
                        'subscribe' => $userinfo['subscribe'],
                        'openid' => $userinfo['openid'],
                        'nickname' => base64_encode($userinfo['nickname']),
                        'sex' => $userinfo['sex'],
                        'city' => $userinfo['city'],
                        'province' => $userinfo['province'],
                        'country' => $userinfo['country'],
                        'headimgurl' => $userinfo['headimgurl'],
                        'tagid_list' => json_encode($userinfo['tagid_list']),
                        'subscribe_scene' => $userinfo['subscribe_scene']
                    );

                    //先判断该用户以前是否关注过
                    $res = Db::name('wxuser')->where('openid',$openid)->find();
                    if($res){
                        unset($info_arr['openid']);
                        $info_arr['addtime'] = time();
                        Db::name('wxuser')->where('openid',$openid)->update($info_arr);
                    }else{
                        Db::name('wxuser')->insert($info_arr);
                    }

                    $arr = explode('_',$info['key']);
                    if($arr[0]=="qrscene"){   //未关注扫描二维码事件推送
                        /*** 扫描特制二维码事件推送，内容在关键字列表，Event参数为关键字名称***/
                        /*$dataa = Db::name('weichat_keywords')->where(['keywords_name'=>$arr[1],'status'=>1,'msg_type'=>'qrscene'])->find();
                        if($dataa){
                            $data = unserialize($dataa['news_info']);
                            $result = array();
                            foreach ($data as $k=>$v){
                                $result[$k]['Title']=$v['title'];
                                $result[$k]['Description']=$v['digest'];
                                $result[$k]['PicUrl']=WEBURL.$v['cover_url'];
                                $result[$k]['Url']=$v['content_url'];
                            }
                            $weObj->news($result)->reply();exit;
                        }*/
                        if($arr[1] == 'chancheng'){
                            $userinfo = $weObj->getUserInfo($openid);
                            $unionid = $userinfo['unionid'];
                            $userid = $arr[2];
                            $result = $personal->bingdingcli($userid,$openid,$unionid);
                            $json_result = json_decode($result,true);
                            if($json_result['ret'] == 1){
                                $weObj->text($json_result['msg'])->reply();exit;
                            }else{
                                $weObj->text($json_result['msg'])->reply();exit;
                            }
                        }


                    }else{
                        $msg = Db::name('weichat_keywords')->where(['msg_type'=>'subscribe','status'=>1])->find();
                        $weObj->text($msg['content'])->reply();exit;
                    }
                    $msg = Db::name('weichat_keywords')->where(['msg_type'=>'subscribe','status'=>1])->find();
                    $weObj->text($msg['content'])->reply();exit;

                }else if($info['event']==Wechat::EVENT_UNSUBSCRIBE){//取消订阅事件推送
                    //标记用户状态
                    Db::name('wxuser')->where('openid',$openid)->setField('subscribe',0);
                }else if($info['event']==Wechat::EVENT_CARD_USER_GET) {//用户领取优惠券
                    //\think\facade\Log::write($weObj->getRevData());
                    $arr = $weObj->getRevData();
                    $data = [
                        'openid' => $arr['FromUserName'],
                        'createtime' => $arr['CreateTime'],
                        'cardid' => $arr['CardId'],
                        'cardcode' => $arr['UserCardCode'],
                        'status' => 1
                    ];
                    Db::name('xzy_coupon_user')->insert($data);
                    Db::name('xzy_coupon')->where('card_id',$arr['card_id'])->setDec('coupon_rest');//减少相应库存
                    /****根据网吧权益卡券活动下发模板消息*****/
                    //已在领取的操作里下发
//                    $msg = array();
//                    $msg['touser']=$arr['FromUserName'];
//                    $msg['template_id']='';
//                    $msg['url']=''; //点击模板消息跳到卡券详情页
//                    $msg['data']['first']['value']='';
//                    $msg['data']['keyword1']['value']='';
//                    $msg['data']['keyword2']['value']='';
//                    $msg['data']['remark']['value']='';
//                    $weObj->sendTemplateMessage($msg);


                }else if($info['event']==Wechat::EVENT_CARD_USER_DEL) { //用户删除卡券
                    $arr = $weObj->getRevData();
					//用户删除后,更新字段,不能删除
                    Db::name('xzy_coupon_user')->where('card_id',$arr['CardId'])->setField('status',5);

                    
                    //  \think\facade\Log::write($res_in);
                }else if($info['event'] ==Wechat::EVENT_CARD_GIFTING){//用户转赠卡券

                }else if($info['event']==Wechat::EVENT_CARD_CONSUME){//核销卡券
                    $arr = $weObj->getRevData();
                    $where = [
                        'card_id' => $arr['CardId'],
                        'openid' => $arr['FromUserName'],
                    ];
                    Db::name('xzy_coupon_user')->where($where)->setField('status',3);
                }


                //\think\facade\Log::write($weObj->getRevData()); //$weObj->getRevScanInfo()
                break;
            case Wechat::MSGTYPE_IMAGE:
                //$weObj->text('我还不能识别图片，还在学习中。')->reply();
                $weObj->transfer_customer_service()->reply();
                break;
            default:
                $weObj->text("no answer")->reply();
        }
    }
    /**
     * 创建菜单
     */
    public function weichatMenu(){
        //测试数组
        $data=array (
            'button' => array (
                0 => array (
                    'type' => 'click',
                    'name' => '今日歌曲',
                    'key' => 'V1001_TODAY_MUSIC',
                ),
            ),
        );
        $where['parent_id']=0;
        $where['weichat_id']=get_weichat_id();
        $list=Db::name('weichat_menu')->order('sort DESC,menu_id ASC')->where($where)->select();
        $menuData = [];
        if ($list){
            foreach ($list as $key=>$val) {
                $menuData[$val['menu_id']] = $this->_subMenu($val);
                $list_child=Db::name('weichat_menu')->order('sort DESC,menu_id ASC')->where(array('parent_id'=>$val['menu_id'],'weichat_id'=>get_weichat_id()))->select();
                if ($list_child){
                    unset($menuData[$val['menu_id']]['type']);
                    unset($menuData[$val['menu_id']]['key']);
                    unset($menuData[$val['menu_id']]['url']);
                    foreach ($list_child as $k=>$v){
                        $menuData[$val['menu_id']]['sub_button'][]=$this->_subMenu($v);
                    }
                }
            }
        }
        $weObj=new Wechat(get_weichat_options());
        $data['button']=array_values($menuData);
        if ($weObj->createMenu($data)){
            return ajaxReturn(200,'同步成功');
        }else{
            return ajaxReturn(0,'同步失败');
        }
    }
    public function _subMenu($data) {
        switch ($data['type']){
            case 1:
                $menuData['type'] = 'click';
                $menuData['key'] = $data['key'];
                break;
            case 2:
                $menuData['type'] = 'view';
                $menuData['url'] = $data['key'];
                break;
            default:
                $menuData['type'] = 'click';
                $menuData['key'] = $data['key'];
                break;
        }
        $menuData['name'] = $data['name'];
        return $menuData;

    }
    /**
     * 获取永久素材总数
     */
    public function getForeverCount(){
        $webObj=new Wechat(get_weichat_options());
        var_dump($webObj->getForeverCount());
    }
    /**
     *  获取永久素材列表(认证后的订阅号可用)
     */
    public function getForeverList($type='image',$offset='0',$count='10'){
        $webObj=new Wechat(get_weichat_options());
        var_dump($webObj->getForeverList($type,$offset,$count));
    }
    /**
     * 上传永久其他类型素材
     */
    public function uploadForeverMedia($url='/uploads/admin/20170419/8cb36838cc73b0bfdceff8f2c043b743.jpg', $type='image',$is_video=false,$video_info=array()){
        //判断相对路径
        var_dump(model('weichat/WeichatMaterialImage')->add($url, $type,$is_video,$video_info));
    }
    /**
     * 上传临时图片素材
     *  返回url
     */
    public function uploadImg(){
        $data['media'] = '@uploads/admin/20170419/8cb36838cc73b0bfdceff8f2c043b743.jpg';
        $webObj=new Wechat(get_weichat_options());
        var_dump($data);
        var_dump($webObj->uploadImg(($data)));
    }


    /**
     * 上传临时图片素材
     *  返回media_id，type,created_at
     */


    public function uploadMedia($filename){
        $data['media'] = $filename;
        $webObj=new Wechat(get_weichat_options());
        return $webObj->uploadMedia($data,'image');
    }

    /**
     * 上传永久图文素材
     */
    public function uploadForeverArticles(){
        $weiObj=new Wechat(get_weichat_options());
        /*$data=array(
            array(
                "title"=> '测试',
                "thumb_media_id"=> 'GgLJUrcKt-y2CyAuUKdQotAbSCog_jN10u2mqN7vyDE',//'GgLJUrcKt-y2CyAuUKdQojXXQ3nYhUTX0O9diy5VlVg',
                "author"=> 'hongkai',
                "digest"=> '新增摘要测试',
                "show_cover_pic"=> 1,
                "content"=> '图文消息内容',
                "content_source_url"=> 'http://www.baidu.com'
            )
        );*/
        $data[]=array(
            "title"=> '测试',
            "thumb_media_id"=> 'GgLJUrcKt-y2CyAuUKdQotAbSCog_jN10u2mqN7vyDE',//'GgLJUrcKt-y2CyAuUKdQojXXQ3nYhUTX0O9diy5VlVg',
            "author"=> 'hongkai',
            "digest"=> '新增摘要测试',
            "show_cover_pic"=> 1,
            "content"=> '图文消息内容',
            "content_source_url"=> 'http://www.baidu.com'
        );
        $data['articles']=$data;
        var_dump($weiObj->uploadForeverArticles($data));
    }
    public function test(){
        var_dump(model('Index')->updateForeverArticles());
    }
}
