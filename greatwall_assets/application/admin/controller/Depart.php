<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2018/4/22
 * Time: 11:10
 */

namespace app\admin\controller;


class Depart extends Admin
{
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '事业部管理',
                'description' => '事业部管理',
            ),
            'menu' => array(
                array(
                    'name' => '事业部管理',
                    'url' => url('index'),
                    'icon' => 'list',
                ),
            ),
            '_info' => array(
                array(
                    'name' => '添加事业部',
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
        $limit=0;
        $list = model('XzyDepart')->loadList($where,$limit);
        //位置导航
        $breadCrumb = array('事业部管理'=>url());
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
        $model = model('XzyDepart');
        if (input('post.')){
            if (input('post.id')){
                $status=$model->edit();
            }else{
                $status=$model->add();
            }
            if($status!==false){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            if($id){
                $this->assign('info', $model->getInfo($id));
            }else{
                $this->assign('info', array('status'=>1));
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
        if(model('XzyShop')->del($id)){
            return ajaxReturn(200,'删除成功！');
        }else{
            return ajaxReturn(0,'删除失败');
        }
    }
}