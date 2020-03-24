<?php
namespace app\admin\model;
use think\Model;
use think\Db;
use think\facade\Env;

/**
 * Class File 后台文件上传模型
 * dingzq hatabc@qq.com
 */
class File extends Model{
    /**
     * 上传图片
     * @return
     * status  上传状态 1成功0失败
     * msg     返回上传消息
     * file_id 上传图片文件id
     * url     上传图片返回路径
     */
	public function uploadImg($file='file',$location='admin'){
		// 获取表单上传文件 例如上传了001.jpg
		if (!empty($_FILES[$file]['name'])){
			$file = request()->file($file);
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->validate(['size'=>1024*2*1000,'ext'=>'jpg,png,gif'])->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads'. DIRECTORY_SEPARATOR . $location);
            if($info){
				// 成功上传后 获取上传信息
				$data['url']='/uploads/'.$location.'/'.str_replace('\\','/',$info->getSaveName());
				$data['original']='/uploads/'.$location.'/'.str_replace('\\','/',$info->getSaveName());
				$data['ext']=$info->getExtension();
				$data['size']=$info->getSize();
				$data['time']=time();
				$rs=Db::name('file')->insertGetId($data);
				if ($rs>0){
                    $msg['status'] =200;
                    $msg['url']=$data['url'];
					return $msg;
				}else{
					$msg['status']='0';
					//$msg['msg']='上传失败';
					return $msg;
				}
			}else{
				// 上传失败获取错误信息
				$msg['code']='0';
				//$msg['msg']=$file->getError();;
				return $msg;
			}
		}
	}
    /**
     * 查询文件信息
     * @param 文件id $file_id
     * @param 限制字段 $field
     * @return 一维数组
     */
	public function getInfo($file_id,$field='*'){
		return $this->name('file')->field($field)->where('file_id',$file_id)->find();
	}

    public function add($data=array()){
        return $this->save($data);
    }
}
