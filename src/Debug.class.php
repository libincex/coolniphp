<?php
/*//=================================
//
//	调试操作类 [更新时间: 2013-9-5]
//
//===================================*/

class Debug
{
    private static $Time = []; //记录程序的分段执行时间数组

    //初始化
    public static function init()
    {
        if (CLN_IS_DEBUG) {
            //调试-记录总执行开始时间
            Debug::startTime('App', 'TotalTime');
        }
    }

    //计录开始时间
    public static function startTime($key, $text = '')
    {
        //是否开启调试模式
        if (CLN_IS_DEBUG) {
            $key = trim($key);
            self::$Time[$key]['startime'] = gettimeofday(true); //开始时间
            self::$Time[$key]['text'] = $text; //说明
        }
    }

    //计录结束时间
    public static function endTime($key)
    {
        //是否开启调试模式
        if (CLN_IS_DEBUG) {
            //返回时间差
            $key = trim($key);
            $time = self::$Time[$key]['time'] = number_format(gettimeofday(true) - self::$Time[$key]['startime'], '6', '.', '');
            unset(self::$Time[$key]['startime']);

            return $time;
        }
    }

    //添加信息到调试信息池
    public static function put($key, $value)
    {
        //是否开启调试模式
        if (CLN_IS_DEBUG) {

            //写入调试日志
            self::log(array(
                'key' => trim($key),
                'value' => $value
            ));
        }
    }

    //获取进程的所有信息,并记录日志中
    public static function sysinfo()
    {

        //是否开启调试模式
        if (CLN_IS_DEBUG) {

            //调试-记录总执行时间
            Debug::endTime('App');

            $data = array(
                'router' => CLN_MODULE == Crontab::CRONTAB ? CLN_MODULE : CLN_URI, //如果是定时任务主程序，则只显示他的标识
                'time' => self::$Time,
                //'SessionID'=>Session::id(),
                //'config'=>Config::get(), //获取配置信息
                //'file'=>get_included_files(), //载入文件路径
                'memory' => intval(memory_get_usage() / 1024) . 'K', //内存使用量
                'memory_peak' => intval(memory_get_peak_usage() / 1024) . 'K', //内存使用峰值
            );

            if (PHP_OS != 'WINNT') {
                //cpu使用信息数组
                $cpu = getrusage();
                $data['CPU_UserTime'] = $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;
                $data['CPU_SystemTime'] = $cpu['ru_stime.tv_sec'] + $cpu['ru_stime.tv_usec'] / 1000000;
            }

            self::log($data); //写日志
        }
    }

    ///写日志
    private static function log($data)
    {
        Log::put('debug', $data);
    }
}
