<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/23/023
 * Time: 17:31
 */

namespace app\index\Model;
use think\Model;
class CommentModel extends Model
{
    protected $name = 'comment2019';
    protected $pk = 'id';
    protected static function init()
    {
        //TODO:初始化内容
    }
}