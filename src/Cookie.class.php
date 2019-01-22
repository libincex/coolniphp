<?php
/*//=================================
//
//	cookie操作类 [更新时间: 2015-10-23]
//  (仅http方式下有效)
//===================================*/

class Cookie
{
    private static $prefix; //cookie名称前缀
    private static $cacheCookie = array(); //cookie缓冲(以便实现兼容: 一个执行流程中设置了cookie,又能立即读取)

    //初始化
    public static function init()
    {
        if(!isset(self::$prefix)) {
           self::$prefix = trim(C('http.cookie.prefix'));
        }
    }

	//获取
	public static function get($name)
	{
        $name = self::$prefix.trim($name);
        $val = $_COOKIE[$name]; //先从$_COOKIE中获取
        empty($val) && $val = self::$cacheCookie[$name]; //再从cacheCookie中获取

		return trim($val);
	}

    //设置
    public static function set($name, $value, $expire = 0, $domain = NULL)
    {
        $name = self::$prefix . trim($name);
        $expire = (int)$expire;
        $expire && $expire < time() && $expire = time() + $expire; //兼容处理
        if (empty($domain)) {
            $bool = setcookie($name, trim($value), $expire, '/');
        } else {
            $bool = setcookie($name, trim($value), $expire, '/', $domain);
        }

        //保存 cacheCookie
        self::$cacheCookie[$name] = $value;

        return $bool;
    }

    //删除
    public static function del($name)
    {
        return setcookie($name, '', 0, '/');
    }

}
?>