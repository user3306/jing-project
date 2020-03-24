<?php
namespace app\rncms\model;
use think\Model;
use org\HttpDown;

/**
 * Class Content 内容基础模型
 * dingzq hatabc@qq.com
 */
class Content extends Model
{
    protected $type       = [
        'time' => 'timestamp',
    ];
    protected $insert = ['time'];
    protected function setTimeAttr(){
        return time();
    }
    //栏目列表
    public function loadList($where = array(), $class_id=0){
        $data=$this->loadData($where);
        $cat = new \org\Category(array('class_id', 'parent_id', 'name', 'cname'));
        $data = $cat->getTree($data, intval($class_id));
        return $data;
    }
    //内容数据
    public function loadData($where = array(), $limit = 0){
        $list=$this->name('Content')->where($where)->order('sequence asc , content_id desc')->limit($limit)->select();
        return $list;
    }

    /**
     * 获取信息
     * @param int $content_id ID
     * @return array 信息
     */
    public function getInfo($content_id)
    {
        $map = array();
        if(!empty($content_id)) {
            $map[] = ['A.content_id', '=', $content_id];
            $info = $this->getWhereInfo($map);
            if (empty($info)) {
                $this->error = '文章不存在！';
            }
        }else{
            $info=['class_id'=>0,'status'=>1,'tpl'=>'','position'=>'','redpacket_type'=>'1','content_id'=>0];
        }
        return $info;
    }

    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function getWhereInfo($where,$order = '')
    {
        $info = $this->name("content")
            ->alias('A')
            ->join('category C','A.class_id = C.id')
            ->field('A.*,C.name as class_name,C.app,C.urlname as class_urlname,C.image as class_image')
            ->where($where)
            ->order($order)
            ->find();
        if(!empty($info)){
            $info['app'] = strtolower($info['app']);
        }

        return $info;
    }

    //新增
    public function add(){
        if (!empty($_POST['position'])){
            $_POST['position']=implode(',',$_POST['position']);
        }
        $_POST['image']=$_POST['smallurl'];
        $model=new Content($_POST);
        $contentId=$model->allowField(true)->save();
        if (!$contentId){
            return false;
        }

        return $model->id;
    }
    //修改
    public function edit(){
        $content_id=input('post.content_id');
        $_POST['image']=$_POST['smallurl'];
        $model=new Content();
        if(empty($content_id)){
            return false;
        }
        if (!empty($_POST['position'])){
            $_POST['position']=implode(',',$_POST['position']);
        }

        $status = $model->allowField(true)->save($_POST,array('content_id'=>$content_id));
        if($status === false){
            return false;
        }
        //保存扩展表
        //if(!$this->saveExtData($_POST)){
        //    return false;
        //}

        return true;
    }
    //删除
    public function del($content_id){
        $map = array();
        $map['content_id'] = $content_id;
        return $this->where($map)->delete();
    }

    /**
     * 更新扩展信息
     * @param string $type 更新类型
     * @return bool 更新状态
     */
    public function saveExtData($data){
        //查询栏目信息
        $classId = $data['class_id'];
        //获取字段集信息
        $fieldsetInfo = model('kbcms/Fieldset')->getInfoClassId($classId);
        //保存扩展字段
        if(!empty($fieldsetInfo)){
            $expandModel = model('kbcms/FieldData');
            //设置模型信息
            $expandModel->setTable(config('database.prefix').'ext_'.$fieldsetInfo['table']);

            $_POST['data_id'] = $data['content_id'];
            if($expandModel->getInfo($data['content_id'])){
                $type = 'edit';
            }else{
                $type = 'add';
            }
            if(!$expandModel->saveData($type,$fieldsetInfo)){
                $this->error = '保存失败';
                return false;
            }
        }
        return true;
    }


    /**
     * 获取编辑器内容里的外部资源并替换
     * @param $content
     */
    public function GetContFile($content){
        $content = stripslashes($content);
        $host = 'http://'.$_SERVER['HTTP_HOST'];

        //过滤图片文件
        $pic_arr = array();
        preg_match_all("/src=[\"|'|\s]{0,}(https?:\/\/([^>]*)\.(gif|jpg|png|bmp))/isU", $content, $pic_arr);
        $pic_arr = array_unique($pic_arr[1]);

        $htd = new HttpDown();
        foreach($pic_arr as $k=>$v)
        {

            if(preg_match('#'.$host.'#i', $v)) continue;
            if(!preg_match('#^https?:\/\/#i', $v)) continue;


            $htd->OpenUrl($v);


            $type = $htd->GetHead('content-type');


            if($type == 'image/gif'){
                $tempfile_ext = 'gif';
            }else if($type == 'image/png'){
                $tempfile_ext = 'png';
            }else if($type == 'image/wbmp'){
                $tempfile_ext = 'bmp';
            }else{
                $tempfile_ext = 'jpg';
            }

            $upload_dir = '/data/wwwroot/xjboc.wx.snrunning.cn/public/uploads/admin';

            $ymd = date('Ymd');
            $upload_dir .= '/'.$ymd;

            if(!file_exists($upload_dir))
            {
                mkdir($upload_dir);

                $fp = fopen($upload_dir.'/index.htm', 'w');
                fclose($fp);
            }

            //上传文件名称
            $filename = time().rand(1,99999).'.'.$tempfile_ext;

            //上传文件路径
            $save_url = '/uploads/admin/'.$ymd.'/'.$filename;
            $save_dir = $upload_dir.'/'.$filename;
            $rs = $htd->SaveToBin($save_dir);
            if($rs)
            {
                $content = str_replace(trim($v), $save_url, $content);
            }
        }
        $htd->Close();

        //回传转义字符串
        return $content;
    }
    

}
