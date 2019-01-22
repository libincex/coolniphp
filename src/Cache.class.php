<?php
/*//=================================
//
//	缓存主操作类 [更新时间: 2015-6-12]
//
//===================================*/

class Cache
{
    //缓存类型(名称对应类名
    private static $type = array(
        'file'=>'CacheFile',
        'memcache'=>'CacheMemcache',
        'memcached'=>'CacheMemcache',
        'xcache'=>'CacheXCache',
        'redis'=>'CacheRedis',
    );

    private static $obj=array(); //缓存操作对象单例数组

	//初始化,返回一个缓存的实例
	public static function init($type='')
	{
        $type = trim($type);
        $types = array_keys(self::$type);
        !in_array($type,$types) && $type = strtolower(trim(C('cache'))); //如果没有指定，则选取配制文件中的参数
        !in_array($type,$types) && $type = 'file'; //如果配置文件中也没有指定，则默认使用文件缓存

		if(!isset(self::$obj[$type])){
            $class = self::$type[$type];
            self::$obj[$type] = new $class();
		}

		return self::$obj[$type];
	}

	//设置
	public static function set($key,$val,$time=0)
	{
		return self::init()->set($key,$val,$time);
	}

	//获取
	public static function get($key)
	{
		return self::init()->get($key);
	}

    //生成完整key
    private static $prefix; //前缀
    public static function key($key='')
    {
        !isset(self::$prefix) && self::$prefix = 'K'.md5_16(CLN_ID); //初始化前缀

        return self::$prefix.'_'.trim($key);
    }
}

?>