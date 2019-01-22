<?php
/*//=================================
//
//	路由操作类 [更新时间: 2014-3-14]
//
//===================================*/

class Router
{
    //路由信息数组
    private static $info = array(
        'module' => 'index', //模块默认名称
        'controller' => 'index',
        'action' => 'index',
    );

    //当前传入页面的数据(若是参数名冲突,$_GET优先于$_POST
    private static $parameter;

    //初始化, 路由分析
    public static function init()
    {
        //根据不同的工作模式分别处理
        if (CLN_IS_CLI) {
            self::cli(); //CLI工作模式
        } else {
            self::http(); //HTTP(默认)
        }

        //安全处理(禁止请求__construct, __destruct 方法)
        in_array(self::$info['action'], array('__construct', '__destruct')) && self::$info['action'] = 'index';

        //设置路径信息
        define('CLN_MODULE', self::info('module')); //当前的模块名称,设置到config数组中
        define('CLN_CONTROLLER', self::info('controller')); //当前的控制类名称,设置到config数组中
        define('CLN_ACTION', self::info('action')); //当前的方法名称,设置到config数组中
        define('CLN_URI', implode('/', self::$info)); //当前路由的URI
    }

    //取得路由信息
    public static function info($name = NULL)
    {
        if (!isset($name)) {
            return self::$info;
        }
        return self::$info[$name];
    }

    //取得当前输入页面的数据
    public static function data($name = '', $default = NULL)
    {
        $name = trim($name);
        if (empty($name)) {
            return self::$parameter;
        } else {
            return isset(self::$parameter[$name]) ? self::$parameter[$name] : $default;
        }
    }

    //用baseUrl与传入的uri合成完整的url, 如果省略，则返回当前url
    public static function url($uri = '')
    {
        $uri = trim(trim(trim($uri), '\\'), '/');
        if ($uri == '') {
            //如果参数为空，则返回当前网址
            return CLN_HTTP_PROTOCOL . strtolower($_SERVER['SERVER_NAME']) . $_SERVER['REQUEST_URI'];
        }

        return CLN_BASE_URL . "/{$uri}";
    }


//内部方法
//================================

    /*
    //自定义路由处理,返回匹配到的路由路径
    //(如果返回空，表示未匹配到用户自定义的路由规则)
    //配置文件中, 例：
        'userRoute'=>array(
            'productList-{type}-{page}.html'=>'/index/product/list/type/{type}/page/{page}',
            'AdminLogin'=>'/index/login',
        ),
    */
    private static function userRoute()
    {
        global $argv; //外部传入的参数内容数组

        $urlArr = C('route.userRoute');
        if (!is_array($urlArr) || empty($urlArr)) {
            return '';
        }

        //获取当前的URL
        $url = trim(CLN_IS_CLI ? $argv[1] : $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        //生成临时key,用于替换填充串
        $tmpKey = 'KEY' . uniqid();

        $arr = array();
        foreach ($urlArr as $key => $val) {
            //判断是否符合
            $pattern = str_replace($tmpKey, '(.*)', preg_quote(preg_replace('/\{.+?\}/i', $tmpKey, $key), '/'));
            if (!preg_match("/{$pattern}/i", $url)) {
                continue;
            }
            //获取key
            $data = array();
            preg_match_all('/\{(.+?)\}/i', $key, $data);
            $parameters = is_array($data[0]) ? $data[0] : array();

            //提取值
            preg_match_all("/{$pattern}/i", $url, $data);
            unset($data[0]);
            $vals = array();
            $i = 0;
            foreach ($data as $d) {
                if (!isset($parameters[$i])) {
                    break;
                }
                $vals[$parameters[$i]] = trim($d[0]);
                $i++;
            }

            //填充变量值
            $uri = str_replace($parameters, $vals, $val);

            //返回值
            return preg_replace('/\{.+?\}/i', '', $uri);
        }

        return '';
    }


    //WEB应用的路由初始化
    private static function http()
    {
        //是否匹配到用户自定义路由
        $url = self::userRoute();
        if (empty($url)) {
            //处理url,统一url格式
            $url = trim($_SERVER['REQUEST_URI']);
            $purl = trim($_SERVER['SCRIPT_NAME']);
            empty($purl) && $purl = trim($_SERVER['PHP_SELF']);

            if ($purl == mb_substr($url, 0, strlen($purl))) {
                $url = substr_replace($url, '', 0, strlen($purl));
            }
        }
        $url = trim(str_replace(array('/?', '&', '='), array('?', '/', '/'), $url), '/');
        $urlArr = array_map('trim', explode('?', $url));

        //分析uri:
        //1. 获取路由路径三参数MCA
        $mca = array_map('trim', explode('/', $urlArr[0]));
        !empty($mca[0]) && self::$info['module'] = $mca[0]; //模块名
        !empty($mca[1]) && self::$info['controller'] = $mca[1]; //控制器名
        !empty($mca[2]) && self::$info['action'] = $mca[2]; //方法名
        unset($mca[0], $mca[1], $mca[2]);
        $urlArr[0] = implode('/', $mca);

        //2. 获取Get传递的数据
        $parame = array();
        $urlVal = trim(implode('/', $urlArr), '/');
        if (!empty($urlVal)) {
            $urlVal = array_map('trim', explode('/', $urlVal));

            //提取变量名与变量值
            foreach ($urlVal as $key => $val) {
                if ($key % 2) {
                    !empty($name) && $parame[$name] = urldecode($val); //保存变量值
                } else {
                    $name = empty($val) ? '' : $val; //保存变量名
                }
            }
        }

        //3. 处理传入的参数(去掉PHP的魔术引号的作用
        if (get_magic_quotes_gpc()) {
            $parame = Security::quote($parame, -1);
            $_POST = (!is_array($_POST) || empty($_POST)) ? array() : Security::quote($_POST, -1);
        }
        self::$parameter = $parame + $_POST;

        /*
        print_r($parame);
        print_r(self::$info);
        print_r(self::$parameter);exit;
        */
        return true;
    }

    //CLI工作模式的路由初始化
    private static function cli()
    {
        //1.取得命令行中的参数
        global $argv; //外部传入的参数内容数组

        //2.是否匹配到用户自定义路由
        $url = self::userRoute();
        empty($url) && $url = $argv[1];

        //3.取得路由路径参数
        $path = trim(trim($url), '/'); //取除前后不必要的字符
        $path = explode('/', $path);

        //4.分析路由
        $info = array();
        //取得模块名
        $module = trim($path[0]);
        !empty($module) && $info['module'] = $module;
        //取得控制器名
        $controller = trim($path[1]);
        !empty($controller) && $info['controller'] = $controller;
        //取得方法名
        $action = trim($path[2]);
        !empty($action) && $info['action'] = $action;
        //保存路由数据
        self::$info = $info + self::$info;

        //处理传入的参数
        unset($path[0], $path[1], $path[2]);
        $parame = array();
        foreach ($path as $key => $p) {
            if ($key % 2) {
                //参数名
                $parame[$p] = '';
            } else {
                //参数值
                $parame[$path[$key - 1]] = $p;
            }
        }
        self::$parameter = $parame;

        return true;
    }

}

?>