<?php
/**
 * 粉丝管理
 * @author caizhuan <[<email address>]>
 * @date(20190422)
 */
namespace app\admin\controller;
use app\admin\controller\Admin;
use  app\index\controller\Apismall;
use think\Db;

class FanList extends Admin{
	public function __controller(){
		parent::__controller();
	}
	/**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '粉丝管理',
                'description' => '管理网站的客户',
            ),
            'menu' => array(
                array(
                    'name' => '粉丝列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            // '_info' => array(
            //     array(
            //         'name' => '添加客户',
            //         'url' => url('info'),
            //         'icon' => 'plus',
            //     ),
            // )
        );
    }


	/**
	 * 粉丝信息
	 * @param  string $[username] [<description>]
	 * @param string $[mobile] [<description>]
	 * return 
	 */
	public function index(){
		//筛选条件
        $where = array();
        $starttime = input('starttime');    //开始时间
        $endtime = input('endtime');        //结束时间
		$userid = input('userid');

        if (!empty($starttime)) {
            $where[] = ['i.addtime','>=',strtotime($starttime.' 00:00:00')];
        }

        if (!empty($endtime)) {
            $where[] = ['i.addtime','<=',strtotime($endtime.' 23:59:59')];
        }
		
		if (!empty($userid)) {
            $where[] = ['u.userid','like','%'.$userid.'%'];
        }
		
		$searchinfo  = array();
		$searchinfo = request()->param();
		$apismall = new Apismall();
        $list = model('fan')->getCustomerList($where,20,$searchinfo);
	
		foreach($list as $key=>$value){
			$list[$key]['nickName'] = $apismall->hex2str($value["nickName"]);
			if($value['gender'] == 1){
				$list[$key]['gender'] = '男';	
			}else if($value['gender'] == 2){
				$list[$key]['gender'] = '女';	
			}else{
				$list[$key]['gender'] = '未设置';	
			}
			$list[$key]['cli_mobile'] = !empty($value['cli_mobile'])?'已授权':'未授权';
		}
		
        //位置导航
        $breadCrumb=array(array('name'=>'粉丝列表','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('pageMaps',$_GET);
        $this->assign('_page',$list->render());
		return $this->fetch();
	}
	
	
	public function exportuserlist(){
		$csvFileName = '粉丝信息'.date("Y-m-d").'.csv';
		$columns = [
			 'Openid','昵称', '性别', '所在地区' , '所在省份','关注时间','推荐人','客户姓名','所在公司','客户手机号','是否授权'
		];


		//设置好告诉浏览器要下载excel文件的headers
		header('Content-Description: File Transfer');
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="'. $csvFileName .'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		$fp = fopen('php://output', 'a');//打开output流
		mb_convert_variables('GBK', 'UTF-8', $columns);
		fputcsv($fp, $columns);//将数据格式化为CSV格式并写入到output流中  

		$res = Db::name('user_information2019')
             ->alias('i')
             ->join('bingdinginfo2019 u','i.unionId=u.unionid','left')
			 ->join('entryinfo2019 c','i.unionId=c.unionid','left')
			 ->join('member2019 m','u.userid=m.userid','left')
             ->order('i.id ASC')//根据id降序排列
             ->field(['i.openId','i.nickName','i.gender','i.province','i.country','i.addtime','m.username','u.userid','c.cli_name','c.cli_company','c.write_mobile','c.cli_mobile'])->select();
		$apismall = new Apismall();
		if(is_array($res) && !empty($res)) {
			foreach ($res as $rowData) {
				$rowData['nickName'] = $apismall->hex2str($rowData["nickName"]);
				if($rowData['gender'] == 1){
					$rowData['gender'] = '男';	
				}else if($rowData['gender'] == 2){
					$rowData['gender'] = '女';	
				}else{
					$rowData['gender'] = '未设置';	
				}
				$rowData['addtime'] = !empty($rowData['addtime'])?date('Y-m-d H:i:s',$rowData['addtime']):'';
				$rowData['cli_mobile'] = !empty($rowData['cli_mobile'])?'已授权':'未授权';
			 	mb_convert_variables('GBK', 'UTF-8', $rowData);
				fputcsv($fp, $rowData); 
			}
		}
		unset($res);//释放变量的内存
		//刷新输出缓冲到浏览器
		ob_flush();
		flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。

		fclose($fp);
		exit(); 
	}
}
?>