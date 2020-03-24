<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2018-2-9
 * Time: 14:53
 */

namespace app\home\controller;


use think\Controller;

class WxNews extends Controller
{
    public function show()
    {
        $content_id = input("content_id");
        $where[] = ['content_id','=',$content_id];
        $content = model('Content')->getInfo($where);
        $this->assign('title',$content['title']);
        $this->assign('views',$content['views']);
        $this->assign('content',$content['content']);
        return $this->fetch();
    }
}