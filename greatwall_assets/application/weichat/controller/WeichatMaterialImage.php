<?php
namespace app\weichat\controller;
use app\admin\controller\Admin;
use think\Db;

/**
 * 后台图片素材
 */
class WeichatMaterialImage extends Admin {
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '图片素材管理',
                'description' => '管理网站后台管理员',
                ),
            'menu' => array(
                    array(
                        'name' => '图片素材列表',
                        'url' => url('index'),
                        'icon' => 'list',
                    ),
                ),
            '_info' => array(
                    array(
                        'name' => '添加图片',
                        'url' => url('info'),
                    ),
                    array(
                        'name' => '一键上传素材到微信素材库',
                        'url' => url('uploadWxImage'),
                        'function'=>'ajax'
                    ),
                    array(
                        'name' => '一键下载微信素材库到本地',
                        'url' => url('downWxImage'),
                    ),
                ),
            );
    }
	/**
     * 列表
     */
    public function index(){
        //查询数据
        $list = model('WeichatMaterialImage')->loadList();
        //模板传值
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 详情
     */
    public function info(){
        $materialId = input('material_id');
        $model = model('WeichatMaterialImage');
        if (input('post.')){
            if ($materialId){
//                $status=$model->edit();
            }else{
                $status=$model->add();
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
     * 查看详情
     */
    public function seeinfo(){
        $materialId = input('material_id');
        $model = model('WeichatMaterialImage');
        $this->assign('info', $model->getInfo($materialId));
        return $this->fetch();
    }
    /**
     * 一键上传素材到微信素材库
     */
    public function uploadWxImage(){
        if (get_weichat_id()){
            $weichat_id=get_weichat_id();
        }
        $where = [
            'media_id'=>'',
            'weichat_id'=>$weichat_id
        ];
        $list=Db::name('weichat_material_image')->where($where)->select();
        if (empty($list)){
            return ajaxReturn(0,'本地数据全部上传完毕!');
        }
        foreach ($list as $key=>$val){
            $info=model('Index')->wxuploadimage($val['image']);
            if ($info!==false){
                $where_image['material_id']=$val['material_id'];
                $where_data['media_id']=$info['media_id'];
                $where_data['url']=$info['url'];
                Db::name('weichat_material_image')->where($where_image)->update($where_data);
            }else{
                return ajaxReturn(0,$val['image'].'上传失败');
            }
        }
        return ajaxReturn(200,'上传成功',url('index'));
    }
    /**
     * 一键下载微信素材库到本地
     */
    public function downWxImage(){

        if($_POST){
            //获取图片素材总数
            $offset = input('post.offset/d');
            $foreverCount=model('Index')->getForeverCount();
            $image_count=$foreverCount['image_count'];
            $pro = ($offset+20) / $image_count *100;
            $con = number_format($pro,2);
            //导入到本地
            $wx_list=model('Index')->getForeverList('image',$offset,20);
            if (empty($wx_list['item'])){
                return ajaxReturn(1,'全部下载完成');
            }
            $data=array();
            foreach ($wx_list['item'] as $key=>$val){
                //根据media_id判断图片是否存在重复信息
                $where_check['media_id']=$val['media_id'];
                if (get_weichat_id()){
                    $where_check['weichat_id']=get_weichat_id();
                }
                $check=Db::name('weichat_material_image')->where($where_check)->find();
                if($check){ //微信素材按照时间从1开始推送，所以第二次下载遇到重复的即认为没有最新资源下载，已经全部更新完成
                    return ajaxReturn(1,'全部下载完成');
                }else{
                    $image=$this->_downloadImg($val['media_id'],$val['url']);
                    if (!$image){
                        continue;
                    }
                    $data[$key]['media_id']=$val['media_id'];
                    $data[$key]['name']=$val['name'];
                    $data[$key]['image']='/'.$image;
                    $data[$key]['url']=$val['url'];
                    $data[$key]['weichat_id']=get_weichat_id();
                    $data[$key]['add_time']=time();
                }

            }
            $rs=\think\Db::name('weichat_material_image')->insertAll($data);
            if ($rs){
                $msg = [
                  'msg'=>'下载成功',
                  'pro'=>$con
                ];
                return ajaxReturn(200,$msg);
            }else{
                $msg = [
                    'msg'=>'下载中断',
                    'pro'=>$con
                ];
                return ajaxReturn(0,$msg);
            }
        }else{
            return $this->fetch();
        }


    }
    /**
     * 保存图片
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
            // 获取图片扩展名
            $picExt = substr ( $picUrl, strrpos ( $picUrl, '=' ) + 1 );
            // $picExt=='jpeg'
            if (empty ( $picExt ) || $picExt == 'jpeg') {
                $picExt = 'jpg';
            }
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
        if(empty($materialId)){
            return ajaxReturn(0,'参数不能为空');
        }
        if(model('WeichatMaterialImage')->del($materialId)){
            return ajaxReturn(200,'图片素材删除成功！');
        }else{
            return ajaxReturn(0,'图片素材删除失败');
        }
    }
}

