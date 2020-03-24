<?php

namespace app\admin\model;

use think\Model;

class GameWheelPrizeinfo extends Model{

    /**
     * 增加奖品信息
     */
    public function addPrizeAll($data){
        return $this->saveAll($data);

    }
	


}