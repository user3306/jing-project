<?php
namespace app\admin\controller;

use org\weixin\Wechat;
use think\Db;
use think\facade\Cache;

class Index extends Admin{
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info' => array(
                'name' => '管理首页',
                'description' => '站点运行信息',
            ),
            'menu' => array(
                array(
                    'name' => '首页',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
            ),
        );
    }
    public function index(){
        $this->assign('langList',model('Lang')->loadList());
        $this->assign('loginUserInfo',$this->loginUserInfo);
        return $this->fetch();
    }
	
	public function test(){
		return $this->fetch();
	}
    //控制台
    public function home(){
        //收藏
        $collectNum = model('Collected')->getCollectNum();
        $nocollectNum = model('Collected')->getNoCollectNum();
        $total = $collectNum+$nocollectNum;
        $info = [];
        $info[0]['name'] = '收藏';
        $info[1]['name'] = '未收藏';
        if ($total != 0) {
            $info[0]['value']= round($collectNum/$total,2);
            $info[1]['value'] =  round($nocollectNum/$total,2);
        }else{
            $info[0]['value']= 0;
            $info[1]['value'] = 0;
        }
    
        $this->assign('collectname',$info);


        //评论
        $discussNum = model('Collected')->getDiscussNum();
        $nodiscussNum = model('Collected')->getNoDiscussNum();
        $total = $discussNum+$nodiscussNum;
        $info = [];
        $info[0]['name'] = '已评论';
        $info[1]['name'] = '未评论';
        if ($total != 0) {
            $info[0]['value']= round($discussNum/$total,2);
            $info[1]['value'] =  round($nodiscussNum/$total,2);
        }else{
            $info[0]['value']= 0;
            $info[1]['value'] = 0;
        }
    
        $this->assign('discussname',$info);


        //资产类目
        $areaNum = model('Collected')->getTypeData(1); 
        $where = [];
        $str = "";
        $areastr = "";
        //数组统计
        if (!empty($areaNum)) {

            $pid = implode(',', array_column($areaNum, 'id'));
            $where[] = ['id','in',$pid];
            $res = model('Collected')->gettypename($where);
         
            //$areaname = implode(',',array_column($res,'assettype','id'));
            
            // foreach ($areaNum as $key => $value) {
            //     if (in_array($value['pid'], array_column($res, 'id'))) {
            //         $areaNum[$key]['areaname'] = $areaname[$value['pid']];
            //         $str .= '{name:\"'. $areaname[$value['pid']].'\",value:'.$value['num'].'}';

            //         $areastr .= $areaname[$value['pid']];
            //     }
            // }
            $areastr .= implode("','",array_column($res,'assettype','id'));
            $str .= implode(',', array_column($areaNum,'num'));
            
        }

        $str .= "";
        $areastr .= "";
        
        $this->assign('str',$str);
        $this->assign('areastr',$areastr);

        //押品类型
        $productNum = model('Collected')->getTypeData(2);

        $where = [];
        $pstr = "";
        $productstr = '"';
        //数组统计
        if (!empty($productNum)) {

            $pid = implode(',', array_column($productNum, 'id'));
            $where[] = ['id','in',$pid];
            $res = model('Collected')->gettypename($where);
                
            //$areaname = implode(',',array_column($res,'assettype','id'));
            
            // foreach ($areaNum as $key => $value) {
            //     if (in_array($value['pid'], array_column($res, 'id'))) {
            //         $areaNum[$key]['areaname'] = $areaname[$value['pid']];
            //         $str .= '{name:\"'. $areaname[$value['pid']].'\",value:'.$value['num'].'}';

            //         $areastr .= $areaname[$value['pid']];
            //     }
            // }
            $productstr .= implode('","',array_column($res,'assettype','id'));
            $pstr .= implode(',', array_column($productNum,'num'));
            
        }

        $pstr .= "";
        $productstr .= '"';
    
        $this->assign('pstr',$pstr);
        $this->assign('productstr',$productstr);


        //规模数据统计
        $scopeNum = model('Collected')->getTypeData(3);

        $where = [];
        $sstr = "";
        $scopestr = '"';
        //数组统计
        if (!empty($scopeNum)) {

            $pid = implode(',', array_column($scopeNum, 'id'));
            $where[] = ['id','in',$pid];
            $res = model('Collected')->gettypename($where);
                
            //$areaname = implode(',',array_column($res,'assettype','id'));
            
            // foreach ($areaNum as $key => $value) {
            //     if (in_array($value['pid'], array_column($res, 'id'))) {
            //         $areaNum[$key]['areaname'] = $areaname[$value['pid']];
            //         $str .= '{name:\"'. $areaname[$value['pid']].'\",value:'.$value['num'].'}';

            //         $areastr .= $areaname[$value['pid']];
            //     }
            // }
            $scopestr .= implode('","',array_column($res,'assettype','id'));
            $sstr .= implode(',', array_column($scopeNum,'num'));
            
        }

        $sstr .= "";
        $scopestr .= '"';
    
        $this->assign('sstr',$sstr);
        $this->assign('scopestr',$scopestr);

        //行业规模统计
        $tradeNum = model('Collected')->getTypeData(4);

        $where = [];
        $tstr = "";
        $tradestr = '"';
        //数组统计
        if (!empty($tradeNum)) {

            $pid = implode(',', array_column($tradeNum, 'id'));
            $where[] = ['id','in',$pid];
            $res = model('Collected')->gettypename($where);
                
            //$areaname = implode(',',array_column($res,'assettype','id'));
            
            // foreach ($areaNum as $key => $value) {
            //     if (in_array($value['pid'], array_column($res, 'id'))) {
            //         $areaNum[$key]['areaname'] = $areaname[$value['pid']];
            //         $str .= '{name:\"'. $areaname[$value['pid']].'\",value:'.$value['num'].'}';

            //         $areastr .= $areaname[$value['pid']];
            //     }
            // }
            $tradestr .= implode('","',array_column($res,'assettype','id'));
            $tstr .= implode(',', array_column($tradeNum,'num'));
            
        }

        $tstr .= "";
        $tradestr .= '"';
    
        $this->assign('tstr',$tstr);
        $this->assign('tradestr',$tradestr);
        return $this->fetch();
    }
    //后台菜单
    public function menu(){
        $list = model('admin/menu')->menuLoadlist();
        $this->assign('list',$list);
        return $this->fetch();
    }
}
