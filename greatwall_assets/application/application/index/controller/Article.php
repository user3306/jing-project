<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/19/019
 * Time: 16:52
 */

namespace app\index\controller;
use app\index\model\ArticleModel;
use think\Controller;
use think\Db;
use think\Request;
class Article extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function comm($ret,$msg,$data=''){
        $api = controller('Apismall');
        return $api->return_info($ret=$ret,$msg=$msg,$data=$data);
    }
    //获取20条精品信息
    public function articlelist(){
        /*$result = ArticleModel::where('competitive',1)
                              ->field(array('id','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','asset_childclass'))
                              ->limit(20)
                              ->select();*/


        $data = array();

        $result = Db::name('assetinfo2019')
                    ->alias('i')
                    ->join('asset_type2019 t ',' i.asset_childclass = t.id')
                    ->field(array('i.id as aid','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','t.pname as bigassettype','asset_childclass','t.assettype as childassettype','asset_danbaomethod' ))
                    ->where(['competitive'=>1,'isshelves'=>0,'status'=>1])
                    ->group('asset_name')
					->limit(20)
                    ->select();
        $data['boutique'] = $result;
        //首页轮播图
        $wheel_planting = Db::name('assetinfo2019')->where(['defaultasset'=>1,'competitive'=>1])->field(['id','asset_name','pic'])->limit(5)->order('id desc')->select();

        $data['wheel_planting'] = $wheel_planting;

        //dump($result);
        return  self::comm(1,'',$data);
    }

    public function wheel_planting_detaile(){
        $datas = $this->request->post();
        $aid = isset($datas['id']) ? $datas['id'] : '';
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token = isset($datas['token']) ? $datas['token'] : '';
        if(empty($aid) || empty($cli_openid)){
            echo self::comm(2,'必要参数缺失！');
        }
        $is_collection = 0;//是否收藏 0未收藏，1已收藏

        //根据openid及aid判断用户是否收藏
        $collect = Db::name('collectioninfo2019')->where(array('info_id'=>$aid,'cli_openid'=>$cli_openid))->find();
        if(!empty($collect)){
            $is_collection = 1;
        }
        $result = Db::name('assetinfo2019')->where('id',$aid)->field(['id','asset_name','asset_managerline','desc'])->find();
        $result['is_collection'] = $is_collection;
        return  self::comm(1,'',$result);
    }
    //资产信息详细信息
    public function articledetail(){
        $datas =     $this->request->post();
        /* $aid= 5;
        $cli_openid = 'oa-UZ0SU789io-iFt20c1R-aqRt4'; */
        $aid = isset($datas['aid']) ? $datas['aid'] : ''; //资产信息id
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : ''; //小程序openid
        $is_collection = 0;//是否收藏 0未收藏，1已收藏
        if(empty($aid)||empty($cli_openid)){
            return   self::comm(2,'必要参数缺失！');
            die();
        }

        $result = array();
        $usertype = self::getusertype($cli_openid);
        $data = ['cli_openid' => $cli_openid, 'info_id' => $aid, 'browse_time' => date('Y-m-d H:i:s'), 'browse_date' => date('Y-m-d'),'usertype'=>$usertype];
        $get_insert=Db::name('browse2019')->insert($data);
        if($get_insert == 1){
            Db::name('entryinfo2019')->where('cli_openid',$cli_openid)->update(['viewNum'=>Db::raw('viewNum+1')]);
        }
        $detail = Db::name('assetinfo2019')
            ->alias('b')
            ->leftJoin('asset_type2019 h ',' b.asset_quxian = h.id')
            ->leftJoin('asset_type2019 g ',' b.asset_trade = g.id')
            ->leftJoin('asset_type2019 i ',' b.asset_childclass = i.id')
            ->field(array('asset_name','g.assettype as asset_trade','h.pname as asset_dishi','h.assettype as asset_quxian','asset_danbaomethod','asset_basemoney','asset_getmoney','asset_danbaoperson' ,'collection_number','discussNum','asset_manageruserid','asset_managerline','i.pname as asset_bigclass','i.assettype as asset_childclass','intr'))
            ->where('b.id',$aid)
            ->find();

        $detailist = Db::name('assetinfo2019')
                ->alias('a')
                ->leftJoin('asset_type2019 d', '  a.asset_leavediquxian = d.id')
                ->leftJoin('asset_type2019 l', ' a.asset_childclass = l.id')
                ->field(['l.pname as asset_bigclass','l.assettype as asset_childclass','asset_diyanperson','d.pname as asset_leavedishi','d.assettype as asset_leavediquxian','asset_diyanumber','intr','desc'])
                ->where('asset_name',$detail['asset_name'])
                ->select();



        $is_ascription = 0; //代表客户或者不归属自己管辖
        if($usertype == 1){
            $personal = controller('Personal');
            $userid =  $personal->getUserid($cli_openid);
            if($userid == $detail['asset_manageruserid']){
                $is_ascription = 1;// 表示该资产归属与自己  展示收藏和评论的数量
            }
        }

        //根据openid及aid判断用户是否收藏
        $collect = Db::name('collectioninfo2019')->where(array('info_id'=>$aid,'cli_openid'=>$cli_openid))->find();
        if(!empty($collect)){
            $is_collection = 1;
        }
        $detail['usertype'] = $usertype;
        $detail['is_ascription'] = $is_ascription;
        $detail['is_collection'] = $is_collection;

        $result['detail'] = $detail;
        $result['detailist'] = $detailist;

        return  self::comm(1,'',$result);
    }

    //信息收藏表
    public function collectionasset(){
        $datas=$this->request->post();

        $is_coll = isset($datas['is_coll']) ? $datas['is_coll'] : 0;
        $aid=isset($datas['aid']) ? $datas['aid'] : '';
        $cli_openid=isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $succ = '';
        $usertype = self::getusertype($cli_openid);
        if(empty($aid)||empty($cli_openid) ){
            echo self::comm(2,'必要参数缺失！');
            die();
        }
        /*$info=Db::table('changcheng_collectioninfo2019')->where('cli_openid',$cli_openid)->where('info_id',$aid)->find();
        if(is_array($info)){
            echo $this->return_info(3,'您已经收藏过了');
        }*/
        //根据资产id（$aid）查询资产名称
        $assetname = ArticleModel::where('id',$aid)->field('asset_name')->find();
        $assetlist = ArticleModel::where([['asset_name','like',$assetname['asset_name']]])->field('id')->select()->toArray();
        if($is_coll == 1){
            //收藏操作
            foreach ($assetlist as $key=>$val){
                $data = ['cli_openid' => $cli_openid, 'info_id' => $val['id'], 'collect_time' => date('Y-m-d H:i:s'), 'collet_date' => date('Y-m-d'),'usertype'=>$usertype];
                $get_insert = Db::name('collectioninfo2019')->insert($data);
                if($get_insert==1){
                    Db::name('assetinfo2019')->where('id',$val['id'])->update(['collection_number'=>Db::raw('collection_number+1')]);
                    Db::name('entryinfo2019')->where('cli_openid',$cli_openid)->update(['collectNum'=>Db::raw('collectNum+1')]);
                    $succ = 1;
                }else{
                    $succ = 0;
                }
            }
            if($succ==1){
                echo self::comm(1,'收藏成功');
            }else{
                echo self::comm(2,'收藏失败，请稍后重试！');
            }

            /*$data = ['cli_openid' => $cli_openid, 'info_id' => $aid, 'collect_time' => date('Y-m-d H:i:s'), 'collet_date' => date('Y-m-d'),'usertype'=>$usertype];
            $get_insert=Db::name('collectioninfo2019')->insert($data);
            if($get_insert==1){
                Db::name('assetinfo2019')->where('id',$aid)->update(['collection_number'=>Db::raw('collection_number+1')]);
                Db::name('entryinfo2019')->where('cli_openid',$cli_openid)->update(['collectNum'=>Db::raw('collectNum+1')]);
                echo self::comm(1,'收藏成功');
            }else{
                echo self::comm(2,'收藏失败，请稍后重试！');
            }*/
        }else{
            //取消收藏操作
            foreach ($assetlist as $key=>$val){
                $del = Db::name('collectioninfo2019')->where(array('info_id'=>$val['id'],'cli_openid'=>$cli_openid))->delete();
                Db::name('assetinfo2019')->where('id',$val['id'])->update(['collection_number'=>Db::raw('collection_number-1')]);
                Db::name('entryinfo2019')->where('cli_openid',$cli_openid)->update(['collectNum'=>Db::raw('collectNum-1')]);
                if($del == 1){
                    $succ = 1;
                }else{
                    $succ = 0;
                }
            }
            if($succ==1){
                echo self::comm(1,'取消成功');
            }else{
                echo self::comm(2,'取消失败，请稍后重试！');
            }
           /* $del = Db::name('collectioninfo2019')->where(array('info_id'=>$aid,'cli_openid'=>$cli_openid))->delete();
            Db::name('assetinfo2019')->where('id',$aid)->update(['collection_number'=>Db::raw('collection_number-1')]);
            Db::name('entryinfo2019')->where('cli_openid',$cli_openid)->update(['collectNum'=>Db::raw('collectNum-1')]);
            if($del == 1){

                echo self::comm(1,'取消成功');
            }else{
                echo self::comm(2,'取消失败，请稍后重试！');
            }*/
        }
    }

    //评论
    public function comment(){
        $datas=$this->request->post();
        $aid=isset($datas['aid']) ? $datas['aid'] : '';
        $cli_openid=isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $content=isset($datas['content']) ? $datas['content'] : '';
        if(empty($aid) ||empty($cli_openid) || empty($content)){
            echo self::comm(2,'必要参数缺失！');
            die();
        }

        //根据aid查询资产归属人userid

        $userinfo = ArticleModel::where('id',$aid)->field('asset_manageruserid')->find();
        if(!empty($userinfo)){
            $userid = $userinfo['asset_manageruserid'];
        }
        $data = array('info_id'=>$aid,'cli_openid'=>$cli_openid,'comment_content'=>$content,'comment_time'=>time(),'asset_userid'=>$userid);
        $result = Db::name('comment2019')->insert($data);
        if($result ==1 ){
            Db::name('assetinfo2019')->where('id',$aid)->update(['discussNum'=>Db::raw('discussNum+1')]);
            echo self::comm(1,'评论成功');
        }else{
            echo self::comm(1,'评论失败，请稍后重试！');
        }
    }
    public function getusertype($cli_openid){
        $info = Db::name('entryinfo2019')->where('cli_openid',$cli_openid)->field('usertype')->find();
        if(!empty($info)){
            $usertype = $info['usertype'];
        }else{
            $usertype = 2;
        }
        return $usertype;
    }

}