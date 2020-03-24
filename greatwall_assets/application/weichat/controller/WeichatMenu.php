<?php
namespace app\weichat\controller;
use app\admin\controller\Admin;
use org\weixin\Wechat;
use think\Db;

/**
 * 后台公众号菜单
 */
class WeichatMenu extends Admin {
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '公众号菜单管理',
                'description' => '管理网站后台管理员',
                ),
            'menu' => array(
                    array(
                        'name' => '公众号菜单列表',
                        'url' => url('index'),
                        'icon' => 'list',
                    ),
                ),
            '_info' => array(
                    array(
                        'name' => '添加公众号菜单',
                        'url' => url('info'),
                    ),
                    array(
                        'name' => '同步菜单',
                        'url' => url('createmenu'),
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
        $list = model('WeichatMenu')->loadList();
        //模板传值
        $this->assign('list',$list);
        $this->assign('type_arr',array('1'=>'关键词回复','2'=>'页面跳转'));
        return $this->fetch();
    }
    /**
     * 详情
     */
    public function info(){
        $weichatId = input('menu_id');
        $model = model('WeichatMenu');
        if (input('post.')){
            /*
            $check=$this->wxMenuCheck();
            if ($check!==true){
                return ajaxReturn(0,$check);
            }
            */
            if ($weichatId){
                /*
                $check_status=$this->parentWxMenuCheck();
                if ($check_status!==true){
                    return ajaxReturn(0,$check_status);
                }
                */
                $status=$model->edit();
            }else{
                /*
                $check_status=$this->parentWxMenuThreeCheck();
                if ($check_status!==true){
                    return ajaxReturn(0,$check_status);
                }
                */
                $status=$model->add();
            }
            if($status!==false){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $this->assign('info', $model->getInfo($weichatId));
            $this->assign('menuList',$model->loadList());//菜单
            return $this->fetch();
        }
    }

    /**
     * 删除
     */
    public function del(){
        $weichatId = input('id');
        if(empty($weichatId)){
            return ajaxReturn(0,'参数不能为空');
        }
        if($weichatId == 1){
            return ajaxReturn(0,'保留公众号菜单无法删除');
        }
        if(model('WeichatMenu')->del($weichatId)){
            return ajaxReturn(200,'公众号菜单删除成功！');
        }else{
            return ajaxReturn(0,'公众号菜单删除失败');
        }
    }
	


    /**
     * 创建菜单
     * @return \think\response\Json
     */
    public function createmenu()
    {
        $button = array();
        $topMenu = model('weichat_menu')->where([['parent_id','=',0]])->limit(3)->order('sort','asc')->select();
        foreach ($topMenu as $key => $vo) {
            $button[$key] = array('name' => $vo['name']);
            $itemMenu = model('weichat_menu')->where([['parent_id','=',$vo['menu_id']]])->order('sort','asc')->limit(5)->select();
            //拼接子菜单
            if (count($itemMenu)) {
                foreach ($itemMenu as $voo) {
                    if($voo['type']=='view'){

                        if(substr($voo['key'],0,24) == 'https://mp.weixin.qq.com'){
                            $button[$key]['sub_button'][] = array(
                                'type' => $voo['type'],
                                'name' => $voo['name'],
                                'url' => $voo['key'],
                            );
                        }else{
                            $button[$key]['sub_button'][] = array(
                                'type' => $voo['type'],
                                'name' => $voo['name'],
                                'url' => "http://unicom.wx.snrunning.cn/weichat/index/redirecturl.html?nurl=".urlencode($voo['key']).""
                            );
                        }


                    }else if($voo['type']=='miniprogram'){
                        $button[$key]['sub_button'][] = array(
                            'type' => $voo['type'],
                            'name' => $voo['name'],
                            'url' => "http://mp.weixin.qq.com",
                            'appid'=>$voo['appid'],
                            'pagepath'=>$voo['pagepath']
                        );

                    }else{
                        $button[$key]['sub_button'][] = array(
                            'type' => $voo['type'],
                            'name' => $voo['name'],
                            'key' => $voo['key'],
                            'sub_button'=> [ ]
                        );
                    }

                }
            } else {
                if($vo['type']=='view') {
                    $button[$key]['type'] = $vo['type'];
                    $button[$key]['url'] = $vo['key'];
                }else{
                    $button[$key]['type'] = $vo['type'];
                    $button[$key]['key'] = $vo['key'];
                }
            }
        }

        $data = compact('button');
        $wxObj=new Wechat(get_weichat_options());
        //dump($data);
        $wxObj->deleteMenu();
        $result = $wxObj->createMenu($data);

        if($result){
            return ajaxReturn(200,'公众号菜单已成功更新！');
        }else{
            return ajaxReturn(0,'公众号菜单更新失败，错误信息：'.$wxObj->errMsg);
        }
    }

    public function sort()
    {
        $weichatId = input('id_value');

        $result = model('weichat_menu')->where('menu_id', $weichatId)->update(['sort' => input('field_value')]);
        if($result){
            return ajaxReturn(200,'ok！');
        }else{
            return ajaxReturn(0,'error');
        }
    }

    /**
     * 微信菜单点击次数统计
     */
    public function menuhits(){
        //查找当日菜单点击量
        $starttime = input('starttime');
        $endtime = input('endtime');
        if(empty($starttime) && empty($endtime)){
            $starttime = date('Y-m-d',time());
            $endtime = date('Y-m-d',time());
        }

        $list = Db::table('rn_weichat_menuhits')
            ->alias('a')
            ->field('sum(a.hits) as total,ANY_VALUE(a.menu_key) as menu_key,ANY_VALUE(b.name) as name')
            ->join('rn_weichat_menu b','a.menu_key=b.key')
            ->where('a.createtime','between',[$starttime,$endtime])
            ->group('a.menu_key')
            ->select();


//        $sql = "SELECT sum(a.hits) as total,a.menu_key,a.hits,b.key,b.`name` from rn_weichat_menuhits a JOIN rn_weichat_menu b ON a.menu_key=b.`key` GROUP BY a.menu_key";
        //mysql 5.7 写法 ANY_VALUE()
//        $sql = "SELECT sum(a.hits) as total,ANY_VALUE(a.menu_key) as menu_key,ANY_VALUE(b.`name`) as name from rn_weichat_menuhits a JOIN rn_weichat_menu b ON a.menu_key=b.`key` GROUP BY a.menu_key";


//        $list = Db::query($sql);

        $this->assign('list',$list);

        return $this->fetch();
    }


}

