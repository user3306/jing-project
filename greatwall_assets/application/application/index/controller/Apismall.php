<?php
namespace app\index\controller;
use think\Controller;
use org\weixin\Wechat;
use think\Request;
use app\index\controller\Personal;
use think\Db;
use app\index\model\BindingModel;
use Wechat\wxBizDataCrypt;

class Apismall  extends  Controller
{
	
    private  $appid;
	private  $appsecret; 
	private  $action;
	private  $openid;
	private  $code;
	private  $username;
	private  $password;
	
	public function __construct()
	{
	/*private 访问符限制的属性仅在当前对象的内部使用*/
	//$this->appid = 'wx4e0d9ecfa67da4fd';
	//$this->appsecret = 'd13c35dbe37d9c9f543fdcd3d154b8be';
	/***长城资产小程序***/
		$getinfo=smallroutineinfo();
		$this->appid = $getinfo['appid'];
		$this->appsecret = $getinfo['appsecret'];

	

  }
	public function return_info($ret='-1',$msg='身份验证错误',$data=null)
    {
          return json_encode(array( 'ret' => $ret , 'msg' => $msg , 'data' => $data));
    }
    public function islogin(Request $request)
    {
        //echo $this->appid;
		$data2			= $request->post();
		$this->openid	= isset($data2['openid'])  	 ? $data2['openid']   : '';

		$status=2;
		$backinfo='必要参数缺失';
		$job=null;
		if (empty($this->openid )){
			$status=2;
			$backinfo='必要参数缺失';
		}
		$info=Db::table('changcheng_member2019')->where('openid',$this->openid)->find();
		if(empty($info)){
			$status=3;
			$backinfo='还未登录过';
		}else{
			if ( !empty( $info['is_disabled'] ) && $info['is_disabled'] ==1 ) {
				$status=4;
				$backinfo='用户已经被封号';
			}else{
				$status=0;
				$backinfo='已登录';
				
				$job=array('job'=>$info['job']);
			}
		}
		echo $this->return_info($status,$backinfo,$job);
		exit;
    }
	public function firstentry(Request $request)
    {
        //echo $this->appid;
        $data2			= $request->post();
		$this->openid	= isset($data2['openid'])  	 ? $data2['openid']   : '';
		$status=2;
		$backinfo='必要参数缺失';
		$job=null;
		if (empty($this->openid )){
			$status=2;
			$backinfo='必要参数缺失';
		}
		$job=array('cli_mobile'=>'no','unionid'=>'no');
		$info=Db::name('entryinfo2019')->where('cli_openid',$this->openid)->find();

		if(empty($info)){
			$status=1;
			$backinfo='无更新信息';
			$job=array('cli_mobile'=>'no','unionid'=>'no');
		}else{
			
			$status=1;
			$backinfo='有更新信息';
			$job=array('cli_mobile'=>'yes','unionid'=>'yes');
			if ( empty( $info['cli_mobile'] )  ) {
				
				$job['cli_mobile']='no';
			}
			if(empty( $info['unionid'] )){
				$job['unionid']='no';
			}
		}
		echo $this->return_info($status,$backinfo,$job);
		exit;
		//print_r($data2);
    } 
    //员工登录 功能暂时去掉

	public function login(Request $request)
    {
        //echo $this->appid;
		//echo  $this->return_info();
		$data2			= $request->post();
		$this->openid	= isset($data2['openid'])  	 ? $data2['openid']   : '';
		$this->username	= isset($data2['username'])  ? $data2['username'] : '';
		$this->password	= isset($data2['password'])  ? $data2['password'] : '';
		
		if ( empty($this->openid ) || empty($this->username) || empty( $this->password ) ) {
			echo $this->return_info(2,'必要参数缺失');
			exit;
		}
		$info=Db::table('changcheng_member2019')->where('openid',$this->openid)->find();
		
		if(is_array($info)){
			echo $this->return_info(3,'您已经登录过了');
			exit;
		}
		$info=Db::table('changcheng_member2019')->where('userid',$this->username)->find();
		if(empty($info)){
			echo $this->return_info(4,'您输入的员工号不存在!');
			exit;
		}
		//是否已经绑定
		if(isset($info['openid'])&&!empty($info['openid'])){
			echo $this->return_info(5,'您输入的EHR号已经被绑定!');
			exit;
		}
		//密码是否正确
		if ( $this->password!=$info['password'] ) {

			echo $this->return_info(6,'密码输入错误!');
			
			exit;

		}else{
			//进行登录绑定
			$gengxin=Db::name('member2019')->where('userid', $this->username)->update(['openid' => $this->openid,'login_time'=>date('Y-m-d H:i:s')]);
			if($gengxin==0){
				echo $this->return_info(7,'登录失败，请稍后重试');
			
				exit;
				
			}else{
				echo $this->return_info(1,'登录成功',array('job'=>$info['job']));
				exit;
				
				
			}

		}
    } 
	
	
	//获取小程序openid
	
	public function getopenid(Request $request)
    {
        //echo $this->appid;
		
		$data2= $request->post();
		$this->action    = isset($data2['action'])  ? $data2['action'] : '' ;
		//$this->openid= isset($data2['openid'])  ? $data2['openid'] : '';
		$this->code= isset($data2['code'])  ? $data2['code'] : '';

		if($this->action!='get_openid'){
			echo $this->return_info(2,'必要参数缺失');
			exit;
		}
		if(empty($this->code)){
			echo $this->return_info(2,'必要参数缺失');
			exit;
		}

		$result =  file_get_contents("https://api.weixin.qq.com/sns/jscode2session?appid=".$this->appid."&secret=".$this->appsecret."&js_code=".$this->code."&grant_type=authorization_code");
		$data   = json_decode($result,true);
		/*
		    expires_in:7200
            openid:"oa-UZ0SU789io-iFt20c1R-aqRt4"
            session_key:"nEwCMnoJhd/4U+ufSijObA=="
            unionid:"oc4e0wFd1iaA1aVG4wFY9Ye1AXsw"
		 */

		if(isset($data['openid'])){
			$cehck=Db::table('changcheng_entryinfo2019')->where('cli_openid',$data['openid'])->find();
			if(empty($cehck)){
				$datas = ['cli_openid' => $data['openid'], 'add_time' => date('Y-m-d H:i:s')];
				if(isset($data['unionid'])){
					$datas = ['cli_openid' => $data['openid'], 'unionid'=>$data['unionid'],'add_time' => date('Y-m-d H:i:s')];
				}
				//Db::name('entryinfo2019')->strict(false)->insert($datas);
			}else{
				if(isset($data['unionid'])){
					//Db::name('entryinfo2019')->where('cli_openid', $data['openid'])->update(['unionid' => $data['unionid']]);
				}
			}
			echo $this->return_info(0,'获取成功',$data);
		}else{
			echo $this->return_info(2,'身份获取失败');
		}
		//echo
    }
	
	//获取小程序unionid ，手机号码
	/***********
	action  phone_auth  电话
	action  user_auth  用户信息
	
	**********/
	
	public function getunionid(Request $request)
    {
        $this->appid;
		$data2= $request->post();
		//$this->action    = isset($data2['action'])  ? $data2['action'] : '' ;
		//$this->openid= isset($data2['openid'])  ? $data2['openid'] : '';
		$this->code= isset($data2['code'])  ? $data2['code'] : '';
		$action=isset($data2['action'])  ? $data2['action'] : 'user_auth';
		$encryptedData=isset($data2['encryptedData'])  ? $data2['encryptedData'] : '';
		$iv=isset($data2['iv'])  ? $data2['iv'] : '';
		
		$qr_path = "./logs/";
		if(!file_exists($qr_path)){
			mkdir($qr_path, 0700,true);//判断保存目录是否存在，不存在自动生成文件目录
		}
		
		file_put_contents($qr_path.'err.log',json_encode($data2).PHP_EOL,FILE_APPEND);
		if(empty($this->code)||empty($action)||empty($encryptedData)){
			
			echo $this->return_info(2,'必要参数缺失');
			exit;
		}
		
		$result =  file_get_contents("https://api.weixin.qq.com/sns/jscode2session?appid=".$this->appid."&secret=".$this->appsecret."&js_code=".$this->code."&grant_type=authorization_code");

		/*$strs = stripslashes($result);*/
		$data   = json_decode($result,true);
		//dump($data);
		
		if(isset($data['openid'])){
			
			 $this->openid=$data['openid'];
		}else{
			
			echo $this->return_info(2,'身份获取失败');
			exit;
		}
		//查看有没有,没有则更新数据
		$cehck=Db::table('changcheng_entryinfo2019')->where('cli_openid',$this->openid)->find();
		if(empty($cehck)){
			$datas = ['cli_openid' => $this->openid, 'add_time' => date('Y-m-d H:i:s')];
			Db::name('entryinfo2019')->strict(false)->insert($datas);
		}
		$session_key=$data['session_key'];
		$this->return_info(1,'身份获取成功',$data2);
		$pc = new wxBizDataCrypt($this->appid,$session_key);
		$errCode = $pc->decryptData($encryptedData, $iv, $datat);
		
		if ($errCode == 0){
			$datat=json_decode($datat,true);
			unset($datat['watermark']);
			unset($datat['timestamp']);
			unset($datat['appid']);
            $datat['addtime'] = time();
			if($action=='phone_auth'){
			    $usertype = 2;
			    //根据手机号查询白名单是否存在
                $info = Db::name('member2019')->where('mobile',$datat['purePhoneNumber'])->find();
                if(!empty($info)){
                    $usertype = 1;
                    Db::name('member2019')->where('mobile', $datat['purePhoneNumber'])->update(['openid' => $this->openid,'login_time'=>date('Y-m-d H:i:s')]);
                    //调用personal控制器 触发生成二维码
                    $request = new Request();
                    $personal = new Personal($request);
                    $personal->getcode($this->openid,1);
                }
				Db::name('entryinfo2019')->where('cli_openid', $this->openid)->update(['cli_mobile' => $datat['purePhoneNumber'],'usertype'=>$usertype]);
                Db::name('user_information2019')->where('openId', $this->openid)->update(['purePhoneNumber' => $datat['purePhoneNumber']]);
                
			}elseif($action=='user_auth'){
			    //根据openid判断用户信息是否存在
                $info = Db::name('user_information2019')->where('unionId',$datat['unionId'])->find();
                if(empty($info)){
                    $datat['nickName'] = self::str2hex($datat['nickName']);
                    Db::name('user_information2019')->data($datat)->insert();
                }else{
                    Db::name('user_information2019')->where('unionId',$datat['unionId'])->update(['openId'=>$datat['openId']]);
                }
				
               // 查询小程序openid判断用户有没有进行扫码
                $smallbinding = Db::name('small_binding2019')->where('cli_openid',$this->openid)->field('userid')->find();
                if(!empty($smallbinding)){
                    $insertdata =  ['userid' => $smallbinding['userid'], 'cli_openid' => $this->openid ,'unionid'=>$datat['unionId'],'bingding_time' => date('Y-m-d H:i:s'), 'bingding_date' => date('Y-m-d')];
                    $smallbind = BindingModel::where('unionid',$datat['unionId'])->field('id')->find();
                    if(empty($smallbind)){ //说明用户没有进行公众号扫码 也没有进行小程序扫码 插入数据
                        BindingModel::insert($insertdata);
                    }else{ //说明用户过扫码进行公众号扫码 也没有进行小程序扫码 插入数据

                        BindingModel::where('unionid',$datat['unionId'])->update($insertdata);
                    }
                }
			    Db::name('entryinfo2019')->where('cli_openid', $this->openid)->update(['unionid' => $datat['unionId']]);
			}
			echo $this->return_info(1,'身份获取成功');
		} else {
			echo $this->return_info(2,'身份获取失败');
		}
    }


    /*
     * 存储微信昵称头像emoji表情转存
     */
    function str2hex($str){
		$hex = base64_encode($str);
		
        /* $hex = '';
        for($i=0,$length=mb_strlen($str); $i<$length; $i++){
            $hex .= dechex(ord($str{$i}));
        }*/
        return $hex ;

    }

    /*
     * 存储微信昵称头像emoji表情显示
     */

    function hex2str($hex){
        $str = base64_decode($hex);
        /* $arr = str_split($hex, 2);
        foreach($arr as $bit){
            $str .= chr(hexdec($bit));
        } */
        return $str;
    }


	/*public function selectinfo(Request $request){
		$datas=$request->post();
		$info=Db::name('assetinfo2019')->where('id','>',20)->select();
	}
	public function accesstokencg(){
		//print_r($sifd);
		//进行绑定

	}*/
}
