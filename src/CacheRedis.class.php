<?php

/*//=================================
//
//	缓存-redis操作类 [更新时间: 2015-11-21]
//
//===================================*/

class CacheRedis extends Redis
{
    private $outTime = 1; //连接超时(秒)

    //初始化
    function __construct()
    {
        $param = C('redis');
        $p = $param[0];

        if ((int)$p['persistent']) {
            //持久连接
            $bool = $this->pconnect($p['host'], $p['port'], $this->outTime);
        } else {
            //普通连接
            $bool = $this->connect($p['host'], $p['port'], $this->outTime);
        }

        if (!$bool) {
            Errors::stop('连接Redis服务器失败'); //执行错误处理
        }

        //设置前缀
        $this->setOption(Redis::OPT_PREFIX, Cache::key());
    }

    //string
    //=================================

    //设置
    public function set($key, $val, $expire = 0)
    {
        $expire = (int)$expire;
        $val = serialize($val); //对数据进行序列化
        if ($expire) {
            //设置生命周期
            return parent::setex($key, $expire, $val);
        } else {
            //永久
            return parent::set($key, $val);
        }
    }

    //读取
    public function get($key)
    {
        $data = parent::get($key);

        return isset($data) ? unserialize($data) : NULL; //取出后反序列化
    }

    //队列
    //=================================
    public function lPush($key, $value)
    {
        return parent::lPush($key, serialize($value));
    }

    public function rPop($key)
    {
        return unserialize(trim(parent::rPop($key)));
    }

    public function lRange($key, $start, $end)
    {
        $rs = parent::lRange($key, (int)$start, (int)$end);
        if (empty($rs)) {
            return array();
        }

        foreach ($rs as &$r) {
            $r = unserialize($r);
        }
        return $rs;
    }


    //lock
    //=================================

    //获取时间锁(进行时间方面的限定)
    //参数: $key 锁的标识, $expire 锁的最大时间(单位s,必须大于0)
    //返回: 如果获取成功,则返回锁信息数组(解锁时有用), 失败返回false
    public function lock($key, $expire)
    {
        $key = trim($key);
        $expire = (int)$expire;
        if (empty($key) || $expire < 0) {
            return false;
        }

        //生成key
        $vkey = "RedisLock_{$key}";
        //生成token,解锁时有用到的
        $token = uniqid(rand(10000, 99999), true);
        //执行(版本 2.6.12 之前使用)
        $bool = $this->eval("return redis.call('SET',KEYS[1],'{$token}','EX',{$expire},'NX')", array($vkey), 1);
        //执行(版本 2.6.12 之后可以直接使用set)
        //$bool = $this->set($vkey, $token, array('nx', 'ex' => $expire));

        //返回锁信息
        return $bool ? array('key' => $key, 'token' => $token) : false;
    }

    //释放时间锁
    //参数: $lock 锁信息
    //返回: 解锁是否成功
    public function unlock($lock)
    {
        if (!is_array($lock)) {
            return false;
        }

        $key = trim($lock['key']);
        $token = trim($lock['token']);
        if (empty($key) || empty($token)) {
            return false;
        }

        return $this->lockVal($key) == $token ? $this->clearLock($key) : false;
    }

    //获取数量锁(进行数量方面的限定)
    //返回: 如果获取成功，则返回当前锁定的数量, 如果失败，则返回0
    public function lockNum($key, $num)
    {
        $key = trim($key);
        $num = (int)$num;
        if (empty($key) || $num < 1) {
            return false;
        }

        $vkey = "RedisLock_{$key}";
        $pkey = "RedisLockProtect_{$key}";

        $script = "
            if not redis.call('SET',KEYS[2],1,'PX',500,'NX') then
                return 0
            end

            local n = redis.call('GET',KEYS[1])
            if n and tonumber(n) >= {$num} then
                return 0
            end

            n = redis.call('INCR',KEYS[1])
            if n and n > {$num} then
                redis.call('DECR',KEYS[1])
                n = 0
            end

            redis.call('DEL',KEYS[2])

            return n
       ";
        $val = $this->eval($script, array($vkey, $pkey), 2);
        $val === false && Log::put('redis', $this->getLastError());

        return $val;
    }

    //释放一个数量锁
    //说明: 在获取锁执行完业务逻辑后，可以调用此方法主动释锁
    public function unlockNum($key)
    {
        $key = trim($key);
        if (empty($key)) {
            return false;
        }

        $vkey = "RedisLock_{$key}";
        return $this->decr($vkey);
        /*
        $vkey = "RedisLock_{$key}";
        $pkey = "RedisLockProtect_{$key}";

        $script = "
            if not redis.call('SET',KEYS[2],1,'PX',500,'NX') then
                return 0
            end

            local n = redis.call('GET',KEYS[1])
            if n and tonumber(n)<=0 then
                return 0
            end

            local n = redis.call('DECR',KEYS[1])
            if n and n < 0 then
                n = redis.call('INCR',KEYS[1])
            end

            redis.call('DEL',KEYS[2])

            return n
       ";
        $val = $this->eval($script,array($vkey,$pkey),2);
        $val===false && Log::put('redis',$this->getLastError());

        return $val;
        */
    }

    //清除锁(时间锁,数量锁)
    public function clearLock($key)
    {
        $vkey = 'RedisLock_' . trim($key);
        $val = $this->eval("return redis.call('DEL',KEYS[1])", array($vkey), 1);
        $val === false && Log::put('redis', $this->getLastError());

        return $val;
    }

    //获取锁key的值(时间锁,数量锁)
    public function lockVal($key)
    {
        $vkey = 'RedisLock_' . trim($key);
        $val = $this->eval("return redis.call('GET',KEYS[1])", array($vkey), 1);
        $val === false && Log::put('redis', $this->getLastError());

        return $val;
    }

}