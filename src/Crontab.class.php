<?php
/*//=================================
//
//	crontab 定时脚本 操作类 [更新时间: 2016-4-20]
//	仅 CLI模式下
//===================================*/


class Crontab
{
    const CRONTAB = '__CRONTAB__'; //定时脚本主程序的标记

    //任务列表
    private static $tasklist = [];

    //初始化
    public static function init()
    {
        if (CLN_IS_CLI && CLN_MODULE == self::CRONTAB && empty(self::$tasklist)) {
            $tasklist = C('cli.crontab');
            is_array($tasklist) && !empty($tasklist) && self::$tasklist = $tasklist;

            //执行任务
            $num = self::play(); //开始执行任务列表
            $num && Log::put('crontab', "crontab_main num: {$num}"); //写日志
            exit; //结束程序执行
        }
    }

    //开始执行任务列表
    private static function play()
    {
        //选取当前定时要执行的任务,开始执行
        $processArr = [];
        foreach (self::$tasklist as $task => $time) {
            if (self::isRun($time)) {
                $command = URL("{$task} 2>&1");
                $processArr[] = array(
                    'command' => $command,
                    'process' => popen($command, 'r'), //开启子进程
                    'startTime' => gettimeofday(true), //记录开始时间
                );
            }
        }

        //监听子进程是否执行完成
        $n = $num = count($processArr); //当前执行的子进程 计数器
        while ($n) {
            foreach ($processArr as &$p) {
                $p['result'] .= fread($p['process'], 8192);
                if (feof($p['process'])) {
                    //计算执行时间
                    $p['time'] = number_format(gettimeofday(true) - $p['startTime'], '6', '.', '');
                    //关闭任务
                    pclose($p['process']);
                    //当前执行的子进程 计数器 -1
                    $n--;
                }
            }

            usleep(10); //间隔休息时间,微秒数
        }

        //记录执行各任务的日志
        foreach ($processArr as $r) {
            unset($r['process']);
            Log::put('crontab', $r);
        }

        return $num;

    }

    //解析任务的定时格式, 判定当前任务是否执行
    public static function isRun(string $time)
    {
        $time = trim($time);
        if ($time === '') {
            return false;
        }

        //提取数据
        $timeArr = [];
        foreach (array_map('trim', explode(',', $time)) as $t) {
            if ($time !== '') {
                $timeArr[] = $t;
            }
        }

        if (!self::isRunByFormat($timeArr[0], 'i')) return false; //分
        if (!self::isRunByFormat($timeArr[1], 'H')) return false; //时
        if (!self::isRunByFormat($timeArr[2], 'd')) return false; //日
        if (!self::isRunByFormat($timeArr[3], 'm')) return false; //月
        if (!self::isRunByFormat($timeArr[4], 'w')) return false; //星期(0-6:星期日-星期六)

        return true;
    }

    //格式判断
    //参数: $tf 时间模板, $ts 验证当前时间标识( i 分, H 时, d 日, m 月, w 星期)
    private static function isRunByFormat(string $tf, string $ts = 'i')
    {
        $t = (int)date($ts);
        $tf = trim($tf);

        if ($tf === '*') {
            return true;
        }

        if ((int)$tf === $t) {
            return true;
        }

        $data = [];
        preg_match_all('/^([0-9]+)\-([0-9]+)$/', $tf, $data);
        if (isset($data[0][0])) {
            $min = (int)$data[1][0];
            $max = (int)$data[2][0];
            if ($t >= $min && $t <= $max) {
                return true;
            }
        }

        return false;
    }
}