<?php
namespace app\rncms\model;
use app\kbcms\model\Content;
use think\Model;
/**
 * Class ContentArticle 文章内容信息模型
 * dingzq hatabc@qq.com
 */
class ContentArticle extends Model {

    // time 发布时间 读取器
    protected function getTimeAttr($time)
    {
        return date('Y-m-d H:i', $time);
    }
    /**
     * 获取列表
     * @return array 列表
     */
    public function loadList($where = array(), $limit = 10, $order = 'A.time desc,A.content_id desc', $fieldsetId = 0){
        //基础条件
        $where[] = ['C.app','=','article'];
        //语言判断
        if (get_lang_id()){
            $where[]=['C.lang_id','=',get_lang_id()];
        }
        $model =  $this->name('content')
                        ->alias('A')
                        ->join('category C','A.class_id = C.id');
        $field = 'A.*,C.name as class_name,C.app,C.urlname as class_urlname,C.image as class_image,C.parent_id';
        //获取最终结果
        $pageList = $model->field($field)
                    ->where($where)
                    ->order($order)
                    ->paginate($limit);
        if (!empty($pageList)){
            $i = 0;
            foreach ($pageList as $key=>$value){
                $pageList[$key]['app']=strtolower($value['app']);
                $pageList[$key]['i'] = $i++;
            }
        }
        return $pageList;
    }
    /**
     * 获取数量
     * @return int 数量
     */
    public function countList($where = array()){
        $where['C.app'] = 'article';
        return $this->name("content")
                ->alias('A')
                ->join('content_article B',' A.content_id = B.content_id','left')
                ->join('category C','A.class_id = C.class_id','left')
                ->where($where)
                ->count();
    }
    public function countll($where = array()){
        return $this->where($where)->count();
    }

    // 新增
    public function add(){
        $model=new ContentArticle($_POST);
        $rs=$model->allowField(true)->save();
        if ($rs>0){
            return true;
        }else{
            return false;
        }
    }
    // 修改
    public function edit(){
        $_POST['app']='article';

        $model=new Content();
        $where = array();
        $where[] = ['content_id','=',input('post.content_id')];

		//dump($where);
        $status = $model->allowField(true)->save($_POST,$where);
        if($status === false){
            return false;
        }
        return true;
    }

    /**
     * 删除信息
     * @param int $content_id ID
     * @return bool 删除状态
     */
    public function del($content_id)
    {
        Model::startTrans();
        $status_content = model('Content')->del($content_id);
        if(!$status_content){
            $this->rollBack();
            return false;
        }
        $map = array();
        $map[] = ['content_id','=',$content_id];
        $status = $this->where($map)->delete();
        if($status){
            Model::commit();
        }else{
            Model::rollback();
        }
        return $status;
    }

}
