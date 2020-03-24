<?php
namespace app\weichat\controller;
use think\Controller;
use app\admin\controller\Admin;
use \org\weixin\Wechat;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/21 0021
 * Time: 下午 4:37
 */
class AdminWeichat extends Admin{
    public $weObj='';
    public $wehchatId=1;
    public $options=array();

    public function __construct(\think\facade\Request $request){
        parent::__construct($request);
        /* 设置路由参数 */
    }
    //当任何函数加载时候  会调用此函数
    public function initialize(){//默认的方法  会自动执行 特征有点像构造方法
//        $where['is_bind']=1;
//        $weichat_info=model('Weichat')->getWhereInfo($where);
//        $this->options = array(
//            'token' => $weichat_info['token'], //填写你设定的key
//            'encodingaeskey' => $weichat_info['encodingaeskey'], //填写加密用的EncodingAESKey
//            'appid' => $weichat_info['appid'], //填写高级调用功能的app id, 请在微信开发模式后台查询
//            'appsecret' => $weichat_info['secret'] //填写高级调用功能的密钥
//        );
        //$this->weObj = new Wechat($options); //创建实例对象

        /*$weObj = new Wechat($options); //创建实例对象
        $type = $weObj->getRev()->getRevType();
        switch($type) {
            case Wechat::MSGTYPE_TEXT:
                $weObj->text("33测试测试测试")->reply();
                break;
            case Wechat::MSGTYPE_EVENT:
                break;
            case Wechat::MSGTYPE_IMAGE:
                break;
            default:
                $weObj->text("help info")->reply();
        }*/
    }
}