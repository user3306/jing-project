<?php
/**
 * 数据统计功能
 * @auth:caizhuan
 * @date:20190422
 */
namespace app\admin\controller;
use app\admin\controller\Admin;
class CollectedList extends Admin{
	public function __controller(){
		parent::__controller();
	}
	/**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '数据统计管理',
                'description' => '管理网站的数据统计',
            ),
            'menu' => array(
                array(
                    'name' => '数据统计列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            // '_info' => array(
            //     array(
            //         'name' => '添加评论',
            //         'url' => url('info'),
            //         'icon' => 'plus',
            //     ),
            // )
        );
    }


	/**
	 * 数据统计列表
	 * @param  string $[username] [<description>]
	 * @param string $[mobile] [<description>]
	 * return 
	 */
	public function index(){

        $list = model('Collected')->getCollectedList(20);

        //位置导航
        $breadCrumb=array(array('name'=>'资产评论列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('_page',$list->render());
		return $this->fetch();
	}


    public function discuss(){

        //收藏
        $collectNum = model('Collected')->getCollectNum();
        $nocollectNum = model('Collected')->getNoCollectNum();
        $total = $collectNum+$nocollectNum;
        $info = [];
        $info[0]['name'] = '收藏';
        $info[1]['name'] = '被收藏';
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

            $pid = implode(',', array_column($areaNum, 'pid'));
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

            $pid = implode(',', array_column($productNum, 'pid'));
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

            $pid = implode(',', array_column($scopeNum, 'pid'));
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

            $pid = implode(',', array_column($tradeNum, 'pid'));
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


    /**
     * 查看详情
     * @param intval $[id] [<description>]
     * return false
     */
    public function info(){
        $info = model('Collected')->getCollectedDetail(input('id'),20);
        $this->assign('list',$info);
        return $this->fetch();
    }
}
?>