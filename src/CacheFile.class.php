<?php
/*//=================================
//
//	缓存-文件 操作类 [更新时间: 2015-6-12]
//
//===================================*/


class CacheFile
{
    private $path; //缓存目录

    //初始化
    function __construct()
    {
        //获取当前服务器的临时目录
        $this->path = sys_get_temp_dir().'/CoolniPHP/CacheFile';
        !is_dir($this->path) && mkdir($this->path,0777,true);
    }

    //设置
    public function set($key,$val,$time=0)
    {
        $key = Cache::key(md5(trim($key)));
        $filePath = $this->path."/{$key}.cache";
        $time = (int)$time;
        !$time && $time = 315360000; //最长10年时间

        $data = json_encode(array(
            'value'=>$val,
            'expire'=>time() + $time,
        ));

        return file_put_contents($filePath,$data);
    }

    //获取
    public function get($key)
    {
        $key = Cache::key(md5(trim($key)));
        $filePath = $this->path."/{$key}.cache";
        if(!is_file($filePath)){
            return NULL;
        }

        $data = json_decode(file_get_contents($filePath),1);
        if(!is_array($data) || empty($data['expire']) || (int)$data['expire']<time()){
            return NULL;
        }

        return $data['value'];
    }


}
