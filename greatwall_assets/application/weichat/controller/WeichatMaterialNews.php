<?php
namespace app\weichat\controller;
use app\admin\controller\Admin;
use think\Db;
use \org\weixin\Wechat;

/**
 * 后台图文素材
 */
class WeichatMaterialNews extends Admin {
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '图文素材管理',
                'description' => '管理网站后台管理员',
                ),
            'menu' => array(
                    array(
                        'name' => '图文素材列表',
                        'url' => url('index'),
                        'icon' => 'list',
                    ),
                ),
            '_info' => array(
                    array(
                        'name' => '添加图文',
                        'url' => url('info'),
                    ),
                    array(
                        'name' => '一键上传素材到微信素材库',
                        'url' => url('uploadWxVideo'),
                        'function'=>'ajax'
                    ),
                    array(
                        'name' => '一键下载微信素材库到本地',
                        'url' => url('downWxVideo'),
                        'function'=>'ajax'
                    ),
                ),
            );
    }
	/**
     * 列表
     */
    public function index(){
        //查询数据
        $list = model('WeichatMaterialNews')->loadList();
        //模板传值
        $this->assign('list',$list);
        $this->assign('_page',$list->render());
        return $this->fetch();
    }
    /**
     * 详情
     */
    public function info(){
        $materialId = input('material_id');
        $model = model('WeichatMaterialNews');
        if (input('post.')){
            $data = json_decode ( $_POST ['dataStr'], true );
            $insert=array();
            foreach ( $data as $key => $vo ) {
                $save= array ();
                foreach ( $vo as $k => $v ) {
                    $save[$v ['name']] = safe ( $v ['value'] );
                }
                if (empty ( $save['title'] )) {
                    return ajaxReturn(0,'请填写第'.($key+1).'篇文章的标题');
                }
                if (empty ( $save['author'] )) {
                    return ajaxReturn(0,'请填写第'.($key+1).'篇文章的作者');
                }
                if (empty ( $save['image'] )) {
                    return ajaxReturn(0,'请填写第'.($key+1).'篇文章的封面图');
                }
                if (empty ( $save['content'] )) {
                    return ajaxReturn(0,'请填写第'.($key+1).'篇文章的正文');
                }
                if (!is_url($save['content_source_url']) && !empty ( $save['content_source_url'] )) {
                    return ajaxReturn(0,'第'.($key+1).'篇文章的原文链接不合法');
                }
                if (empty ( $save['digest'] )) {
                    return ajaxReturn(0,'请填写第'.($key+1).'篇文章的摘要');
                }

                if ($save['if_upload']==1){
                    $info=model('Index')->wxuploadimage($save['image']);
                    $save['thumb_media_id']=$info['media_id'];
                    $save['thumb_url']=$info['url'];
                }
                unset($save['if_upload']);
                $save['content']=html_in($save['content']);
                $save['show_cover_pic']=1;//是否显示封面，0为false，即不显示，1为true，即显示
                $insert[$key]=$save;
                $sql_data['data']=json_encode($insert);
            }
            if ($materialId){
                //dump($insert);
                //修改服务器上面的素材
                $info_me = Db::name('weichat_material_news')->field('media_id,data')->where('material_id', $materialId)->find();
                if ($info_me['data']){
                    $data_arr=json_decode($info_me['data'],true);
                    foreach ($data_arr as $key=>$val){
                        if (!empty($insert[$key]['thumb_media_id'])){
                            $thumb_media_id=$insert[$key]['thumb_media_id'];
                        }else{
                            $thumb_media_id=$val['thumb_media_id'];
                        }
                        $brr['articles']=array(
                            "title"=> $insert[$key]['title'],
                            "thumb_media_id"=> $thumb_media_id,
                            "author"=> $insert[$key]['author'],
                            "digest"=> $insert[$key]['digest'],
                            "show_cover_pic"=> $val['show_cover_pic'],
                            "content"=> html_out($insert[$key]['content']),
                            "content_source_url"=> $insert[$key]['content_source_url'],
                        );

                        //dump($brr);

                        $rs=model('Index')->updateForeverArticles($info_me['media_id'],$brr,$key);

                        if ($rs['errcode']!== 0){
                            return ajaxReturn(0,'远程素材更新失败');
                        }
                    }
                }
                $status=Db::name('weichat_material_news')->where('material_id',$materialId)->update($sql_data);
            }else{
                $sql_data['add_time']=time();
                $sql_data['weichat_id']=get_weichat_id();
                $status=Db::name('weichat_material_news')->insert($sql_data);
            }
            if($status!==false){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $this->assign('info', $model->getInfo($materialId));
            return $this->fetch();
        }
    }

    /**
     * 详情
     */
    public function sendgrpmsg(){

        //找出分组数据        
        $webObj=new Wechat(get_weichat_options());
        //dump($webObj->getGroup());

        $tags = $webObj->getTag();
        $this->assign('tagslist', $tags['tags']);

        $materialId = input('material_id');
        $model = model('WeichatMaterialNews');
       
        $this->assign('info', $model->getInfo($materialId));
        return $this->fetch();
        
    }

    /**
     * 素材预览
     * @return [type] [description]
     */
    public function priviewmsg(){
        $materialId = input('materialId');
        $weixinid = input('weixinid');

        $webObj=new Wechat(get_weichat_options());
        $data = array(
                    'touser'=>$weixinid,
                    'mpnews'=>array(
                                    'media_id'=>$materialId
                                ),
                    'msgtype'=>'mpnews'
                );

        $result = $webObj->previewMassMessage($data);
        //dump($webObj->errMsg);
        return 'success';
    }


    /**
     * 素材预览
     * @return [type] [description]
     */
    public function sendmsg(){
        $materialId = input('materialId');
        $tagid = input('tagid');

        $webObj=new Wechat(get_weichat_options());
        $data = array(
            'filter'=>array(
                'is_to_all'=>false,
                'tag_id'=>$tagid
            ),
            'mpnews'=>array(
                'media_id'=>$materialId
            ),
            'msgtype'=>'mpnews',
            'send_ignore_reprint'=>0
        );

        $result = $webObj->sendGroupMassMessage($data);
        //dump($webObj->errMsg);
        return 'success';
    }
    /**
     * 一键上传素材到微信素材库
     */
    public function uploadWxVideo(){
        $where=array();
        $where[]=['media_id','=',0];
        $list = model('WeichatMaterialNews')->allList($where);
        if (empty($list)){
            return ajaxReturn(0,'本地数据全部上传完毕!');
        }
        if ($list){
            foreach ($list as $key=>$val){
                if ($val['data']){
                    $data_arr=json_decode($val['data'],true);
                    $upload_arr=array();
                    foreach ($data_arr as $k=>$v){
                        $upload_arr[$k]=array(
                            "title"=> $v['title'],
                            "thumb_media_id"=> $v['thumb_media_id'],
                            "author"=> $v['author'],
                            "digest"=> $v['digest'],
                            "show_cover_pic"=> $v['show_cover_pic'],
                            "content"=> html_out($v['content']),
                            "content_source_url"=> $v['content_source_url'],
                        );
                    }
                    $rs=model('Index')->uploadForeverArticles($upload_arr);
                    if (empty($rs['media_id'])){
                        return ajaxReturn(0,'远程素材更新失败');
                    }
                    $sql_data['media_id']=$rs['media_id'];
                    $sql_data['weichat_id']=get_weichat_id();
                    $sql_data['add_time']=time();
                    Db::name('weichat_material_news')->where('material_id',$val['material_id'])->update($sql_data);

                }
            }
        }
        return ajaxReturn(200,'上传成功',url('index'));
    }
    /**
     * 一键下载微信素材库到本地
     */
    public function downWxVideo(){
        //获取图文素材总数
        $foreverCount=model('Index')->getForeverCount();
        $news_count=$foreverCount['news_count'];

//        dump($foreverCount);die;
        //导入到本地
        $wx_list=model('Index')->getForeverList('news',0,$news_count);
//        $wx_list=model('Index')->getForeverList('news',0,20);

//        dump($wx_list);die;

        if (empty($wx_list['item'])){
            return ajaxReturn(0,'服务器上没有素材可下载!');
        }
        $data=array();
        $count = 0;
        //查询最后一条数据主键设置为分组
        foreach ($wx_list['item'] as $key=>$val){
            if($count>10)
                break;
            //根据media_id判断图文是否存在重复信息
            $check=Db::name('weichat_material_news')->where('media_id',$val['media_id'])->count();
            if ($check==0){
                $data[$key]['media_id']=$val['media_id'];
                foreach ($val['content']['news_item'] as $k=>$v){
                    $val['content']['news_item'][$k]['image']='/'.$this->_downloadImg($v['thumb_media_id'],$v['thumb_url']);

                }
                $data[$key]['data']=json_encode($val['content']['news_item']);
                $data[$key]['weichat_id']=get_weichat_id();
                $data[$key]['add_time']=$val['update_time'];
            }
            $count++;
        }
        if (empty($data)){
            return ajaxReturn(0,'素材已经全部下载完毕');
        }
        $rs=\think\Db::name('weichat_material_news')->insertAll($data);
        if ($rs!==false){
            return ajaxReturn(200,'下载成功',url('index'));
        }else{
            return ajaxReturn(0,'服务器上没有素材可下载!');
        }
    }
    /**
     * 保存图文
     */
    public function _downloadImg($midia_id,$picUrl = ''){
        $savePath='uploads/wx/'.date('Ymd',time());
        if (is_dir($savePath)!=true){
            mkdirs ( $savePath );
        }
        if (empty ( $picUrl )) {
            $picContent=model('Index')->getForeverMedia($midia_id);
            //$picjson = json_decode ( $picContent, true );
            $picName = md5(time()) . '.jpg';
            $picPath = $savePath . '/' . $picName;
            $res = file_put_contents ( $picPath, $picContent );
        }else{
            $content = wp_file_get_contents ( $picUrl );
            // 获取图文扩展名
            //$picExt = substr ( $picUrl, strrpos ( $picUrl, '=' ) + 1 );
            $picExt='jpg';
            //if (empty ( $picExt ) || $picExt == 'jpeg') {
            //    $picExt = 'jpg';
            //}
            $picName = md5(time().rand(10000,99999)) . '.' . $picExt;
            $picPath = $savePath . '/' . $picName;
            $res = file_put_contents ( $picPath, $content );
        }
        if (!$res){
            return false;
        }
        return $picPath;
    }

    /**
     * 删除
     */
    public function del(){
        $materialId = input('id');
        $info=model('WeichatMaterialNews')->getInfo($materialId);
        if (empty($info)){
            return ajaxReturn(0,'该素材不存在');
        }
        if(empty($materialId)){
            return ajaxReturn(0,'参数不能为空');
        }
        if(model('WeichatMaterialNews')->del($materialId)){
            model('Index')->delForeverMedia($info['media_id']);
            return ajaxReturn(200,'图文素材删除成功！');
        }else{
            return ajaxReturn(0,'图文素材删除失败');
        }
    }
}

