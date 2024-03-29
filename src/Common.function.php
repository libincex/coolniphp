<?php
/*//=================================
//
//	快捷函数 [更新时间: 2022-05-30]
//
//===================================*/


//配置函数
//==================================

/**
 * 取得或设置Config项内容
 * @param string $name
 * @param null $value
 * @return array|bool|mixed|null
 */
function C(string $name, $value = NULL)
{
    $name = trim($name);
    if (empty($name)) {
        return NULL;
    }
    if (isset($value)) {
        return Config::set($name, $value); //设置
    } else {
        return Config::get($name); //获取
    }
}

/**
 * 添加自动载入路径
 * @param string $libPath
 * @return array|bool|mixed|null
 */
function addLibPath(string $libPath)
{
    //获取
    $libPathArr = C('libPath');
    //添加
    !in_array($libPath, $libPathArr) && $libPathArr[] = $libPath;
    //保存
    return C('libPath', array_unique($libPathArr));
}

//路由函数
//==================================

/**
 * 快捷url,生成一个完整的url
 * 特殊标识 '{M}','{C}','{A}' 分别代表当前的 module,controller,action
 * @param string $uri
 * @return string
 */
function URL(string $uri = '')
{
    $mc = CLN_MODULE . '/' . CLN_CONTROLLER;
    $mca = CLN_MODULE . '/' . CLN_CONTROLLER . '/' . CLN_ACTION;
    //不区分大小写
    $uri = str_replace(array(
        '{M}', '{C}', '{A}', '{MC}', '{MCA}',
        '{m}', '{c}', '{a}', '{mc}', '{mca}',
    ), array(
        CLN_MODULE, CLN_CONTROLLER, CLN_ACTION, $mc, $mca,
        CLN_MODULE, CLN_CONTROLLER, CLN_ACTION, $mc, $mca,
    ), trim($uri));

    $uri{0} != '/' && $uri = '/' . $uri;

    return CLN_BASE_URL . $uri; //CLI工作模式中，入口文件与参数之间有空格
}

/**
 * 生成带参数的url, 可以传入参数
 * (如果参数的值为空empty(): 则会被过滤,不会生成在url中)
 * @param string $uri
 * @param array $params
 * @return string
 */
function pURL(string $uri = '', array $params = [])
{
    if (empty($uri)) {
        $uri = implode('/', [
            CLN_MODULE,
            CLN_CONTROLLER,
            CLN_ACTION
        ]);
    }
    $uri{0} != '/' && $uri = '/' . $uri;
    if (is_array($params) && !empty($params)) {
        foreach ($params as $key => $val) {
            if (!empty($key) && !empty($val)) {
                $uri .= "/$key/$val";
            }
        }
    }

    return CLN_BASE_URL . $uri;
}


/**
 * 静态文件url, 生成一个静态文件完整的url
 * @param string $uri
 * @return string
 */
function sURL(string $uri = '')
{
    $uri = trim($uri);
    if (empty($uri)) {
        return '';
    }

    //容错处理(如果传入url是完整路径，则直接返回)
    if (strtolower(substr($uri, 0, 7)) == 'http://' || strtolower(substr($uri, 0, 8)) == 'https://') {
        return $uri;
    }

    $uri{0} != '/' && $uri = '/' . $uri;
    $sUrl = C('route.sUrl');

    return $sUrl[array_rand($sUrl)] . $uri;
}

/**
 * 上传的文件的url
 * @param string $uri
 * @return string
 */
function fURL(string $uri = '')
{
    return File::url($uri);
}

/**
 * url跳转
 * @param string $url
 * @param bool $is301 是否永久跳转到指定网址
 */
function gourl(string $url, $is301 = false)
{
    $url = trim($url);
    if ($is301) {
        $httpcode = 301;
        $js = "window.replace('{$url}')";
    } else {
        $httpcode = 302;
        $js = "window.href('{$url}')";
    }
    header("Location: {$url}", TRUE, $httpcode);
    exit("<script language='javascript'>{$js};</script>");
}


//数据库操作
//==================================

/**
 * 数据表操作对象
 * @param string $table
 * @param string $as
 * @return mixed
 */
function T(string $table, string $as = '')
{
    return DB::init()->table($table, $as);
}

/**
 * 取得当前数据库中的表实例(适用于带前缀的表操作)
 * @param string $table
 * @param string $as
 * @return mixed
 */
function PT(string $table, string $as = '')
{
    return DB::init()->ptable($table, $as);
}

/**
 * mysql的sql查询操作
 * @param $sqlArr
 * @return mixed
 */
function mysqlQuery($sqlArr)
{
    return DB::init()->query($sqlArr);
}

/**
 * mysql的更新操作(非select)
 * @param $sqlArr
 * @return mixed
 */
function mysqlRun($sqlArr)
{
    return DB::init()->run($sqlArr);
}

//视图函数
//==================================

/**
 * 载入模板,并取得模板渲染之后的内容
 * @param string $tplFile 子模板文件
 * @param array $tplData 传入子模板的数据(数组: ['变量名'=>'值'])
 * @param bool $isDisplay 是否显示,默认为true,如果为false则仅返回渲染的模板内容
 * @return string|void
 */
function tpl(string $tplFile, array $tplData = [], $isDisplay = true)
{
    return View::tpl($tplFile, $tplData, $isDisplay);
}

/**
 * 载入静态文件,js
 * @param $jsUrl
 */
function js($jsUrl)
{
    View::loadJS($jsUrl);
}

/**
 * 载入静态文件css
 * @param $cssUrl
 */
function css($cssUrl)
{
    View::loadCSS($cssUrl);
}

//缓存相关
//====================

/**
 * 取得或设置缓存内容
 * @param string $key
 * @param null $value
 * @param int $time
 * @return mixed|null
 */
function Cache(string $key, $value = NULL, int $time = 0)
{
    $key = trim($key);
    if (empty($key)) {
        return NULL;
    }
    if (isset($value)) {
        return Cache::set($key, $value, $time); //设置
    } else {
        return Cache::get($key); //获取
    }
}

//调试函数
//==================================

/**
 * 写入调试信息
 * @param string $key
 * @param $value
 */
function Debug(string $key, $value)
{
    Debug::put($key, $value);
}

//session相关
//==================================

/**
 * 获取/设置 session
 * @param string $key
 * @param null $val
 * @return array|mixed
 */
function S(string $key, $val = NULL)
{
    if (isset($val)) {
        return Session::set($key, $val);
    } else {
        return Session::get($key);
    }
}

//加密解密相关
//================================

/**
 * 文本数据加密
 * @param $val
 * @param int $expiry 密文有效期, 加密时候有效秒，0为永久有效
 * @param string $key 指定用于解密的key(默认为AppID参数)
 * @return string
 */
function SEncode($val, int $expiry = 0, string $key = '')
{
    empty($key) && $key = CLN_ID;
    return Security::AuthCode(serialize($val), 'ENCODE', $key, $expiry);
}


/**
 * 文本数据解密
 * @param string $text 密文
 * @param string $key 指定用于解密的key(默认为AppID参数)
 * @return mixed
 */
function SDecode(string $text, string $key = '')
{
    empty($key) && $key = CLN_ID;
    return unserialize(Security::AuthCode(trim($text), 'DECODE', $key));
}

/**
 * 新base64 编码, 可以安全用于url
 * @param string $str
 * @return mixed
 */
function Base64Encode(string $str)
{
    //加密时将 +号转为-号，/号转成_号, 以便于在url中传输
    return str_replace(['+', '/'], ['-', '_'], trim(base64_encode($str), '='));
}


/**
 * 新base64 解码, 可以安全用于url
 * @param string $str
 * @return bool|string
 */
function Base64Decode(string $str)
{
    //解密时将 -号转回+号，_号转回/
    return base64_decode(str_replace(array('-', '_'), array('+', '/'), $str));
}


/**
 * 短md5(16个字符,一般用于缓存时需要生成较短的key名)
 * @param string $str
 * @return bool|string
 */
function md5_16(string $str)
{
    return substr(md5($str), 8, 16);
}

//输入输出数据处理
//=====================

/**
 * 将经过HTML编码的特殊字符转换回原字符
 *
 * @param string $text
 * @param int $type 1 将特殊字符转换为HTML编码, -1 将HTML编码的特殊字符转换回字符(默认)
 * @return array
 */
function Html(string $text, int $type = -1)
{
    $type = (int)$type;
    !in_array($type, array(-1, 1)) && $type = -1;

    return Security::html($text, $type);
}


/**
 * 返回json数据结果, 然后停止程序执行, 相当于 echo json_encode($data); exit; 的简写。
 * 兼容跨域的jsonp操作( 如果传入的参数中有”callback”,则以jsonp输出数据 ), 可以用于api模式的接口快速输出数据
 * @param $data
 */
function JD($data)
{
    $jsonp = trim(Router::data('callback'));

    ob_end_clean();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    exit(empty($jsonp) ? $json : "{$jsonp}({$json})");
}

/**
 * 对一组数据进行批量处理，更适合批量规划
 * 格式化数组, 类型: 'int','text'
 * $formatList = [
 * '类型名称'=>[key1,key3,...], //将指定的一批key按规定的类型进行格式化 (或格式: 'key1,key3,....'
 * 'int' => 'key2,key4,*', //将'key2'和'key4'做为'int'处理,将没有明确指出类型的数据项 全部转成 'int'
 * 'text'=>'key3,key4', //批量将所有字段都做为“text”处理(除显示指定的'key1'与'key2'之外)
 * 'float'=>'key5,key6', //将'key5'和'key6'做为'float'处理
 * @param array $formatList
 * @param array $data
 * @return array|null
 */
function DV(array $formatList = [], array $data = [])
{
    if (empty($data)) {
        $data = Router::data();
        /*
        $post = [];
        $get = [];
        parse_str(trim(file_get_contents("php://input")), $post);
        $query_string = trim(empty($_SERVER['QUERY_STRING']) ? $_SERVER['query_string'] : $_SERVER['QUERY_STRING']);
        parse_str($query_string, $get);
        $data = array_merge($get, $post);

        empty($data) && $data = $_REQUEST;
        */
    }

    if (empty($formatList) || !is_array($formatList)) {
        return $data;
    }

    //数据项对应类型
    $df = [];
    foreach ($formatList as $key => $val) {
        $key = trim(strtolower($key));
        if (!is_array($val)) {
            $val = explode(',', $val);
            $val = array_map('trim', $val);
        }
        foreach ($val as $v) {
            if (empty($v)) {
                continue;
            }

            $df[$v] = $key;
        }
    }

    //处理星号* 的批量指定类型
    if (isset($df['*'])) {
        foreach ($data as $k => $d) {
            if (!isset($df[$k])) {
                $df[$k] = $df['*'];
            }
        }
    }
    unset($df['*']);

    //格式化数据
    $redata = [];
    foreach ($df as $v => $f) {
        switch ($f) {
            //整数 123345
            case 'int':
                $redata[$v] = intval($data[$v]);
                break;
            //浮点数 12345.6789
            case 'float':
                $redata[$v] = floatval($data[$v]);
                break;
            //钱 1050.50
            case 'money':
                $redata[$v] = number_format((float)$data[$v], 2, '.', '');
                break;
            //日期
            case 'date':
                $num = strtotime($data[$v]);
                $redata[$v] = $num ? date('Y-m-d', $num) : '';
                break;
            //日期时间
            case 'datetime':
                $num = strtotime($data[$v]);
                $redata[$v] = $num ? date('Y-m-d H:i:s', $num) : '';
                break;
            //时间
            case 'time':
                $num = strtotime($data[$v]);
                $redata[$v] = $num ? date('H:i:s', $num) : '';
                break;
            //时间戳
            case 'timestamp':
                $redata[$v] = (int)strtotime($v);
                break;
            //数组
            case 'arr':
            case 'array':
                if (!is_array($data[$v])) {
                    $data[$v] = empty($data[$v]) ? [] : [$data[$v]];
                }
                $redata[$v] = $data[$v];
                break;
            //主键ID列表(整型,以逗号分隔) ，返回数组型
            case 'ids':
                is_array($data[$v]) && $data[$v] = implode(',', $data[$v]);
                $redata[$v] = array_unique(array_filter(array_map('intval', explode(',', $data[$v]))));
                break;
            //主键ID列表(整型,以逗号分隔), 返回字符串型
            case 'idlist':
                is_array($data[$v]) && $data[$v] = implode(',', $data[$v]);
                $redata[$v] = array_unique(array_filter(array_map('intval', explode(',', $data[$v]))));
                $redata[$v] = implode(',', $redata[$v]);
                break;
            //json串
            case 'json':
                $redata[$v] = json_decode(trim($data[$v]), 1);
                break;
            //序列化串
            case 'serialize':
                $redata[$v] = unserialize(trim($data[$v]));
                break;
            //允许包含html代码的字符串
            case 'html':
                $redata[$v] = trim($data[$v]);
                break;
            //默认 text,将过滤掉html标签
            default :
                $redata[$v] = strip_tags(trim($data[$v]));
        }
    }

    return $redata;
}


//多语言函数
//=====================

/**
 * 多语言文本转换(带模板变量功能)
 * @param string $string 文本内容
 * @param array $tplData 文本内容模板中的变量数据
 * @return string
 * 示例：
 * echo L('你好,{name}!,电话:{phone}。', array(
 * 'name' => 'CEX',
 * 'phone' => '13312345678',
 * ));
 */
function L(string $string, array $tplData = [])
{
    return Language::translate($string, $tplData);
}


//工具函数
//=====================


/**
 * 将字符串编码格式转成utf8
 * @param string $str
 * @return string
 */
function toUTF8(string $str)
{
    return mb_convert_encoding($str, 'UTF-8', 'ASCII,UTF-8,GBK,ISO-8859-1');
}


/**
 * ysql搜索值处理, 转义 _, %
 * @param $value
 * @return mixed
 */
function mysqlSearchValue($value)
{
    $value = str_replace([
        '_', '%'
    ], [
        '\_', '\%',
    ], $value);

    return $value;
}

/**
 * 对二维数组排序(支持多字段排序)
 * array|string $sortRule 排序规则:
 * $sortRule = array('key1'=>'desc','key2'=>'asc',...); //数组
 * $sortRule = 'key1 desc,key2 asc,...'; //类SQL写法
 * @param array $rs
 * @param $sortRule
 * @return array
 */
function arraySort(array $rs, $sortRule)
{
    if (empty($rs) || !is_array($rs)) {
        return $rs; //原样返回
    }

    //规则处理
    if (!is_array($sortRule)) {
        $arr = [];
        preg_match_all('/([^,^\s]+)\s+(desc|asc)/is', trim($sortRule), $arr);

        $sortRule = [];
        foreach ($arr[1] as $key => $val) {
            $sortRule[trim($val)] = trim($arr[2][$key]);
        }
    }
    if (empty($sortRule)) {
        return $rs; //原样返回
    }

    //从排序规则中取出一组(排序的键名 ，排序方式：正序|倒序)
    $key = key($sortRule);
    $sort = strtolower(array_shift($sortRule));

    //组织数据
    $data = []; //数据容器
    foreach ($rs as $index => $r) {
        $data[$r[$key]][$index] = $r;
    }

    //排序
    switch ($sort) {
        case 'asc': //升序
            ksort($data);
            break;
        case 'desc': //降序
            krsort($data);
            break;
    }

    //数据处理
    $rs = [];
    foreach ($data as $index => $d) {
        if (count($d) > 1 && !empty($sortRule)) {
            //递归处理二级排序
            $self = __FUNCTION__;
            $d = $self($d, $sortRule);
        }

        foreach ($d as $k => $r) {
            $rs[$k] = $r;
        }
    }

    return $rs;
}

/**
 * 从数组中提出多列组成新的数组(函数array_column的改进版)
 * @param array $arr 二维数组
 * @param $column 字段列表(支持字符串列表和数组列表)，
 * @param string $index 指定做为新数据索引的字段值(默认为原数组的索引值)
 * @return array
 */
function arrayColumn(array $arr, $column, string $index = '')
{
    !is_array($column) && $column = array_filter(array_map('trim', explode(',', $column)));
    if (!is_array($arr) || empty($arr) || empty($column)) {
        return $arr;
    }
    $index = trim($index);

    $data = [];
    foreach ($arr as $key => $val) {
        if (!is_array($val)) {
            $data[$key] = $val;
            continue;
        }

        //提取列数据
        $r = [];
        foreach ($column as $c) {
            $r[$c] = $val[$c];
        }
        //取得索引值
        $k = empty($index) ? $key : $val[$index];

        $data[$k] = $r;
    }

    return $data;
}

/**
 * 从数组中提取keys列表对应的元素，组成新的数组
 * @param array $arr 源数组(用于一维数组)
 * @param string $keys 数组key列表(逗号分隔)
 * @return array
 */
function subArray(array $arr, string $keys = '')
{
    if (empty($arr)) {
        return [];
    }

    $keys = array_filter(array_unique(array_map('trim', explode(',', $keys))));
    if (empty($keys)) {
        return [];
    }

    $newArr = [];
    foreach ($keys as $key) {
        isset($arr[$key]) && $newArr[$key] = $arr[$key];
    }

    return $newArr;
}

/**
 * 去除数组中keys列表对应的元素，返回新数组
 * @param array $arr 源数组(用于一维数组)
 * @param string $noKeys 要去掉的数组key列表(逗号分隔)
 * @return array
 */
function notSubArray(array $arr, string $noKeys = '')
{
    if (empty($arr)) {
        return [];
    }

    $noKeys = array_filter(array_unique(array_map('trim', explode(',', $noKeys))));
    if (empty($noKeys)) {
        return $arr;
    }

    foreach ($noKeys as $key) {
        unset($arr[$key]);
    }

    return $arr;
}

/**
 * 根据传入的二维数组，生成一个html的表格
 * 用于输出调试数据啥的，就很方便了
 * @param array $rs
 * @return string
 */
function getTable(array $rs)
{
    //先取得全部的字段名列表
    $keys = [];
    foreach ($rs as $r) {
        $_keys = array_keys($r);
        $keys = array_unique(array_merge($keys, $_keys));
    }

    //生成html
    $html = '<table cellpadding="5" border="1" cellspacing="0">';

    //字段名
    $html .= '<tr>';
    foreach ($keys as $key) {
        $html .= "<td align='center'>{$key}</td>";
    }
    $html .= "</tr>\n";

    //记录列表
    foreach ($rs as $r) {
        $html .= '<tr>';
        foreach ($keys as $key) {
            $html .= "<td>{$r[$key]}</td>";
        }
        $html .= "</tr>\n";
    }

    $html .= "</table>
        <br>";

    return $html;
}

/**
 * 是否在指定的闭区间之内
 * @param $val
 * @param $left
 * @param $right
 * @return bool
 */
function between($val, $left, $right)
{
    return $left <= $val && $val <= $right;
}

/**
 * 日期将设定格式转换成串返回,如果省略参数则取得系统当前日期时间
 * 转换字符串规则(不分大小写)：y为年,m为月,d为日,h为时,f为分,s为秒,i为毫秒微秒,w为星期,数字0为时间戳
 * 函数引用: $str=Now(['日期格式字符串'])
 * @param string $str
 * @return string
 */
function Now(string $str = 'y-m-d h:f:s')
{
    $reValue = ''; //定义返回值变量
    $str = trim($str);
    if (isset($str)) {
        //参数为有效字符串
        $num = mb_strlen($str, 'utf-8'); //取参数的长度

        //获取微秒数
        $time = gettimeofday();
        $microsec = mb_substr('00000' . $time['usec'], -6, 6, 'utf-8');

        for ($i = 0; $i < $num; $i++) {
            $char = mb_substr($str, $i, 1, 'utf-8');//每次取出单个字符
            switch (strtolower($char)) {
                //年转换
                case 'y':
                    $reValue .= date('Y');
                    break;
                //月转换
                case 'm':
                    $reValue .= date('m');
                    break;
                //日转换
                case 'd':
                    $reValue .= date('d');
                    break;
                //时转换
                case 'h':
                    $reValue .= date('H');
                    break;
                //分转换
                case 'f':
                    $reValue .= date('i');
                    break;
                //秒转换
                case 's':
                    $reValue .= date('s');
                    break;
                //毫秒微秒转换
                case 'i':
                    $reValue .= $microsec;
                    break;

                //星期几转换
                case 'w':
                    $reValue .= date('l');
                    break;

                //时间戳转换
                case '0':
                    $reValue .= time();
                    break;

                //默认为没有转换
                default:
                    $reValue .= $char;
                    break;
            }
        }
    }
    return $reValue;
}

/**
 * 获取唯一的个随机字符串
 * @return string
 */
function ID()
{
    return md5(uniqid(mt_rand(10000000, 99999999), true));
}

/**
 * 迭代器, 参数与功能与array_walk_recursive函数相似
 * 不同点，遍历中将执行的返回结果回写入到对应元素中
 * @param $arr
 * @param $funcname
 * @return array
 */
function Iterator(&$arr, $funcname)
{
    if (!is_array($arr)) {
        return $funcname($arr);
    }

    $fun = create_function('&$val,$key', '$val = ' . $funcname . '($val);');
    array_walk_recursive($arr, $fun);
    return $arr;
}

/**
 * 获取中文“星期几”
 * @param int $time 时间戳
 * @return string
 */
function cnWeek(int $time)
{
    $days = array('', '一', '二', '三', '四', '五', '六', '日');
    $n = date('N', $time);
    return '星期' . $days[$n];
}

/**
 * 获取年龄(即: 给出的时间 到当前时间 的年数), 支持时间格式，和时间戳格式
 * @param $time
 * @return bool|float|int
 */
function Age($time)
{
    if (preg_match('/^[0-9]+$/', $time)) {
        $year = date('Y', $time);
    } else {
        $time = strtotime($time);
        if ($time < 0 || $time === false) {
            return false;
        }
        $year = date('Y', $time);
    }

    return abs(date('Y') - $year);
}

/**
 * 检测字符串是否是正确的Email格式
 * @param string $email
 * @return false|int
 */
function isEmail(string $email)
{
    return preg_match('/^[A-Za-z0-9_.-]+@([A-Za-z0-9_.-]+.)+[A-Za-z]{2,6}$/is', trim($email));
}

/**
 * 检测字符串是否是正确的手机格式
 * @param $mobile
 * @return false|int
 */
function isMobile($mobile)
{
    return preg_match('/^1[0-9]{10}$/', trim($mobile));
}

//环境
//==========================

/**
 * 当前请求是否是来自ajax
 * @return bool
 */
function isAjax()
{
    //检查系统信息
    if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        return true;
    }

    if (!class_exists('getallheaders', false)) {
        return false;
    }

    //检查请求头信息
    $arr = getallheaders();
    return trim($arr['X-Requested-With']) == 'XMLHttpRequest';
}

/**
 * 当前是否是https协议
 * @return bool
 */
function isHttps()
{
    $bool = (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
        || (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS'] == 'on'))
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off');

    return $bool;
}

/**
 * 当前PHP工作模式是否是CLI
 * @return bool
 */
function isCLI()
{
    return strtolower(PHP_SAPI) === 'cli' || defined('STDIN');
}


/**
 * 获取当前客户端ip
 * @return string
 */
function ip()
{
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }

    return $realip;
}

/**
 * 获取当前客户端 USER_AGENT
 * @return string
 */
function ua()
{
    return trim($_SERVER['HTTP_USER_AGENT']);
}

//并发锁功能(需要redis支持)
//=====================

/**
 * 获取时间锁(进行时间方面的限定)
 * 返回: 锁信息
 * @param $key
 * @param int $expire
 * @return mixed
 */
function lock($key, $expire = 60)
{
    return Cache::init('redis')->lock($key, $expire);
}

/**
 * 释放时间锁
 * 说明: 在获取锁执行完业务逻辑后，需要调用此方法主动释锁
 * @param $lock
 * @return mixed
 */
function unlock($lock)
{
    return Cache::init('redis')->unlock($lock);
}


/**
 * 获取数值锁(进行数量方面的限定)
 * 如果获取成功，则返回当前锁定的数量, 如果失败，则返回0
 * @param $key
 * @param int $num
 * @return mixed
 */
function lockNum($key, $num = 1)
{
    return Cache::init('redis')->lockNum($key, $num);
}

/**
 * 释放一个数量锁
 * 说明: 在获取锁执行完业务逻辑后，可以调用此方法主动释锁
 * @param $key
 * @return mixed
 */
function unlockNum($key)
{
    return Cache::init('redis')->unlockNum($key);
}

/**
 * 获取锁key的值(时间锁,数量锁)
 * @param $key
 * @return mixed
 */
function lockVal($key)
{
    return Cache::init('redis')->lockVal($key);
}
