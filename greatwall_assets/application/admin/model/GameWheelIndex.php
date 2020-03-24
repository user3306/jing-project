<?php

namespace app\admin\model;

use think\Model;

class GameWheelIndex extends Model{

    /**
     * 提价更新首页面所有图片
     */
    public function add($data){
        return $this->allowField(true)->save($data);

    }

}