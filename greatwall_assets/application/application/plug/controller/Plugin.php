<?php
namespace app\plug\controller;
use app\admin\controller\Admin;
/**
 * 后台插件
 */
class Plugin extends Admin {
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '插件管理',
                'description' => '管理网站后台插件',
            ),
            'menu' => array(
                array(
                    'name' => '插件列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
            ),
        );
    }

    /**
     * 列表
     */
    public function index(){
        $plugin_list = model('plugin')->allList();
        $plugin_list = group_same_key($plugin_list,'type');
        $this->assign('payment',$plugin_list['payment']);
        return $this->fetch();
    }
    /*
     * 插件信息配置
     */
    public function setting(){
        $condition['type'] = input('type');
        $condition['code'] = input('code');
        if(input('post.')){
            $config = $_POST['config'];
            //空格过滤
            $config = trim_array_element($config);
            if($config){
                $config = serialize($config);
            }
            $row = model('Plugin')->edit(array('config_value'=>$config),$condition);
            if($row!==false){
                return ajaxReturn(200,'操作成功');
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $row = model('plugin')->getWhereInfo($condition);
            if(!$row){
                return $this->error("不存在该插件");
            }
            $row['config'] = unserialize($row['config']);
            $this->assign('plugin',$row);
            $this->assign('config_value',unserialize($row['config_value']));
            return $this->fetch();
        }
    }
}

