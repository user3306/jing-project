<?php
namespace app\home\model;
use think\Model;

/**
 * Class Content 内容基础模型
 * dingzq hatabc@qq.com
 */
class Content extends Model
{
    //栏目列表
    public function loadList($where = array(), $class_id=0){
        $data=$this->loadData($where);
        $cat = new \org\Category(array('class_id', 'parent_id', 'name', 'cname'));
        $data = $cat->getTree($data, intval($class_id));
        return $data;
    }
    //栏目数据
    public function loadData($where = array(), $limit = 0){
        $list=$this->name('content')->where($where)->order('sequence ASC , content_id desc')->limit($limit)->select();
        return $list;
    }

    public function getInfo($where = array())
    {
        $content =  $this->name('content')->where($where)->find();
        return $content;
    }

    /**
     * 获取内容URL
     * @param int $info 栏目信息
     * @return bool 删除状态
     */
    public function getUrl($info){
        if (!empty($info['content_id'])){
            $tmp['content_id']=$info['content_id'];
        }
        if (!empty($info['urltitle'])){
            $tmp['urltitle']=$info['urltitle'];
        }
        if (!empty($info['class_urlname'])){
            $tmp['class_urlname']=$info['class_urlname'];
        }
        return match_url('home/'.strtolower($info['app']).'/detail',$tmp);
    }
}
