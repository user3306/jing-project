<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/23/023
 * Time: 9:18
 */

namespace app\index\controller;
use app\index\model\ArticleModel;
use app\index\model\BindingModel;
use app\index\model\UserModel;
use app\index\model\CommentModel;
use app\index\controller\Apismall;
use app\index\model\AssettypeModel;
use org\weixin\Wechat;
use function rdbc\auth\controller\menu;
use think\Controller;
use think\Db;
use think\Request;
use app\index\controller\Article;

class Personal extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function comm($ret='',$msg='',$data=''){
//        $api = controller('Apismall');
        $api = new Apismall();
		
        //$api->return_info($ret=$ret,$msg=$msg,$data=$data);
        return $api->return_info($ret=$ret,$msg=$msg,$data=$data);
    }

    /*******************************************************************个人中心***********************************************************************************/

//    判断用户身份
    public function  user_identity(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }

        $jobtype = 3; //1 项目经理 2 员工 3 客户 4 管理层  5 部门负责人
        $groupadmin = Db::name('admin_group')->where('name','管理层')->field('group_id')->find();
        $groupdepart = Db::name('admin_group')->where('name','部门负责人')->field('group_id')->find();
        $groupmanager = Db::name('admin_group')->where('name','项目经理')->field('group_id')->find();
        $groupstaff = Db::name('admin_group')->where('name','员工')->field('group_id')->find();
//        根据openid查询用户身份
        $info =UserModel::where('openid',$cli_openid)->field(array('userid','mobile','job_info','job'))->find();
        if(!empty($info)){
            if($info['job'] == $groupadmin['group_id']){
                $jobtype = 4;
            }else if($info['job'] == $groupdepart['group_id']){
                $jobtype = 5;

            }else if($info['job'] == $groupmanager['group_id']){
                $jobtype = 1;

            }else if($info['job'] == $groupstaff['group_id']){
                $jobtype = 2;
            }
        }
        if($jobtype == 3){
            $data = self::customerinfo($cli_openid);
        }else{
            $data = self::userinfo($info['userid']);
        }
        $data['jobtype'] = $jobtype;
        
        return self::comm(1,'获取用户身份',$data);
    }

    //客户身份为员工身份或者项目经理
    public function userinfo($userid){
        $data = array();
        //当日
        $day = date('Y-m-d');
        //根据openid获取用户的相关信息
        //员工引流客户总数
        $allnum = BindingModel::where('userid',$userid)->count('id');
        $data['allnum'] = $allnum;
        //员工当日引流客户数
        $whereOr = array();
        $whereOr[] = ['bingding_date|bingding_date2','eq',$day];
        $daynum = BindingModel::where('userid',$userid)->where($whereOr)->count('id');
        $data['daynum'] = $daynum;
        return $data;
    }

    /*
     *身份为客户
     */
    public function customerinfo($cli_openid){
        $data = array();
        //根据openid判断用户是否绑定了信息
        $result = Db::name('entryinfo2019')
                    ->alias('e')
                    ->leftJoin('user_information2019 u',' e.cli_openid=u.openid')
                    ->where('cli_openid',$cli_openid)
                    ->field(array('is_perfect','avatarUrl'))
                    ->find();
        $data['is_perfect'] = $result['is_perfect'];
        $data['avatarUrl'] =  $result['avatarUrl'];
        return $data;
    }


    public function mycustomerinfo(){
        $datas = $this->request->post();
		$cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        $result = Db::name('entryinfo2019')
            ->alias('e')
            ->leftJoin('user_information2019 u',' e.cli_openid=u.openid')
            ->leftJoin( 'asset_type2019 a ', ' e.cli_asset_childclass=a.id')
            ->where('cli_openid',$cli_openid)
            ->field(array('is_perfect','avatarUrl','cli_name','cli_company','write_mobile','cli_provincial','cli_city','cli_county','cli_asset_childclass'))
            ->find();

        $city_arr = AssettypeModel::where('id','in',$result['cli_city'])->field('assettype')->select()->toArray();
        $asset_childclass_arr =  AssettypeModel::where('id','in',$result['cli_asset_childclass'])->field('assettype')->select()->toArray();
        $result['cli_city_name']  = null;
        $result['cli_asset_childclass_name'] = null;
        foreach ($city_arr as $key=>$val){
            $result['cli_city_name'] .= $val['assettype'].',';
        }
        foreach ($asset_childclass_arr as $key=>$val){
            $result['cli_asset_childclass_name'] .= $val['assettype'].',';
        }
        $result['cli_city_name']=substr($result['cli_city_name'],0,-1);
        $result['cli_asset_childclass_name']=substr($result['cli_asset_childclass_name'],0,-1);
        return self::comm(1,'查看个人信息',$result);
        //根据openid判断用户是否更新内容
    }

    /*
     *
     * 身份为客户
     * 收藏的资产列表
     */

    public function collention_asset(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
        //$cli_openid = 'oa-UZ0SU789io-iFt20c1R-aqRt4';
        $result = Db::name('collectioninfo2019')
            ->alias('c')
            ->leftJoin('assetinfo2019 a',' c.info_id=a.id')
            ->leftJoin('asset_type2019 t','a.asset_childclass=t.id')
            ->where(['cli_openid'=>$cli_openid,'isshelves'=>0,'status'=>1])
            ->field(array('a.id as aid','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','t.pname as bigassettype','asset_childclass','t.assettype as childassettype','asset_danbaomethod' ,'defaultasset'))
			->group('asset_name')
            ->limit($id,'10')
            ->select();
        return self::comm(1,'我收藏的资产',$result);
    }

    /*
     *
     * 完善客户信息 显示感兴趣的资产押品小类
     */

    public function perfect_info(){
        //显感兴趣的押品大小类类型
		
		$data[] = array('id'=>-1,'assettype'=>'全部','pid'=>0);
        $result = AssettypeModel::where(['lever'=>2,'is_status'=>1])->field(['id','assettype','pic','pid'])->order('sort')->select()->toArray();
        foreach($result as $key=>$val){
			$data[] = $val;
		}
        return self::comm('1','感兴趣的资产押品类型',$data);
		
       /* $result = AssettypeModel::where('lever',2)->where('pid!=0')->field(array('id','assettype'))->select();
        return self::comm('1','感兴趣的资产押品小类',$result);*/
		//$result[] = array('id'=>'-2','assettype'=>'其他类资产','pic'=>'/uploads/admin/20190514/a00de5a3905cafca0d117f94f195d5a.png','pid'=>'');
        /*$data['showpledgelist'] = $showpledgelist;*/
    }
    /*
     *
     * 完善客户信息 显示城市级联
     */

    /**
     * 处理类型
     * @param  [type] $menuinfo [description]
     * @return [type]           [description]
     */
    public function handleMenu($menuinfo){
        $parentMenu = array();
        $i = 0;
        if (!empty($menuinfo)) {
            foreach ($menuinfo as $key => $value) {

                if ($value['pid'] == 0) {
                    $parentMenu[$i]['parentname'] = $value['assettype'];
                    $parentMenu[$i]['id'] = $value['id'];
                    $parentMenu[$i]['chilrenname'] = array();
                    $i++;
                }
            }
            foreach ($parentMenu as $key => $value) {
                $j = 0;
                foreach ($menuinfo as $k => $val) {
                    if($value['id'] == $val['pid']){
                        $parentMenu[$key]['chilrenname'][$j]['typename'] = $val['assettype'];
                        $parentMenu[$key]['chilrenname'][$j]['id'] = $val['id'];
                        $j++;
                    }
                }
            }
        }
        return $parentMenu;
    }
    public function City_Country($menuinfo){
        $parentMenu = array();
        $parentMenu[0]['parentname'] = '全部';
        $parentMenu[0]['id'] = '-1';
        $parentMenu[0]['chilrenname'] = array();

        $i = 1;
        if (!empty($menuinfo)) {
            foreach ($menuinfo as $key => $value) {

                if ($value['pid'] == 0) {
                    $parentMenu[$i]['parentname'] = $value['assettype'];
                    $parentMenu[$i]['id'] = $value['id'];
                    $parentMenu[$i]['chilrenname'] = array();
                    $i++;
                }
            }

            foreach ($parentMenu as $key => $value) {
                $parentMenu[0]['chilrenname'][0]['typename'] = '全部';
                $parentMenu[0]['chilrenname'][0]['id'] = '-1';
                $j = 1;
                foreach ($menuinfo as $k => $val) {

                    if($value['id'] == $val['pid']){
                        $parentMenu[$key]['chilrenname'][0]['typename'] = '全部';
                        $parentMenu[$key]['chilrenname'][0]['id'] = '-1';
                        $parentMenu[$key]['chilrenname'][$j]['typename'] = $val['assettype'];
                        $parentMenu[$key]['chilrenname'][$j]['id'] = $val['id'];
                        $j++;
                    }
                }
            }
        }
        return $parentMenu;
    }

    public function prefect_city(){

        //城市显示
		$data[] = array('id'=>-1,'assettype'=>'全部','pid'=>0);
        $city = AssettypeModel::where('lever',1)->where('pid=0')->field(['id','assettype','pid'])->select()->toArray();
		foreach($city as $key=>$val){
			$data[] = $val;
		}
        return self::comm('1','城市选择',$data); 
       /*  $data = array();
        //查询展示城市
        $city = AssettypeModel::where('lever',1)->field(array('id','assettype','pid'))->select()->toArray();
        $data = self::handleMenu($city);
		
        return self::comm('1','城市选择',$data); */
    }

    /*
     *
     * 完善客户信息 提交信息
     */
    public function sub_customerinfo(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        $cli_city = explode(',',$datas['city']);
		if(in_array('-1',$cli_city)){
			$datas['city'] = '42,43,44,45,46,47,48,49,50,51';
		}
		
		$asset_childclass_str = '';
        $asset_childclass = explode(',',$datas['asset_childclass']);
		if(in_array(-1,$asset_childclass)){
			$res = AssettypeModel::where(['lever'=>2,'is_status'=>1])->field('id')->select()->toArray();
			foreach ($res as $k=>$val){
				$asset_childclass_str .= $val['id'].',';
			}
			
		}else{
			 //获取数组长度
			$arrlength = count($asset_childclass);
			if(in_array('-2',$asset_childclass)){ //判断数组中是否存在其他类资产（-2） 存在
				array_shift($asset_childclass);
				$res = AssettypeModel::where(['lever'=>2,'is_status'=>0,'pid'=>0])->field('id')->select()->toArray();
				foreach ($res as $k=>$val){
					$asset_childclass[] = $val['id'];
				}
			}
			//获取数组长度
			$arrlength = count($asset_childclass);
			
			for($x=0;$x<$arrlength;$x++)
			{
				$asset_childclass_str .=$asset_childclass[$x].',';
			}
		}
		$asset_childclass_str=substr($asset_childclass_str,0,-1);
       
        $data = array(
            'cli_name'=>$datas['cli_name'],
            'cli_company'=>$datas['cli_company'],
            'write_mobile'=>$datas['write_mobile'],
            'cli_provincial'=>'陕西省',
            'cli_city'=>$datas['city'],
            'cli_county'=>'',
            'cli_asset_childclass'=>$asset_childclass_str,
            'is_perfect'=>1,
            'updatetime'=>time());
        $result = Db::name('entryinfo2019')->where('cli_openid',$cli_openid)->update($data);
        if($result == 1){
            return self::comm(1,'信息完善成功');
        }else{
            return self::comm(2,'信息完善失败，请稍后重试！');
        }
    }

    /*
     * 修改用户头像
     */

    public function  update_headimg(){
        $datas = $this->request->post();
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        $day = date('Ymd');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/uploads/ 目录下
        $name = 'changcheng_'.$cli_openid.'.jpg';
        $info = $file->move( 'Uploads/customer/',$name);
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            $info->getExtension();
            $filename = $info->getSaveName();
            $info->getFilename();
            $avatarUrl = 'https://'.$_SERVER['SERVER_NAME'].'/Uploads/customer/'.$filename;
            $result = Db::name('user_information2019')->where('openid',$cli_openid)->update(['avatarUrl'=>$avatarUrl]);
            if($result == 1){
                return self::comm(1,'修改成功');
            }else{
                return self::comm(1,'修改失败，请稍后重试！');
            }

        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }

    }

    /*
     *查看项目经理名下的资产
     */
    public function name_assets(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }

//        根据openid 查询项目经理userid
        $userid = self::getUserid($cli_openid);

//      根据userid查询项目经理名下的资产
        $result = Db::name('assetinfo2019')
            ->alias('i')
            ->join('asset_type2019 t ',' i.asset_childclass = t.id')
            ->field(array('i.id as aid','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','t.pname as bigassettype','asset_childclass','t.assettype as childassettype','asset_danbaomethod' ))
            ->where(['asset_manageruserid'=>$userid,'isshelves'=>0,'status'=>1])
            ->group('asset_name')
            ->limit($id,'10')
            ->select();

        return self::comm(1,'信息展示',$result);
    }


    /*
     *部门负责人查看项目经理名下的资产
     */
    public function department_assets(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }

        /*$cli_openid = 'oa-UZ0SU789io-iFt20c1R-aqRt4';
        $token = md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'));*/

        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }

//        根据openid 查询项目经理userid
        $userid = self::getDepartment_head($cli_openid);

//      根据userid查询项目经理名下的资产
        $result = Db::name('assetinfo2019')
            ->alias('i')
            ->join('asset_type2019 t ',' i.asset_childclass = t.id')
            ->field(array('i.id as aid','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','t.pname as bigassettype','asset_childclass','t.assettype as childassettype','asset_danbaomethod' ))
            ->where(['isshelves'=>0,'status'=>1])
            ->where([['asset_manageruserid','in',$userid]])
            ->group('asset_name')
            ->limit($id,'10')
            ->select();

        return self::comm(1,'信息展示',$result);
    }


    /*
     *
     * 项目经理查看评论
     */

    public function view_messages(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';

        $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        //根据openid查询用户userid
        $userid = self::getUserid($cli_openid);
        //根据userid查询所属自己资产下的评论信息
        $unreadnum = CommentModel::where(array('asset_userid'=>$userid,'status'=>0))->count('id');
        //未读信息
        $unreadlist = Db::name('comment2019')
                        ->alias('c')
                        ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
                        ->field(array('asset_name','comment_content','c.id as cid','c.info_id as aid'))
                        ->where(array('asset_userid'=>$userid,'c.status'=>0))
                        ->order('c.id desc')
                        ->select();
        //已读信息
        $readlist = Db::name('comment2019')
                        ->alias('c')
                        ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
                        ->field(array('asset_name','comment_content','c.id as cid','c.info_id as aid'))
                        ->where(array('asset_userid'=>$userid,'c.status'=>1))
                        ->limit($id,'10')
                        ->select();
        $data = array();
        $data['unreadnum'] = $unreadnum;
        $data['unreadlist'] = $unreadlist;
        $data['readlist'] = $readlist;


        return self::comm(1,'留言列表',$data);
    }


    /*
     * 管理层查看所有留言信息
     */


    public function view_adminmessage(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token = isset($datas['token']) ? $datas['token'] : '';
        $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
        //$cli_openid = 'oa-UZ0SU789io-iFt20c1R-aqRt4';
        if(empty($cli_openid) || empty($token)){
            echo self::comm(2,'必要参数缺失！');
            die();
        }
        if($token!=md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm(3,'身份验证错误！');
            die();
        }
        //根据openid查询用户userid
        $userid = self::getUserid($cli_openid);
        //根据userid查询所属自己资产下的评论信息
        $unreadnum = CommentModel::where('status',0)->count('id');
        //未读信息
        $unreadlist = Db::name('comment2019')
            ->alias('c')
            ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
            ->field(array('asset_name','comment_content','c.id as cid','c.info_id as aid'))
            ->where('c.status',0)
            ->order('c.id desc')
            ->select();
        //已读信息
        $readlist = Db::name('comment2019')
            ->alias('c')
            ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
            ->field(array('asset_name','comment_content','c.id as cid','c.info_id as aid'))
            ->where('c.status',1)
            ->limit($id,'10')
            ->select();
        $data = array();
        $data['unreadnum'] = $unreadnum;
        $data['unreadlist'] = $unreadlist;
        $data['readlist'] = $readlist;
        return self::comm(1,'留言列表',$data);
    }


    /*
     * 部门负责人查看名下员工留言信息
     */
    public function view_managermessage(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token = isset($datas['token']) ? $datas['token'] : '';
        $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
       /* $cli_openid = 'oa-UZ0SU789io-iFt20c1R-aqRt4';
        $token = md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'));*/
        if(empty($cli_openid) || empty($token)){
            echo self::comm(2,'必要参数缺失！');
            die();
        }
        if($token!=md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm(3,'身份验证错误！');
            die();
        }
        //根据openid查询用户所属部门负责人 并获取底下员工工号

        $userid_data = self::getDepartment_head($cli_openid);
        $unreadnum = CommentModel::where('status',0) ->where([['asset_userid','in',$userid_data]])->count('id');

        //未读信息
        $unreadlist = Db::name('comment2019')
            ->alias('c')
            ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
            ->field(array('asset_name','comment_content','c.id as cid','c.info_id as aid'))
            ->where('c.status',0)
            ->where([['asset_userid','in',$userid_data]])
            ->order('c.id desc')
            ->select();


        //已读信息
        $readlist = Db::name('comment2019')
            ->alias('c')
            ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
            ->field(array('asset_name','comment_content','c.id as cid','c.info_id as aid'))
            ->where('c.status',1)
            ->where([['asset_userid','in',$userid_data]])
            ->limit($id,'10')
            ->select();

        $data = array();
        $data['unreadnum'] = $unreadnum;
        $data['unreadlist'] = $unreadlist;
        $data['readlist'] = $readlist;
        return self::comm(1,'留言列表',$data);
    }



    /*
     *
     * 项目经理回复留言
     */

    public function reply_comment(){
        $datas = $this->request->post();
        $aid = isset($datas['aid']) ? $datas['aid'] : '';//资产id
        $cid = isset($datas['cid']) ? $datas['cid'] : '';//评论id
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        $data = array();
        if(empty($aid) || empty($cid) || empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        //根据aid查询资产相关信息
        $assetlist =  self::view_assetdetail($aid);

        //根据cid查询评论内容
        $commentlist = CommentModel::where('id',$cid)->field('comment_content')->find();
        $data['assetlist'] = $assetlist;
        $data['commentlist'] = $commentlist;
        return self::comm(1,'评论详情',$data);
    }


    /*
     * 留言提交
     */

    public function sub_reply(){
        $datas = $this->request->post();
        $cid = isset($datas['cid']) ? $datas['cid'] : '';//评论的id
        $reply_content = isset($datas['reply_content']) ? $datas['reply_content'] : ''; //回复的内容
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($cid) || empty($reply_content) || empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
        $result = CommentModel::where('id',$cid)->update(['reply_content'=>$reply_content,'reply_time'=>time(),'status'=>1]);
        if($result == 1){
            return self::comm(1,'回复成功！');

        }else{
            return self::comm(2,'回复失败，请稍后重试！');

        }
    }

    /*
     *
     * 客户查看评论回复
     */

    public function view_reply(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }

        if( empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }

        //根据openid查看回复的内容
        $unreadnum = CommentModel::where(array('cli_openid'=>$cli_openid,'is_read'=>0,'status'=>1))->count('id');
        //判断项目经理是否已回复 如果没有回复不展示

        //未查看信息
        $unreadlist = Db::name('comment2019')
            ->alias('c')
            ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
            ->field(array('asset_name','reply_content','c.id as cid','c.info_id as aid'))
            ->where(array('cli_openid'=>$cli_openid,'c.is_read'=>0,'c.status'=>1))
            ->order('c.id desc')
            ->select();
        //已读信息
        $readlist = Db::name('comment2019')
            ->alias('c')
            ->leftJoin('assetinfo2019 a ',' a.id = c.info_id')
            ->field(array('asset_name','reply_content','c.id as cid','c.info_id as aid'))
            ->where(array('cli_openid'=>$cli_openid,'c.is_read'=>1,'c.status'=>1))
            ->limit($id,'10')
            ->select();
        $data = array();
        $data['unreadnum'] = $unreadnum;
        $data['unreadlist'] = $unreadlist;
        $data['readlist'] = $readlist;
        return self::comm(1,'回复列表',$data);
    }

    /*
     * 查看回复
     */
    public function reply_detail(){
        $datas = $this->request->post();
        $aid = isset($datas['aid']) ? $datas['aid'] : '';//资产id
        $cid = isset($datas['cid']) ? $datas['cid'] : '';//评论id
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
        if(empty($aid) || empty($cid) || empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }

        $data = array();
        if(empty($aid) || empty($cid)){
            echo self::comm(2,'必要参数缺失！');
            die();
        }
        $result = CommentModel::where('id',$cid)->update(['is_read'=>1]);
        //根据aid查询资产相关信息

        $assetlist = self::view_assetdetail($aid);
        //dump($assetlist);
        //根据cid查询评论内容
        $relpylist = CommentModel::where('id',$cid)->field('reply_content')->find();
        $data['assetlist'] = $assetlist;
        $data['relpylist'] = $relpylist;
        return self::comm(1,'回复详情',$data);

    }

    /*
     *
     * 资产详细信息
     */

    public function view_assetdetail($aid){
        $assetlist = Db::name('assetinfo2019')
            ->alias('i')
            ->join('asset_type2019 t ',' i.asset_childclass = t.id')
            ->field(array('i.id as aid','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','t.pname as bigassettype','asset_childclass','t.assettype as childassettype','asset_danbaomethod' ))
            ->where('i.id',$aid)
            ->find();
        return $assetlist;
    }

    /**
     *生成小程序二维码和公众号
     *传参数openid
     *
     **/
    public function getcode($cli_openid,$type=2){
        if(empty($cli_openid)){
            $data2=$this->request->post();
            $cli_openid =isset($data2['cli_openid'])  ? $data2['cli_openid'] : '';
        }
        //根据openid获取用户userid
        $userid = self::getUserid($cli_openid);
        $accesstoken = smallaccesstoken();
        $useri="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$accesstoken";

        $qr_path = "./Uploads/";
        if(!file_exists($qr_path.'staff_one/')){
            mkdir($qr_path.'staff_one/', 0700,true);//判断保存目录是否存在，不存在自动生成文件目录
        }
        $filename = 'staff_one/'.$userid.'.png';
        $file = $qr_path.$filename;
        if(file_exists($file)){
            $smallcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename;
            //print_r($_SERVER['SERVER_NAME'].'/Uploads/'.$filename);
            //exit;
        }else{
            $date=array(
                'scene'=>'userid='.$userid,
                'page'=>'',
                'width'=>430,
                'auto_color'=>false

            );
            $result=PostWeixin($useri,json_encode($date));
            $errcode = json_decode($result,true);
            if($errcode['errcode']) {
                echo self::comm(3,'小程序二维码生成失败');
                die();
            }
            $res = file_put_contents($file,$result);//将微信返回的图片数据流写入文件
            if($res===false){
                echo self::comm(4,'微信公众号二维码生成失败');
                die();
            }else{
                $smallcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename;
            }
        }
        $qr_path = "./Uploads/";
        if(!file_exists($qr_path.'staff_two/')){
            mkdir($qr_path.'user_two/', 0700,true);//判断保存目录是否存在，不存在自动生成文件目录
        }
        $filename = 'staff_two/'.'chancheng_'.$userid.'.png';
        $file = $qr_path.$filename;
        if(file_exists($file)){
            $wechatcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename.'';
        }else{
            $wxObj = new Wechat(get_weichat_options());
            $data = $wxObj->getQRCode('chancheng_'.$userid,2);
            $ticket=$wxObj->getQRUrl($data['ticket']);
            $res = file_put_contents($file,$ticket);//将微信返回的图片数据流写入文件
            if($res===false){
                echo self::comm(2,'二维码生成失败');
                die();
            }else{
                $wechatcode='https://'.$_SERVER['SERVER_NAME'].'/Uploads/'.$filename.'';
            }
        }
        if($type == 2){
            //file_put_contents('errcode1.log',print_r(array('smallcode'=>$smallcode,'wechatcode'=>$wechatcode),true).PHP_EOL,FILE_APPEND);
            //echo json_encode(array( 'ret' => 1 , 'msg' => '二维码生成成功' , 'data' => array('smallcode'=>$smallcode,'wechatcode'=>$wechatcode)));
            echo self::comm(1,'二维码生成成功',array('smallcode'=>$smallcode,'wechatcode'=>$wechatcode));
             

        }
    }
    //用户扫码绑定
    public function bingdingcli($userid,$openid,$unionid){//微信公众号绑定关系
        if(empty($userid)|| empty($openid)){
            return self::comm(2,'参数错误');
        }
        $userinfo = UserModel::where('userid',$userid)->find();
        if(empty($userinfo)){
            return self::comm(3,'未查询到员工信息');
        }

        //根据unionid查询用户是否有绑定信息
        $data = ['userid' => $userid, 'openid' => $openid ,'unionid'=>$unionid,'bingding_time2' => date('Y-m-d H:i:s'), 'bingding_date2' => date('Y-m-d')];
        $userbdinfo = BindingModel::where('unionid',$unionid)->find();
        if(!empty($userbdinfo)){
            $bdinfo = BindingModel::where('openid',$openid)->find();
            if(!empty($bdinfo)){
                return self::comm(4,'用户已经绑定过了');
            }else{
                //说明用户已经绑定了信息
               $get_update = BindingModel::where('unionid',$unionid)->update($data);
                if($get_update==1){
                    return self::comm(1,'财通天下，智融长城。欢迎关注中国长城资产陕西分公司！');
                }else{
                    return self::comm(2,'绑定失败，请稍后重试');
                }
            }
        }else{
            //进行绑定
            $get_insert = BindingModel::insert($data);
            if($get_insert==1){
                return self::comm(1,'财通天下，智融长城。欢迎关注中国长城资产陕西分公司！');
            }else{
                return self::comm(2,'绑定失败，请稍后重试');
            }
        }
    }

    /*
     *
     * 小程序扫码绑定关系
     */

    public function smallbinding(){
        $datas = $this->request->post();
        $userid = isset($datas['userid']) ? $datas['userid'] : '';//员工工号
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';//扫码人小程序openid
        $unionid = isset($datas['unionid']) ? $datas['unionid'] : '';//扫码人小程序openid
        $data = array();
        if(empty($cli_openid) || empty($userid)){
            echo self::comm(2,'必要参数缺失！');
            die();
        }
        $userinfo = UserModel::where('userid',$userid)->find();
        if(empty($userinfo)){
            return self::comm(3,'未查询到员工信息');
        }
        //根据cli_openid判断用户信息是否存在
        if (empty($unionid)){
            $info = Db::name('small_binding2019')->where('cli_openid',$cli_openid)->field('id')->find();
            unset($datas['unionid']);
            $datas['add_time'] = date('Y-m-d H:i:s');
            if (!empty($info)){
                echo self::comm(4,'用户已经绑定过了！');
                die();
            }else{
                $result = Db::name('small_binding2019')->insert($datas);
                if($result == 1){
                    return self::comm(1,'用户绑定成功！');
                }else{
                    return self::comm(2,'用户绑定失败，请稍后重试！');
                    
                }
            }
        }else{
            //根据unionid查询用户绑定信息
            $data = ['userid' => $userid, 'cli_openid' => $cli_openid ,'unionid'=>$unionid,'bingding_time' => date('Y-m-d H:i:s'), 'bingding_date' => date('Y-m-d')];
            $userbdinfo = BindingModel::where('unionid',$unionid)->find();
            if(!empty($userbdinfo)){//说明用户扫过码
                $bdinfo = BindingModel::where('cli_openid',$cli_openid)->find();
                if(!empty($bdinfo)){
                    return self::comm(4,'用户已经绑定过了');
                }else{
                    //说明用户已经绑定了信息
                    $get_update = BindingModel::where('unionid',$unionid)->update($data);
                    if($get_update==1){
                        return self::comm(1,'绑定成功');
                    }else{
                        return self::comm(2,'绑定失败，请稍后重试');
                    }
                }
            }else{//说明用户没有扫过码 进行绑定
                $get_insert = BindingModel::insert($data);
                if($get_insert==1){
                    return self::comm(1,'绑定成功');
                }else{
                    return self::comm(2,'绑定失败，请稍后重试');
                }
            }
        }
    }
    public function getUserid($cli_openid){
        $userid = '';
        $userinfo = UserModel::where('openid',$cli_openid)->field(['userid','department_head'])->find();
        if(!empty($userinfo)){
            $userid = $userinfo['userid'];
        }
        return $userid;
    }

    public function getDepartment_head($cli_openid){
        //根据openid查询用户所属部门负责人
        $department_head = UserModel::where('openid',$cli_openid)->field('department_head')->find();
        //根据department_head查询所属自己管辖的员工评论信息
        $userres = UserModel::where('department_head',$department_head['department_head'])->field('userid')->select()->toArray();

        $userid_arr = '';
        if(!empty($userres)){
            foreach ($userres as $key=>$val){
                $userid_arr .= $val['userid'].',';
            }
        }

        $userid_data = substr($userid_arr,0,-1);
        //$unreadnum = CommentModel::where('status',0) ->where([['asset_userid','in',$userid_data]])->count('id');
        return $userid_data;
    }

    /*******************************************************************资讯***********************************************************************************/

    //更多精品推荐
    public function more_boutique(){
        $datas = $this->request->post();
		$city = isset($datas['city']) ? $datas['city'] : '';
        $page = isset($datas['page']) ? $datas['page'] : 0;
		$where = [];
        if(!empty($city) && $city!='-1'){
            $where[] = ['asset_leavedishi','eq',$city];
        }

        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
		
        $result = Db::name('assetinfo2019')
            ->alias('i')
            ->join('asset_type2019 t ',' i.asset_childclass = t.id')
            ->field(array('i.id as aid','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','t.pname as bigassettype','asset_childclass','t.assettype as childassettype','asset_danbaomethod' ))
            ->where(['status'=>1,'isshelves'=>0])
			->where($where)
            ->group('asset_name')
            ->limit($id,'10')
            ->select();
        return  self::comm(1,'',$result);
    }

    /*模糊搜索功能*/
    public function search_list(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : ''; //用户openid
        $city = isset($datas['city']) ? $datas['city'] : ''; //城市
        $country = isset($datas['country']) ? $datas['country'] : ''; //区县
        $pledge = isset($datas['pledge']) ? $datas['pledge'] : '';//押品大类
        $smallpledge = isset($datas['smallpledge']) ? $datas['smallpledge'] : '';//押品小类
        $scale = isset($datas['scale']) ? $datas['scale'] : '';//规模
        $industry = isset($datas['industry']) ? $datas['industry'] : '';//行业
        $keywords = isset($datas['keywords']) ? $datas['keywords'] : '';//关键字
        $page = isset($datas['page']) ? $datas['page'] : 0;
        $request = new Request();
        $article = new Article($request);
        $ussrtype = $article->getusertype($cli_openid);//获取用户类型

        if(empty($pledge) && !empty($smallpledge)){
            $pledgepid = AssettypeModel::where(['lever'=>2,'id'=>$smallpledge])->field('pid')->find();
            $pledge = $pledgepid['pid'];
        }
        $data = array(
            'areaID'=>$city,
            'productID'=>$pledge,
            'scropID'=>$scale,
            'tradeID'=>$industry,
            'usertype'=>$ussrtype,
            'screendate'=>time(),
            'openid'=>$cli_openid
        );
        if(!empty($pledge)  && $pledge==-2){
            $res = AssettypeModel::where(['lever'=>2,'is_status'=>0,'pid'=>0])->field('id')->select()->toArray();
            foreach ($res as $k=>$val){
                $pledgedata = array(
                        'areaID'=>$city,
                        'productID'=>$val['id'],
                        'scropID'=>$scale,
                        'tradeID'=>$industry,
                        'usertype'=>$ussrtype,
                        'screendate'=>time(),
                        'openid'=>$cli_openid
                );
                Db::name('screendata2019')->insert($pledgedata);
            }
        }
        else if((!empty($city) && $city!=-1) || (!empty($country) && $country!=-1) || (!empty($pledge)  && $pledge!=-1) || (!empty($smallpledge) && $smallpledge!=-1) || !empty($scale) || !empty($industry)){
            Db::name('screendata2019')->insert($data);
        }

        $where = [];
        if(!empty($city) && $city!='-1'){
            $where[] = ['asset_leavedishi','eq',$city];
        }
        if(!empty($country) && $country!='-1'){
            $where[] = ['asset_leavediquxian','eq',$country];
        }
        if(!empty($pledge) && $pledge!=-1 && $pledge!=-2){
            $where[] = ['asset_bigclass','eq',$pledge];
        }else if($pledge == -2){
            //查询不显示的大类id
            $pledgeid = array();
            $res = AssettypeModel::where(['lever'=>2,'is_status'=>0,'pid'=>0])->field('id')->select()->toArray();
            foreach ($res as $k=>$val){
                $pledgeid[] = $val['id'];
            }
            $where[] = ['asset_bigclass','in',$pledgeid];
        }
        if(!empty($smallpledge) && $smallpledge!='-1'){
            $where[] = ['asset_childclass','eq',$smallpledge];
        }
        if(!empty($industry)){
            $where[] = ['asset_trade','eq', $industry];
        }
        if(!empty($scale)){
            if($scale == 188){
                $where[] = ['asset_basemoney','<','500'];
            }
            else if($scale == 189){
                $where[] = ['asset_basemoney','>=','500'];
                $where[] = ['asset_basemoney','<=','1000'];
            }
            else if($scale == 190){
                $where[] = ['asset_basemoney','>=','1000'];
                $where[] = ['asset_basemoney','<=','3000'];
            }
            else if($scale == 191){
                $where[] = ['asset_basemoney','>=','3000'];
                $where[] = ['asset_basemoney','<=','5000'];
            }else if($scale == 192){
                $where[] = ['asset_basemoney','>','5000'];
            }
        }
        if(!empty($keywords)){
            $where[] = ['asset_name','like', '%'.$keywords.'%'];
        }
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
        $result = Db::name('assetinfo2019')
            ->alias('i')
            ->join('asset_type2019 t ',' i.asset_childclass = t.id')
            ->field(array('i.id as aid','asset_name','asset_basemoney','asset_getmoney','asset_bigclass','t.pname as bigassettype','asset_childclass','t.assettype as childassettype' ,'asset_danbaomethod'))
            ->where($where)
            ->where(['isshelves'=>0,'i.status'=>1])
			->group('asset_name')
            ->limit($id,'10')
            ->select();

        //echo Db::name('assetinfo2019')->getLastSql();

        return  self::comm(1,'',$result);
    }
    public function classification(){

        //等级  4行业，3规模 ,2押品 1地市
        $data = array();
        //获取城市区县
        $citylist = AssettypeModel::where('lever', 1)->field(['id','assettype','pic','pid'])->order('sort')->select();
        $citylists = self::City_Country($citylist);
        $data['citylist'] = $citylists;

        //获取押品大类及子类
        $pledgelist = AssettypeModel::where('lever', 2)->field(['id','assettype','pic','pid'])->order('sort')->select();
        $pledgelists = self::City_Country($pledgelist);
        $data['pledgelist'] = $pledgelists;


        //显示的押品大小类类型
        $showpledgelist = AssettypeModel::where(['lever'=>2,'is_status'=>1])->field(['id','assettype','pic','pid'])->order('sort')->select();
        //$showpledgelist[] = array('id'=>'-2','assettype'=>'其他类资产','pic'=>'/uploads/admin/20190514/a00de5a3905cafca0d117f94f195d5a.png','pid'=>'');
        $data['showpledgelist'] = $showpledgelist;


        //获取规模
        $scalelist = AssettypeModel::where(['lever' => 3, 'pid'=>0])->field(['id','assettype','pic'])->order('sort')->select();
        $data['scalelist'] = $scalelist;

        //获取行业
        $industrylist = AssettypeModel::where(['lever' => 4, 'pid'=>0])->field(['id','assettype','pic'])->order('sort')->select();
        $data['industrylist'] = $industrylist;
        return self::comm(1,'分类列表',$data);

    }
	
	
	/**
	*
	*当日客户引流数
	*/
	public function day_drainage(){
		$datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : '';
		$api = new Apismall();
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
		 $page = isset($datas['page']) ? $datas['page'] : 0;
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
		$data = array();
		// 获取当日引流客户总数
		$whereOr = array();
		$day = date('Y-m-d'); 
        $whereOr[] = ['bingding_date|bingding_date2','eq',$day];
		// 根据openid获取员工userid
		$userid = self::getUserid($cli_openid);
        $daynum = BindingModel::where('userid',$userid)->where($whereOr)->count('id');
        $data['daynum'] = $daynum;
		$day_drainage = Db::name('bingdinginfo2019')
                            ->alias('b')
                            ->leftJoin('user_information2019 u',' b.unionid=u.unionId')
                            ->leftJoin('entryinfo2019 e ',' b.unionid=e.unionid')
                            ->where('b.userid',$userid)
                            ->where($whereOr)
							->where("u.nickName!=''" )
                            ->field(['u.nickName as nickName','u.purePhoneNumber as purePhoneNumber','e.cli_name as cli_name','e.cli_company as cli_company'])
							->limit($id,'10')
                            ->select();
		foreach($day_drainage as $key=>$val){
			$day_drainage[$key]['nickName'] =  $api->hex2str($val['nickName']);
		}  
		$data['day_drainage']=$day_drainage;
		return self::comm(1,'客户引流',$data);
		
	}
	public function customerList(){
        $datas = $this->request->post();
        $cli_openid = isset($datas['cli_openid']) ? $datas['cli_openid'] : '';
        $token  = isset($datas['token']) ? $datas['token'] : ''; 
		/* $cli_openid = 'oaCz74mfAZJYDBvfs_FJlKbE0sjA';
		$token = md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd')); */
		$api = new Apismall();
        if(empty($cli_openid) || empty($token)){
            echo self::comm('2','必要参数缺失！');
            die();
        }
        if($token != md5($cli_openid.'JKSHFUHFF_FFF4s5f45sW'.date('Ymd'))){
            echo self::comm('3','身份验证错误！');
            die();
        }
		$page = isset($datas['page']) ? $datas['page'] : 0;
		
		
        if($page == 1 || $page == 0){
            $id = 0;
        }else{
            $id = ($page-1)*10;
        }
		
		$data = array();
		// 根据openid获取员工userid
		$userid = self::getUserid($cli_openid);
        $allnum = BindingModel::where('userid',$userid)->count('id');
        $data['daynum'] = $allnum;
		$day_drainage = Db::name('bingdinginfo2019')
                            ->alias('b')
                            ->leftJoin('user_information2019 u',' b.unionid=u.unionId')
                            ->leftJoin('entryinfo2019 e ',' b.unionid=e.unionid')
                            ->where('b.userid',$userid)
							->where("u.nickName!=''" )
                            ->field(['u.nickName as nickName','u.purePhoneNumber as purePhoneNumber','e.cli_name as cli_name','e.cli_company as cli_company'])
							->limit($id,'10')
                            ->select();
		
		foreach($day_drainage as $key=>$val){
			$day_drainage[$key]['nickName'] =  $api->hex2str($val['nickName']);
		} 
		$data['day_drainage']=$day_drainage;
		return self::comm(1,'客户引流列表',$data);
    }

}