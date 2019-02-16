<?php
/*//=================================
//
//	缓存-memcache操作类 [更新时间: 2014-11-12]
//
//===================================*/
/*
在config文件中的配置:

	//缓存
	'memcache'=>array(
		array('host'=>'127.0.0.1','port'=>'11211'),
	),

*/

class CacheMemcache
{
	private static $memcache; //memcache缓存对象

	//初始化
	function __construct()
	{
		if(!isset(self::$memcache)){

			//初始化对象
			if(class_exists('Memcached')){
				//Memcached 持久化处理
				self::$memcache = new Memcached('CoolniPHP_Memcached');
				$serverList = self::$memcache->getServerList();
				if(empty($serverList)){
					self::$memcache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
					foreach(C('memcache') as $r){
						self::$memcache->addServer($r['host'],$r['port'],1);
					}
				}
			}elseif(class_exists('Memcache')){
				self::$memcache = new Memcache();
				foreach(C('memcache') as $r){
					self::$memcache->addServer($r['host'],$r['port'],true,1); //持久化处理
				}
			}else{
				Errors::stop('未安装Memcache或Memcached扩展'); //执行错误处理
			}
			
		}
	}
	
	//设置
	public function set($key,$val,$time=0)
	{
        $key = Cache::key($key);
		if(get_class(self::$memcache)=='Memcached'){ //使用memcached扩展
			return self::$memcache->set($key,$val,(int)$time);
		}else{ //使用memcache扩展
			return self::$memcache->set($key,$val,0,(int)$time);
		}
	}
	
	//获取
	public function get($key)
	{
		return self::$memcache->get(Cache::key($key));
	}
	

}
