<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/20/020
 * Time: 16:36
 */

namespace app\weichat\controller;
use org\weixin\Wechat;
use think\Controller;
use think\Db;
use think\facade\Cookie;
use app\index\controller\Personal;
use think\facade\Log;
use think\Request;

class Test extends Controller
{
    public function test(){
        $userid = 'jingm';
        //根据用户openid获取员工的专属二维码

        $filename = './Uploads/user_two/chancheng_'.$userid.'.png';
        if (file_exists($filename)) {
            $ercodeimg = "https://cczc.snrunning.com/Uploads/user_two/chancheng_".$userid.".png";
            $Medianame = '@Uploads/user_two/chancheng_'.$userid.'.png';

            $media_id = '';
            //根据userid查询media_id 是否存在,并判断是否过期
            $mediainfo = Db::name('bingdingmedia2019')->where('userid',$userid)->find();
            if(empty($mediainfo)){
                $result = self::uploadMedia($Medianame);
                if (!empty($result['media_id'])){

                    echo 2222;

                    //根据media_id查询图片是否存在
                    Db::name('bingdingmedia2019')->insert(['userid'=>$userid,'media_id'=>$result['media_id'],'create_time'=>$result['created_at']]);

                    echo Db::name('bingdingmedia2019')->getLastSql();die;

                    $media_id = $result['media_id'];
                }
            }else{
                $time = time();
                if($time-$mediainfo['create_time']>=259200){
                    $result = self::uploadMedia($filename);
                    if (!empty($result['media_id'])){
                        //根据media_id查询图片是否存在
                        Db::name('bingdingmedia2019')->where('userid',$userid)->update(['media_id'=>$result['media_id'],'create_time'=>$result['created_at']]);
                        $media_id = $result['media_id'];

                    }
                }else{
                    $media_id = $mediainfo['media_id'];
                }
            }


            //$weObj->image($media_id)->reply();
            exit();

        }

    }
    public function uploadImg($filename){
       /* $userid = 'jingm';
        $filename = '@Uploads/user_two/chancheng_'.$userid.'.png';*/
        $data['media'] = $filename;
		$type = 'image';
        $webObj=new Wechat(get_weichat_options());
        //var_dump($data);
        return $webObj->uploadMedia($data,$type);
    }

    /**
     * 上传临时图片素材
     *  返回media_id，type,created_at
     */
    public function uploadMedia($filename){
        $data['media'] = $filename;
        $webObj=new Wechat(get_weichat_options());
        var_dump($webObj->uploadMedia($data,'image'));
    }
}