<?php
/*//=================================
//
//	日志操作类 [更新时间: 2015-11-12]
//
//===================================*/
/*

//方法
//======================

//1.写入日志内容
$bool = Log::put($name,$data=array());

*/

class Log
{
    private static $LogDir; //目录

    //初始化
    public static function init()
    {
        if(empty(self::$LogDir)){
            self::$LogDir = rtrim(C('log.path'), '/\\');
            !empty(self::$LogDir) && @mkdir(self::$LogDir, 0777, true);
        }
    }

    //写入日志
    public static function put($name,$data=array())
    {
        $name = trim($name);
        empty($name) && $name = 'log';
        (is_array($data) || is_object($data)) && $data = json_encode($data);
        //日志目录
        $y = date('Y');
        $logDir = self::$LogDir."/{$y}";
        @mkdir($logDir, 0777, true);
        //日志文件路径
        $day = date('Ymd');
        $filename = "{$logDir}/{$name}_{$day}.log";
        //写入日志内容
        $file = fopen($filename,'a+'); //打开文件，如果不存在则创建，将指针移到文件尾
        $bool = fwrite($file,date('Y-m-d H:i:s')."   {$data}"."\r\n");
        fclose($file);

        return $bool;
    }

}
