<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2018/6/16
 * Time: 8:30
 */

namespace app\admin\controller;

use org\weixin\Wechat;
use think\Db;

class Membercard extends Admin
{

    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '会员卡管理',
                'description' => '会员卡管理',
            ),
            'menu' => array(
                array(
                    'name' => '会员卡管理',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
            ),
            '_info' => array(
                array(
                    'name' => '添加记录',
                    'url' => url('info'),
                ),
            ),
        );
    }
    /**
     * 列表
     */
    public function index(){
        //筛选条件
        $where = array();
        //查询数据
        $limit=15;
        $list = model('XzyMembercard')->loadList($where,$limit);

        //位置导航
        $breadCrumb = array('会员卡管理'=>url());
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('_page',$list->render());
        return $this->fetch();
    }
    /**
     * 详情
     */
    public function info(){
        $id = input('id');
        $model = model('XzyMembercard');
        //$pfault = model('XzyPfault');

        if (input('post.')){

            $weObj=new Wechat(get_weichat_options());

           

            if (input('post.id')){

                $dateType = array("type"=> 'DATE_TYPE_PERMANENT');
                if(input('post.date_type')=='DATE_TYPE_PERMANENT'){
                    $dateType = array("type"=> 'DATE_TYPE_PERMANENT');
                }elseif(input('post.date_type')=='DATE_TYPE_FIX_TIME_RANGE'){
                    $dateType = array(
                        "type"=> 'DATE_TYPE_FIX_TIME_RANGE',
                        "begin_timestamp"=>strtotime(input('post.begin_timestamp')),
                        "end_timestamp"=>strtotime(input('post.end_timestamp'))
                    );
                }elseif(input('post.date_type')=='DATE_TYPE_FIX_TERM'){
                    $dateType = array(
                        "type"=> 'DATE_TYPE_FIX_TERM',
                        "fixed_term"=> input('post.fixed_term'),
                        "fixed_begin_term"=>input('post.fixed_begin_term'),
                    );
                }

                $data = array(
                    "card_id"=>input('post.card_id'),
                    "member_card"=>array(
                        "background_pic_url"=> input('post.background_pic_url'),
                        "base_info"=>array(
                            "logo_url"=>"http://vip.xinziyou.com/static/home/images/xinziyou.jpg",
                            "color"=> input('post.color'),
                            "notice"=>trim(input('post.notice')),
                            "description"=>trim(input('post.description')),
                            "date_info"=>$dateType
                        ),
                        "prerogative"=>trim(input('post.prerogative')),
                        "auto_activate"=>true
                    )
                );
                //\think\facade\Log::write($data);

                $result = $weObj->updateCard($data);

                if(!$result)
                {
                    return ajaxReturn(0,'会员卡微信端修改失败。错误信息：'.$weObj->errMsg);
                }

                //$resArr = json_decode($result,true);

                $status=$model->edit();
            }else{
                $dateType = array("type"=> 'DATE_TYPE_PERMANENT');
                if(input('post.date_type')=='DATE_TYPE_PERMANENT'){
                    $dateType = array("type"=> 'DATE_TYPE_PERMANENT');
                }elseif(input('post.date_type')=='DATE_TYPE_FIX_TIME_RANGE'){
                    $dateType = array(
                        "type"=> 'DATE_TYPE_FIX_TIME_RANGE',
                        "begin_timestamp"=>strtotime(input('post.begin_timestamp')),
                        "end_timestamp"=>strtotime(input('post.end_timestamp'))
                    );
                }elseif(input('post.date_type')=='DATE_TYPE_FIX_TERM'){
                    $dateType = array(
                        "type"=> 'DATE_TYPE_FIX_TERM',
                        "fixed_term"=> input('post.fixed_term'),
                        "fixed_begin_term"=>input('post.fixed_begin_term'),
                    );
                }
                    

                 $data = array(
                    'card'=>array(
                        "card_type"=>"MEMBER_CARD",
                        "member_card"=>array(
                            "background_pic_url"=> input('post.background_pic_url'),
                            "base_info"=>array(
                                "logo_url"=>"http://vip.xinziyou.com/static/home/images/xinziyou.jpg",
                                "brand_name"=> trim(input('post.brand_name')),
                                "code_type"=> "CODE_TYPE_QRCODE",
                                "title"=> trim(input('post.title')),
                                "color"=> input('post.color'),
                                "notice"=>trim(input('post.notice')),
                                "description"=>trim(input('post.description')),
                                 "date_info"=>$dateType,
                                "sku"=>array(
                                    "quantity"=>input('post.quantity')
                                ),
                                "get_limit"=> 1,
                                "use_custom_code"=>false,
                                "can_give_friend"=> false

                            ),
                            "supply_bonus"=> false,
                            "supply_balance"=>false,
                            "prerogative"=>trim(input('post.prerogative')),
                            "auto_activate"=>true
                        )
                    )
                );
                //\think\facade\Log::write($data);

                $result = $weObj->createCard($data);

                if(!$result)
                {
                    return ajaxReturn(0,'会员卡微信端创建失败。');
                }

                //$resArr = json_decode($result,true);
                $_POST['card_id']=$result['card_id'];

                $_POST['posttime']=time();
                $status=$model->add();
            }
            if($status!==false){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{


            //$this->assign('pfault', $pfault->loadList());
            if($id){
                $this->assign('info', $model->getInfo($id));
            }else{

                $this->assign('info', array('color'=>'Color010','card_id'=>'','date_type'=>'DATE_TYPE_FIX_TIME_RANGE'));
            }

            //$this->assign('groupList',model('AdminGroup')->loadList());
            return $this->fetch();
        }
    }


    /**
     * 删除
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }
        $cardinfo = Db::name('xzy_membercard')->field('card_id')->where('id',$id)->find();
        $weObj=new Wechat(get_weichat_options());
        $result = $weObj->delCard($cardinfo['card_id']);
        if(model('XzyMembercard')->del($id)){
            //删除卡券
            return ajaxReturn(200,'删除成功！');
        }else{
            return ajaxReturn(0,'删除失败');
        }
    }
}