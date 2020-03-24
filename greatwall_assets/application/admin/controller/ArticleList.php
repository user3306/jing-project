<?php
/**
 * 文章列表
 * @author caizhuan <[<email address>]>
 * @date(20190425)
 */
namespace app\admin\controller;
use app\admin\controller\Admin;


/**
 * Class AdminContent 文章控制器类
 * dingzq hatabc@qq.com
 */
class ArticleList extends Admin{
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '文章管理',
                'description' => '管理网站的所有文章',
            ),
            'menu' => array(
                array(
                    'name' => '文章列表',
                    'url' => url('index'),
                    'icon' => 'list',
                ),

            ),
            '_info' => array(
                array(
                    'name' => '添加文章',
                    'url' => url('info'),
                    'icon' => 'plus',
                ),
            )
        );
    }
	//文章文章列表
	public function index(){
        //筛选条件
        $where = array();
        $title = input('title');
        if(!empty($title)){
            $where[] = ['a.title','like','%'.$title.'%'];
        }

        $list = model('Article')->getArticleList($where,20);
   
        //位置导航
        $breadCrumb=array(array('name'=>'文章管理','url'=>url('index')));
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('_page',$list->render());
        $this->assign('pageMaps',$_GET);
		return $this->fetch();
	}


    /**
     * 详情
     */
    public function info(){
        $id=input('post.id');
        if (input('post.')){
        	$arr = $_POST;
        	$arr['addtime'] = time();
            if ($id){
                $status=model('Article')->edit($arr,$id);
            }else{
                $status=model('Article')->add($arr);
            }
            if($status){
                return ajaxReturn(200,'操作成功',url('index'));
            }else{
                return ajaxReturn(0,'操作失败');
            }
        }else{
            $result = model('ArticleType')->getArticleTypeInfo();
            $typeinfo = model('ArticleType')->handleMenu($result);
          
            $this->assign('info',model('Article')->getArticleDetail(input('id')));//页面信息
			$this->assign('typeinfo',$typeinfo);
            return $this->fetch();
        }
    }


	//文章文章删除
	public function del(){
        $id=input('id');
        if(empty($id)){
            return ajaxReturn(0,'参数不能为空');
        }
        if(model('ContentArticle')->del($content_id)){
            return ajaxReturn(200,'文章删除成功！');
        }else{
            return ajaxReturn(0,'文章删除失败！');
        }
	}

	public function upload()
    {
        return '
                {
                  "code": 0
                  ,"msg": ""
                  ,"data": {
                    "src": "http://res.layui.com/images/layui/logo.png"
                  }
                }  
            ';
    }
}
?>