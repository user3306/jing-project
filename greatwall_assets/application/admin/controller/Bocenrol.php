<?php
namespace app\admin\controller;

/**
 * 湘江新区公众号报名系统
 */
use think\Db;
use org\weixin\Wechat;
use think\facade\Env;

class Bocenrol extends Admin{

    protected function _infoModule()
    {
        return array(
            'info' => array(
                'name' => '报名系统',
                'description' => '报名系统',
            ),
            'menu' => array(
                array(
                    'name' => '活动列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
            ),
            '_info' => array(
                array(
                    'name' => '添加活动',
                    'url' => url('info'),
                ),
                array(
                    'name' => '下载白名单模板',
                    'url' => url('exportmodel'),
                ),
            ),
        );
    }

    public function index(){
        $list = Db::name('boc_enrol')->order('createtime desc')->paginate(12);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function info(){
        $id = input('id/d');
        $info = Db::name('boc_enrol')->where(['id'=>$id])->find();
        if(input('post.')){
            $id = input('post.id');//
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['createtime'] = time();
            unset($_POST['id']);
            if($id){//修改
                $res = Db::name('boc_enrol')->where(['id'=>$id])->update($_POST);
                if($res){
                    return ajaxReturn(200,'操作成功',url('index'));
                }else{
                    return ajaxReturn(0,'操作失败1');
                }
            }else{//新建一个活动
                $id = Db::name('boc_enrol')->insertGetId($_POST);
                if($id){
                    $this->create_user_table($id);
                    return ajaxReturn(200,'操作成功',url('index'));
                }else{
                    return ajaxReturn(0,'操作失败0');
                }
            }
        }
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 报名用户列表
     */
    public function userlist(){
        $id = input('id/d');
        if($_GET){
            if(!empty($_GET)){
                $id = trim($_GET['act_id']);
            }
            if(!empty($_GET['starttime'] && !empty($_GET['endtime']))){
                $where[] = [
                    'createtime','>=',strtotime($_GET['starttime'])
                ];
                $where[] = [
                    'createtime','<=',strtotime($_GET['endtime'])
                ];
            }else if(!empty($_GET['mobile'])){
                $where[] = [
                    'mobile','=',trim($_GET['mobile'])
                ];
            }else{
                $where[] = [
                    'createtime','>=',strtotime($_GET['starttime'])
                ];
                $where[] = [
                    'createtime','<=',strtotime($_GET['endtime'])
                ];
                $where[] = [
                    'mobile','=',trim($_GET['mobile'])
                ];
            }
        }else{
            $where = [];
        }
        $count['all'] = Db::name('boc_enrol_visit')->count('id');
        $count['visit'] = Db::name('boc_enrol_visit')->group('openid')->count('id');
        $count['register'] = Db::name('boc_enrol_userdata_'.$id)->count('id');
        $list = Db::name('boc_enrol_userdata_'.$id)->order('createtime desc')->where($where)->paginate(15);
        $this->assign('list',$list);
        $this->assign('act_id',$id);
        $this->assign('count',$count);
        return $this->fetch();
    }

    /**
     * 轮播列表
     */
    public function bannerlist(){
        $id = input('id/d');

        $list = Db::name('boc_enrol_banner')->where(['act_id'=>$id])->order('id desc')->select();
        $this->assign('act_id',$id);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 轮播详情
     */
    public function bannerinfo(){
        $id = input('id/d');
        $act_id = input('act_id/d');
        $info = Db::name('boc_enrol_banner')->where('id',$id)->find();
        if($_POST){
            $id = input('post.id');
            $data = [
                'title'=>input('post.title'),
                'image'=>input('post.image'),
                'url'=>input('post.url'),
                'listorder'=>input('post.listorder'),
                'act_id'=>input('post.act_id'),
                'add_time'=>time()
            ];
            if(!empty($id)){
                $res = Db::name('boc_enrol_banner')->where('id',input('post.id'))->update($data);
            }else{
                $res = Db::name('boc_enrol_banner')->insert($data);
            }
            if($res){
                return ajaxReturn(200,'操作成功',url('bannerlist'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }
        $this->assign('act_id',$act_id);
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 上传白名单
     */
    public function whitelist(){
        $act_id = input('id/d');

        if($_FILES){

            $file = $_FILES['file'];

            if(substr($file['name'],-3) !== 'csv'){
                return ajaxReturn(0,'请上传csv文件');
            }
            if($file['error']>0){
                return ajaxReturn(1,'上传失败');
            }
            $act_id = input('post.act_id');
            $filename = date('YmdHis').'.csv';
            $dir = "/data/wwwroot/xjboc.wx.snrunning.cn/public/uploads/boc/enrol/";
            move_uploaded_file($file['tmp_name'],$dir.$filename);
            $csv_file = fopen($dir.$filename,'r');

            while ($csv_arr = fgetcsv($csv_file)){
                $csv_array[] = $csv_arr;
            }
            array_shift($csv_array);
            $i=0;
            foreach ($csv_array as $k=>$v){
                $data = [
                    'act_id'=>$act_id,
                    'mobile'=>$v[0]
                ];
                $check = Db::name('boc_enrol_white')->where($data)->find();
                if(!$check){
                    $res = Db::name('boc_enrol_white')->insert($data);
                    if(!$res){
                        continue;
                    }
                }else{
                    continue;
                }
                $i++;
            }
            fclose($csv_file);
            return ajaxReturn(200,'上传'.$i.'条成功,',url('index'));
        }
        $this->assign('act_id',$act_id);
        return $this->fetch();
    }


    public function del(){

        return ajaxReturn(0,'删除暂时未开放');

        $id = input('id/d');
        if(empty($id)){
            return ajaxReturn(0,'参数错误');
        }
        $res = Db::name('boc_enrol')->delete($id);
        if($res){
            return ajaxReturn(200,'操作成功',url('index'));
        }else{
            return ajaxReturn(0,'操作失败，请重试');
        }

    }


    /**
     * 删除一个banner图片
     */
    public function delBanner(){
        $id = input('id/d');
        if($id){
            $res = Db::name('boc_enrol_banner')->delete($id);
            if($res){
                return ajaxReturn('200','操作成功');
            }else{
                return ajaxReturn('0','操作失败');
            }
        }else{
            return ajaxReturn(0,'参数错误');
        }
    }

    /**
     *  创建一次活动的同时，创建活动报名用户表
     * @param $act_id
     * @return bool
     */
    public function create_user_table($act_id){
        $sql = "CREATE TABLE rn_boc_enrol_userdata_".$act_id." (
        id INT(11) NOT NULL AUTO_INCREMENT,
        openid CHAR(28) DEFAULT NULL,
        act_id INT(11) DEFAULT NULL,
        realname VARCHAR(20) DEFAULT NULL,
        mobile VARCHAR(11) DEFAULT NULL,
        createtime INT(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $res = Db::execute($sql);
        return $res;

    }

     /**
      * 下载excel白名单模板
      */
    public function exportmodel(){
        $columns = ['手机号'];
        $csvFileName = '白名单模板.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment; filename="'. $csvFileName .'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a');//打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        fputcsv($fp, $columns);//将数据格式化为CSV格式并写入到output流中

        ob_flush();
        flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        fclose($fp);
    }

    /**
     * 导出报名用户信息表
     */
    public function exportuserlist(){
        $act_id = input('act_id');
        $act_name = Db::name('boc_enrol')->where(['id'=>$act_id])->value('title');
        $columns = ['id','openid','姓名','手机号','报名时间' ];
        $csvFileName = $act_name.'.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment; filename="'. $csvFileName .'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a');//打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        fputcsv($fp, $columns);//将数据格式化为CSV格式并写入到output流中
        $sendcount = Db::name('boc_enrol_userdata_'.$act_id)->field('id,openid,realname,mobile,createtime')->order('id asc')->select();
        $tmparr = array();
        foreach ($sendcount as $key => $value) {
            $tmparr['id'] = $value['id'];
            $tmparr['openid'] = $value['openid'];
            $tmparr['realname'] = $value['realname'];
            $tmparr['mobile'] = $value['mobile'];
            $tmparr['createtime'] = date('Y-m-d H:i:s',$value['createtime']);
            mb_convert_variables('GBK', 'UTF-8', $tmparr);
            fputcsv($fp, $tmparr);
        }
        ob_flush();
        flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        fclose($fp);

    }


    /**
     * 预览
     */
    public function preview(){
        $id = input('id/d');
        $uri = "http://wxxcb.hunnu.edu.cn/web/index/reurl?act_id=".$id.'&model=enrol';
        require_once Env::get('root_path') . 'vendor/phpqrcode/phpqrcode.php';
        $pic_url = '/uploads/boc/qrcode/'.time().'.jpg';
        $outfile='/data/wwwroot/xjboc.wx.snrunning.cn/public'.$pic_url;
        $level = 'L';
        $size =4;
        $QRcode = new \QRcode();
        ob_start();
        $QRcode->png($uri,$outfile,$level,$size,2);
//        $qrcode = base64_encode(ob_get_contents());
        ob_end_clean();
        $this->assign('pic_url',$pic_url);
        return $this->fetch();
    }

    /**
     * 授权跳转
     */



}