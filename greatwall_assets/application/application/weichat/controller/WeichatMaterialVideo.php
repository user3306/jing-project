<?php
namespace app\weichat\controller;
use app\admin\controller\Admin;
use think\Db;

/**
 * 后台视频素材
 */
class WeichatMaterialVideo extends Admin {
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '视频素材管理',
                'description' => '管理网站后台管理员',
                ),
            'menu' => array(
                    array(
                        'name' => '视频素材列表',
                        'url' => url('index'),
                        'icon' => 'list',
                    ),
                ),
            '_info' => array(
                    array(
                        'name' => '添加视频',
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
        $list = model('WeichatMaterialVideo')->loadList();
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
        $model = model('WeichatMaterialVideo');
        if (input('post.')){
            if ($materialId){
                $status=$model->edit();
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
     * 一键上传素材到微信素材库
     */
    public function uploadWxVideo(){
        $where=array();
        $where['media_id']=['eq',''];
        $list=model('WeichatMaterialVideo')->allList($where);
        if (empty($list)){
            return ajaxReturn(0,'不存在本地数据!');
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
        //获取视频素材总数
        $foreverCount=model('Index')->getForeverCount();
        $image_count=$foreverCount['image_count'];
        //导入到本地
        $wx_list=model('Index')->getForeverList('image',0,$image_count);
        if (empty($wx_list['item'])){
            return ajaxReturn(0,'服务器上没有素材可下载!');
        }
        $data=array();
        foreach ($wx_list['item'] as $key=>$val){
            //根据media_id判断视频是否存在重复信息
            $check=Db::name('weichat_material_image')->where('media_id',$val['media_id'])->count();
            if ($check==0){
                $image=$this->_downloadImg($val['media_id'],$val['url']);
                $data[$key]['media_id']=$val['media_id'];
                $data[$key]['name']=$val['name'];
                $data[$key]['image']='/'.$image;
                $data[$key]['url']=$val['url'];
                $data[$key]['add_time']=time();
            }
        }
        $rs=\think\Db::name('weichat_material_image')->insertAll($data);
        if ($rs!==false){
            return ajaxReturn(200,'下载成功',url('index'));
        }else{
            return ajaxReturn(0,'服务器上没有素材可下载!');
        }
    }
    /**
     * 保存视频
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
            // 获取视频扩展名
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
        if(model('WeichatMaterialVideo')->del($materialId)){
            return ajaxReturn(200,'视频素材删除成功！');
        }else{
            return ajaxReturn(0,'视频素材删除失败');
        }
    }
}

