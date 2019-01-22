<?php

/*//=================================
//
//	数据库的统一操作类 [更新时间: 2016-12-7]
//
//===================================*/

class DB
{
    private static $dbs = array(); //数据库操作实例

    //初始化,取得数据库操作实例,如果操作失败返回false
    //参数: $type 数据库类型(mysql、....), 需要配置对应的连接数据库信息
    public static function init($type = 'mysqlx', $configKey = 'mysql')
    {
        $type = strtolower(trim($type));
        $configKey = trim($configKey);

        //单例
        $key = "{$type}-{$configKey}";
        if (empty(self::$dbs[$key])) {
            switch ($type) {
                case 'mysql': //mysql
                    self::$dbs[$key] = new DBMysql(C($configKey));

                default : //默认 升级版的pdo mysql操作类
                    self::$dbs[$key] = new DBMysqlX(C($configKey));
                    break;
            }
        }

        return self::$dbs[$key];
    }

}
?>