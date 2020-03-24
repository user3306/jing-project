<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/26/026
 * Time: 15:25
 */

namespace app\index\controller;
use app\index\model\ArticleModel;
use app\index\model\BindingModel;
use app\index\model\UserModel;
use app\index\model\CommentModel;
use app\index\controller\Apismall;
use app\index\model\AssettypeModel;
use org\weixin\Wechat;
use function rdbc\auth\controller\menu;
use think\Controller;
use think\Db;
use Picture\ImageManager;
class Test extends  Controller
{
    public function comm($ret='',$msg='',$data=''){
        $api = new Apismall();
        return $api->return_info($ret=$ret,$msg=$msg,$data=$data);
    }
    /**
     *生成小程序二维码和公众号
     *传参数openid
     *
     **/
    public function getcode($cli_openid='oa-UZ0SU789io-iFt20c1R-aqRt4',$type=2){
        if(empty($cli_openid)){
            $data2=$this->request->post();
            $cli_openid =isset($data2['cli_openid'])  ? $data2['cli_openid'] : '';
        }
        //根据openid获取用户userid

        //生成小程序二维码开始


        $userid = self::getUserid($cli_openid);
        $accesstoken = smallaccesstoken();
        $useri="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$accesstoken";

        $qr_path = "./Uploads/";
        if(!file_exists($qr_path.'user_one/')){
            mkdir($qr_path.'user_one/', 0700,true);//判断保存目录是否存在，不存在自动生成文件目录
        }
        $filename = 'user_one/'.$userid.'.png';
        $file = $qr_path.$filename;
        if(file_exists($file)){
            $smallcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename;
            //print_r($_SERVER['SERVER_NAME'].'/Uploads/'.$filename);
            //exit;
        }else{
            $date=array(
                'scene'=>'userid='.$userid,
                'page'=>'',
                'width'=>430,
                'auto_color'=>false

            );
            $result=PostWeixin($useri,json_encode($date));
            $errcode = json_decode($result,true);
            if($errcode['errcode']) {
                echo self::comm(3,'小程序二维码生成失败');
                die();
            }
            $res = file_put_contents($file,$result);//将微信返回的图片数据流写入文件
            if($res===false){
                echo self::comm(4,'微信公众号二维码生成失败');
                die();
            }else{
                $smallcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename;
            }
        }
        //生成小程序二维码结束

        //生成微信二维码开始

        $qr_path = "./Uploads/";
        if(!file_exists($qr_path.'user_two/')){
            mkdir($qr_path.'user_two/', 0700,true);//判断保存目录是否存在，不存在自动生成文件目录
        }
        $filename = 'user_two/'.'chancheng_'.$userid.'.png';

        $file = $qr_path.$filename;
        if(file_exists($file)){
            $wechatcode=$wechatcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename;
        }else{
            $wxObj = new Wechat(get_weichat_options());
            $data = $wxObj->getQRCode('chancheng_'.$userid,2);
            $ticket=$wxObj->getQRUrl($data['ticket']);
            $res = file_put_contents($file,$ticket);//将微信返回的图片数据流写入文件
            if($res===false){
                echo self::comm(2,'二维码生成失败');
                die();
            }else{
                $wechatcode=$wechatcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename;
            }
        }

        dirname(dirname(dirname(__DIR__)));
		
        $picFile = dirname(dirname(dirname(__DIR__))).'/public/Uploads/'.$filename;
        $newFile = dirname(dirname(dirname(__DIR__))).'/public/Uploads/'.$filename;
        $water = array('waterName'=>'static/home/images/ew.png','waterPos'=>5);
        ImageManager::imageUpload($picFile, $newFile, 430, 430, 2, $water);
		
        if($type == 2){
            echo self::comm(1,'二维码生成成功',array('smallcode'=>$smallcode,'wechatcode'=>$wechatcode));
        }
    }

    public function getUserid($cli_openid){
        $userid = '';
        $userinfo = UserModel::where('openid',$cli_openid)->field(['userid','department_head'])->find();
        if(!empty($userinfo)){
            $userid = $userinfo['userid'];
        }
        return $userid;
    }
	 
	public function updatenikeName(){
		die;
		$api = new Apismall();
        $userinfo = Db::name('user_information2019')
                            ->alias('u')
                            ->leftJoin(' getopenid2019 b',' u.unionid=b.unionid')
                            ->field(['u.id as uid','b.wx_openid as wx_openid'])
                            ->select();
		//echo Db::name('user_information2019')->getLastSql();die;

        $weObj = new Wechat(get_weichat_options());
         foreach ($userinfo as $key=>$val){
            $info = $weObj->getUserInfo($val['wx_openid']);
			if(!empty($info['nickname'])){
				$nickname = $api->str2hex($info['nickname']);
				Db::name('user_information2019')->where('id',$val['uid'])->update(['nickName'=>$nickname]);
			}else{
				echo $val['wx_openid'].'<br />';
			}
            
        } 
	}

	public function demo(){
		echo self::hex2str('e5bca0e5');
	}
	public function getOpenid(){
	
        $weObj = new Wechat(get_weichat_options());
		$userinfo = Db::name('getopenid2019')->field(['id','wx_openid'])->select();
		foreach($userinfo as $k=>$v){
			$info = $weObj->getUserInfo($v['wx_openid']);
			if(!empty($info['unionid'])){
				//根据unionid判断用户信息是否存在
				$infos = Db::name('user_information2019')->where('unionId',$info['unionid'])->find();
				if(!empty($infos)){
					Db::name('user_information2019')->where('unionId',$info['unionid'])->update(['wx_openId'=>$info['openid']]);
				}else{
					$save = array(
						'wx_openId'=>$info['openid'],
						'nickName'=>base64_encode($info['nickname']),
						'gender'=>$info['sex'],
						'language'=>$info['language'],
						'city'=>$info['city'],
						'province'=>$info['province'],
						'country'=>$info['country'],
						'avatarUrl'=>$info['headimgurl'],
						'unionId'=>$info['unionid'],
						'addtime'=>time()
					);
					Db::name('user_information2019')->insert($save);
				}
				
				
				//Db::name('getopenid2019')->where('id',$v['id'])->update(['unionid'=>$info['unionid']]);
			}else{
				echo $v['wx_openid'].'<br />';
			}
			
		} 
		die;
        $wx_openid = $weObj->getUserList();
        //dump($wx_openid['data']['openid']);
		$openid_res = $wx_openid['data']['openid'];
		foreach($openid_res as $k=>$v){
			Db::name('getopenid2019')->insert(['wx_openid'=>$v]);
		}
    }
}