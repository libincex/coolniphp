<?php
/*//=================================
//
//	session操作类 [更新时间: 2018-7-6]
//
//===================================*/

/*
说明：
由于session是基于cache功能进行存储的， 所以要使用框架中的session功能，
必须有效的启用框架中的cache

首先要执行session初始化
Session::init();

//1.获取 sessionID
$id = Session::id();

//2.取得/设置 加过密的sessionID (适用于非web模式的会话)
$sid = Session::sid($sid='');

//3.设置
$bool = Session::set($key,$val);

//4.获取
$value = Session::get($key);

*/

class Session
{
    private static $CookieKey = 'CLNSID'; //从外部获取SessionID的变量名称(url参数，cookie)
    private static $Domain; //session存储在cookie的域名
    private static $ID; //SessionID
    private static $SID; //SessionID
    private static $SessionTime; //Session的有效时间(单位s)
    private static $SecretKey; //用于加密SessionID的key
    private static $CacheKey; //缓存Session数据的key

    //初始化配置
    private static $isInit = false;

    public static function init()
    {
        if (!self::$isInit) {
            self::$SessionTime = (int)C('session.lifeTime');
            self::$Domain = trim(C('session.domain'));
            empty(self::$Domain) && self::$Domain = trim(array_shift(explode(':', $_SERVER['HTTP_HOST'])));
            self::$SecretKey = CLN_ID;

            //尝试从外部获取ID
            $SID = trim($_REQUEST[self::$CookieKey]); //1. 优先获取外部提交的sid
            empty($SID) && $SID = Cookie::get(self::$CookieKey); //2. 如果为空则从cookie中获取
            $sArr = explode('.', trim(SDecode($SID, self::$SecretKey)));
            $ID = trim($sArr[0]);
            $expireTime = (int)$sArr[1];

            //未从外部获取到 或 已过期，则生成一个新的
            if (empty($ID) || $expireTime < time()) {
                $ID = ID();
                $expireTime = 0; //清除有效期
            }
            self::$CacheKey = "session_{$ID}"; //缓存key

            //检测过期时间
            if ($expireTime - time() < self::$SessionTime / 2) {
                //刷新Cookie
                $expireTime = time() + self::$SessionTime;
                $SID = SEncode("{$ID}.{$expireTime}", self::$SessionTime, self::$SecretKey);
                Cookie::set(self::$CookieKey, $SID, $expireTime, self::$Domain);
            }

            self::$ID = $ID;
            self::$SID = $SID;
            self::$isInit = true;
        }
    }

    //取得/设置 加过密的SessionID (主要用于来自外部的 输入/输出)
    //返回: 加密的SessionID
    public static function sid($SID = '')
    {
        $SID = trim($SID);

        //设置: 传入了指定参数sid
        if (!empty($SID) && $SID != self::$SID) {
            //解密SID
            $sArr = explode('.', trim(SDecode($SID, self::$SecretKey)));
            $ID = trim($sArr[0]);
            $expireTime = (int)$sArr[1];
            //检查有效性
            if (!empty($ID) && $expireTime > time()) {
                //重新初始化
                self::$ID = $ID;
                self::$CacheKey = "session_{$ID}"; //缓存key

                //刷新Cookie
                $expireTime = time() + self::$SessionTime;
                self::$SID = SEncode("{$ID}.{$expireTime}", self::$SessionTime, self::$SecretKey);
                Cookie::set(self::$CookieKey, self::$SID, $expireTime, self::$Domain);
            }
        }

        //返回加密的SessionID
        return self::$SID;
    }

    //获得/设置 SessionID
    //返回: 原值SessionID
    public static function id($ID = '')
    {
        $ID = trim($ID);

        //设置: 传入了指定参数ID
        if (!empty($ID) && self::$ID != $ID && preg_match('/^1[0-9a-z]{16,32}$/i', $ID)) {
            //重新初始化
            self::$ID = $ID;
            self::$CacheKey = "session_{$ID}"; //缓存key

            //刷新Cookie
            $expireTime = time() + self::$SessionTime;
            self::$SID = SEncode("{$ID}.{$expireTime}", self::$SessionTime, self::$SecretKey);
            Cookie::set(self::$CookieKey, self::$SID, $expireTime, self::$Domain);
        }

        return self::$ID;
    }

    //获取
    public static function get($key = '')
    {
        //生成Session 的 Cache Key
        $SKey = 'session_' . self::id();

        //取得
        $data = Cache::get($SKey);
        //var_dump($data);exit;
        !is_array($data) && $data = [];

        $key = trim($key);
        return empty($key) ? $data : $data[$key];
    }

    //设置
    public static function set($key, $val)
    {
        //取得
        $data = self::get();
        //设置
        $data[trim($key)] = $val;

        //生成session Key
        $SKey = 'session_' . self::id();
        return Cache::set($SKey, $data, self::$SessionTime);
    }

}
