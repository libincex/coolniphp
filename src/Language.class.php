<?php

/*//=================================
//
//	i18n国际化(多语言包)
//  更新时间: 2018-05-29
//
//===================================*/
/*
使用：

//设置当前语言包为 en
Language::set('en');

//语言转换,带模板变量功能
echo Language::lan('你好,{name}!,电话:{phone}。', array(
        'name' => 'CEX',
        'phone' => '13312345678',
    ));

*/

Language::init(); //初始化

class Language
{
    //当前使用的语言包
    protected static $lan = 'cn';
    //语言包数据
    protected static $data = [];

    //初始化数据
    public static function init()
    {
        //设置语言
        $lan = trim(S('CLN-Language'));
        empty($lan) && $lan = trim(C('language.default'));
        empty($lan) && $lan = self::$lan;
        $lan != self::$lan && self::$lan = $lan;

        //判断语言包是否载入
        if (!isset(self::$data[self::$lan])) {
            //载入语言包
            $file = realpath(C('language.path')) . "/{$lan}.php";
            $lanData = include($file);
            self::$data[self::$lan] = (empty($lanData) || !is_array($lanData)) ? [] : $lanData;
        }

        return true;
    }

    //设置当前语言包
    public static function set($language)
    {
        $language = trim($language);
        if (empty($language)) {
            return false;
        }

        if ($language == self::$lan) {
            return true;
        }

        //重新初始化
        self::$lan = $language;
        S('CLN-Language', $language);
        $bool = self::init();

        return $bool;
    }

    //获得当前使用的语言包名称
    public static function get()
    {
        return self::$lan;
    }

    //语言转换
    public static function translate($string, $tplData = [])
    {
        if (!is_string($string)) {
            return $string;
        }

        $text = self::$data[self::$lan][$string];
        if (!isset($text)) { //未找到，直接返回原文本
            return $string;
        }

        //语言与模板变量替换
        if (!empty($tplData) && is_array($tplData)) {

            foreach ($tplData as $key => $val) {
                $text = str_replace("{{$key}}", $val, $text);
            }
        }

        return $text;
    }

}
