<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/28/028
 * Time: 14:33
 */

namespace app\officeaccount\controller;
use think\Controller;
use app\admin\model\Article;
use app\admin\model\Asset;
use app\admin\model\ArticleType;
use think\Db;
use think\Request;
use think\View;
class Finance extends Controller
{
    public function financeclass(){
        $class = Db::name('articletype2019')->where('pid',4)->field(['id','typename'])->select();
        return \view('knowledge',['classlist'=>$class]);
    }
    public function financecontent(Request $request){
        $dates = $request->post();
        $typeid = isset($dates['typeid']) ? $dates['typeid'] : 5;
        $basicscontent = Db::name('article2019')->where('typeid',$typeid)->select();
        $returnHtml = '';
        if(!empty($basicscontent)){
            foreach ($basicscontent as $key=>$val){
                $url   = url('officeaccount/Finance/articledetail', ['id' => $val['id']]);
                $returnHtml .="<li><a href='$url'>$val[title]</a></li>";
            }
        }
        return $returnHtml;
    }
    public function articledetail(Request $request){
        $datas = $request->param();
        $id = $datas['id'];
        $content = Db::name('article2019')->alias('a')->join('articletype2019 b','a.typeid=b.id','left')->where(array('a.id'=>$id))->find();
		if($id == 40){
			return \view('videoList',['content'=>$content]);
		}else{
			return \view('financedetail',['content'=>$content]);
		}
        
    }
    public function lawclass(){
        $lawclass = Db::name('articletype2019')->where('pid',11)->field(['id','typename'])->select();
        return \view('law',['lawclass'=>$lawclass]);
    }
    public function lawcontent(Request $request){
        $dates = $request->post();
        $typeid = isset($dates['typeid']) ? $dates['typeid'] : 12;
        $lawcontent = Db::name('article2019')->where('typeid',$typeid)->select();
        $returnHtml = '';
        if(!empty($lawcontent)){
            foreach ($lawcontent as $key=>$val){
                $url   = url('officeaccount/Finance/articledetail', ['id' => $val['id']]);
                $returnHtml .="<li><a href='$url'>$val[title]</a></li>";
            }
        }
        return $returnHtml;
    }
	
	public function companyclass(){
		$companyclass = Db::name('articletype2019')->where('pid',14)->field(['id','typename'])->select();
        return \view('profile',['companyclass'=>$companyclass]);
    }
	public function companyprofile(Request $request){
		
        $dates = $request->post();
        $typeid = isset($dates['typeid']) ? $dates['typeid'] : 14;
        $lawcontent = Db::name('article2019')->where('typeid',$typeid)->select();
		$returnHtml = '';
        if($typeid == 16){
			$content = Db::name('article2019')->where('typeid',16)->find();
			$returnHtml =  $content['content'];
        }else{
            if(!empty($lawcontent)){
                foreach ($lawcontent as $key=>$val){
                    $url   = url('officeaccount/Finance/articledetail', ['id' => $val['id']]);
                    $returnHtml .="<li><a href='$url'>$val[title]</a></li>";
                }
            }
           
        }
		
		return $returnHtml;
	}
}