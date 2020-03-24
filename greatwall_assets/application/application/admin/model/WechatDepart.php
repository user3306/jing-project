<?php
/**
 * Created by PhpStorm.
 * User: dingzq
 * Date: 2017-12-7
 * Time: 10:34
 */

namespace app\admin\model;


use think\Model;

class WechatDepart extends Model
{
    protected $table = 'rn_departinfo';
    /**
     * 获取部门列表，分页呈现
     * @param array $where 搜索字段
     * @param int $limit 每页数量
     * @return \think\Paginator
     */
    public function loadlist($where = array(), $limit = 0){
        $data = $this->name('departinfo')
            ->field('*')
            ->where($where)
            ->order('wxid','asc')
            ->paginate($limit,false,['query' => request()->param()]);
        return $data;
    }

    /**
     * 获取部分信息
     * @param array $where
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function limitlist($where = array(), $limit = 10)
    {
        $data = $this->name('departinfo')
            ->field('*')
            ->where($where)
            ->order('wxid','asc')
            ->limit($limit)
            ->cache('All_departinfo_Data',864000)
            ->select();
        return $data;
    }

    /**
     * 根据wxid获取部门信息
     * @param $wxid
     * @return array|false|\PDOStatement|string|Model
     */
    public function getInfo($wxid)
    {
        return $this->name('departinfo')
            ->field('*')
            ->where('wxid','=',$wxid)
            ->find();
    }

    /**
     * 新增
     */
    public function add(){
        return $this->allowField(true)->save($_POST);
    }
    /**
     * 更新
     */
    public function edit(){
        if (empty(input('post.wxid'))){
            return false;
        }
        $where['wxid']=input('post.wxid');
        return $this->allowField(true)->save($_POST,$where);
    }
    /**
     * 删除信息
     * @param int $userId ID
     * @return bool 删除状态
     */
    public function del($wxid){
        $map = array();
        $map['wxid'] = $wxid;
        return $this->where($map)->delete();
    }

    /**
     * 获取当前字段最大值
     * @param $type 哪个字段
     * @return mixed
     */
    public function getMaxid($type)
    {
        $data = $this->name('departinfo')
            ->field('max('.$type.') as m')
            ->find();
        return $data->m;
    }
}