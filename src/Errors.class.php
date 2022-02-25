<?php
/*//=================================
//
//	错误操作类 [更新时间: 2014-7-7]
//
//===================================*/

class Errors
{

    //显示错误信息，继续执行
    public static function show($text)
    {
        echo CLN_IS_DEBUG ? trim($text) : 'Server Error';

        self::log($text);
    }


    //显示错误信息，并停止程序执行
    public static function stop($text)
    {
        self::log($text);

        exit(CLN_IS_DEBUG ? trim($text) : 'Server Error');
    }


    //写错误日志
    private static function log($text)
    {
        //输出http错误头信息
        if (!CLN_IS_CLI) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        Log::put('error', $text);
    }

}
