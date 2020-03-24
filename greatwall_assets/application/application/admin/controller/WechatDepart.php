<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2017-11-30
 * Time: 16:35
 */

namespace app\admin\controller;


use think\facade\Cache;
use com\snrunning\Wechat;
use com\snrunning\ErrCode;
use think\facade\Log;

class WechatDepart extends Admin
{
    /**
     * 当前模块参数
     * @return array
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '测试',
                'description' => '微信部门',
            ),
            'menu' => array(
                array(
                    'name' => '部门管理',
                    'url' => url('departlist'),
                    'icon' => 'list',
                ),array(
                    'name' => '部门搜索',
                    'url' => url('departsearch'),
                    'icon' => 'list',
                ),
            ),
            '_info' => array(
                array(
                    'name' => '添加部门',
                    'url' => url('info'),
                ),
            ),
        );
    }

    public function index()
    {
        if(Cache::has('WechatDepart'))
            $departStr=Cache::get('WechatDepart');
        else
        {
            $model = model('admin/TxlAccessToken','service');
            $token = $model->token();
            $url = "https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token=".$token;
            $res = json_decode(http_get($url)['content']);
            $departStr = json_encode($res->department);
            Cache::set('WechatDepart',$departStr,604800);
            //dump($departStr);
        }
        $this->assign('departStr',$departStr);
        return $this->fetch();
    }

    /**
     * 部门列表
     * @return mixed
     */
    public function departlist()
    {
        $stype = input('get.stype');
        $key = input('get.key');
        //如果有传的值，显示传输的值，如果没有，就显示默认的50条记录
        $where = array();

        if(!empty($stype)&&$stype==1){
            $where['departid'] = ['=',$key];
        }
        if(!empty($stype)&&$stype==2){
            //$key = iconv('GBK','UTF-8',$key);
            $where['depart'] = ['like',"%$key%"];
        }
        //dump($where);
        //查询数据
        $limit=15;
        $list = model('WechatDepart')->loadList($where,$limit);
        //位置导航
        //模板传值
        $this->assign('datalist',$list);
        $this->assign('_page',$list->render());
        return $this->fetch();
    }

    /**
     * 部门搜索
     * @return mixed|\think\response\Json
     */
    public function departsearch()
    {
        if (input('post.')) {
            $stype = input('stype');
            $key = input('key');
            //url('admin/wechat_depart/departlist',['stype'=>$stype,'key'=>$key])
            return ajaxReturn(200,'搜索中','departlist.html?stype='.$stype.'&key='.$key);
        }
        else {
            return $this->fetch();
        }
    }

    public function info()
    {
        $wxid = input('wxid');
        $model = model('WechatDepart');
        //dump(Cache::get('All_departinfo_Data'));
        //先找出最大的wxid和wxorder
        $maxid = $model->getMaxid('wxid')+1;
        $maxorder = $model->getMaxid('wxorder')+1;

        $departtype = array(array('val'=>'1','type'=>'网点'),array('val'=>'9','type'=>'管辖行'));

        if (input('post.')) {
            //Log::error(input('post.'));
            if($wxid == 1){
                return ajaxReturn(0,'不可修改');
            }

            $mtoken = model('admin/TxlAccessToken','service');
            $token = $mtoken->token();
            $options = array(
                'token'=>$token
            );
            $wechat = new Wechat($options);

            //先修改网上的信息
            if (input('post.action')=='edit'){
                //更新微信端信息
                $data = array(
                    "name"=>input('post.depart'),
                    "parentid"=>input('post.wxparentid'),
                    "order"=>input('post.wxorder'),
                    "id"=>input('post.wxid')
                );
                $result = $wechat->updateDepartment($data);
                if($result===false)
                {
                    return ajaxReturn(0,'微信端更新失败！'.ErrCode::getErrText($wechat->errCode));
                }
                $status=$model->edit();
            }else{
                //找出最大的wxid和wxorder，然后+1
                $data = array(
                    "name"=>input('post.depart'),
                    "parentid"=>input('post.wxparentid'),
                    "order"=>input('post.wxorder'),
                    "id"=>input('post.wxid')
                );
                Log::error($data);
                $result = $wechat->createDepartment($data);
                if($result===false)
                {
                    return ajaxReturn(0,'微信端增加失败！'.ErrCode::getErrText($wechat->errCode));
                }
                $status=$model->add();
            }
            if($status!==false){
                //删除部门缓存
                Cache::rm('All_departinfo_Data');
                return ajaxReturn(200,'操作成功',url('departlist'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }
        else {
            $data = $model->getInfo($wxid);
            $pdata = $wxid?$model->getInfo($data->wxparentid):array();
            $plist = $model->limitlist([], 10000);
            $this->assign('plist', $plist);
            $this->assign('departtype', $departtype);
            $this->assign('pdepart', $pdata);
            $this->assign('depart', $data);
            $this->assign('maxid', $maxid);
            $this->assign('maxorder', $maxorder);
            $action = $wxid?'edit':'add';
            $this->assign('action', $action);
            return $this->fetch();
        }
    }

    /**
     * 删除
     */
    public function del(){
        $wxid = input('wxid');
        if(empty($wxid)){
            return ajaxReturn(0,'参数不能为空');
        }
        if($wxid == 1){
            return ajaxReturn(0,'根部门无法删除');
        }
        $mtoken = model('admin/TxlAccessToken','service');
        //$token = $mtoken->token();
        $options = array(
            'token'=>$mtoken->token()
        );
        $wechat = new Wechat($options);
        $result = $wechat->deleteDepartment($wxid);
        if($result===false) {
            return ajaxReturn(0,'微信端删除部门出错！'.ErrCode::getErrText($wechat->errCode));
        }
        if(model('WechatDepart')->del($wxid)){
            Cache::rm('All_departinfo_Data');
            return ajaxReturn(200,'部门删除成功！');
        }else{
            return ajaxReturn(0,'本地部门数据删除失败');
        }
    }

}