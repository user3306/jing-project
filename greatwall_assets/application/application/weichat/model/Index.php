<?php
namespace app\weichat\model;
use think\Model;
use \org\weixin\Wechat;
/**
 * 微信素材
 */
class Index extends Model {
    /**
     * 上传素材
     * @param string $url
     * @param string $type
     * @param bool $is_video
     * @param array $video_info
     * @return bool|false|int
     */
    public function wxuploadimage($url='/uploads/admin/20170419/8cb36838cc73b0bfdceff8f2c043b743.jpg', $type='image',$is_video=false,$video_info=array()){
        //判断相对路径
        if (substr( $url, 0, 1 )=='/'){
            $url=substr( $url, 1);
        }
        $data['media']='@'.$url;
        $webObj=new Wechat(get_weichat_options());
        return $webObj->uploadForeverMedia($data, $type,$is_video,$video_info);
    }
    /**
     * 获取永久素材(认证后的订阅号可用)
     * 返回图文消息数组或二进制数据，失败返回false
     */
    public function getForeverMedia($media_id,$is_video=false){
        $webObj=new Wechat(get_weichat_options());
        return $webObj->getForeverMedia($media_id,$is_video);
    }
    
    /**
     *  获取永久素材列表(认证后的订阅号可用)
     */
    public function getForeverList($type='image',$offset='0',$count='20'){
        $webObj=new Wechat(get_weichat_options());
        return $webObj->getForeverList($type,$offset,$count);
    }
    /**
     * 获取永久素材总数
     */
    public function getForeverCount(){
        $webObj=new Wechat(get_weichat_options());
        return $webObj->getForeverCount();
    }
    /**
     * 上传永久图文素材
     * $data=array(
            array(
            "title"=> '测试',
            "thumb_media_id"=> 'GgLJUrcKt-y2CyAuUKdQohfehbN7ddm3pVW78FtgqYA',
            "author"=> 'hongkai',
            "digest"=> '新增摘要测试',
            "show_cover_pic"=> 1,
            "content"=> '图文消息内容',
            "content_source_url"=> 'http://www.baidu.com'
            )
        );
     */
    public function uploadForeverArticles($data){
        $weiObj=new Wechat(get_weichat_options());
        $data['articles']=$data;
        $news =$weiObj->uploadForeverArticles($data); 
        //dump($weiObj->errMsg);
        return $news;
    }
    /**
     * 修改永久图文素材
     */
    public function updateForeverArticles($media_id='',$data=array(),$index=0){
        $weiObj=new Wechat(get_weichat_options());
        return $weiObj->updateForeverArticles($media_id,$data,$index);
    }
    /**
     * 删除永久图文素材
     */
    public function delForeverMedia($media_id){
        $weiObj=new Wechat(get_weichat_options());
        return $weiObj->delForeverMedia($media_id);
    }
}
