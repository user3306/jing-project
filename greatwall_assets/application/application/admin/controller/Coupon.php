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

class Coupon extends Admin
{

    /**
     * 当前模块参数
     */
    protected function _infoModule()
    {
        return array(
            'info' => array(
                'name' => '卡券管理',
                'description' => '卡券管理',
            ),
            'menu' => array(
                array(
                    'name' => '卡券管理',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
                array(
                    'name' => ' 卡券货架',
                    'url' => url('landingpage'),
                    'icon' => 'list',
                ),
                array(
                    'name' => ' 卡券核销',
                    'url' => url('closure'),
                    'icon' => 'list',
                ),


            ),
            '_info' => array(
                array(
                    'name' => '添加记录',
                    'url' => url('info'),
                ),
                array(
                    'name' => '一键获取所有卡券',
                    'url' => url('freshcard'),
                    'function' => 'ajax',
                ),
            ),
        );
    }

    /**
     * 列表
     */
    public function index()
    {


        //筛选条件
        $where = array();
        //查询数据
        $limit = 15;
        $list = model('XzyCoupon')->loadList($where, $limit);

        //位置导航
        $breadCrumb = array('卡券管理' => url());
        //模板传值
        $this->assign('breadCrumb', $breadCrumb);
        $this->assign('list', $list);
        $this->assign('_page', $list->render());
        return $this->fetch();
    }

    /**
     * 卡券货架页面
     */
    public function landingpage()
    {
        $weObj = new Wechat(get_weichat_options());
        $info = Db::name('xzy_coupon_page')->where('id', 1)->find();
        $this->assign('info', $info);
        if (input('post.')) {
            $card_list = json_encode($_POST['card_list']);
            $card = array();
            foreach ($_POST['card_list'] as $k => $v) {
                $card[$k]['card_id'] = $v;
                $card[$k]['thumb_url'] = Db::name('xzy_coupon')->where('card_id', $v)->value('logo_url');
            }

            if (input('post.can_share') == "true") {
                $can_share = true;
            } else {
                $can_share = true;
            }
            $buffer = [
                'banner' => 'http://' . $_SERVER['SERVER_NAME'] . input('post.banner'),
                'page_title' => input('post.page_title'),
                'can_share' => $can_share,
                'scene' => input('post.scene'),
                'card_list' => array_values($card)
            ];
            $ress = $weObj->cardLandingpage($buffer);
            if ($ress['errcode'] == 0) {
                $data = [
                    'banner' => input('post.banner'),
                    'page_title' => input('post.page_title'),
                    'can_share' => input('post.can_share'),
                    'card_list' => $card_list,
                    'url' => $ress['url'],
                    'page_id' => $ress['page_id']
                ];
                $res = Db::name('xzy_coupon_page')->where('id', 1)->update($data);
                if ($res) {
                    return ajaxReturn(200, '操作成功', url('index'));
                } else {
                    return ajaxReturn(0, '操作失败');
                }
            } else {
                return ajaxReturn(0, '操作失败');
            }

        }


        $coupon = Db::name('xzy_coupon')->select();
        $card_page_list = json_decode($info['card_list']);
        $this->assign('card_page_list',$card_page_list);
        $this->assign('coupon', $coupon);

        return $this->fetch();
    }


    /**
     * 更新卡券到本地
     * 暂时只有优惠券类型的卡券
     * card_type: GENERAL_COUPON 优惠券类型 base_info;default_detail:string(3072)优惠券专用，填写优惠详情
     * card_type: GIFT 兑换券类型 base_info;gift:string(3072)兑换券专用，填写兑换内容的名称。
     * card_type: DISCOUNT 折扣券类型 base_info;discount:int 折扣券专用，表示打折额度（百分比）。填30就是七折。
     * card_type: CASH 代金券类型 base_info ;least_cost int代金券专用，表示起用金额（单位为分）,如果无起用门槛则填0。 reduce_cost int 代金券专用，表示减免金额。（单位为分
     * card_type: GROUPON 团购券类型 base_info; deal_detail string( 3072 ) 团购券专用，团购详情。
     */
    public function freshcard()
    {
        //先获取全部卡券id
        $weObj = new Wechat(get_weichat_options());
        $offset = 0;
        $info = $weObj->getCardIdList($offset);

//        $res = $weObj->getCardInfo('p0iqKjldKZZaS5tgf2Mc03XiN4ak');//测试，获取卡券详情
//        dump($res);die;
        if ($info['total_num'] == 0) {
            return ajaxReturn(0, '没有可更新的卡券');
        }
        $card_id_list = $info['card_id_list'];
        foreach ($card_id_list as $v) {
            $is_have = Db::name('xzy_coupon')->where('card_id', $v)->find();
            if (!$is_have) {
                $res = $weObj->getCardInfo($v);
                if ($res['card']['card_type'] == 'GROUPON') {
                    $card_class = 'groupon';
                } elseif ($res['card']['card_type'] == 'GENERAL_COUPON') {
                    $card_class = 'general_coupon';
                } elseif ($res['card']['card_type'] == 'GIFT') {
                    $card_class = 'gift';
                } elseif ($res['card']['card_type'] == 'CASH') {
                    $card_class = 'cash';
                } elseif ($res['card']['card_type'] == 'DISCOUNT') {
                    $card_class = 'discount';
                } elseif ($res['card']['card_type'] == 'MEMBER_CARD'){//会员卡不在这里，在membercard模块
                    continue;
//                    $card_class = 'member_card';
                } else{
                    return ajaxReturn(0,'网络错误');
                }
                $data = [
                    'card_type' => $res['card']['card_type'],
                    'card_id' => $res['card'][$card_class]['base_info']['id'],
                    'logo_url' => $res['card'][$card_class]['base_info']['logo_url'],
                    'code_type' => $res['card'][$card_class]['base_info']['code_type'],
                    'brand_name' => $res['card'][$card_class]['base_info']['brand_name'],
                    'title' => $res['card'][$card_class]['base_info']['title'],
                    'color' => $res['card'][$card_class]['base_info']['color'],
                    'notice' => $res['card'][$card_class]['base_info']['notice'],
                    'description' => $res['card'][$card_class]['base_info']['description'],
                    'get_limit' => $res['card'][$card_class]['base_info']['get_limit'],
                    'date_type' => $res['card'][$card_class]['base_info']['date_info']['type'],
                    'begin_timestamp' => isset($res['card'][$card_class]['base_info']['date_info']['begin_timestamp']) ? $res['card'][$card_class]['base_info']['date_info']['begin_timestamp'] : 0,
                    'fixed_term' => isset($res['card'][$card_class]['base_info']['date_info']['fixed_term']) ? $res['card'][$card_class]['base_info']['date_info']['fixed_term'] : 0,
                    'end_timestamp' => isset($res['card'][$card_class]['base_info']['date_info']['end_timestamp']) ? $res['card'][$card_class]['base_info']['date_info']['end_timestamp'] : 0,
                    'fixed_begin_term' => isset($res['card'][$card_class]['base_info']['date_info']['fixed_begin_term']) ? $res['card'][$card_class]['base_info']['date_info']['fixed_begin_term'] : 0,
                    'deal_detail' => isset($res['card'][$card_class]['deal_detail']) ? $res['card'][$card_class]['deal_detail'] : '',
                    'quantity' => $res['card'][$card_class]['base_info']['sku']['quantity'],//现有的库存
                    'coupon_rest' => $res['card'][$card_class]['base_info']['sku']['quantity'],//卡券剩余数量，初始和微信端库存一样
                    'total_quantity' => $res['card'][$card_class]['base_info']['sku']['total_quantity'],//总共的库存
                    'least_cost' => isset($res['card'][$card_class]['least_cost']) ? $res['card'][$card_class]['least_cost'] : 0,
                    'reduce_cost' => isset($res['card'][$card_class]['least_cost']) ? $res['card'][$card_class]['least_cost'] : 0,
                    'discount' => isset($res['card'][$card_class]['discount']) ? $res['card'][$card_class]['discount'] : 0,
                    'gift' => isset($res['card'][$card_class]['gift']) ? $res['card'][$card_class]['gift'] : '',
                    'default_detail' => isset($res['card'][$card_class]['default_detail']) ? $res['card'][$card_class]['default_detail'] : '',
                ];
                $result = Db::name('xzy_coupon')->insert($data);
                if(!$result){
                    return ajaxReturn(0, '更新失败');
                }
            }
        }
        return ajaxReturn(200, '更新成功',url('index'));
    }




    /**
     * 详情
     */
    public function info(){
        $id = input('id');
        $model = model('XzyCoupon');
        //$pfault = model('XzyPfault');
        if ($_POST){
            $weObj=new Wechat(get_weichat_options());

            if (input('post.id')){//修改卡券信息
                $dateType = array();
                if(input('post.date_type')=='DATE_TYPE_FIX_TIME_RANGE'){
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
                $card_type = input('post.card_type');

                $data = array(
                    "card_id"=>input('post.card_id'),
                    strtolower($card_type)=>array( //这里应该填小写，微信到处都是坑
                        "base_info"=>array(
//                            "logo_url"=>"http://unicom.wx.snrunning.cn/static/recruit/coupon_logo.png",
                            "logo_url"=>"http://mmbiz.qpic.cn/mmbiz/UUvUkibibRhPgzOsCexzVRGhdaqq2ODjYyZ30wTgUfvx5DiaJSvOS7JVfr9zKick1xaCZFy69iavOdIswMQk3khPMpA/0",
                            "color"=> input('post.color'),
                            "notice"=>trim(input('post.notice')),
                            "description"=>trim(input('post.description')),
                            "date_info"=>$dateType,
                            "get_limit"=> input('post.get_limit'),
                            "use_limit"=> input('post.use_limit')
                        )
                    )
                );
                //\think\facade\Log::write($data);

                $result = $weObj->updateCard($data);
                if(!$result)
                {
                    return ajaxReturn(0,'优惠券微信端修改失败。错误信息：'.$weObj->errMsg);
                }

                //$resArr = json_decode($result,true);

                $status=$model->edit();
            }else{//新增
                $dateType = array();
                if(input('post.date_type')=='DATE_TYPE_FIX_TIME_RANGE'){
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
                //基本卡券数据，所有类型通用
                $base_info = array(
                    "logo_url"=>"http://mmbiz.qpic.cn/mmbiz/UUvUkibibRhPgzOsCexzVRGhdaqq2ODjYyZ30wTgUfvx5DiaJSvOS7JVfr9zKick1xaCZFy69iavOdIswMQk3khPMpA/0",
                    "brand_name"=> trim(input('post.brand_name')),
                    "code_type"=> input('post.code_type'),//码型
                    "title"=> trim(input('post.title')),
                    "color"=> input('post.color'),
                    "notice"=>trim(input('post.notice')),
                    "description"=>trim(input('post.description')),
                    "sku"=>array(
                        "quantity"=>input('post.quantity')
                    ),
                    "date_info"=>$dateType,
                    "get_limit"=> input('post.get_limit'),
                    "use_limit"=> input('post.use_limit'),
                    "use_custom_code"=>false,
                    "can_give_friend"=> false,
                );


                //判断卡券类型
                $card_type = input('post.card_type');
                if($card_type == 'GENERAL_COUPON'){//优惠券类型
                    $data = array(
                        'card'=>array(
                            "card_type"=>$card_type,
                            "general_coupon"=>array(
                                "base_info"=> $base_info,
                                "default_detail"=>trim(input('post.default_detail'))
                            )
                        )
                    );
                }elseif ($card_type == 'GROUPON'){ //团购券类型
                    $data = array(
                        'card'=>array(
                            "card_type"=>$card_type,
                            "groupon"=>array(
                                "base_info"=> $base_info,
                                "deal_detail"=>trim(input('post.deal_detail')),
                            )
                        )
                    );
                }elseif ($card_type == 'CASH'){
                    $data = array(
                        'card'=>array(
                            "card_type"=>$card_type, //代金券类型
                            "cash"=>array(
                                "base_info"=> $base_info,
                                "least_cost"=>trim(input('post.least_cost/d')),
                                "reduce_cost"=>trim(input('post.reduce_cost/d')),
                            )
                        )
                    );
                }elseif ($card_type == 'DISCOUNT'){
                    $data = array(
                        'card'=>array(
                            "card_type"=>$card_type, //折扣券类型
                            "discount"=>array(
                                "base_info"=> $base_info,
                                "discount"=>trim(input('post.discount/d'))
                            )
                        )
                    );
                }elseif ($card_type == 'GIFT'){
                    $data = array(
                        'card'=>array(
                            "card_type"=>$card_type, //兑换券类型
                            "gift"=>array(
                                "base_info"=> $base_info,
                                "gift"=>trim(input('post.gift'))
                            )
                        )
                    );
                }else{
                    return ajaxReturn(0,'请选择合适的优惠券类型');
                }
                //\think\facade\Log::write($data);

                $result = $weObj->createCard($data);

                if(!$result)
                {
                    return ajaxReturn(0,'优惠券微信端创建失败。错误信息：'.$weObj->errMsg);
                }

                //$resArr = json_decode($result,true);
                $_POST['card_id']=$result['card_id'];

                $_POST['posttime']=time();
                $_POST['coupon_rest'] = trim(input('post.quantity'));
                $status=$model->add();
            }
            if($status!==false){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{

            //返回编辑
            //$this->assign('pfault', $pfault->loadList());
            if($id){
                $this->assign('info', $model->getInfo($id));
            }else{

                $this->assign('info', array('color'=>'Color010','card_id'=>'','code_type'=>'','card_type'=>'','date_type'=>'DATE_TYPE_FIX_TIME_RANGE'));
            }

            //$this->assign('groupList',model('AdminGroup')->loadList());
            return $this->fetch();
        }
    }

    /**
     * 修改库存
     */
    public function quantity(){
        $id = input('id/d');
        $this->assign('id',$id);
        if($_POST){
            $id = input('post.id/d');
            $weObj = new Wechat(get_weichat_options());
            $card_id = model('XzyCoupon')->getCard_id($id);
            if(!$card_id){
                return ajaxReturn(0,'参数错误');
            }
            $increase_stock_value = input('post.increase_stock_value/d');
            $reduce_stock_value = input('post.reduce_stock_value/d');
            $data = [
                'card_id' => $card_id,
                'increase_stock_value' => $increase_stock_value,
                'reduce_stock_value' => $reduce_stock_value,
            ];
            $res = $weObj->modifyCardStock($data);
            if($res['errcode'] == 0){
                if($increase_stock_value > 0){
                    Db::name('xzy_coupon')->where('id',$id)->setInc('quantity',$increase_stock_value);
                }else if($reduce_stock_value > 0){
                    Db::name('xzy_coupon')->where('id',$id)->setDec('quantity',$reduce_stock_value);
                }
                return ajaxReturn(200,'修改成功',url('index'));
            }else{
                return ajaxReturn(0,'修改失败');
            }
        }
        return $this->fetch();

    }

    /**
     * 核销卡券
     * 本地状态$status 0=>已删除 1=>正常 2=>已核销 3=>已过期 4=>转赠中 5=>转赠超时 6=>已失效 7=>卡券未被添加或未被领取
     */
    public function closure(){
        $code = input('post.code');
        if($code){
            //查询数据库验证code
            $is_have = Db::name('xzy_membercard')->where('code',$code)->find();
            if(!$is_have){
                return ajaxReturn(0,'该code码输入有误');
            }
            //先查询Code接口
            $weObj = new Wechat(get_weichat_options());
            $info = $weObj->checkCardCode($code);
            if($info['errcode']==0){
                if($info['can_consume'] == true){
                    if($info['user_card_status'] == 'NORMAL'){//正常
                        $res = $weObj->consumeCardCode($code);
                        if($res['errcode']==0){
                            //微信服务器核销成功，修改本服务器数据状态
                            $map = [
                                'openid'=>$res['openid'],
                                'code'=>$code,
                                'card_id'=>$res['card_id']
                            ];
                            Db::name('xzy_membercard')->where($map)->setField('status',2);
                            /****是否发送模板消息，后期根据需求修改*****/
                            return ajaxReturn(200,'操作成功');
                        }else{
                            return ajaxReturn(0,'核销失败');
                        }

                    }elseif($info['user_card_status'] == 'CONSUMED'){//已核销
                        Db::name('xzy_membercard')->where(['openid'=>$info['openid'],'card_id'=>$info['card_id'],'code'=>$code])->setField('status',2);
                        return ajaxReturn(0,'卡券已被核销');
                    }elseif($info['user_card_status'] == 'EXPIRE'){//已过期
                        Db::name('xzy_membercard')->where(['openid'=>$info['openid'],'card_id'=>$info['card_id'],'code'=>$code])->setField('status',3);
                        return ajaxReturn(0,'卡券已过期');
                    }elseif($info['user_card_status'] == 'GIFTING'){//转赠中
                        Db::name('xzy_membercard')->where(['openid'=>$info['openid'],'card_id'=>$info['card_id'],'code'=>$code])->setField('status',4);
                        return ajaxReturn(0,'转赠中，不能核销');
                    }elseif($info['user_card_status'] == 'GIFT_TIMEOUT'){//转赠超时
                        Db::name('xzy_membercard')->where(['openid'=>$info['openid'],'card_id'=>$info['card_id'],'code'=>$code])->setField('status',5);
                        return ajaxReturn(0,'转赠超时，不能核销');
                    }elseif($info['user_card_status'] == 'DELETE'){//已删除
                        Db::name('xzy_membercard')->where(['openid'=>$info['openid'],'card_id'=>$info['card_id'],'code'=>$code])->setField('status',0);
                        return ajaxReturn(0,'该卡券已删除');
                    }elseif($info['user_card_status'] == 'UNAVAILABLE'){//已失效
                        Db::name('xzy_membercard')->where(['openid'=>$info['openid'],'card_id'=>$info['card_id'],'code'=>$code])->setField('status',6);
                        return ajaxReturn(0,'该卡券已失效');
                    }else{//code未被添加或被转赠领取的情况则统一报错：invalid serial code
                        Db::name('xzy_membercard')->where(['openid'=>$info['openid'],'card_id'=>$info['card_id'],'code'=>$code])->setField('status',7);
                        return ajaxReturn(0,'该卡券未被领取');
                    }
                }else{//卡券不可核销
                    return ajaxReturn(0,'卡券不可核销');
                }
            }else{
                return ajaxReturn(0,'网络错误');
            }
        }else{
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
        $cardinfo = Db::name('xzy_coupon')->field('card_id')->where('id',$id)->find();
        $weObj=new Wechat(get_weichat_options());
        $result = $weObj->delCard($cardinfo['card_id']);
        if(model('XzyCoupon')->del($id)){
            //删除卡券
            return ajaxReturn(200,'删除成功！');
        }else{
            return ajaxReturn(0,'删除失败');
        }
    }
}