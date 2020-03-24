<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use \think\Db;
use \think\facade\Cache;
use \think\facade\Env;
// 应用公共文件
/**
 * 获取后台菜单多维数组
 * @param array $where
 * @return false|PDOStatement|string|\think\Collection
 */
function getMenuList($where = array('pid' => 0))
{
    $where['status'] = 1;
    $list_one = Db::name('admin_menu')->where($where)->order('sort ASC,id DESC')->select();
    if ($list_one) {
        foreach ($list_one as $key => $val) {
            $list_one[$key]['iconfont'] = '&' . $val['iconfont'];
            $list_two = Db::name('admin_menu')->where(array('pid' => $val['id'], 'status' => 1))->order('sort ASC,id DESC')->select();
            if ($list_two) {
                $list_one[$key]['sub'] = $list_two;
                foreach ($list_two as $k => $v) {
                    $list_one[$key]['sub'][$k]['iconfont'] = '&' . $v['iconfont'];
                    $list_three = Db::name('admin_menu')->where(array('pid' => $v['id'], 'status' => 1))->order('sort ASC,id DESC')->select();
                    if ($list_three) {
                        $list_one[$key]['sub'][$k]['sub'] = $list_three;
                        foreach ($list_three as $kk => $vv) {
                            $list_one[$key]['sub'][$k]['sub'][$kk]['iconfont'] = '&' . $vv['iconfont'];
                        }
                    }
                }
            }
        }
    }
    return $list_one;
}
/**
 * 获取所有模块Service
 * @param string $name 指定service名
 * @return array
 */
function get_all_service($name, $method, $vars = array())
{
    if (empty($name)) return null;
    $apiPath = Env::get('app_path') . 'admin/service/' . $name . '.php';
    //glob：获取当前文件路径
    $apiList = glob($apiPath);
    if (empty($apiList)) {
        return;
    }
    $appPathStr = strlen(Env::get('app_path'));
    $method = 'get' . $method . $name;      //getAdminMenu
    $data = array();
    $tmp = array();
    foreach ($apiList as $value) {
        $path = substr($value, $appPathStr, -4);
        $path = str_replace('\\', '/', $path);
        $appName = explode('/', $path);
        $appName = $appName[0];
        $config = load_config($appName . '/config');
        if (!empty($config['APP_SYSTEM']) && (!empty($config['APP_STATE']) || !empty($config['APP_INSTALL']))) {
            continue;
        }
        $class = model($appName . '/' . $name, 'service');
        if (method_exists($class, $method)) {
            if (!empty($class->$method($vars))) {
                $tmp = $class->$method($vars);  // model：service下的Menu，Menu->getAdminMenu
            }
        }
    }
    $data['data']['list'] = $tmp;
    $data['status'] = 200;
    return $data;
}
/**
 * 获取菜单权限
 * $menu 所有菜单
 * $menu_purview 权限菜单
 */
function get_menu_purview($menu, $menu_purview)
{
    //print_r($menu);
    //print_r($menu_purview);exit;
    $menu_purview_arr = explode(',', $menu_purview);
    if ($menu) {
        foreach ($menu as $key => $val) {//一级分类
            if (!in_array($val['id'], $menu_purview_arr)) {
                unset($menu[$key]);
            }
            if (!empty($val['sub'])) {
                foreach ($val['sub'] as $kk => $vv) {
                    if (!in_array($vv['id'], $menu_purview_arr)) {
                        unset($menu[$key]['sub'][$kk]);
                    }
                    if (!empty($vv['sub'])) {
                        foreach ($vv['sub'] as $kkk => $vvv) {
                            if (!in_array($vvv['id'], $menu_purview_arr)) {
                                unset($menu[$key]['sub'][$kk]['sub'][$kkk]);
                            }
                        }
                    }
                }
            }
        }
    }
    $tmp['data']['list'] = $menu;
    $tmp['status'] = 200;
    return $tmp;
}
/**
 * 获取页面类型
 */
function get_page_type()
{
    return array(
        'article' => array(
            'name' => '文章',
            'listType' => 1,
            'order' => 0,
        ),
        'page' => array(
            'name' => '页面',
            'listType' => 0,
            'order' => 0,
        ),
    );
}
/**
 * 获取指定模块Service
 * @param string $name 指定service名
 * @return Service
 */
function service($appName, $name, $method, $vars = array())
{
    $config = load_config($appName . '/config');
    if (!empty($config['APP_SYSTEM']) && (!empty($config['APP_STATE']) || !empty($config['APP_INSTALL']))) {
        return;
    }
    $class = model($appName . '/' . $name, 'service');
    if (method_exists($class, $method)) {
        return $class->$method($vars);
    }
}
/**
 * 读取模块配置
 * @param string $file 调用文件
 * @return array
 */
function load_config($file)
{
    $file = get_config_file($file);
    return require $file;
}
/**
 * 解析配置文件路径
 * @param string $file 文件路径或简写路径
 * @return dir
 */
function get_config_file($file)
{
    $name = $file;
    if (!is_file($file)) {
        $str = explode('/', $file);
        $strCount = count($str);
        switch ($strCount) {
            case 1:
                //$app = APP_NAME;
                $app = 'admin';
                $name = $str[0];
                break;
            case 2:
                $app = $str[0];
                $name = $str[1];
                break;
        }
        $app = strtolower($app);
        if (empty($app) && empty($file)) {
            throw new \Exception("Config '{$file}' not found'", 500);
        }
        $file = Env::get('app_path') . "{$app}/conf/{$name}.php";
        if (!file_exists($file)) {
            throw new \Exception("Config '{$file}' not found", 500);
        }
    }
    return $file;
}
/**
 * 读取模块配置
 * @param string $file 调用文件
 * @return array
 */
function load_controller($file)
{
    $file = get_controller_file($file);
    return require $file;
}
/**
 * 解析配置文件路径
 * @param string $file 文件路径或简写路径
 * @return dir
 */
function get_controller_file($file)
{
    $name = $file;
    if (!is_file($file)) {
        $str = explode('/', $file);
        $strCount = count($str);
        switch ($strCount) {
            case 1:
                //$app = APP_NAME;
                $app = 'admin';
                $name = $str[0];
                break;
            case 2:
                $app = $str[0];
                $name = $str[1];
                break;
        }
        $app = strtolower($app);
        if (empty($app) && empty($file)) {
            throw new \Exception("Controller '{$file}' not found'", 500);
        }
        $file = Env::get('app_path') . "{$app}/controller/{$name}.php";
        if (!file_exists($file)) {
            throw new \Exception("Controller '{$file}' not found", 500);
        }
    }
    return $file;
}
/**
 * 二维数组排序
 * @param array $array 排序的数组
 * @param string $key 排序主键
 * @param string $type 排序类型 asc|desc
 * @param bool $reset 是否返回原始主键
 * @return array
 */
function array_order($array, $key, $type = 'asc', $reset = false)
{
    if (empty($array) || !is_array($array)) {
        return $array;
    }
    foreach ($array as $k => $v) {
        $keysvalue[$k] = $v[$key];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    $i = 0;
    foreach ($keysvalue as $k => $v) {
        $i++;
        if ($reset) {
            $new_array[$k] = $array[$k];
        } else {
            $new_array[$i] = $array[$k];
        }
    }
    return $new_array;
}
//ajaxReturn返回json数据
function ajaxReturn($code, $msg = '操作成功', $url = '', $data = array(array('name' => 'paco', 'url' => 'snrunning.com')), $render = true)
{
    $tmp['status'] = $code;
    $tmp['msg'] = $msg;
    $tmp['url'] = $url;
    $tmp['data'] = $data;
    $tmp['render'] = $render;
    return json($tmp);
}
//获取ip
function get_client_ip()
{
    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
        $ip = getenv("REMOTE_ADDR");
    else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
        $ip = $_SERVER['REMOTE_ADDR'];
    else
        $ip = "unknown";
    return $ip;
}
/**
 * 调用指定模块的API
 * @param string $name 指定api名
 * @return Api
 */
function api($appName, $name, $method, $vars = array())
{
    header("Content-type: text/html; charset=utf-8");
    $config = load_config($appName . '/config');
    if (!$config['APP_SYSTEM'] && (!$config['APP_STATE'] || !$config['APP_INSTALL'])) {
        return;
    }
    $class = model($appName . '/' . $name, 'api');
    if (method_exists($class, $method)) {
        return $class->$method($vars);
    }
}
/**
 * 数据签名认证
 * @param  array $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data)
{
    //数据类型检测
    if (!is_array($data)) {
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}
/**
 * html代码输入
 */
function html_in($str)
{
    $str = htmlspecialchars($str);
    if (!get_magic_quotes_gpc()) {
        $str = addslashes($str);
    }
    return $str;
}
/**
 * html代码输出
 */
function html_out($str)
{
    if (function_exists('htmlspecialchars_decode')) {
        $str = htmlspecialchars_decode($str);
    } else {
        $str = html_entity_decode($str);
    }
    $str = stripslashes($str);
    return $str;
}
/**
 * 站点设置信息调取
 */
function get_site($name = 'site_title')
{
    $config_info = model('admin/Config')->getInfo();
    return $config_info[$name];
}
/**
 * 删除目录及目录下所有文件或删除指定文件
 * @param str $path 待删除目录路径
 * @param int $delDir 是否删除目录，1或true删除目录，0或false则只删除文件保留目录（包含子目录）
 * @return bool 返回删除状态
 */
function delDirAndFile($path, $delDir = FALSE)
{
    $handle = opendir($path);
    if ($handle) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..")
                is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
        }
        closedir($handle);
        if ($delDir)
            return rmdir($path);
    } else {
        if (file_exists($path)) {
            return unlink($path);
        } else {
            return FALSE;
        }
    }
}
/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名
 */
function convert_arr_key($arr, $key_name)
{
    $arr2 = array();
    foreach ($arr as $key => $val) {
        $arr2[$val[$key_name]] = $val;
    }
    return $arr2;
}
/**
 * @param $arr
 * @param $key_name
 * @param $key_name2
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名 数组指定列为元素 的一个数组
 */
function get_id_val($arr, $key_name, $key_name2)
{
    $arr2 = array();
    foreach ($arr as $key => $val) {
        $arr2[$val[$key_name]] = $val[$key_name2];
    }
    return $arr2;
}
//php获取中文字符拼音首字母
function getFirstCharter($str)
{
    if (empty($str)) {
        return '';
    }
    $fchar = ord($str{0});
    if ($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str{0});
    $s1 = iconv('UTF-8', 'gbk', $str);
    $s2 = iconv('gbk', 'UTF-8', $s1);
    $s = $s2 == $str ? $s1 : $str;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284) return 'A';
    if ($asc >= -20283 && $asc <= -19776) return 'B';
    if ($asc >= -19775 && $asc <= -19219) return 'C';
    if ($asc >= -19218 && $asc <= -18711) return 'D';
    if ($asc >= -18710 && $asc <= -18527) return 'E';
    if ($asc >= -18526 && $asc <= -18240) return 'F';
    if ($asc >= -18239 && $asc <= -17923) return 'G';
    if ($asc >= -17922 && $asc <= -17418) return 'H';
    if ($asc >= -17417 && $asc <= -16475) return 'J';
    if ($asc >= -16474 && $asc <= -16213) return 'K';
    if ($asc >= -16212 && $asc <= -15641) return 'L';
    if ($asc >= -15640 && $asc <= -15166) return 'M';
    if ($asc >= -15165 && $asc <= -14923) return 'N';
    if ($asc >= -14922 && $asc <= -14915) return 'O';
    if ($asc >= -14914 && $asc <= -14631) return 'P';
    if ($asc >= -14630 && $asc <= -14150) return 'Q';
    if ($asc >= -14149 && $asc <= -14091) return 'R';
    if ($asc >= -14090 && $asc <= -13319) return 'S';
    if ($asc >= -13318 && $asc <= -12839) return 'T';
    if ($asc >= -12838 && $asc <= -12557) return 'W';
    if ($asc >= -12556 && $asc <= -11848) return 'X';
    if ($asc >= -11847 && $asc <= -11056) return 'Y';
    if ($asc >= -11055 && $asc <= -10247) return 'Z';
    return null;
}
/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getCatGrandson2($cat_id)
{
    $GLOBALS['catGrandson'][] = $cat_id;
    foreach ($GLOBALS['category_id_arr'] as $k => $v) {
        // 找到孙子
        if ($v == $cat_id) {
            getCatGrandson2($k); // 继续找孙子
        }
    }
}
/**
 * 多个数组的笛卡尔积
 *
 * @param unknown_type $data
 */
function combineDika()
{
    $data = func_get_args();
    $data = current($data);
    $cnt = count($data);
    $result = array();
    $arr1 = array_shift($data);
    foreach ($arr1 as $key => $item) {
        $result[] = array($item);
    }
    foreach ($data as $key => $item) {
        $result = combineArray($result, $item);
    }
    return $result;
}
/**
 * 两个数组的笛卡尔积
 * @param unknown_type $arr1
 * @param unknown_type $arr2
 */
function combineArray($arr1, $arr2)
{
    $result = array();
    foreach ($arr1 as $item1) {
        foreach ($arr2 as $item2) {
            $temp = $item1;
            $temp[] = $item2;
            $result[] = $temp;
        }
    }
    return $result;
}
/**
 * 将二维数组以元素的某个值作为键 并归类数组
 * array( array('name'=>'aa','type'=>'pay'), array('name'=>'cc','type'=>'pay') )
 * array('pay'=>array( array('name'=>'aa','type'=>'pay') , array('name'=>'cc','type'=>'pay') ))
 * @param $arr 数组
 * @param $key 分组值的key
 * @return array
 */
function group_same_key($arr, $key)
{
    $new_arr = array();
    foreach ($arr as $k => $v) {
        $new_arr[$v[$key]][] = $v;
    }
    return $new_arr;
}
/**
 * 过滤数组元素前后空格 (支持多维数组)
 * @param $array 要过滤的数组
 * @return array|string
 */
function trim_array_element($array)
{
    if (!is_array($array))
        return trim($array);
    return array_map('trim_array_element', $array);
}
/**
 * 获取url 中的各个参数  类似于 pay_code=alipay&bank_code=ICBC-DEBIT
 * @param type $str
 * @return type
 */
function parse_url_param($str)
{
    $data = array();
    $parameter = explode('&', end(explode('?', $str)));
    foreach ($parameter as $val) {
        $tmp = explode('=', $val);
        $data[$tmp[0]] = $tmp[1];
    }
    return $data;
}
/**
 * 中文字符串截取
 */
function len($str, $len = 0)
{
    if (!empty($len)) {
        return \org\Util::msubstr($str, 0, $len);
    } else {
        return $str;
    }
}
/**
 * 获取插件配置
 */
function get_plug_info($name = 'weixin', $type = 'payment')
{
    $where['code'] = ['eq', $name];
    $where['type'] = ['eq', $type];
    $paymentPlugin = \think\Db::name('plugin')->where($where)->find(); // 找到微信支付插件的配置
    $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化
    return $config_value;
}
/**
 * 写文件
 */
function write_text($content, $payType = 'weixin')
{
    $path = $payType . "_log/";
    if (!is_dir($path)) {
        mkdir($path, 0777);  // 创建文件夹test,并给777的权限（所有权限）
    }
    $file = $path . "weixin_log.txt";    // 写入的文件
    $fp = fopen($file, 'a+b');
    fwrite($fp, var_export($content, true));
    //$b = file_get_contents('php://input');
    //$postObj = simplexml_load_string($b, 'SimpleXMLElement', LIBXML_NOCDATA); //解析返回的xml
//$content = $_REQUEST;  // 写入的内容
//var_dump($content);
//fwrite($fp, var_export($postObj->out_trade_no, true));
//fwrite($fp, var_export($postObj->out_trade_no, true));
}
/**
 * 写文件
 */
function write_lang_file($file)
{
    $content = <<<EOF
<?php
/**
 * 语言
 */
return [
    "test" => "测试语言",
];
EOF;
    file_put_contents($file, $content);
}
/**
 * 获取语言信息
 */
function get_lang_info($lang_id, $field = '*')
{
    if (empty($lang_id)) {
        return;
    }
    $info = Db::name('lang')->field($field)->where('lang_id', $lang_id)->find();
    if ($field != '*') {
        return $info[$field];
    } else {
        return $info;
    }
}
/**
 * 获取当前语言id
 */
function get_lang_id()
{
    if (cookie('think_var')) {
        $where['lang'] = cookie('think_var');
        $info = model('admin/lang')->getWhereInfo($where);
        if ($info) {
            return $info['lang_id'];
        }
    }
    return false;
}
/**
 * 获取当前微信id
 */
function get_weichat_id()
{
    if (session('admin.weichat_id')) {
        $rs = session('admin.weichat_id');
    } else {
        $info = Db::name('weichat')->where('is_bind', 1)->find();
        $rs = $info['weichat_id'];
    }
    return $rs;
}
/**
 * 自适应URL规则
 * @param string $str URL路径
 * @param string $params 自动解析参数
 * @param string $mustParams 必要参数
 * @return url
 */
function match_url($str, $params = array(), $mustParams = array())
{
    $newParams = array();
    $keyArray = array_keys($params);
    if (config('REWRITE_ON')) {
        //获取规则文件
        $config = config('REWRITE_RULE');
        $configArray = array_flip($config);
        $route = $configArray[$str];
        if ($route) {
            preg_match_all('/<(\w+)>/', $route, $matches);
            foreach ($matches[1] as $value) {
                if ($params[$value]) {
                    $newParams[$value] = $params[$value];
                }
            }
        } else {
            if (!empty($keyArray)) {
                $newParams[$keyArray[0]] = current($params);
            }
        }
    } else {
        if (!empty($keyArray)) {
            $newParams[$keyArray[0]] = current($params);
        }
    }
    //语言
    $lang_arr = array();
    /*if (get_lang_id()){
        $lang_arr=array('lang_id'=>get_lang_id());
    }*/
    $newParams = array_merge((array)$newParams, (array)$mustParams, (array)$lang_arr);
    $newParams = array_filter($newParams);
    return url($str, $newParams);
}
function get_table_last_id($table = 'weichat_material_news')
{
    //查询最后一条数据主键设置为分组
    $last_info = Db::query("SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_name='" . config('database.prefix') . "$table'");
    return $last_info[0]['AUTO_INCREMENT'];
}
/*******************************微信相关开始**********************************/
/**
 * 获取当前微信配置信息
 */
function get_weichat_options()
{
//    Cache::clear('wechat');die;
    if (Cache::tag('wechat')->get('wx_option')) {
        $wx_option = Cache::tag('wechat')->get('wx_option');
    } else {
        $where[] = ['weichat_id', '=', 3];
        $weichat_info = Db::name('weichat')->where($where)->find();
        $wx_option = array(
            'token' => $weichat_info['token'], //填写你设定的key
            'encodingaeskey' => $weichat_info['encodingaeskey'], //填写加密用的EncodingAESKey
            'appid' => $weichat_info['appid'], //填写高级调用功能的app id, 请在微信开发模式后台查询
            'appsecret' => $weichat_info['secret'] //填写高级调用功能的密钥
        );
        Cache::tag('wechat')->set('wx_option', $wx_option);
    }
    return $wx_option;
}
/**
 * 获取微信配置信息
 * $weichat_id  微信配置id
 */
function weichat_info($weichat_id)
{
    if (empty($weichat_id)) {
        return;
    }
    return Db::name('wechat')->where('weichat_id', $weichat_id)->find();
}
// 防超时的file_get_contents改造函数
function wp_file_get_contents($url)
{
    $context = stream_context_create(array(
        'https' => array(
            'timeout' => 30
        )
    )); // 超时时间，单位为秒
    return file_get_contents($url, 0, $context);
}
// 创建多级目录
function mkdirs($dir)
{
    if (!is_dir($dir)) {
        if (!mkdirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777)) {
            return false;
        }
    }
    return true;
}
// 全局的安全过滤函数
function safe($text, $type = 'html')
{
    // 无标签格式
    $text_tags = '';
    // 只保留链接
    $link_tags = '<a>';
    // 只保留图片
    $image_tags = '<img>';
    // 只存在字体样式
    $font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
    // 标题摘要基本格式
    $base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike><section><header><footer><article><nav><audio><video>';
    // 兼容Form格式
    $form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
    // 内容等允许HTML的格式
    $html_tags = $base_tags . '<meta><ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
    // 全HTML格式
    $all_tags = $form_tags . $html_tags . '<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
    // 过滤标签
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = strip_tags($text, ${$type . '_tags'});
    // 过滤攻击代码
    if ($type != 'all') {
        // 过滤危险的属性，如：过滤on事件lang js
        while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat [0], $mat [1] . $mat [3], $text);
        }
        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_ireplace($mat [0], $mat [1] . $mat [3], $text);
        }
    }
    return $text;
}
/*******************************微信相关结束**********************************/
/*******************************验证规则开始**********************************/
function is_url($str)
{
    return preg_match("/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\’:+!]*([^<>\"])*$/", $str);
}
/*******************************验证规则结束**********************************/
//微信各种方法汇总
/**
 * GET 请求
 * @param string $url
 */
function http_get($url)
{
    $oCurl = curl_init();
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
    curl_setopt($oCurl, CURLOPT_HEADER, 1);
    // $sContent = curl_exec($oCurl);
    // $aStatus = curl_getinfo($oCurl);
    $sContent = execCURL($oCurl);
    curl_close($oCurl);
    return $sContent;
}
/**
 * POST 请求
 * @param string $url
 * @param array $param
 * @param boolean $post_file 是否文件上传
 * @return string content
 */
function http_post($url, $param, $post_file = false)
{
    $oCurl = curl_init();
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    if (PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')) {
        $is_curlFile = true;
    } else {
        $is_curlFile = false;
        if (defined('CURLOPT_SAFE_UPLOAD')) {
            curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
        }
    }
    if ($post_file) {
        if ($is_curlFile) {
            foreach ($param as $key => $val) {
                if (isset($val["tmp_name"])) {
                    $param[$key] = new \CURLFile(realpath($val["tmp_name"]), $val["type"], $val["name"]);
                } else if (substr($val, 0, 1) == '@') {
                    $param[$key] = new \CURLFile(realpath(substr($val, 1)));
                }
            }
        }
        $strPOST = $param;
    } else {
        $strPOST = json_encode($param);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($oCurl, CURLOPT_POST, true);
    curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
    curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
    curl_setopt($oCurl, CURLOPT_HEADER, 1);
    // $sContent = curl_exec($oCurl);
    // $aStatus  = curl_getinfo($oCurl);
    $sContent = execCURL($oCurl);
    curl_close($oCurl);
    return $sContent;
}
function PostWeixin($url, $data_string = '')
{
    $header = "Content-type: text/html";//定义content-type为xml
    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $url);//设置链接
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);//POST数据
    $response = curl_exec($ch);//接收返回信息
    if (curl_errno($ch)) {//出错则显示错误信息
        //print curl_error($ch);
        return 'Error';
    }
    curl_close($ch); //关闭curl链接
    return $response;//显示返回信息
}
/**
 * 执行CURL请求，并封装返回对象
 */
function execCURL($ch)
{
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $result = array('header' => '',
        'content' => '',
        'curl_error' => '',
        'http_code' => '',
        'last_url' => '');
    if ($error != "") {
        $result['curl_error'] = $error;
        return $result;
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $result['header'] = str_replace(array("\r\n", "\r", "\n"), "<br/>", substr($response, 0, $header_size));
    $result['content'] = substr($response, $header_size);
    $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result['last_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $result["base_resp"] = array();
    $result["base_resp"]["ret"]     = $result['http_code'] == 200 ? 0 : $result['http_code'];
    $result["base_resp"]["err_msg"] = $result['http_code'] == 200 ? "ok" : $result["curl_error"];
    return $result;
}
function postJson($url, $data = NULL, $json = false)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($data)) {
        if ($json && is_array($data)) {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if ($json) { //发送JSON数据
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_HTTPHEADER,
                array('Content-Type: application/json; charset=utf-8', 'Content-Length:' . strlen($data)));
        }
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $res     = curl_exec($curl);
    $errorno = curl_errno($curl);
    if ($errorno) {
        return array('errorno' => false, 'errmsg' => $errorno);
    }
    curl_close($curl);
    return json_decode($res, true);
}
//给URL地址追加参数
function appendParamter($url, $key, $value)
{
    return strrpos($url, "?", 0) > -1 ? "$url&$key=$value" : "$url?$key=$value";
}
//生成指定长度的随机字符串
function createNonceStr($length = 16)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str   = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}
//读取本地文件
function get_php_file($filename)
{
    if (file_exists($filename)) {
        return trim(substr(file_get_contents($filename), 15));
    } else {
        return '{"expire_time":0}';
    }
}
//写入本地文件
function set_php_file($filename, $content)
{
    $fp = fopen($filename, "w");
    fwrite($fp, "<?php exit();?>" . $content);
    fclose($fp);
}
//加载本地的应用配置文件
function loadConfig()
{
    return json_decode(get_php_file("../config.php"));
}
//根据应用ID获取应用配置
function getConfigByAgentId($id)
{
    $configs = loadConfig();
    foreach ($configs->AppsConfig as $key => $value) {
        if ($value->AgentId == $id) {
            $config = $value;
            break;
        }
    }
    return $config;
}
/**
 * 计算两点地理坐标之间的距离
 * @param Decimal $longitude1 起点经度
 * @param Decimal $latitude1 起点纬度
 * @param Decimal $longitude2 终点经
 * @param Decimal $latitude2 终点纬度
 * @param Int $unit 单位 1:米 2:公里
 * @param Int $decimal 精度 保留小数位数
 * @return Decimal
 */
function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
{
    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI           = 3.1415926;
    $radLat1      = $latitude1 * $PI / 180.0;
    $radLat2      = $latitude2 * $PI / 180.0;
    $radLng1      = $longitude1 * $PI / 180.0;
    $radLng2      = $longitude2 * $PI / 180.0;
    $a            = $radLat1 - $radLat2;
    $b            = $radLng1 - $radLng2;
    $distance     = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance     = $distance * $EARTH_RADIUS * 1000;
    if ($unit == 2) {
        $distance = $distance / 1000;
    }
    return round($distance, $decimal);
}
/**
 *时间格式化输出
 */
function to_date($timestamp, $format = 'Y-m-d H:i:s')
{
    if (is_numeric($timestamp)) {
        return date($format, $timestamp);
    } else {
        return $timestamp;
    }
}
//获取指定长度随机字符串
function GetRandStr($length = 16)
{
    //'!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
    $chars      = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $random_str = '';
    for ($i = 0; $i < $length; $i++) {
        //这里提供两种字符获取方式
        //第一种是使用 substr 截取$chars中的任意一位字符；
        //第二种是取字符数组 $chars 的任意元素
        //$password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        $random_str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $random_str;
}
/**
 * 展示提示信息并关闭窗口
 * @param  string $msg 提示内容
 */
function closeWindowMsg($msg = '')
{
    echo "<script>setTimeout(function(){WeixinJSBridge.call('closeWindow');},1100);alert('" . $msg . "');</script>";
}
/**
 * 展示提示信息并关闭窗口
 * @param  string $msg 提示内容
 */
function smallroutineinfo()
{
    return array('appid' => 'wx4e0d9ecfa67da4fd', 'appsecret' => 'd13c35dbe37d9c9f543fdcd3d154b8be');
}
function smallaccesstoken()
{ 
    $wx_option = smallroutineinfo();
    $cachename = 'small_access_token' . $wx_option['appid'];
    if (file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . '/' . $cachename . '.json')) {
        $arr = file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/' . $cachename . '.json');
        $arr = json_decode($arr, true);
        if (isset($arr['token'])) {
            if (time() -$arr['expired'] < 3600) {
                return $arr['token'];
            }else{
               return  GetWxtoken();
            }
        }
    }
    return  GetWxtoken();
}
function GetWxtoken(){
    $wx_option = smallroutineinfo();
    $cachename = 'small_access_token' . $wx_option['appid'];
    $result = http_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $wx_option['appid'] . '&secret=' . $wx_option['appsecret']);
    if (!empty($result['content'])) {
        $json = json_decode($result['content'], true);
        if (!$json || empty($json['access_token'])) {
            return false;
        }
        $access_token   = $json['access_token'];
        $arr            = array();
        $arr['token']   = $access_token;
        $arr['expired'] = time();
        file_put_contents(dirname($_SERVER['DOCUMENT_ROOT']) . '/' . $cachename . '.json', json_encode($arr));
        return $access_token;
    } else {
        return false;
    }
}