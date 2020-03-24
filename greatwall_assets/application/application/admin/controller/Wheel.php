<?php
/**
 * 幸运大转盘
 */

namespace app\admin\controller;

use think\Db;
use org\weixin\Wechat;

class Wheel extends Admin{



    /**
     * 设置模块参数
     */

    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '营销活动',
                'description' => '活动管理',
            ),
            'menu' => array(
                array(
                    'name' => '活动列表',
                    'url' => url('wheel_list'),
                    'icon' => 'list',
                ),

            ),
            '_info' => array(
                array(
                    'name' => '新建活动',
                    'url' => url('addGame'),
                ),
            ),
        );
    }

    /**
     * 列表
     */
    public function wheel_list(){

        $list = Db::name('game_wheel')->select();//后期要分页
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     *新建一个活动
     */
    public function addGame(){

        $id = input('id/d');
        $info = Db::name('game_wheel')->where('id',$id)->find();
        $this->assign('info',$info);

        if(input('post.')){
            $id = input('post.gameid/d');
            $data = [
                'title' => input('post.title'),
                'p_title' => input('post.p_title'),
                'max_time' => input('post.max_time'),
                'day_time' => input('post.day_time'),
                'starttime' => strtotime(input('post.starttime')),
                'endtime' => strtotime(input('post.endtime')),
                'posttime' => time(),
                'logo' => input('post.logo'),
                'description' => input('post.description'),
                'isshare' => input('post.isshare'),
                'status' => input('post.status')
            ];
            if($id){//修改
                $res = Db::name('game_wheel')->where('id',$id)->update($data);
                if($res){
                    return ajaxReturn(200,'编辑成功',url('wheel_list'));
                }else{
                    return ajaxReturn(0,'编辑失败');
                }

            }else{//新建
                $gameid = Db::name('game_wheel')->insertGetId($data);//新建一个活动信息
                if($gameid){
                    //新建数据表
                    $this->add_wheel($gameid);
                    $info = [
                        'gameid' => $gameid,
                    ];
                    $data = [
//                        ['gameid' => $gameid, 'prize_rank' => '1','min_angle'=>5,'max_angle'=>43,'version'=>1],
//                        ['gameid' => $gameid, 'prize_rank' => '2','min_angle'=>48,'max_angle'=>87,'version'=>1],
//                        ['gameid' => $gameid, 'prize_rank' => '3','min_angle'=>93,'max_angle'=>132,'version'=>1],
//                        ['gameid' => $gameid, 'prize_rank' => '4','min_angle'=>138,'max_angle'=>177,'version'=>1],
//                        ['gameid' => $gameid, 'prize_rank' => '5','min_angle'=>183,'max_angle'=>223,'version'=>1],
//                        ['gameid' => $gameid, 'prize_rank' => '6','min_angle'=>228,'max_angle'=>266,'version'=>1],
//                        ['gameid' => $gameid, 'prize_rank' => '7','min_angle'=>273,'max_angle'=>312,'version'=>1],
//                        ['gameid' => $gameid, 'prize_rank' => '8','min_angle'=>318,'max_angle'=>358,'version'=>1],
                        ['gameid' => $gameid, 'prize_rank' => '1','min_angle'=>5,'max_angle'=>55,'version'=>1],
                        ['gameid' => $gameid, 'prize_rank' => '2','min_angle'=>65,'max_angle'=>115,'version'=>1],
                        ['gameid' => $gameid, 'prize_rank' => '3','min_angle'=>125,'max_angle'=>175,'version'=>1],
                        ['gameid' => $gameid, 'prize_rank' => '4','min_angle'=>185,'max_angle'=>235,'version'=>1],
                        ['gameid' => $gameid, 'prize_rank' => '5','min_angle'=>245,'max_angle'=>295,'version'=>1],
                        ['gameid' => $gameid, 'prize_rank' => '6','min_angle'=>305,'max_angle'=>355,'version'=>1],

                    ];
                    Db::name('game_wheel_index')->insert($info);//初始化各表数据
                    Db::name('game_wheel_rule')->insert($info);
                    Db::name('game_wheel_result')->insert($info);
                    Db::name('game_wheel_prizeinfo')->insertAll($data);
                    return ajaxReturn(200,'新建成功',url('wheel_list'));
                }else{
                    return ajaxReturn(0,'操作失败');
                }
            }

        }

        return $this->fetch();
    }



    /**
     * 首页
     */

    public function index(){
        if(input('post.')){
            $gameid = input('post.gameid');
            if(empty($gameid)){
                return ajaxReturn('0','网络错误!');
            }
            $data = [
                'img_back' => input('post.img_back'),
                'img_prize' => input('post.img_prize'),
                'img_float' => input('post.img_float'),
                'img_logo1' => input('post.img_logo1'),
                'img_logo2' => input('post.img_logo2'),
                'img_button' => input('post.img_button'),
            ];
            $res = Db::name('game_wheel_index')->where('gameid',$gameid)->update($data);
            if($res){
                return ajaxReturn('200','操作成功',url('wheel_list'));
            }else{
                return ajaxReturn('0','操作失败');
            }
        }

        $gameid = input('id/d');
        $info = Db::name('game_wheel_index')->where('gameid',$gameid)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 规则页面
     */
    public function rule(){
        if (input('post.')){
            $gameid = input('post.gameid/d');
            $res = Db::name('game_wheel_rule')->where('gameid',$gameid)->update(['ruleinfo'=>input('post.ruleinfo'),'rulelogo'=>input('post.rulelogo')]);
            if($res){
                return ajaxReturn(200,'提交成功',url('wheel_list'));
            }else{
                return ajaxReturn(0,'提交失败');
                }
        }
        $gameid = input('id/d');
        $info = Db::name('game_wheel_rule')->where('gameid',$gameid)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 中奖历史记录
     *
     *
     */
    public function wheelData(){
        $gameid = input('id/d');

//        $sql = "select a.id,a.title,a.starttime,a.endtime,b.gameid,b.prize_rank,b.prize_title,c.*,d.openid,d.nickname
//from rn_game_wheel a ,rn_game_wheel_prizeinfo b,rn_game_wheel_data_gameid_".$gameid." c,rn_wxuser d
//where c.openid=d.openid and a.id=".$gameid." and b.gameid=".$gameid." and c.gameid=".$gameid." and c.prize_rank=b.prize_rank ";
//        $info = Db::query($sql);
        $list =Db::table('rn_game_wheel')->alias('a')
            ->join('rn_game_wheel_data_gameid_'.$gameid.' b','b.gameid='.$gameid.' and b.gameid=a.id')
            ->join('rn_game_wheel_prizeinfo c','c.prize_rank=b.prize_rank and b.gameid=c.gameid')
            ->join('rn_wxuser d','d.openid=b.openid')
            ->field('a.id,a.title,a.starttime,a.endtime,a.status as game_status,b.*,c.gameid,c.prize_rank,c.prize_title,d.openid,d.nickname,d.headimgurl')
            ->paginate(20);

        $this->assign('list',$list);
		$this->assign('gameid',$gameid);
        return $this->fetch();

    }

    /**
     * 删除一条中奖记录
     */
    public function del(){
        $id = input('id/d');
        $gameid = input('gameid');
        $res = Db::name('game_wheel_data_gameid_'.$gameid)->delete($id);
        if($res){
            return ajaxReturn('200','操作成功');
        }else{
            return ajaxReturn('0','操作失败');
        }
    }



    public function share(){


        return $this->fetch();
    }

    /**
     * 奖项设置
     */
    public function prize(){

        if($_POST){
            //获取当前规则版本
            $data = $_POST['data'];
            foreach($data as $v){
                $info = [
                    'prize_title' => $v['prize_title'],
                    'chance' => $v['chance'],
                    'allnum' => $v['allnum'],
                    'prizenum' => $v['allnum'],
                    'link' => $v['link']
                ];
                $map = [
                    'gameid' => $v['gameid'],
                    'prize_rank' => $v['prize_rank']
                ];
                Db::name('game_wheel_prizeinfo')->where($map)->update($info);
            }
            return ajaxReturn('200','操作成功',url('wheel_list'));
        }
        $gameid = input('id/d');
        $this->assign('gameid',$gameid);
        $info = Db::name('game_wheel_prizeinfo')->where('gameid',$gameid)->order('prize_rank asc')->select();
        $this->assign('info',$info);
        return $this->fetch();

    }

    /**
     * 上传奖品设置页面的奖品图片，暂时注释
     */
//    public function prizeImg(){
//        $prize_rank = intval($_POST['prize_rank']);
//        foreach ($_FILES as $key=>$val){
//            $file=$key;
//            break;
//        }
//        $data=model('admin/File')->uploadImg($file,'game');
//        if ($data['url']){
//            $msg['status'] =200;
//            $msg['prize_rank'] = $prize_rank;
//            $msg['url'] = $data['url'];
//        }else{
//            $msg['status'] =0;
//        }
//        return json_encode($msg);
//    }


    /**
     * 中奖页面
     */
    public function winning(){
        if($_POST){
            $gameid = input('post.gameid/d');
            if(empty($gameid)){
                return ajaxReturn('0','网络错误');
            }
            $data = [
                'resultinfo' => input('post.resultinfo'),
                'resultlogo' => input('post.resultlogo'),
                'img' => input('post.img'),
                'url' => input('post.url'),
            ];
            $res = Db::name('game_wheel_result')->where('gameid',$gameid)->update($data);
            if($res){
                return ajaxReturn('200','操作成功',url('wheel_list'));
            }else{
                return ajaxReturn('0','操作失败');
            }
        }
        $gameid = input('id/d');
        $info = Db::name('game_wheel_result')->where('gameid',$gameid)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

	/**
	 *确认用户兑奖
	 */
	 public function sure(){
		 if(input('id/d')){
			echo "测试中";
		 }
	 }


    /**
     * 添加规则页面，中奖页面logo
     */
    public function addLogo(){
        foreach ($_FILES as $key=>$val){
            $file=$key;
            break;
        }
        $data=model('admin/File')->uploadImg($file,'game');
        if ($data['url']){
            $msg['status'] =200;
            $msg['url'] = $data['url'];
        }else{
            $msg['status'] =0;
        }
        return json_encode($msg);
    }



    /**
     * 上传首页背景图片
     */
    public function back_img(){
        foreach ($_FILES as $key=>$val){
            $file=$key;
            break;
        }
        $data=model('admin/File')->uploadImg($file,'game');
        if ($data['url']){
            $msg['status'] =200;
            $msg['url'] = $data['url'];
            $msg['prize_rank'] = $_POST['prize_rank'];
        }else{
            $msg['status'] =0;
        }
        return json_encode($msg);
    }


    /*
     * 添加一次新活动
     */
    public function add_wheel($gameid){
        //新建活动数据表
        $sql = "CREATE TABLE rn_game_wheel_data_gameid_".$gameid." (
        id INT(11) NOT NULL AUTO_INCREMENT,
        openid CHAR(40) NOT NULL,
        gameid INT(11) NOT NULL,
        prize_rank INT(1) NOT NULL,
        createtime INT(11) NOT NULL DEFAULT 0,
        code CHAR(30) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 0,
        changecode_time INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $res = Db::execute($sql);
        if($res){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 删除一期活动，包括数据等全部删除
     * 如需求有变动再修改
     */
    public function delWheel(){
        $gameid = input('id/d');
        $res = Db::name('game_wheel')->where('id',$gameid)->delete();
        Db::name('game_wheel_index')->where('gameid',$gameid)->delete();
        Db::name('game_wheel_rule')->where('gameid',$gameid)->delete();
        Db::name('game_wheel_result')->where('gameid',$gameid)->delete();
        Db::name('game_wheel_prizeinfo')->where('gameid',$gameid)->delete();
        Db::query("drop table rn_game_wheel_data_gameid_"."$gameid" );
        return ajaxReturn('200','删除成功');

    }
    


}


