<?php
namespace app\admin\controller;

use think\Db;
use org\weixin\Wechat;
use org\WXPayDiscounts;
class Recruit extends Admin{


    /**
     * 设置模块参数
     */

    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '功能',
                'description' => '菜单点击次数',
            ),
            'menu' => array(
                array(
                    'name' => '菜单点击次数',
                    'url' => url('menuhits'),
                    'icon' => 'list',
                ),
                array(
                    'name' => '员工列表',
                    'url' => url('memberlist'),
                    'icon' => 'list',
                ),
                array(
                    'name' => '合伙人列表',
                    'url' => url('partnerlist'),
                    'icon' => 'list',
                ),
            ),
            '_info' => array(
                array(
                    'name' => '导出未审核列表',
                    'url' => url('exportlist'),
                ),
                array(
                    'name' => '导入已审核列表',
                    'url' => url('uploadlist'),
                ),
                array(
                    'name' => '增加员工信息',
                    'url' => url('memberedit'),
                ),
            ),
        );
    }

    /**
     * 微信菜单当日点击次数
     */

    public function menuhits(){
        //查找当日菜单点击量
        $starttime = input('starttime');
        $endtime = input('endtime');
        if(empty($starttime) && empty($endtime)){
            $starttime = date('Y-m-d',time());
            $endtime = date('Y-m-d',time());
        }

        $list = Db::table('rn_unicom_menu_hits')
            ->alias('a')
            ->field('sum(a.hits) as total,a.menu_key,b.key,b.name')
            ->join('rn_weichat_menu b','a.menu_key=b.key')
            ->where('a.createtime','between',[$starttime,$endtime])
            ->group('a.menu_key')
            ->select();
//        $sql = "SELECT sum(a.hits) as total,a.menu_key,a.hits,b.key,b.`name` from rn_unicom_menu_hits a JOIN rn_weichat_menu b ON a.menu_key=b.`key` AND a.createtime BETWEEN '2018-09-02'AND'2018-09-03' GROUP BY a.menu_key;";
//        $list = Db::query($sql);

        $this->assign('list',$list);

        return $this->fetch();
    }

    /**
     * 合伙人注册申请列表
     */
    public function partnerlist(){
        $name = input('keyword');
        if($name){
            $list = Db::table('rn_unicom_partner')
                ->alias('a')
                ->field('a.*,b.mobile as p_mobile,b.name as p_name,b.department')
                ->join('rn_unicom_member_info b','a.p_mobile=b.mobile')
                ->where('a.name','like',$name)
                ->order('createtime desc')
                ->paginate(15,false,['query'=>request()->param()]);
        }else{
            $list = Db::table('rn_unicom_partner')
                ->alias('a')
                ->field('a.*,b.mobile as p_mobile,b.name as p_name,b.department')
                ->join('rn_unicom_member_info b','a.p_mobile=b.mobile')
                ->order('createtime desc')
                ->paginate(15,false,['query'=>request()->param()]);
        }

        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 员工/初创人列表
     */
    public function memberlist(){
        $name = input('keyword');
        if($name){
            $list = Db::name('unicom_member_info')->order('id asc')->where('name','like',$name)->paginate(15);
        }else{
            $list = Db::name('unicom_member_info')->order('id asc')->paginate(15);
        }
        $this->assign('list',$list);

        return $this->fetch();
    }

    /**
     * 员工内容修改
     */
    public function memberedit(){
        $id = input('id/d');
        if(input('post.')){
            $id = input('post.id/d');
            if($id){
                $data = [
                    'name' => input('post.name'),
                    'mobile'=> input('post.mobile/d'),
                    'department' => input('post.department'),
                    'group' => input('post.group'),
                    'add' => input('post.add'),
                    'position' => input('post.position'),
                    'img'=>input('post.img')

                ];
                $res = Db::name('unicom_member_info')->where('id',$id)->update($data);
            }else{
                $data = [
                    'name' => input('post.name'),
                    'mobile'=> input('post.mobile/d'),
                    'department' => input('post.department'),
                    'group' => input('post.group'),
                    'add' => input('post.add'),
                    'position' => input('post.position'),
                    'img'=>input('post.img')
                ];
                $res = Db::name('unicom_member_info')->insert($data);
            }

            if($res){
                return ajaxReturn(200,'修改成功');
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }

        $info = Db::name('unicom_member_info')->where('id',$id)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 删除一条员工信息
     */
    public function del(){
        $id = input('id/d');
        if($id){
            $res = Db::name('unicom_member_info')->delete($id);
            if($res){
                return ajaxReturn(200,'删除成功');
            }else{
                return ajaxReturn(0,'删除失败');
            }
        }else{
            return ajaxReturn(0,'参数错误');
        }
    }

    /**
     * 网吧权益活动用户列表
     */
    public function internetbar(){

        $list = Db::name('unicom_internetbar_user')->order('createtime desc')->paginate(20);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 德克士活动等级用户列表
     */
    public function dicos(){
        $list = Db::name('unicom_dicos_user')->order('createtime desc')->paginate(20);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 微信支付代金券/立减优惠
     */
    public function sendpaycoupon(){
        $id = input('post.id/d');
        if($id){
            $openid = Db::name('unicom_dicos_user')->where('id',$id)->value('openid');
            $payCoupon = new WXPayDiscounts;
            $partner_trade_no = '1218486601'.date('Ymd').rand(100000,999999);
            $data = [
                'coupon_stock_id' => '9311738',//代金券批次id
                'openid_count' => 1,
                'partner_trade_no' => $partner_trade_no,
                'openid' => $openid,
                'appid' => 'wxd5ba66c5e22dcd5b',
                'mch_id' => '1218486601' //商户号
            ];
            $appkey = 'f2ed0fd4c4d3dd650177de52195a9c29';  //商户秘钥
            $res = $payCoupon->send_coupon($data,$appkey);
//            dump($res);die;
            if($res['return_code'] == 'SUCCESS'){
                //发送模板消息
                $weObj=new Wechat(get_weichat_options());
                $template_id = 'ZX-4Go0ZrFW1S9USGdxTux_STneWZl0aFj30hoH_RWk';
                $arr = array();
                $arr['touser']=$openid;
                $arr['template_id']=$template_id;
                $arr['url']='';
                $arr['data']['first']['value']='获得代金券通知';
                $arr['data']['keyword1']['value']='恭喜获得代金券一张';
                $arr['data']['keyword2']['value']=date('Y-m-d H:i:s');
                $arr['data']['remark']['value']='德克士联通代金券，德克士门店微信支付满2元立减1元';
                $weObj->sendTemplateMessage($arr);
                $info = [
                    'openid'=>$openid,
                    'template_id'=>$template_id,
                    'status'=>1,
                    'time'=>date('Y-m-d H:i:s'),
                ];
                Db::name('pfault_info')->insert($info); //插入模板消息发送记录
                return ajaxReturn(200,'操作成功');
            }else if(isset($res['err_code'])){
                return ajaxReturn(0,$res['err_code_des']);
            }else{
                return ajaxReturn(0,'操作失败');
            }

        }else{
            return ajaxReturn(0,'参数错误');
        }
    }


    /**
     * 发券 模板消息
     */
    public function putcard(){
        if($_POST){
            $card_id = input('post.card_id');
            if(empty($card_id)){
                return ajaxReturn(0,'请选择一个卡券');
            }
            $openid = input('post.openid');
            $cryptstr = md5('unicom'.$card_id.$openid);
            $url = WEBURL.'/recruit/internetbar/quan.html?openid='.$openid.'&card_id='.$card_id.'&cryptstr='.$cryptstr;
            $weObj=new Wechat(get_weichat_options());
            $arr = array();
            $arr['touser']=$openid;
            $arr['template_id']='ZX-4Go0ZrFW1S9USGdxTux_STneWZl0aFj30hoH_RWk';
            $arr['url']=$url;
            $arr['data']['first']['value']='点击领取您的卡券';
            $arr['data']['keyword1']['value']='卡券下发';
            $arr['data']['keyword2']['value']=date('Y-m-d H:i:s');
            $arr['data']['remark']['value']='领取后将放入您的卡包，再次到店面消费时请出示，谢谢。';
            $weObj->sendTemplateMessage($arr);
            return ajaxReturn(200,'操作成功',url('internetbar'));
        }

        $id = input('id/d');
        $info = Db::name('unicom_internetbar_user')->where('id',$id)->find();
        //状态正常卡券
        $ticketsList = Db::name('xzy_coupon')->where('date_type','DATE_TYPE_FIX_TERM')->whereOr('end_timestamp','>',time())->select();
        $this->assign('ticketsList',$ticketsList);
        $this->assign('info',$info);
        return $this->fetch();
    }






    /**
     * 导出未审核列表
     */
    public function exportlist(){
        $columns = ['合伙人姓名','合伙人openid','身份证号码','手机号','编号','身份证正面','身份证背面','初创人手机号','所属渠道','所属网格','所属专业线' ];
        $csvFileName = '未审核合伙人列表.csv';
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

//        $sendcount = Db::name('unicom_partner')->where('status',0)->select();
        $sendcount = Db::table('rn_unicom_partner')->alias('a')
            ->field('a.*,b.mobile as p_mobile,b.department,b.group,b.add')
            ->join('rn_unicom_member_info b','a.p_mobile=b.mobile')
            ->where('status',0)
            ->select();
        $tmparr = array();
        foreach ($sendcount as $key => $value) {
            $tmparr['name'] = $value['name'];
            $tmparr['openid'] = $value['openid'];
            $tmparr['cardid'] = $value['cardid'];
            $tmparr['mobile'] = $value['mobile'];
            $tmparr['cbss'] = $value['cbss'];
            $tmparr['card_img_up'] = $value['card_img_up'];
            $tmparr['card_img_dn'] = $value['card_img_dn'];
            $tmparr['p_mobile'] = $value['p_mobile'];
            $tmparr['department'] = $value['department'];
            $tmparr['group'] = $value['group'];
            $tmparr['add'] = $value['add'];
            mb_convert_variables('GBK', 'UTF-8', $tmparr);
            fputcsv($fp, $tmparr);
        }
        ob_flush();
        flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        fclose($fp);


    }


    /**
     * 导入审核列表
     */
    public function uploadlist(){
        if($_FILES){

            $file = $_FILES['file'];

            if(substr($file['name'],-3) !== 'csv'){
                return ajaxReturn(0,'请上传csv文件');
            }
            if($file['error']>0){
                return ajaxReturn(1,'上传失败');
            }

            $temp = Db::name('pfault_template_list')->where('id',19)->find();
            $filename = date('Y-m-d H:i:s').'.csv';
            $dir = "/data/wwwroot/unicom.wx.snrunning.cn/public/uploads/csv/";
            move_uploaded_file($file['tmp_name'],$dir.$filename);
            $csv_file = fopen($dir.$filename,'r');

            while ($csv_arr = fgetcsv($csv_file)){
                $csv_array[] = $csv_arr;
            }
            array_shift($csv_array);
            foreach ($csv_array as $k=>$v){
//            $data[$k]['name'] = iconv('GBK','UTF-8',$v[0]);
//            $data[$k]['card_id'] = $v[1];
//            $data[$k]['cbss'] = $v[3];

                if(!empty($v[4])){
                    $data = [
                        'cbss'=>$v[4],
                        'status'=>1
                    ];
                    $res = Db::name('unicom_partner')->where('mobile',$v[3])->update($data);
                    //推送模板消息
                    if($res){
                        $weObj=new Wechat(get_weichat_options());
                        $arr = array();
                        $arr['touser']=$v[1];
                        $arr['template_id']=$temp['template_id'];
                        $arr['url']="http://unicom.wx.snrunning.cn/recruit/member/partnerindex.html?openid=".$v[1];
                        $arr['data']['first']['value']="审核成功";
                        $arr['data']['keyword1']['value']="您好，你的合伙人注册审核验证成功，请点进入个人中心";
                        $weObj->sendTemplateMessage($arr);
                    }
                }
            }

            fclose($csv_file);
            return ajaxReturn(200,'上传成功');
        }
        return $this->fetch();

    }


    public function getCsvData($file, $row = 2000)
    {
        global $ret_array, $redis, $pre;

        if (empty($file) or !file_exists($file)) {

//            $redis->set($pre . 'upload_mobile_bank_data_status', 0, 30);
//            $ret_array['msg'] = '文件不存在';
//            echo json_encode($ret_array);
            exit;
        }

        $allRow = `cat $file|wc -l`;
        if (empty($allRow)) {

//            $redis->set($pre . 'upload_mobile_bank_data_status', 0, 30);
//            $ret_array['msg'] = '读取文件失败';
//            echo json_encode($ret_array);
            exit;
        }

        $allDataArr = array();
        for ($i = 0, $count = floor($allRow / $row); $i <= $count; $i++) {
            $startRow = $i == 0 ? $i * $row + 2 : $i * $row + 1;
            $endRow   = $i < $count ? $i * $row + $row : $i * $row + $allRow % $row;
            $dataArr  = $this->readFromCsv($file, $startRow, $endRow);

            if (!empty($dataArr)) {
                $sql = "insert into rn_unicom_partner (cbss) VALUES ";
                foreach ($dataArr as $value) {

                    if (!empty($value)) {
                        $sql .= '('.$value[0].'),';

                    }
                }
                dump($sql);
                Db::execute(rtrim($sql,','));
//
//                array_unshift($dataArr, $pre . 'uploadUser');
//                call_user_func_array(array($redis, 'RPUSH'), $dataArr);
            }
            $dataArr = null;
        }
        return $allDataArr;
    }
    /**
     * 读取指定行数的数据
     * @param  string $file  文件路径
     * @param  int $startRow 起始行
     * @param  int $endRow   终止行
     * @return array         数据数组
     */
    private function readFromCsv($file, $startRow, $endRow)
    {
        $file     = escapeshellarg($file); // 对命令行参数进行安全转义
        $tempData = `sed -n $startRow,$endRow'p' $file`;
        $dataArr  = explode("\n", trim(str_replace('"', '', iconv('EUC-CN', 'UTF-8', $tempData))));

        return $dataArr;
    }


}