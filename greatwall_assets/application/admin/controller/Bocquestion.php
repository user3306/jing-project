<?php

namespace app\admin\controller;

use think\Db;
use think\facade\Env;

class Bocquestion extends Admin{


    protected function _infoModule()
    {
        return array(
            'info' => array(
                'name' => '问卷调查',
                'description' => '标准问卷调查系统',
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
        $list = Db::name('boc_question')->order('createtime desc')->paginate(12);
        $this->assign('list',$list);

        return $this->fetch();
    }


    public function info(){
        $id = input('id/d');
        $info = Db::name('boc_question')->where(['id'=>$id])->find();
        if(input('post.')){
            $id = input('post.id');//
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['createtime'] = time();
            unset($_POST['id']);
            if($id){//修改
                $res = Db::name('boc_question')->where(['id'=>$id])->update($_POST);
                if($res){
                    return ajaxReturn(200,'操作成功',url('index'));
                }else{
                    return ajaxReturn(0,'操作失败1');
                }
            }else{//新建一个活动
                $id = Db::name('boc_question')->insertGetId($_POST);
                if($id){
                    $this->create_table($id);
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
     * 试题库
     * 选择题 boc_question_choice  文字题boc_question_text
     * 这里分了两个表，数据在一起提交过来，而且因为前段的问题，type=1_2 处理一下取第一个值，暂且一条一条写入，反正题也不会多
     */
    public function question(){
        if($_POST){
            $data = $_POST['data'];
            $act_id = input('post.act_id');
            foreach($data as $v){
                $id = isset($v['id'])?$v['id']:'';
                $type_arr = explode('_',$v['type']);
                $type = $type_arr[0];
                $info = [
                    'type'=>$type,
                    'question'=>$v['question'],
                    'option_a'=>$v['option_a'],
                    'option_b'=>$v['option_b'],
                    'option_c'=>$v['option_c'],
                    'option_d'=>$v['option_d'],
                ];
                if(empty($id)){
                    Db::name('boc_question_question_'.$act_id)->insert($info);
                }else{
                    Db::name('boc_question_question_'.$act_id)->where(['id'=>$id])->update($info);
                }
            }
            return ajaxReturn('200','操作成功',url('index'));
        }
        $act_id = input('id/d');
        $this->assign('act_id',$act_id);
        $list = Db::name('boc_question_question_'.$act_id)->order('id asc')->select();//选择题列表
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * 数据统计
     */
    public function userlist(){
        $act_id = input('id/d');
        //单选和多选
        $question_table = 'rn_boc_question_question_'.$act_id;
        $anser_table = 'rn_boc_question_answer_'.$act_id;
//        SELECT ANY_VALUE(b.question_id),SUM(if(b.answer='A',1,0)) as optioin_a_num FROM `rn_boc_question_question_3` a JOIN rn_boc_question_answer_3 b ON a.id=b.question_id WHERE a.type<3 GROUP BY b.question_id
//        SELECT ANY_VALUE(b.question_id),SUM(if(b.answer='A',1,0)) as optioin_a_num,FORMAT((SUM(if(b.answer='A',1,0))/1),1) as option_a_radio FROM `rn_boc_question_question_3` a JOIN rn_boc_question_answer_3 b ON a.id=b.question_id WHERE a.type<3 GROUP BY b.question_id

        $sql1 = "SELECT a.*,SUM(if(b.answer='A',1,0)) as option_a_num,SUM(if(b.answer='B',1,0)) as option_b_num,SUM(if(b.answer='C',1,0)) as option_c_num,SUM(if(b.answer='D',1,0)) as option_d_num FROM `$question_table` a JOIN `$anser_table` b ON a.id=b.question_id WHERE a.type<3 GROUP BY b.question_id";

        $list1 = Db::query($sql1);

        //文字题
        $list2 = Db::table('rn_boc_question_question_'.$act_id) ->alias('a')
            ->field('a.id,b.openid,a.question,c.username,c.mobile,b.answer,c.createtime')
            ->join('rn_boc_question_answer_'.$act_id.' b','a.id=b.question_id')
            ->join('rn_boc_question_user_'.$act_id.' c','b.openid=c.openid')
            ->where('a.type',3)
            ->order('c.createtime desc')
            ->paginate(10);
        $count['all'] = Db::name('boc_question_visit_'.$act_id)->count('id');
        $count['visit'] = Db::name('boc_question_visit_'.$act_id)->group('openid')->count();
        $count['register'] = Db::name('boc_question_user_'.$act_id)->count();
        $title = Db::name('boc_question')->where(['id'=>$act_id])->value('title');
        $this->assign('title',$title);
        $this->assign('list1',$list1);
        $this->assign('list2',$list2);
        $this->assign('act_id',$act_id);
        $this->assign('count',$count);
        return $this->fetch();
    }




    /**
     * 轮播列表
     */
    public function bannerlist(){
        $id = input('id/d');

        $list = Db::name('boc_question_banner')->where(['act_id'=>$id])->order('id desc')->select();
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
        $info = Db::name('boc_question_banner')->where('id',$id)->find();
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
                $res = Db::name('boc_question_banner')->where('id',input('post.id'))->update($data);
            }else{
                $res = Db::name('boc_question_banner')->insert($data);
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
            $dir = "/data/wwwroot/xjboc.wx.snrunning.cn/public/uploads/boc/question/";
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
                $check = Db::name('boc_question_white')->where($data)->find();
                if(!$check){
                    $res = Db::name('boc_question_white')->insert($data);
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
     * 导出选择题信息表
     */
    public function exportoption(){
        $act_id = input('act_id');
        $act_name = Db::name('boc_question')->where(['id'=>$act_id])->value('title');
        $columns = [$act_name];
        $csvFileName = $act_name.'_选择题.csv';
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
        $question_table = 'rn_boc_question_question_'.$act_id;
        $anser_table = 'rn_boc_question_answer_'.$act_id;
        $sql1 = "SELECT a.*,SUM(if(b.answer='A',1,0)) as option_a_num,SUM(if(b.answer='B',1,0)) as option_b_num,SUM(if(b.answer='C',1,0)) as option_c_num,SUM(if(b.answer='D',1,0)) as option_d_num FROM `$question_table` a JOIN `$anser_table` b ON a.id=b.question_id WHERE a.type<3 GROUP BY b.question_id";

        $list = Db::query($sql1);
        $tmparr = array();
        $count = Db::name('boc_question_user_'.$act_id)->count();
        foreach ($list as $value) {
            for($k = 0;$k <= 5;$k++){
                if($k % 5 == 0){
                    $type = [
                        1 => '单选题',
                        2 => '多选题',
                    ];
                    $tmparr['question'] = $value['question'].$type[$value['type']];
                    continue;
                }
                if($k % 5 == 1){
                    $tmparr['text1'] = '选项';
                    $tmparr['text2'] = '小计';
                    $tmparr['text3'] = '比例';
                    continue;
                }

                if($k % 5 == 2){
                    $tmparr['option_a'] = "A".$value['option_a'];
                    $tmparr['option_a_num'] = $value['option_a_num'];
                    $tmparr['option_a_ratio'] = $value['option_a_num'] / $count * 100 ."%";
                    continue;
                }
                if($k % 5 == 3){
                    $tmparr['option_b'] = "B".$value['option_b'];
                    $tmparr['option_b_num'] = $value['option_b_num'];
                    $tmparr['option_b_ratio'] = $value['option_b_num'] / $count * 100 ."%";
                    continue;
                }
                if($k % 5 == 4){
                    $tmparr['option_c'] = "C".$value['option_c'];
                    $tmparr['option_c_num'] = $value['option_c_num'];
                    $tmparr['option_c_ratio'] = $value['option_c_num'] / $count * 100 ."%";
                    continue;
                }
                if($k % 5 == 0){
                    $tmparr['option_d'] = "D".$value['option_d'];
                    $tmparr['option_d_num'] = $value['option_d_num'];
                    $tmparr['option_d_ratio'] = $value['option_d_num'] / $count * 100 ."%";
                    continue;
                }
            }

            mb_convert_variables('GBK', 'UTF-8', $tmparr);
            fputcsv($fp, $tmparr);
        }
        ob_flush();
        flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        fclose($fp);

    }

    /**
     * 导出文字题信息表
     */
    public function exporttext(){
        $act_id = input('act_id');
        $act_name = Db::name('boc_question')->where(['id'=>$act_id])->value('title');
        $columns = ['id','openid','姓名','手机号','问题','回答内容','提交时间' ];
        $csvFileName = $act_name.'_文字题.csv';
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

        //文字题
        $list = Db::table('rn_boc_question_question_'.$act_id) ->alias('a')
            ->field('a.id,b.openid,a.question,c.username,c.mobile,b.answer,c.createtime')
            ->join('rn_boc_question_answer_'.$act_id.' b','a.id=b.question_id')
            ->join('rn_boc_question_user_'.$act_id.' c','b.openid=c.openid')
            ->where('a.type',3)
            ->order('c.createtime desc')
            ->select();
        $tmparr = array();
        foreach ($list as $key => $value) {
            $tmparr['id'] = $value['id'];
            $tmparr['openid'] = $value['openid'];
            $tmparr['username'] = $value['username'];
            $tmparr['mobile'] = $value['mobile'];
            $tmparr['question'] = $value['question'];
            $tmparr['answer'] = $value['answer'];
            $tmparr['createtime'] = date('Y-m-d H:i:s',$value['createtime']);
            mb_convert_variables('GBK', 'UTF-8', $tmparr);
            fputcsv($fp, $tmparr);
        }
        ob_flush();
        flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        fclose($fp);

    }

    /**
     * 删除一个banner图片
     */
    public function delBanner(){
        $id = input('id/d');
        if($id){
            $res = Db::name('boc_question_banner')->delete($id);
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
     * 彻底删除一次活动
     */
    public function del(){
        $id = input('id/d');
        if($id){
            $res = Db::name('boc_question')->delete($id);
            if($res){
                Db::execute("DROP TABLE rn_boc_question_choice_".$id);
                Db::execute("DROP TABLE rn_boc_question_data_".$id);
                Db::execute("DROP TABLE rn_boc_question_text_".$id);
                return ajaxReturn('200','操作成功');
            }else{
                return ajaxReturn('0','操作失败');
            }
        }else{
            return ajaxReturn(0,'参数错误');
        }
    }

    /**
     * 预览
     */
    public function preview(){
        $id = input('id/d');
        $uri = "http://wxxcb.hunnu.edu.cn/web/index/reurl?act_id=".$id.'&model=question';
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
     *  创建一次活动的同时，创建试卷表 选择题表，文字题表，用户提交数据表
     * @param $act_id
     * @return bool
     * type 1单选题 2多选题 3文本题
     */
    public function create_table($act_id){
        $sql1 = "CREATE TABLE rn_boc_question_question_".$act_id." (
        id INT(11) NOT NULL AUTO_INCREMENT,
        type tinyint(1) DEFAULT 1,
        question VARCHAR(255) DEFAULT NULL,
        option_a VARCHAR(255) DEFAULT NULL,
        option_b VARCHAR(255) DEFAULT NULL,
        option_c VARCHAR(255) DEFAULT NULL,
        option_d VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $sql2 = "CREATE TABLE rn_boc_question_user_".$act_id." (
        id INT(11) NOT NULL AUTO_INCREMENT,
        openid CHAR(28) DEFAULT NULL,
        username CHAR(50) DEFAULT NULL,
        mobile VARCHAR(11) DEFAULT NULL,
        createtime INT(10) DEFAULT NULL,
        PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $sql3 = "CREATE TABLE rn_boc_question_answer_".$act_id. " (
        id INT(11) NOT NULL AUTO_INCREMENT,
        openid CHAR(28) DEFAULT NULL,
        question_id INT(11) DEFAULT NULL,
        answer VARCHER(255) DEFAULT NULL,
        PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $sql4 = "CREATE TABLE rn_boc_question_visit_".$act_id. " (
        id INT(11) NOT NULL AUTO_INCREMENT,
        openid CHAR(28) DEFAULT NULL,
        createtime INT(10) DEFAULT NULL
        PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        $sql5 = "CREATE TABLE rn_boc_question_white_".$act_id. " (
        id INT(11) NOT NULL AUTO_INCREMENT,
        mobile CHAR(11) DEFAULT NULL,
        PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        Db::execute($sql1);
        Db::execute($sql2);
        Db::execute($sql3);
        Db::execute($sql4);
        Db::execute($sql5);
        return true;

    }



}