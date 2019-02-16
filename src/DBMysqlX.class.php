<?php
/*//=================================
//
//	 	 PDO的新Mysql操作类
//       [更新时间: 2017-10-31]
//
//===================================

使用说明:

一.实例化

1.用数据库配置信息创建实例
$config = array(
	'charset'=>'utf8', //字符集, 默认utf8
	'prefix'=>'clnblog_', //表名前缀
	'isdebug'=>true, //是否调试模式,默认false
	
	//服务器地址, 默认localhost //端口号, 默认3306 //数据库名称, 必选 //用户名, 默认root //密码, 默认空
	//设置为主库(必选项), 只能设置一个主库
	'master'=>array('host'=>'db0', 'port'=>'', 'db'=>'cms0', 'user'=>'root', 'password'=>'123'),
	//开启读写分离的从库配置, 可以设置多个, 设置从库及其权重,默认为1, 取值范围(1-10)
	'slave'=>array(
		array('host'=>'db1', 'port'=>'', 'db'=>'cms1', 'user'=>'root', 'password'=>'123','weight'=>1),
		array('host'=>'db2', 'port'=>'', 'db'=>'cms2', 'user'=>'root', 'password'=>'123','weight'=>5),
		array('host'=>'db3', 'port'=>'', 'db'=>'cms3', 'user'=>'root', 'password'=>'123','weight'=>10),
	)
);
$db = new DBMysqlX($config);

2.使用现有pdo对象初始化
$db = new DBMysqlX();
//单库模式
$db->init($pdo);
//读写分离模式(主,从库)
$db->init($wPdo,$rPdo);

二.执行sql

$rs = $db->query('sql语句模板',array(参数1[,参数2...)); //查询记录集(仅用于查询类语句)
$bool = $db->run('sql语句模板',array(参数1[,参数2...)); //执行一条sql(非查询类的语句

三.数据库操作

1.获取数据库信息
$tables = $db->tables(); //获取库中的所有表名集合
$fields = $db->fields($table); //获取数据表的字段信息集合

2.切换数据库
$db->master($bool=0); //参数: $bool 1 切换到主库操作, 0 切换到从库操作

3.获取数据表操作实例
$t = $db->table('表名'); //打开数据表(完整表名)
$t = $db->ptable('省略前缀的表名'); //打开数据表(省略前缀的表名)

四.数据表操作







*/


//数据库操作类
//==================================

class DBMysqlX
{
    //主(写)库信息
    protected $master = array(
        'db' => NULL, //pdo 数据库连接实例
        'info' => array(), //配置信息
    );

    //从(读)库信息
    protected $slave = array(
        'db' => NULL, //pdo 数据库连接实例
        'info' => array(), //配置信息
    );

    //数据库连接配置信息
    protected $config = array(
        'charset' => 'utf8', //字符集,默认utf8
        'isdebug' => false, //是否调试模式,默认false
        'prefix' => '', //表名前缀,默认空串''
    );

    //构造函数
    function __construct($config = array())
    {
        !is_array($config) && $config = array();
        if (!empty($config)) {
            //工作类型: 0 单库操作(不读写分离, 默认) , 1 执行读写分离配置
            //!empty($config['type']) && $this->config['type']=1;
            //字符集,默认utf8
            !empty($config['charset']) && ($this->config['charset'] = trim($config['charset']));
            //是否调试模式,默认false
            (int)$config['isdebug'] && ($this->config['isdebug'] = true);
            //表名前缀,默认空串''
            !empty($config['prefix']) && ($this->config['prefix'] = trim($config['prefix']));

            //主库 服务器
            $master = $config['master'];
            if (is_array($master) && !empty($master)) {
                $this->master['info'] = array(
                    'host' => trim($master['host']),
                    'db' => trim($master['db']),
                    'user' => trim($master['user']),
                    'password' => trim($master['password']),
                    'port' => (int)$master['port'] > 0 ? $master['port'] : 3306,
                );
            }

            //从库 服务器列表
            !is_array($config['slave']) && $config['slave'] = array();
            if (empty($config['slave'])) {
                $this->slave = $this->master;
            } else {
                $sInfo = array();
                foreach ($config['slave'] as $s) {
                    if (!is_array($s) || empty($s)) {
                        continue;
                    }
                    //端口
                    $s['port'] = (int)$s['port'];

                    //规定权重值的范围
                    $i = (int)$s['weight'];
                    $i < 1 && $i = 1;
                    $i > 10 && $i = 10;

                    //获取配置信息
                    $s = array(
                        'host' => trim($s['host']),
                        'db' => trim($s['db']),
                        'user' => trim($s['user']),
                        'password' => trim($s['password']),
                        'port' => $s['port'] > 0 ? $s['port'] : 3306,
                    );

                    //根据权重写入
                    while ($i) {
                        $sInfo[] = $s;
                        $i--;
                    }
                }
                if (!empty($sInfo)) {
                    shuffle($sInfo); //随机打乱顺序
                    $sInfo = $sInfo[mt_rand(0, count($sInfo) - 1)];
                }
                $this->slave['info'] = $sInfo;
            }
            //print_r($this->master);
            //print_r($this->slave);
            //exit;
        }
    }

    //使用已经有数据库链接 初始化
    //参数: $wDB 写库, $rDB 从库
    function init(PDO $wDB, PDO $rDB = NULL)
    {
        if (empty($wDB)) {
            return false;
        }
        empty($rDB) && $rDB = $wDB;

        $this->master['db'] = $wDB;
        $this->slave['db'] = $rDB;

        return $this;
    }


    //取得pdo_mysql实例
    //参数: $type 类型(0 主库-写, 1 从库-读)
    private function db($type = 0)
    {
        $type = (int)$type;
        if ($type) {
            //从库-读
            //================
            empty($this->slave['db']) && ($this->slave['db'] = $this->connect($this->slave['info']));
            return $this->slave['db'];
        } else {
            //主库-写
            //=================
            empty($this->master['db']) && ($this->master['db'] = $this->connect($this->master['info']));
            return $this->master['db'];
        }
    }

    //数据库连接实例池
    protected static $pdo = array();

    //连接数据库
    //参数： $info 数据库配置信息 array(host, port, db, user, password)
    function connect($info)
    {
        if (!is_array($info) || empty($info)) {
            return false;
        }

        //pdo实例化,同样的数据库信息只实例化一次
        $dsn = "mysql:host={$info['host']};port={$info['port']};dbname={$info['db']}";
        $md5 = md5($dsn);
        if (empty(self::$pdo[$md5])) {
            try {
                $db = new PDO($dsn, $info['user'], $info['password'], array(
                    PDO::ATTR_PERSISTENT => true, //持久连接
                ));
                $db->exec('SET NAMES ' . $this->config['charset']); //设置字符集

                self::$pdo[$md5] = $db;
            } catch (PDOException $e) {
                $this->error($e->getMessage());
                return false;
            }
        }

        return self::$pdo[$md5];
    }


    //读数据时是否切换到主库操作
    //参数: $bool 是否切换到到主库(0 从库, 1 主库)
    private $isMaster = 0; //当前是否为主库

    function master($bool = 0)
    {
        $this->isMaster = (int)$bool;

        return $this;
    }

    //查询记录集(仅select,show等查询一类的语句
    //参数: sql语句模板, array(参数1,参数2...)
    function query($sql, $data = array())
    {
        $startime = gettimeofday(true); //记录开始时间

        $sql = trim($sql);
        if (!is_array($data)) {
            $data = isset($data) ? array(trim($data)) : array();
        }

        //执行sql
        $db = $this->isMaster ? $this->db(0) : $this->db(1); //取得数据库操作对象(是否读主库
        $sth = $db->prepare(trim($sql));
        $bool = $sth->execute($data);
        $errInfo = $sth->errorInfo(); //取得错误信息
        $sqlArr = empty($data) ? $sql : array($sql, $data);

        //写日志
        if ($this->config['isdebug']) {
            $log = array(
                'time' => number_format(gettimeofday(true) - $startime, '6', '.', ''),//计算时间差
                'sql' => $sqlArr,
            );
            !$bool && $log['redata'] = $errInfo;
            $this->log[] = $log;
        }

        if (!$bool) {
            //执行特殊错误处理
            if ($errInfo[0] == 'HY093') {
                $errInfo = array('参数绑定错误');
            }
            $this->error(array('SQL' => $sqlArr, 'ErrInfo' => $errInfo));

            //返回空数组
            $rs = array();
        } else {
            //执行成功
            $rs = $sth->fetchAll(PDO::FETCH_ASSOC); //取得记录集

            //处理空值数据,一般情况下，text类型
            if (is_array($rs)) {
                foreach ($rs as $key1 => $r) {
                    if (is_array($r)) {
                        foreach ($r as $key2 => $val) {
                            if (is_null($val)) {
                                $rs[$key1][$key2] = ''; //处理null的字段
                            } elseif (in_array($val, array('0000-00-00', '0000-00-00 00:00:00'))) {
                                $rs[$key1][$key2] = ''; //处理日期，日期时间为 0000-00-00 或 0000-00-00 00:00:00 的字段
                            }
                        }
                    }
                }
            }
        }

        return $rs;
    }

    //执行一条sql(非select语句
    //参数: $sqlArr array('sql语句模板',参数1[,参数2...)
    protected $lastID; //最后插入行的ID
    protected $num; //受上一个 SQL 语句影响的行数

    function run($sql, $data = array())
    {
        $startime = gettimeofday(true); //记录开始时间

        //执行sql
        $db = $this->db(0); //取得数据库操作对象
        $sth = $db->prepare($sql);
        $bool = $sth->execute($data);
        $errInfo = $sth->errorInfo(); //取得错误信息
        $sqlArr = empty($data) ? $sql : array($sql, $data);

        if (!$bool) {
            //执行特殊错误处理
            if ($errInfo[0] == 'HY093') {
                $errInfo = array('参数绑定错误', $sqlArr);
            }
            $this->error(array('SQL' => $sqlArr, 'ErrInfo' => $errInfo));
        } else {
            //执行成功
            $this->lastID = $db->lastInsertId(); //最后插入行的ID
            $this->num = $sth->rowCount(); //受上一个SQL 语句影响的行数
        }

        //写日志
        if ($this->config['isdebug']) {
            $log = array(
                'time' => number_format(gettimeofday(true) - $startime, '6', '.', ''),//计算时间差
                'sql' => $sqlArr,
            );
            !$bool && $log['redata'] = $errInfo;
            $this->log[] = $log;
        }

        return $bool;
    }

    //获取数据库中 表名列表
    protected $tableNames = array();

    function tables()
    {
        if (empty($this->tableNames)) {
            $rs = $this->query('SHOW TABLES');
            foreach ($rs as $r) {
                $this->tableNames[] = trim(array_shift($r));
            }
            !empty($this->tableNames) && $this->tableNames = array_filter($this->tableNames);
        }

        return $this->tableNames;
    }


    //获取数据表的 字段信息
    protected $fields = array();

    function fields($table)
    {
        $table = trim($table);
        if (empty($table) || !in_array($table, $this->tables())) {
            $this->error("Table '{$table}' doesn't exist");
            return false;
        }

        if (empty($this->fields[$table])) {
            //不存在则去取得该表的字段集
            $result = $this->query("SHOW COLUMNS FROM `{$table}`");

            //整理字段信息
            $info = array();
            if ($result) {
                foreach ($result as $key => $val) {
                    //主键
                    if (!isset($info['pk']) && strtolower($val['Key']) == 'pri') {
                        $info['pk'] = $val['Field'];
                    }
                    //自增键
                    if (!isset($info['ai']) && strtolower($val['Extra']) == 'auto_increment') {
                        $info['ai'] = $val['Field'];
                    }
                    //字段列表信息
                    $info['fields'][$val['Field']] = array(
                        //'name'    => $val['Field'],
                        'type' => $val['Type'],
                        'notnull' => strtolower($val['Null']) === 'no', //not null is empty, null is yes
                        'default' => $val['Default'],
                        //'primary' => strtolower($val['Key'])=='pri',
                        //'autoinc' => strtolower($val['Extra'])=='auto_increment',
                    );
                }
            }
            //缓存进字段数组中
            $this->fields[$table] = $info;
        }

        return $this->fields[$table];
    }

    //创建表实例
    //protected $tableDBs = array();
    //完整表名
    function table($table, $as = '')
    {
        $t = new DBMysqlTable($this);
        return $t->table($table, $as);
        /*
        if (!isset($this->tableDBs[$table])) {
            $this->tableDBs[$table] = new DBMysqlTable($this);
            $this->tableDBs[$table]->table($table, $as);
        }

        return $this->tableDBs[$table];
        */
    }

    //省略前缀的表名
    function ptable($table, $as = '')
    {
        return $this->table($this->config['prefix'] . trim($table), $as);
    }

    //取得最后插入行的ID
    function lastID()
    {
        return $this->lastID;
    }

    //取得上一次执行sql受影响的行数
    function num()
    {
        return $this->num;
    }


    //设置/获取错误
    protected $error = array(); //记录最后一次执行SQL的错误信息

    function error($error = NULL)
    {
        if (isset($error)) {
            //写入错误信息
            $this->error = $error;

            //是否输出错误信息并停止程序
            if ($this->config['isdebug'] && !empty($error)) {
                echo __CLASS__ . " Error :\r\n";
                echo mb_convert_encoding(print_r($error, 1), 'UTF-8', 'ASCII,UTF-8,GBK,ISO-8859-1');
                exit;
            }
        } else {
            //获取错误信息
            return $this->error;
        }
    }

    //获取日志
    protected $log = array(); //执行的SQL日志记录

    function log()
    {
        return $this->log;
    }

}


//表操作类
//==================================

class DBMysqlTable
{

    protected $db;

    function __construct(DBMysqlX $db)
    {
        if (isset($db)) {
            $this->db = $db;
        }
    }

    //配置SQL参数
    //==========================

    protected $table; //表名
    protected $table_as; //表别与别名组合
    protected $pk; //主键
    protected $fields; //字段列表

    function table($table, $as = '')
    {
        $table = trim($table);
        if (empty($table) || !in_array($table, $this->db->tables())) {
            return false;
        }

        //设置表名
        $this->table = $table;
        //表名与别名组合
        $as = trim($as);
        $this->table_as = "`{$table}`" . (empty($as) ? '' : " AS `{$as}`");
        //获取字段信息
        $fields = $this->db->fields($table);
        $this->pk = $fields['pk'];
        $this->fields = array_keys($fields['fields']);

        return $this;
    }

    //读数据时是否切换到主库操作
    //参数: $bool 是否切换到到主库(0 从库, 1 主库)
    function master($bool)
    {
        $this->db->master($bool);

        return $this;
    }

    //清空数据表(由于此操作非常危险，所以使用要谨慎)
    //参数：$bool 是否确定要进行此操作
    function truncate($bool = false)
    {
        if ($bool) {
            //组织sql语句
            $sql = "TRUNCATE `{$this->table}`";
            return $this->db->run($sql);
        } else {
            return false;
        }
    }

    //过滤数据表的字段
    function filterFields($data)
    {
        foreach ($data as $key => $r) {
            if (!in_array($key, $this->fields)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    //关联表
    protected $join;

    function join($table, $on, $as = '')
    {
        $table = trim($table);
        if (empty($table)) {
            return $this;
        }

        //组合表名与别名
        $as = trim($as);
        $table_as = "`{$table}`" . (empty($as) ? '' : " AS `{$as}`");

        $on = trim($on);
        if (empty($on)) {
            return $this;
        }

        $this->join = "LEFT JOIN {$table_as} ON {$on}";
        return $this;
    }

    //where
    protected $where;
    protected $wheredata = array();

    function where($where, $data = NULL)
    {
        //数组型
        if (is_array($where)) {
            $arr = $data = array();
            foreach ($where as $key => $val) {
                if ($key == '_string') { //处理自定义的sql串
                    if (!empty($val)) {
                        if (is_array($val)) { //如果为数组
                            //第一个元素为 sql串
                            $v = trim(array_shift($val));
                            if(!empty($v)){
                                $arr[] = $v;
                                //其它元素为值
                                foreach ($val as $v) {
                                    $data[] = $v;
                                }
                            }
                        } else {
                            $val = trim($val);
                            !empty($val) && $arr[] = $val;
                        }
                    }
                } else {
                    $arr[] = "`{$key}`=?";
                    $data[] = $val;
                }
            }
            $where = implode(' AND ', $arr);
        }

        $where = trim($where);
        if (!empty($where)) {
            $this->where = "WHERE {$where}";

            if (isset($data)) {
                !is_array($data) && $data = array(trim($data));
                $this->wheredata = $data;
            }
        }

        return $this;
    }

    //group
    protected $group;

    function group($group)
    {
        $group = trim($group);
        !empty($group) && $this->group = "GROUP BY {$group}";

        return $this;
    }

    //having
    protected $having;
    protected $havingdata = array();

    function having($having, $data = NULL)
    {
        //数组型
        if (is_array($having)) {
            $arr = $data = array();
            foreach ($having as $key => $val) {
                $arr[] = "`{$key}`=?";
                $data[] = $val;
            }
            $having = implode(' AND ', $arr);
        }

        $having = trim($having);
        if (!empty($having)) {
            $this->having = "HAVING {$having}";

            if (isset($data)) {
                !is_array($data) && $data = array(trim($data));
                $this->havingdata = $data;
            }
        }

        return $this;
    }

    //order
    protected $order;

    function order($order)
    {
        $order = trim($order);
        !empty($order) && $this->order = "ORDER BY {$order}";

        return $this;
    }

    //limit
    protected $limit;

    function limit($m, $n = NULL)
    {
        $m = (int)$m;
        $this->limit = "LIMIT " . (isset($n) ? "{$m}," . intval($n) : $m);

        return $this;
    }

    //指定字段
    protected $field = '*';

    function field($field, $isFilter = true)
    {
        if (!is_array($field)) {
            $field = explode(',', $field);
        }
        $field = array_unique(array_filter(array_map('trim', $field)));
        (bool)$isFilter && $field = array_intersect($field, $this->fields);
        !empty($field) && $this->field = implode(',', $field);

        return $this;
    }

    //各配置参数复位
    function reset()
    {
        $this->join = '';
        $this->field = '*';

        $this->where = '';
        $this->wheredata = array();

        $this->group = '';
        $this->order = '';
        $this->limit = '';

        $this->having = '';
        $this->havingdata = array();
    }

    //获取参数
    function data()
    {
        $data = array();
        foreach ($this->wheredata as $d) {
            $data[] = $d;
        }
        foreach ($this->havingdata as $d) {
            $data[] = $d;
        }

        return $data;
    }

    //读操作
    //==========================

    //记录集
    //参数：$usePKey 是否使用主键做为结果集数组的下标(默认：false)
    function getrs($usePKey = false)
    {
        $sql = "SELECT {$this->field} FROM {$this->table_as} " . trim("{$this->join} {$this->where} {$this->group} {$this->having} {$this->order} {$this->limit}");
        $rs = $this->db->query($sql, $this->data());

        //是否使用主键
        if ($usePKey) {
            $_rs = array();
            foreach ($rs as $r) {
                $_rs[$r[$this->pk]] = $r;
            }
            $rs = $_rs;
        }

        $this->reset(); //复位
        return $rs;
    }

    //一条记录
    function getr()
    {
        $this->limit(1);
        $rs = $this->getrs();
        $r = is_array($rs[0]) ? $rs[0] : array();

        return $r;
    }

    //根据主键值获取记录，或指字段的值
    function get($id, $field = '')
    {
        $id = trim($id);
        $field = trim($field);

        if (empty($this->pk)) {
            return empty($field) ? array() : NULL;
        }

        $r = $this->where("`{$this->pk}`=?", array($id))->field($field)->getr();

        return empty($field) ? $r : $r[$field];
    }

    //统计
    function count()
    {
        $r = $this->field('count(*) AS num', false)->getr();

        return (int)$r['num'];
    }

    //取最大值
    function max($field)
    {
        $r = $this->field("MAX(`{$field}`) as max", false)->getr();
        return $r['max'];
    }

    //取最小值
    function min($field)
    {
        $r = $this->field("MIN(`{$field}`) as min", false)->getr();
        return $r['min'];
    }

    //同时取出最大最小值
    function maxmin($field)
    {
        $r = $this->field("MIN(`{$field}`) as min, MAX(`{$field}`) as max", false)->getr();
        return $r;
    }

    //取平均值
    function avg($field)
    {
        $r = $this->field("AVG(`{$field}`) as avg", false)->getr();
        return $r['avg'];
    }

    //求和
    function sum($field)
    {
        $r = $this->field("SUM(`{$field}`) as sum", false)->getr();
        return $r['sum'];
    }

    //分页获取数据
    function page($page = 1, $size = 20)
    {
        //统计符合条件的记录数
        $sql = "SELECT count(*) as num FROM {$this->table_as} " . trim("{$this->join} {$this->where}");
        $rs = $this->db->query($sql, $this->data());
        $total = (int)$rs[0]['num'];
        if (!$total) {
            return array(
                'total' => 0,
                'size' => (int)$size,
                'page' => 1,
                'pages' => 1,
                'rs' => array(),
            );
        }

        //自动修正参数
        $page = (int)$page;
        $page < 1 && $page = 1;
        $size = (int)$size;
        $size < 1 && $size = 20;
        $rs = $this->limit(($page - 1) * $size, $size)->getrs();

        return array(
            'total' => $total,
            'size' => $size,
            'page' => $page,
            'pages' => ceil($total / $size), //计算总页数
            'rs' => $rs,
        );
    }

    //写操作
    //==========================

    //添加记录
    //参数: $r 记录的各字段数组array('字段名1'=>'字段值1',....)
    //$returnId 是否返回添加记录的自增ID
    function insert($r, $returnId = true)
    {
        $r = $this->filterFields($r); //过滤字段
        if (empty($r)) {
            return false;
        }

        //组织SQL
        $sql = "INSERT INTO {$this->table_as} SET ";
        $data = array();
        foreach ($r as $key => $val) {
            $sql .= "`{$key}`=?,";
            $data[] = $val;
        }
        $sql = rtrim($sql, ',');

        //执行sql
        $bool = $this->db->run($sql, $data);

        $this->reset(); //复位
        if ($returnId) {
            return $bool ? $this->db->lastID() : 0;
        }
        return $bool;
    }


    //如果表中已经存在这条记录，先删除表中的记录，再插入一条新记录
    function replace($r)
    {
        $r = $this->filterFields($r); //过滤字段
        if (empty($r)) {
            return false;
        }

        //生成sql语句
        $sql = "REPLACE INTO {$this->table_as} SET ";
        $data = array();
        foreach ($r as $key => $val) {
            $sql .= "`{$key}`=?,";
            $data[] = $val;
        }
        $sql = rtrim($sql, ',');

        //执行sql
        return $this->db->run($sql, $data);
    }


    //更新数据
    function update($r)
    {
        //过滤字段
        $r = $this->filterFields($r);
        if (empty($r)) {
            return false;
        }

        //组织SQL
        $sql = "UPDATE {$this->table_as} SET ";
        foreach ($r as $key => $val) {
            $sql .= "`{$key}`=?,";
            $data[] = $val;
        }
        $sql = rtrim($sql, ',') . " {$this->where} {$this->order} {$this->limit}";
        $data = array_merge($data, $this->data());

        //执行sql
        $bool = $this->db->run($sql, $data);

        $this->reset(); //复位
        return $bool;
    }


    /*
     * 按主键更新记录
     * 参数:
     * $id 主键的值
     * $fields 记录的各字段数组 array('字段名1'=>'字段值1',....)
     */
    function setr($id, $fields = array())
    {
        $id = trim($id);
        if (empty($id)) {
            return false;
        }

        $this->reset(); //复位
        return $this->where(array($this->pk => $id))->update($fields);
    }

    /*
     * 设置指定主键的记录的字段值($key 参数不为空)
     * 参数:
     * $id 主键的值
     * $key 提定要获取的哪个字段的值
     * $val 设定的值
     */
    function set($id, $key, $val)
    {
        $key = trim($key);
        if (empty($key)) {
            return false;
        }

        return $this->setr($id, array($key => trim($val)));
    }

    /*
     * 根据主键值,对数值型字段进行自增/自减运算(如果传入的数值为负数的话)
     * 参数:
     * $id 主键的值
     * $key 指定的字段
     * $step 自增数值(默认为1,如果数值为负，表示减法运算)
     * 注: 仅用于数值型字段
     */
    function incr($id, $key, $num = 1)
    {
        $id = trim($id);
        $key = trim($key);
        if (empty($id) || empty($key)) {
            return false;
        }

        //组织sql语句
        $sql = "UPDATE {$this->table_as} SET `{$key}`= ? + `{$key}` WHERE `{$this->pk}`=?";

        //执行sql
        return $this->db->run($sql, array((float)$num, $id));
    }

    //删除记录
    function del()
    {
        //防止误操作，删除时where条件不能为空
        if (empty($this->where)) {
            return false;
        }

        //组织sql语句
        $sql = "DELETE FROM {$this->table_as} {$this->where} {$this->order} {$this->limit}";
        $bool = $this->db->run($sql, $this->data());

        $this->reset(); //复位
        return $bool;
    }

    /*
     * 按主键删除记录
     * 参数:
     * $ids 主键的值
     * 例1: $bool = $db->delr(id1); //删除一条记录
     * 例2: $bool = $db->delr(array(id1,id2,id3....)); //删除多条记录,参数类型数组 形式
     * 例3: $bool = $db->delr("id1,id2,id3,id4,...."); //删除多条记录,参数类型字符串列表 形式
     */
    function delr($ids = array())
    {
        !is_array($ids) && $ids = explode(',', $ids);
        $ids = array_filter(array_unique(array_map('trim', $ids)));
        if (empty($ids)) {
            return false;
        }
        $vpad = implode(',', array_pad(array(), count($ids), '?'));

        $this->reset(); //复位
        return $this->where("`{$this->pk}` IN({$vpad})", $ids)->del();
    }

    //取得上一次执行sql受影响的行数
    function num()
    {
        return $this->db->num();
    }

    //事务处理
//=================================

    //开启一个事务
    private $transTimes = 0; //当前开始的事务层级数, 0 表示当前未开启事务

    function transaction()
    {
        !$this->transTimes && $this->db->run('SET AUTOCOMMIT=0'); //设置为不自动提交
        $this->transTimes++; //计数器

        //第一次的事务才执行开启
        if ($this->transTimes == 1) {
            return $this->db->run('START TRANSACTION');
        }

        return true;
    }

    //事务提交
    function commit()
    {
        //嵌套时，最外层的提交才真正提交
        if ($this->transTimes == 1) {
            $this->transTimes = 0; //清0
            $bool = $this->db->run('COMMIT');
            $bool && $this->db->run('SET AUTOCOMMIT=1'); //默认为自动提交
            return $bool;
        }

        $this->transTimes && $this->transTimes--; //计数器

        return true;
    }

    //事务回滚
    function rollback()
    {
        $this->transTimes = 0; //清0
        $bool = $this->db->run('ROLLBACK');
        $bool && $this->db->run('SET AUTOCOMMIT=1'); //默认为自动提交

        return $bool;
    }


    /*
     * 事务封锁 - 排它锁(封锁后，其它事务不能读，也不能写)
     * 参数:
     * $id 数据表中的主键值
     */
    function xLock($id)
    {
        if (empty($id)) {
            return false;
        }

        //执行
        $sql = "SELECT * FROM {$this->table_as} WHERE `{$this->pk}`=? LIMIT 1 FOR UPDATE";
        $rs = $this->db->query($sql, array($id));
        $r = is_array($rs[0]) ? $rs[0] : array();

        return $r;
    }

    //事务封锁 - 共享锁(封锁后，其它事务可以读，但不能写)
    function sLock($id)
    {
        if (empty($id)) {
            return false;
        }

        //执行
        $sql = "SELECT * FROM {$this->table_as} WHERE `{$this->pk}`=? LIMIT 1 LOCK IN SHARE MODE";
        $rs = $this->db->query($sql, array($id));
        $r = is_array($rs[0]) ? $rs[0] : array();

        return $r;
    }

    //获取执行的日志
    function log()
    {
        return $this->db->log();
    }

    //析构
    function __destruct()
    {
        //检查当前是否有事务未关闭,如果有则执行回滚操作
        $this->transTimes && $this->rollback();
    }

}
