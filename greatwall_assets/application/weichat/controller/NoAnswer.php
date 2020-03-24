<?php
namespace app\weichat\controller;
use app\admin\controller\Admin;
/**
 * 未识别回复设置
 */

class NoAnswer extends Admin {

    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '未识别回复设置',
                'description' => '设置未识别回复整体功能',
                ),
            'menu' => array(
                    array(
                        'name' => '未识别回复',
                        'url' => url('NoAnswer/index'),
                        'icon' => 'exclamation-circle',
                    )
                )
        );
    }
	/**
     * 站点设置
     */
    public function index(){
        if (input('post.')){
            $where[]=['weichat_id','eq',get_weichat_id()];
            $rs=\think\Db::name('weichat_reply_config')->where($where)->update(input('post.'));
            if($rs!==false){
                return ajaxReturn(200,'操作成功！');
            }else{
                return ajaxReturn(0,'操作失败！');
            }
        }else{
            $where[]=['weichat_id','eq',get_weichat_id()];
            $info=\think\Db::name('weichat_reply_config')->where($where)->find();
            if (empty($info)){
                $data['weichat_id']=get_weichat_id();
                \think\Db::name('weichat_reply_config')->insert($data);
                return $this->redirect('');
            }
            $this->assign('info',$info);
            return $this->fetch();
        }
    }



}

