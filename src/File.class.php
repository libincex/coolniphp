<?php
/*//=================================
//
//	文件操作类 [更新时间: 2018-05-11]
//
//===================================*/

class File
{
    private static $path; //物理路径
    private static $url; //url路径

    //初始化
    public static function init()
    {
        if (!isset(self::$path)) {
            //取得文件存储配置
            $file = C('file');
            self::$path = realpath(trim(rtrim(trim($file['path']), '/'))); //物理路径
            self::$url = trim(rtrim(trim($file['url']), '/')); //url路径
        }
    }

    //保存上传的文件
    //参数: $name 表单文件域名称
    //返回: 保存文件路径的相对路径
    public static function upload($name)
    {
        set_time_limit(0); //设置不超时

        //路径是否正确
        if (empty(self::$path)) {
            return false;
        }

        //是否有文件上传
        $file = $_FILES[$name];
        if (empty($file) || $file['error']) {
            return false;
        }

        //分解文件名,取得上传文件的扩展名
        $arr = explode('.', $file['name']);
        $ext = count($arr) > 1 ? strtolower(trim(array_pop($arr))) : '';
        if (stripos($ext, '/') !== false || stripos($ext, '\\') !== false) {
            return false;
        }

        //生成完整文件名
        $fileName = empty($ext) ? ID() : (ID() . ".{$ext}");

        //生成保存文件目录
        $dir = date('/Y/md');
        $realDir = self::$path . $dir; //绝对目录
        mkdir($realDir, 0755, true);
        //检查目录是否可写
        if (!is_writable($realDir)) {
            return false;
        }

        //保存文件到物理路径
        $filePath = "{$realDir}/{$fileName}";
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return false;
        }
        if (!is_file($filePath)) {
            return false;
        }

        return "{$dir}/{$fileName}";
    }

    //将内容写入指定的文件路径
    //参数: $fileuri 文件地址的相对路径(包含文件全名)
    public static function save($fileuri, $content)
    {

        $file = self::path($fileuri);
        $dir = dirname($file);
        mkdir($dir, 0755, true);

        return file_put_contents($file, $content);
    }

    //获取文件的绝对路径
    public static function path($uri)
    {
        $uri = trim($uri);
        if (empty($uri)) {
            return '';
        }

        $uri{0} != '/' && $uri = '/' . $uri;
        return self::$path . $uri;
    }

    //获取文件的完整url, 如果$uri为空，则返回$default
    public static function url($uri, $default = '')
    {
        $uri = trim($uri);
        if (empty($uri)) {
            return trim($default);
        }

        //容错处理(如果传入url是完整路径，则直接返回)
        if (strtolower(substr($uri, 0, 7)) == 'http://' || strtolower(substr($uri, 0, 8)) == 'https://') {
            return $uri;
        }

        $uri{0} != '/' && $uri = '/' . $uri;
        return self::$url . $uri;
    }

}
