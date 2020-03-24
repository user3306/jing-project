<?php
namespace app\admin\service;
use think\facade\Log;

/**
 * 后台菜单接口
 */
class Menu{
	/**
	 * 获取菜单结构
	 */
	public function getAdminMenu(){
        //获取表单列表
        /*
         * author：丁志强
         * 此处注释掉，表单菜单固定为30，不便于以后扩展
         * date：2017-12-1
         *
        $formList = model('kbcms/FieldsetForm')->loadList();
        $formMenu = array();
        if(!empty($formList)){
            foreach ($formList as $key => $value) {
                $formMenu[] = array(
                    'id'=>'30'.$key+1,
                    'pid'=>30,
                    'url' => url('kbcms/AdminFormData/index',array('fieldset_id'=>$value['fieldset_id'])),
                    'name' => $value['name'],
                    'iconfont' => '&#xe62a;'
                );
            }
        }
        */
        $data=getMenuList();//获取所有菜单
        /*
        if ($data){
            foreach ($data as $key=>$val){
                if ($val['id']=='30'){
                    $data[$key]['sub']=$formMenu;
                }
            }
        }
        */
        return $data;
	}
}
