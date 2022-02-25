<?php
/*//=================================
//
//	基础数据类型操作项 [更新时间: 2014-11-12]
//
//===================================*/


class DataType
{
	
	//123345
	public static function int($val)
	{
		return (int)$val;
	}
	
	//12345.6789
	public static function float($val)
	{
		return (float)$val;
	}
	
	//钱 1050.50
	public static function money($val)
	{
		return number_format((float)$val,2,'.','');
	}
	
	//限长字符串
	public static function char16($val)
	{
		return mb_substr(trim($val),0,16);
	}
	
	//限长字符串
	public static function char32($val)
	{
		return mb_substr(trim($val),0,32);
	}
	
	//限长字符串
	public static function char64($val)
	{
		return mb_substr(trim($val),0,64);
	}
	
	//限长字符串
	public static function char128($val)
	{
		return mb_substr(trim($val),0,128);
	}
	
	//限长字符串
	public static function char255($val)
	{
		return mb_substr(trim($val),0,255);
	}
	
	//文本
	public static function text($val)
	{
		return strip_tags(trim($val));
	}

	//html
	public static function html($val)
	{
		return trim($val);
	}
	
	//日期
	public static function date($val)
	{
		$num = strtotime($val);
		return $num?date('Y-m-d',$num):'0000-00-00';
	}
	
	//日期时间
	public static function datetime($val)
	{
		$num = strtotime($val);
		return $num?date('Y-m-d H:i:s',$num):'0000-00-00 00:00:00';
	}
	
	//时间
	public static function time($val)
	{
		$num = strtotime($val);
		return $num?date('H:i:s',$num):'00:00:00';
	}
	
	//时间戳
	public static function timestamp($val)
	{
		return (int)strtotime($val);
	}
	
	//数组
	public static function arr($val)
	{
		if(!is_array($val)){
			return [];
		}

		return json_decode(json_encode($val),1);
	}

    //主键ID列表(整型,以逗号分隔)
	//返回数组型
    public static function ids($val)
    {
		is_array($val) && $val = implode(',',$val);
        $val = array_map('trim',explode(',',trim($val)));

        return array_unique(array_filter(array_map('intval',$val)));
    }

	//主键ID列表(整型,以逗号分隔)
	//返回字符串型
	public static function idlist($val)
	{
		$val = self::ids($val);

		return implode(',',$val);
	}

    //json串
    public static function json($val)
    {
        return json_decode(trim($val),1);
    }

	//serialize串
	public static function serialize($val)
	{
		return unserialize($val);
	}
	
}
