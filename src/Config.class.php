<?php
/*//=================================
//
//	配置操作类 [更新时间: 2013-9-5]
//
//===================================*/

class Config
{
    private static $config = [];

    //配置信息
    public static function init(array $config = [])
    {
        if (empty(self::$config)) {

            //处理配置项:
            !is_array($config) && $config = [];

            //环境设置:
            //======================================
            //设置内存
            !empty($config['memory']) && ini_set('memory_limit', $config['memory']);

            //设置时区
            empty($config['timezone']) && $config['timezone'] = 'Asia/Shanghai';
            ini_set('date.timezone', $config['timezone']);

            //设置调试
            define('CLN_IS_DEBUG', (bool)$config['isDebug']);
            if (CLN_IS_DEBUG) { //开启错误显示
                error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR | E_RECOVERABLE_ERROR);
                ini_set('display_errors', 'ON');
            } else { //屏蔽所有错误信息
                error_reporting(0);
                ini_set('display_errors', 'OFF');
            }

            //应用设置:
            //======================================

            //应用ID
            define('CLN_ID', trim($config['AppID']));

            //路径配置:
            //控制器程序路径
            $config['controllerPath'] = realpath(trim($config['controllerPath']));
            //模板路径
            $config['viewPath'] = realpath(trim($config['viewPath']));
            //类库文件路径 兼容单值 与 多值
            !is_array($config['libPath']) && $config['libPath'] = array(trim($config['libPath']));
            foreach ($config['libPath'] as $key => &$lp) {
                $lp = realpath(trim($lp));
            }
            $config['libPath'] = array_unique(array_filter($config['libPath']));

            //各应用模式私有部分
            //======================================
            if (CLN_IS_CLI) { //cli模式
                //输出缓冲区内容并关闭缓冲
                ob_end_flush();

                //入口文件路径
                $files = get_included_files();
                define('CLN_BASE_URL', 'php ' . $files[0] . ' ');

            } else { //http模式
                //设置程序运行超时
                $outTime = (int)$config['http']['outTime'];
                $outTime && set_time_limit($outTime);

                ob_start(); //开启输出缓冲
                header('Content-type: text/html; charset=utf-8'); //默认输出编码为utf-8
                header('X-Powered-By: CoolniPHP');

                //url的协议头
                define('CLN_HTTP_PROTOCOL', isHttps() ? 'https://' : 'http://');
                //入口文件url
                $rootFile = pathinfo($_SERVER['SCRIPT_FILENAME'])['basename'];
                define('CLN_BASE_URL', CLN_HTTP_PROTOCOL . "{$_SERVER['HTTP_HOST']}/{$rootFile}");

                //静态文件所在的网址目录处理(兼容单值与多值 字符串型 与 数组型(随机获取)
                $sUrl = $config['route']['sUrl'];
                !is_array($sUrl) && $sUrl = array($sUrl);
                foreach ($sUrl as &$val) {
                    $val = trim(rtrim(trim($val), '/')); //去掉Url中最后"/"
                }
                $config['route']['sUrl'] = array_filter($sUrl); //过滤空元素, 回写

            }

            //写入配置
            self::$config = $config;
        }
    }

    //获取
    public static function get(string $key = NULL)
    {
        if (!isset($key)) {
            return self::$config;
        }

        //1.优先完整名称的参数匹配
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        //2.尝试对名称进行多维化处理
        $arr = explode('.', $key); //以'.'进行分隔
        $val = &self::$config;
        foreach ($arr as $k) {
            if (!isset($val[$k])) {
                return NULL;
            }
            $val = &$val[$k];
        }

        return $val;
    }

    //设置
    public static function set(string $key, $val)
    {
        if (!isset($key)) {
            return false;
        }

        //1.优先完整名称的参数匹配
        if (isset(self::$config[$key])) {
            self::$config[$key] = $val;
            return true;
        }
        //2.尝试对名称进行多维化处理
        $arr = explode('.', $key); //以'.'进行分隔
        $config = &self::$config;
        foreach ($arr as $k) {
            !is_array($config[$k]) && $config[$k] = [];
            $config = &$config[$k];
        }
        $config = $val;

        return true;
    }

}
