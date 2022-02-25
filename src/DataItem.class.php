<?php
/*//=================================
//
//	数据项操作类 [更新时间: 2014-11-12]
//
//===================================*/

/*

//方法
//======================

//1.注册一个数据项
DataItem::reg('名称','数据类型',默认值);

//2.单个数据处理
$val = DataItem::get('名称',$val);

//3.批量数据处理（对数组中的元素值进行批量处理）
$data = DataItem::get(array(
	'键名'=>'数据项名称',
	....
	'*'=>'数据项名称', //支持"*"通配符,未被指定的数组元素都 ( 除显示用指定数据项来处理的指定的元素外，所有元素)
),$arr);


//默认的数据项：
//======================

//数值型
int
float
money

//字符型
char16
char32
char64
char128
char255
text
html

//日期时间型
date
datetime
time
timestamp

//数组
arr
ids //(返回数组型)主键ID列表(整型,以逗号分隔)
idlist //(返回字符串型)主键ID列表(整型,以逗号分隔)

//数据格式
json
serialize

*/

class DataItem
{

    //默认数据项与名称
    private static $dataitems = [
        'int' => array('type' => 'int'),
        'float' => array('type' => 'float'),
        'money' => array('type' => 'money'),

        'char16' => array('type' => 'char16'),
        'char32' => array('type' => 'char32'),
        'char64' => array('type' => 'char64'),
        'char128' => array('type' => 'char128'),
        'char255' => array('type' => 'char255'),

        'text' => array('type' => 'text'),
        'string' => array('type' => 'text'),

        'html' => array('type' => 'html'),

        'date' => array('type' => 'date'),
        'datetime' => array('type' => 'datetime'),
        'time' => array('type' => 'time'),
        'timestamp' => array('type' => 'timestamp'),

        'arr' => array('type' => 'arr'),
        'array' => array('type' => 'arr'),
        'ids' => array('type' => 'ids'),
        'idlist' => array('type' => 'idlist'),

        'json' => array('type' => 'json'),
        'serialize' => array('type' => 'serialize'),
    ];

    //注册一个新的数据项类型
    private static $types = [];

    public static function reg(string $name, string $type = 'text', $defualtValue = NULL)
    {
        $name = strtolower(trim($name));
        if (empty($name)) {
            return false;
        }

        $type = strtolower(trim($type));
        if (empty(self::$types)) {
            self::$types = get_class_methods('DataType');
        }
        if (!in_array($type, self::$types)) {
            $type = 'text'; //默认类型
        }
        self::$dataitems[$name] = isset($defualtValue) ? ['type' => $type, 'value' => $defualtValue] : ['type' => $type];

        return true;
    }

    //单个处理
    public static function get($dataitem, $val = NULL)
    {
        $dataitem = strtolower(trim($dataitem));
        if (!isset(self::$dataitems[$dataitem])) {
            $dataitem = 'text'; //默认数据项
        }

        $info = self::$dataitems[$dataitem];
        if (empty($val) && isset($info['value'])) {
            return $info['value'];
        }

        return call_user_func_array(array('DataType', $info['type']), array($val));
    }

    /*
    //对一组数据进行批量处理
    //格式化数组
    //$formatList = array(
        'key1'=>'数据项名称',
        'key2'=>'int', //将'key2'做为'int'处理
        '*'=>'text', //批量将所有字段都做为“text”处理(除指定的'key1'与'key2'之外)
    )
    */
    public static function gets(array $formatList = [], array $_data = [])
    {
        !is_array($formatList) && $formatList = [];
        !is_array($_data) && $_data = [];
        if (empty($formatList) && empty($_data)) {
            return [];
        }

        $data = [];
        //1.先过虑$_data中的数据项
        foreach ($_data as $key => $val) {
            if (isset($formatList[$key])) {
                $data[$key] = self::get($formatList[$key], $val);
            } elseif (isset($formatList['*'])) {
                $data[$key] = self::get($formatList['*'], $val);
            }
        }
        //2.再补全$_data中没有的数据项
        foreach ($formatList as $key => $dataitem) {
            if ($key != '*' && !isset($data[$key])) {
                $data[$key] = self::get($dataitem, NULL);
            }
        }

        return $data;
    }


}
