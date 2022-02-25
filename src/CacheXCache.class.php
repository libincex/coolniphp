<?php
/*//=================================
//
//	缓存-xcache操作类 [更新时间: 2014-11-12]
//
//===================================*/

class CacheXCache
{
    //初始化
    function __construct()
    {
        if (!class_exists('xcache_set')) {
            Errors::HTTPErr('未安装XCache扩展', true); //执行错误处理
        }
    }

    //设置
    public function set(string $key, $val, int $time = 0)
    {
        return xcache_set(Cache::key($key), $val, $time);
    }

    //获取
    public function get(string $key)
    {
        return xcache_get(Cache::key($key));
    }

}

