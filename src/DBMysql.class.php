<?php
/*//=================================
//
//	 	 PDO的Mysql操作类
//       [更新时间: 2016-05-04]
//
//===================================

使用说明:
//1.实例化
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
$db = new DBMysql('mysql',$config);


//2.执行sql
$rs = $db->query(array('sql语句模板',参数1[,参数2...)); //查询记录集(仅用于查询类语句)
$bool = $db->run(array('sql语句模板',参数1[,参数2...)); //执行一条sql(非查询类的语句)

//3.数据库操作
//3.1 切换数据库
$db->master($bool=0); //参数: $bool 1 切换到主库操作, 0 切换到从库操作
//3.2 数据表操作
$db->table('表名'); //打开数据表(完整表名)
$db->ptable('省略前缀的表名'); //打开数据表(省略前缀的表名)
$arr = $db->tableInfo($table='表名'); //获取数据表的信息

//4.记录操作
//4.1 读操作
//取得一条记录
$r = $db->getr(array(
		'select'=>'字段1,字段2',
		'where'=>'字段m=? and 字段n=?',
		'group'=>'字段x,字段y',
		'having'=>'字段a=?',
		'order'=>'字段z desc',
		值m,值n,值a,
	));
//取得符合条件的记录集
$rs = $db->getrs(array(
		'select'=>'字段1,字段2',
		'where'=>'字段m=? and 字段n=?',
		'group'=>'字段x,字段y',
		'having'=>'字段a=?',
		'order'=>'字段z desc',
		'limit'=>'n1,n2',
		值m,值n,值a,
	));
//分页搜索
$rs = $db->search(array(
			'select'=>'字段1,字段2',
			'where'=>'字段m=? and 字段n=?',
			'group'=>'字段x,字段y',
			'having'=>'字段a=?',
			'order'=>'字段z desc',
			'limit'=>'n1,n2',
			值m,值n,值a,
		),$page=1,$size=20)
//统计符合条件的记录数
$rs_num = $db->count(array(
		'where'=>'字段m=? and 字段n=?',
		'group'=>'字段x,字段y',
		'having'=>'字段a=?',
		值m,值n,值a,
	));
//获取指定主键的记录 或 指定的字段的值($key 参数不为空)
$r 或 $value = $db->get($id,$key='字段名');


//4.2 写操作
$bool或$id = $db->insert(array('字段名1'=>'字段值1',....),是否返回新添加记录的id); //添加记录
$id = $db->lastID(); //取得最后插入行的ID
//更新记录(防止误操作，更新时where条件不能为空)
$bool = $db->update($sqlArr = array(
			'where'=>'字段m=? and 字段n=?',
			'order'=>'字段z desc',
			'limit'=>'n1,n2',
			值m,值n,值a,
		),array('字段名1'=>'字段值1',....));
$bool = $db->insert_update(array('字段名1'=>'字段值1',....),$setSqlArr=array("字段名1=?,字段名2=1+?,...",值1,值2));//插入一条记录，如果表中已经存在这条记录时，则根据指定的$setSqlArr参数的sql数组更新此条记录
$bool = $db->replace(array('字段名1'=>'字段值1',....)); //如果表中已经存在这条记录，先删除表中的记录，再插入一条新记录
$bool = $db->set($id,$key,$val); //设置指定主键的记录的字段值($key 参数不为空)
$bool = $db->incr($id,$key,$num=1); //根据主键值,对数值型字段进行自增运算

//删除记录(防止误操作，删除时where条件不能为空)
$bool = $db->del($sqlArr = array(
			'where'=>'字段m=? and 字段n=?',
			'order'=>'字段z desc',
			'limit'=>'n1,n2',
			值m,值n,值a,
		));
$num = $db->num(); //取得上一次执行sql受影响的行数

//5.事务处理
$bool = $db->transaction(); //开启一个事务
$bool = $db->commit(); //事务提交
$bool = $db->rollback(); //事务回滚

//事务封锁
$bool = $db->xLock($id); //排它锁(封锁后，其它事务不能读，也不能写), 锁指定记录
$bool = $db->sLock($id); //共享锁(封锁后，其它事务可以读，但不能写), 锁指定记录

//6.其它
$arr = $db->log(); //取得执行sql记录

//注意: 
//在条件数组中，用like进行模糊查询时的写法,%符号要放在字段值元素中。
如: $where = array(
		'name like ?',
		"%查询关键字%",
	);
*/

class DBMysql
{
    //主库信息
    private static $master = array(
        'db'=>NULL, //实例
        'md5'=>'', //md5标识,用于识别数据库连接实例
        'info'=>array(), //配置信息
    );
    //从库信息
    private static $slave = array(
        'db'=>NULL, //实例
        'md5'=>'', //md5标识,用于识别数据库连接实例
        'info'=>array(), //配置信息
    );

    //数据库连接配置信息
    private $config = array(
        'charset'=>'utf8', //字符集,默认utf8
        'isdebug'=>false, //是否调试模式,默认false
        'prefix'=>'', //表名前缀,默认空串''
    );

    private $table = ''; //当前操作的数据表
    private static $tableDB = array(); //当前初始化过的数据表操作实例
    private static $tableInfo = array(); //缓存各表的字段信息 ['pk'] 主键字段名, ['ai'] 自增字段名, fields = array() 字段列表
    private static $log = array(); //执行的SQL日志记录

    //初使化
    //参数: $config 数据库连接信息
    function __construct($config)
    {
        !is_array($config) && $config = array();

        //工作类型: 0 单库操作(不读写分离, 默认) , 1 执行读写分离配置
        //!empty($config['type']) && $this->config['type']=1;
        //字符集,默认utf8
        !empty($config['charset']) && ($this->config['charset'] = trim($config['charset']));
        //是否调试模式,默认false
        (int)$config['isdebug'] && ($this->config['isdebug'] = true);
        //表名前缀,默认空串''
        !empty($config['prefix']) && ($this->config['prefix'] = trim($config['prefix']));

        //主库 服务器
        if(is_array($config['master'])){
            $mInfo = array(
                'host'=>trim($config['master']['host']),
                'db'=>trim($config['master']['db']),
                'user'=>trim($config['master']['user']),
                'password'=>trim($config['master']['password']),
                'port'=>(int)$config['master']['port']>0?$config['master']['port']:3306,
            );
        }else{
            $mInfo = array();
        }
        self::$master['md5'] = md5(json_encode($mInfo));
        self::$master['info'] = $mInfo;

        //从库 服务器列表
        $sInfo = array();
        !is_array($config['slave']) && $config['slave'] = array();
        foreach($config['slave'] as $s){
            if(!is_array($s) || empty($s)){
                continue;
            }

            //端口
            $s['port'] = (int)$s['port'];

            //规定权重值的范围
            $i = (int)$s['weight'];
            $i<1 && $i = 1;
            $i>10 && $i = 10;

            //获取配置信息
            $s = array(
                'host'=>trim($s['host']),
                'db'=>trim($s['db']),
                'user'=>trim($s['user']),
                'password'=>trim($s['password']),
                'port'=>$s['port']>0?$s['port']:3306,
            );

            //根据权重写入
            while($i){
                $sInfo[] = $s;
                $i--;
            }
        }
        if(!empty($sInfo)){
            shuffle($sInfo); //随机打乱顺序
            $sInfo = $sInfo[mt_rand(0,count($sInfo)-1)];
        }
        self::$slave['md5'] = md5(json_encode($sInfo));
        self::$slave['info'] = $sInfo;

        //print_r(self::$master);
        //print_r(self::$slave);
        //exit;
    }

    //连接数据库
    //参数： $info 数据库配置信息
    private function connect($info)
    {
        if(!is_array($info) || empty($info)){
            $this->showErr('没有可用的数据库参数');
        }else{
            try{
                $dsn = 'mysql:host='.$info['host'].';port='.$info['port'].';dbname='.$info['db'];
                $db = new PDO($dsn,$info['user'],$info['password'],array(
                    PDO::ATTR_PERSISTENT=>true, //持久连接
                ));
                $db->exec('SET NAMES '.$this->config['charset']); //设置字符集
            }catch(PDOException $e){
                $this->showErr($e->getMessage());
            }
        }

        return $db;
    }


    //取得pdo_mysql实例
    //参数: $type 类型(0 主库-写, 1 从库-读)
    private function db($type=0)
    {
        $type = (int)$type;
        if($type){
            //从库-读
            //================
            if(!empty(self::$slave['db'])){
                return self::$slave['db'];
            }

            //如果从库为空，则直接读主库
            if(empty(self::$slave['info'])){
                self::$slave['info'] = self::$master['info'];
                self::$slave['md5'] = self::$master['md5'];
            }

            //与主库实例一致
            if(self::$slave['md5']==self::$master['md5'] && !empty(self::$master['db'])){
                return self::$slave['db'] = self::$master['db'];
            }

            return self::$slave['db'] = $this->connect(self::$slave['info']);
        }else{
            //主库-写,
            //=================
            if(!empty(self::$master['db'])){
                return self::$master['db'];
            }

            if(self::$master['md5']==self::$slave['md5'] && !empty(self::$slave['db'])){
                return self::$master['db'] = self::$slave['db'];
            }

            return self::$master['db'] = $this->connect(self::$master['info']);
        }

    }

    //是否切换到主库操作
    //参数: $bool 是否切换到到主库(0 从库, 1 主库)
	private $isMaster = 0; //当前是否为主库
    function master($bool=0)
    {
		$this->isMaster = (int)$bool;
		
        return $this;
    }

    //查询记录集(仅select语句
    //参数: $sqlArr array('sql语句模板',参数1[,参数2...)
    function query($sqlArr)
    {
        $startime = gettimeofday(true); //记录开始时间

        $sql = $this->sql($sqlArr); //处理参数
        $db = $this->isMaster?$this->db(0):$this->db(1); //取得数据库操作对象(是否读主库
		
        //执行sql
        $sth = $db->prepare($sql['sql']);
        $bool = $sth->execute($sql['data']);
        $errInfo = $sth->errorInfo();//取得错误信息
        $this->sqlLog($sqlArr,$errInfo,$startime); //写日志

        if(!$bool){
            //执行特殊错误处理
            if($errInfo[0]=='HY093'){
                $errInfo = array('参数绑定错误',$sqlArr);
            }
            $this->showErr(array('SQL'=>$sqlArr,'ErrInfo'=>$errInfo));
            //返回空数组
            $rs = array();
        }else{
            //执行成功
            $rs = $sth->fetchAll(PDO::FETCH_ASSOC); //取得记录集

            //处理空值数据,一般情况下，text类型
            if(is_array($rs)) {
                foreach($rs as $key1=>$r){
                    if(is_array($r)){
                        foreach($r as $key2=>$val){
                            if(is_null($val)){
                                $rs[$key1][$key2] = ''; //处理null的字段
                            }elseif(in_array($val,array('0000-00-00','0000-00-00 00:00:00'))){
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
    private $lastID; //最后插入行的ID
    private $num; //受上一个 SQL 语句影响的行数
    function run($sqlArr)
    {
        $startime = gettimeofday(true); //记录开始时间

        $sql = $this->sql($sqlArr); //处理参数
        $db = $this->db(0); //取得数据库操作对象
        //print_r($sql);
        //执行sql
        $sth = $db->prepare($sql['sql']);
        $bool = $sth->execute($sql['data']);
        $errInfo = $sth->errorInfo();//取得错误信息
        $this->sqlLog($sqlArr,$errInfo,$startime); //写日志

        if(!$bool){
            //执行特殊错误处理
            if($errInfo[0]=='HY093'){
                $errInfo = array('参数绑定错误',$sqlArr);
            }
            $this->showErr(array($sqlArr,$errInfo));
        }else{
            //执行成功
            $this->lastID = $db->lastInsertId(); //最后插入行的ID
            $this->num = $sth->rowCount(); //受上一个 SQL 语句影响的行数
        }
        return $bool;
    }

    //打开数据表(完整表名)
    function table($table='')
    {
        $this->table = trim($table);
        if(!isset(self::$tableDB[$this->table])){
            self::$tableDB[$this->table] = clone $this;
        }
        return self::$tableDB[$this->table];
    }

    //打开数据表(省略前缀的表名)
    function ptable($table='')
    {
        return $this->table($this->config['prefix'].trim($table));
    }


//读操作
//===========================

    //获取数据表的信息
    //参数: $table 表名
    //返回: $arr 缓存各表的字段信息 ['pk'] 主键字段名, ['ai'] 自增字段名, ['fields'] = array() 字段列表
    function tableInfo($table='')
    {
        $table = trim($table);
        if(empty($table)){
            return self::$tableInfo;
        }

        //取得数据表的字段信息
        if(empty(self::$tableInfo[$table])){
            //不存在则去取得该表的字段集
            $result = $this->query("SHOW COLUMNS FROM `{$table}`");
            //整理字段信息
            $info = array();
            if($result){
                foreach($result as $key=>$val){
                    //主键
                    if(strtolower($val['Key'])=='pri'){
                        $info['pk'][] = $val['Field'];
                    }
                    //自增键
                    if(strtolower($val['Extra'])=='auto_increment'){
                        $info['ai'][] = $val['Field'];
                    }
                    //字段列表信息
                    $info['fields'][$val['Field']] = array(
                        'name'    => $val['Field'],
                        'type'    => $val['Type'],
                        'notnull' => strtolower($val['Null'])==='no', //not null is empty, null is yes
                        'default' => $val['Default'],
                        //'primary' => strtolower($val['Key'])=='pri',
                        //'autoinc' => strtolower($val['Extra'])=='auto_increment',
                    );
                }
            }
            //缓存进字段数组中
            self::$tableInfo[$table] = $info;
            //print_r(self::$tableInfo);
        }

        return self::$tableInfo[$table];
    }


    //获取指定主键的记录 或 指定的字段的值($key 参数不为空)
    //参数: $id 主键的值, $key 提定要获取的哪个字段的值
    function get($id,$key='')
    {
        $tableInfo = $this->tableInfo($this->table);
        $pk = $tableInfo['pk'][0];
        if(empty($pk)){
            return '';
        }

        $r = $this->getr(array('where'=>"`{$pk}`=?",trim($id)));

        $key = trim($key);
        return empty($key)?$r:$r[$key];
    }

    /*
    //取得一条记录
    //参数: sql数组
    $sqlArr = array(
        'select'=>'字段1,字段2',
        'where'=>'字段m=? and 字段n=?',
        'group'=>'字段x,字段y',
        'having'=>'字段a=?',
        'order'=>'字段z desc',
        值m,值n,值a,
    )
    */
    function getr($sqlArr=array())
    {
        if($this->table==''){
            return array();
        }

        //处理sql数组
        $sqlArr = $this->subSql($sqlArr);
        $sql = "SELECT {$sqlArr['select']} FROM `".$this->table."` {$sqlArr['where']} {$sqlArr['group']} {$sqlArr['having']} {$sqlArr['order']} LIMIT 1";
        $sqlArr = is_array($sqlArr['values'])?$sqlArr['values']:array();
        array_unshift($sqlArr,$sql);

        //执行
        $rs = $this->query($sqlArr);
        $r = is_array($rs[0])?$rs[0]:array();

        return $r;
    }

    /*
    //取得符合条件的记录集
    //参数: sql数组, 问号占位符
    $sqlArr = array(
        'select'=>'字段1,字段2',
        'where'=>'字段m=? and 字段n=?',
        'group'=>'字段x,字段y',
        'having'=>'字段a=?',
        'order'=>'字段z desc',
        'limit'=>'n1,n2',
        值m,值n,值a,
    )
    */
    function getrs($sqlArr=array())
    {
        if($this->table==''){
            return array();
        }

        //处理sql数组
        $sqlArr = $this->subSql($sqlArr);
        $sql = "SELECT {$sqlArr['select']} FROM `".$this->table."` {$sqlArr['where']} {$sqlArr['group']} {$sqlArr['having']} {$sqlArr['order']} {$sqlArr['limit']}";

        $sqlArr = is_array($sqlArr['values'])?$sqlArr['values']:array();
        array_unshift($sqlArr,$sql);

        //执行sql
        $rs = $this->query($sqlArr);
        !is_array($rs) && $rs = array();

        return $rs;
    }

    /*
    //统计符合条件的记录数
    //参数: sql数组, 问号占位符
    $sqlArr = array(
        'where'=>'字段m=? and 字段n=?',
        'group'=>'字段x,字段y',
        'having'=>'字段a=?',
        值m,值n,值a,
    )
    */
    function count($sqlArr='')
    {
        if($this->table==''){
            return false;
        }

        //处理sql数组
        $sqlArr = $this->subSql($sqlArr);
        $sql = 'SELECT count(*) as num FROM `'.$this->table."` {$sqlArr['where']} {$sqlArr['group']} {$sqlArr['having']}";
        $sqlArr = is_array($sqlArr['values'])?$sqlArr['values']:array();
        array_unshift($sqlArr,$sql);

        //执行sql
        $rs = $this->query($sqlArr);
        return (float)$rs[0]['num'];
    }

    /*
    //分页搜索
    //参数: sql数组, 问号占位符
    $sqlArr = array(
            'select'=>'字段1,字段2',
            'where'=>'字段m=? and 字段n=?',
            'group'=>'字段x,字段y',
            'having'=>'字段a=?',
            'order'=>'字段z desc',
            值m,值n,值a,
        )
    $page 页码
    $size 每页数
    */
    function search($sqlArr=array(),$page=1,$size=20)
    {
        if($this->table==''){
            return array();
        }
        !is_array($sqlArr) && $sqlArr = array();

        //取得符合条件的总记录数
        $total = $this->count($sqlArr);
        if(!$total){
            //如果总记录数为0,则直接返回
            return array(
                'total'=>0,
                'rs'=>array(),
                'page'=>1,
                'size'=>(int)$size,
                'pages'=>1,
            );
        }

        //自动修正参数
        $page = (int)$page;
        $page<1 && $page = 1;
        $size = (int)$size;
        $size<1 && $size = 20;

        //获取记录集
        $sqlArr['limit'] = (($page-1)*$size).','.$size;
        $rs = $this->getrs($sqlArr);

        return array(
            'total'=>$total,
            'rs'=>$rs,
            'page'=>$page,
            'size'=>$size,
            'pages'=>ceil($total/$size), //计算总页数
        );
    }

    /*
    //多表联合搜索
    //参数: $tables 数据表数组, $sqlArr sql数组(问号占位符)
    $sqlArr = array(
            'select'=>'字段1,字段2',
            'where'=>'字段m=? and 字段n=?',
            'group'=>'字段x,字段y',
            'having'=>'字段a=?',
            'order'=>'字段z desc',
            值m,值n,值a,
        )
    $page 页码
    $size 每页数
    */
    function joinSearch($tables=array(),$_sqlArr=array(),$page=1,$size=20)
    {
        if(empty($tables)){
            return array();
        }
        !is_array($_sqlArr) && $_sqlArr = array();
        is_array($tables) && $tables = implode(',',$tables);

        //取得符合条件的总记录数
        $sqlArr = $this->subSql($_sqlArr);
        $sql = "SELECT count(*) as num FROM {$tables} {$sqlArr['where']} {$sqlArr['group']} {$sqlArr['having']}";
        $sqlArr = is_array($sqlArr['values'])?$sqlArr['values']:array();
        array_unshift($sqlArr,$sql);

        $rs = $this->query($sqlArr);
        $total = (float)$rs[0]['num'];
        if(!$total){
            //如果总记录数为0,则直接返回
            return array(
                'total'=>0,
                'rs'=>array(),
                'page'=>1,
                'size'=>(int)$size,
                'pages'=>1,
            );
        }

        //自动修正参数
        $page = (int)$page;
        $page<1 && $page = 1;
        $size = (int)$size;
        $size<1 && $size = 20;
        $_sqlArr['limit'] = (($page-1)*$size).','.$size;

        //获取记录集
        $sqlArr = $this->subSql($_sqlArr);
        $sql = "SELECT {$sqlArr['select']} FROM {$tables} {$sqlArr['where']} {$sqlArr['group']} {$sqlArr['having']} {$sqlArr['order']} {$sqlArr['limit']}";
        $sqlArr = is_array($sqlArr['values'])?$sqlArr['values']:array();
        array_unshift($sqlArr,$sql);

        $rs = $this->query($sqlArr);
        !is_array($rs) && $rs = array();

        return array(
            'total'=>$total,
            'rs'=>$rs,
            'page'=>$page,
            'size'=>$size,
            'pages'=>ceil($total/$size), //计算总页数
        );
    }

//写操作
//===========================

    //添加记录
    //参数: $r 记录的各字段数组array('字段名1'=>'字段值1',....)
    //$returnId 是否返回添加记录的自增ID
    function insert($r=array(),$returnId=false)
    {
        $r = $this->filterFields($r); //过滤字段
        if($this->table=='' || empty($r)){
            return false;
        }

        //生成sql语句
        $sqlArr = array(
            'INSERT INTO `'.$this->table.'` SET '
        );
        foreach($r as $key=>$val){
            $sqlArr[0].= "`{$key}`=?,";
            $sqlArr[] = $val;
        }
        $sqlArr[0] = rtrim($sqlArr[0],',');

        //执行sql
        $bool = $this->run($sqlArr);
        if((bool)$returnId){
            return $bool?(int)$this->lastID():0;
        }
        return $bool;
    }

    //取得最后插入行的ID
    function lastID()
    {
        return $this->lastID;
    }

    //插入一条记录，如果表中已经存在这条记录时，则根据指定的$setSqlArr参数的sql数组更新此条记录
    function insert_update($r=array(),$setSqlArr=array())
    {
        !is_array($setSqlArr) && $setSqlArr = array();
        if(empty($setSqlArr) || empty($setSqlArr[0])){
            return $this->insert($r);
        }

        //过滤字段
        $r = $this->filterFields($r);
        if($this->table=='' || empty($r)){
            return false;
        }

        //生成sql语句
        $sqlArr = array();
        $sql = 'INSERT INTO `'.$this->table.'` SET ';
        foreach($r as $key=>$val){
            $sql.= "`{$key}`=?,";
            $sqlArr[] = $val;
        }
        $sql = rtrim($sql,',');

        //当已经存在这条记录时,做更新操作的子sql语句
        $setVal = $setSqlArr;
        $setSql = trim(array_shift($setVal));
        if(!empty($setSql)){
            $sql .= " ON DUPLICATE KEY UPDATE $setSql";
            foreach($setVal as $key=>$val){
                $sqlArr[] = $val;
            }
        }
        array_unshift($sqlArr,rtrim($sql,','));

        //执行sql
        return $this->run($sqlArr);
    }

    //如果表中已经存在这条记录，先删除表中的记录，再插入一条新记录
    function replace($r)
    {
        $r = $this->filterFields($r); //过滤字段
        if($this->table=='' || empty($r)){
            return false;
        }

        //生成sql语句
        $sqlArr = array(
            'REPLACE INTO `'.$this->table.'` SET '
        );
        foreach($r as $key=>$val){
            $sqlArr[0].= "`{$key}`=?,";
            $sqlArr[] = $val;
        }
        $sqlArr[0] = rtrim($sqlArr[0],',');

        //执行sql
        return $this->run($sqlArr);
    }

    /*
    //更新
    //参数:
    $sqlArr = array(
            'where'=>'字段m=? and 字段n=?',
            'order'=>'字段z desc',
            'limit'=>'n1,n2',
            值m,值n,值a,
        )
    $fields 记录的各字段数组 array('字段名1'=>'字段值1',....)
    */
    function update($sqlArr=array(),$fields=array())
    {
        //过滤字段
        $fields = $this->filterFields($fields);
        if($this->table=='' || empty($fields)){
            return false;
        }

        //处理sql数组
        $sqlArr = $this->subSql($sqlArr);
        //防止误操作，更新时where条件不能为空
        if(empty($sqlArr['where'])){
            return false;
        }

        $_sql = " {$sqlArr['where']} {$sqlArr['order']} {$sqlArr['limit']}";
        $sqlArr = is_array($sqlArr['values'])?$sqlArr['values']:array();
        $sql = 'UPDATE `'.$this->table.'` SET ';
        foreach($fields as $key=>$val){
            $sql.= "`{$key}`=?,";
        }
        $sql = rtrim($sql,',').$_sql;
        $sqlArr = array_values($fields + $sqlArr);
        array_unshift($sqlArr,$sql);

        //执行sql
        return $this->run($sqlArr);
    }


    /*
     * 按主键更新记录
     * 参数:
     * $id 主键的值
     * $fields 记录的各字段数组 array('字段名1'=>'字段值1',....)
     */
    function setr($id,$fields=array())
    {
        $id = trim($id);
        if(empty($id)){
            return false;
        }

        //取得主键名
        $tableInfo = $this->tableInfo($this->table);
        $pk = $tableInfo['pk'][0];
        if(empty($pk)){
            return false;
        }

        return $this->update(array('where'=>"`{$pk}`=?",$id),$fields);
    }

    /*
     * 设置指定主键的记录的字段值($key 参数不为空)
     * 参数:
     * $id 主键的值
     * $key 提定要获取的哪个字段的值
     * $val 设定的值
     */
    function set($id,$key,$val)
    {
        $key = trim($key);
        if(empty($key)){
            return false;
        }

        return $this->setr($id,array($key=>trim($val)));
    }

    /*
     * 根据主键值,对数值型字段进行自增/自减运算(如果传入的数值为负数的话)
     * 参数:
     * $id 主键的值
     * $key 指定的字段
     * $step 自增数值(默认为1,如果数值为负，表示减法运算)
	 * 注: 仅用于数值型字段
     */
    function incr($id,$key,$num=1)
    {
		$id = trim($id);
        $key = trim($key);
        if(empty($id) || empty($key)){
            return false;
        }

        $tableInfo = $this->tableInfo($this->table);
        $pk = $tableInfo['pk'][0];
        if(empty($pk)){
            return false;
        }
		
		//组织sql语句
		$sqlArr = array(
			'UPDATE `'.$this->table."` SET `{$key}`= ? + `{$key}` WHERE `{$pk}`=?",
			(float)$num,$id
		);
		
        //执行sql
        return $this->run($sqlArr);
    }

    //取得上一次执行sql受影响的行数
    function num()
    {
        return $this->num;
    }

    /*
     * 删除
     * 参数:
     * $sqlArr = array(
           'where'=>'字段m=? and 字段n=?',
           'order'=>'字段z desc',
           'limit'=>'n1,n2',
           值m,值n,值a,
       )
    */
    function del($sqlArr=array())
    {
        if($this->table==''){
            return false;
        }

        //处理sql数组
        $sqlArr = $this->subSql($sqlArr);
        //防止误操作，删除时where条件不能为空
        if(empty($sqlArr['where'])){
            return false;
        }

        //组织sql语句
        $sql = 'DELETE FROM `'.$this->table."` {$sqlArr['where']} {$sqlArr['order']} {$sqlArr['limit']}";
        $sqlArr = is_array($sqlArr['values'])?$sqlArr['values']:array();
        array_unshift($sqlArr,$sql);

        //执行sql
        return $this->run($sqlArr);
    }


    /*
     * 按主键删除记录
     * 参数:
     * $ids 主键的值
     * 例1: $bool = $db->delr(id1); //删除一条记录
     * 例2: $bool = $db->delr(array(id1,id2,id3....)); //删除多条记录,参数类型数组 形式
     * 例3: $bool = $db->delr("id1,id2,id3,id4,...."); //删除多条记录,参数类型字符串列表 形式
     */
    function delr($ids=array())
    {
        !is_array($ids) && $ids = explode(',',$ids);
        $ids = array_filter(array_unique(array_map('trim',$ids)));
        if(empty($ids)){
            return false;
        }

        //取得主键名
        $tableInfo = $this->tableInfo($this->table);
        $pk = $tableInfo['pk'][0];
        if(empty($pk)){
            return false;
        }

        $vpad = implode(',',array_pad(array(),count($ids),'?'));
        $ids['where'] = "`{$pk}` in ({$vpad})";

        return $this->del($ids);
    }


    //取得执行记录
    function log()
    {
        return self::$log;
    }

//事务处理
//=================================

    //开启一个事务
    private static $transTimes = 0; //当前开始的事务层级数, 0 表示当前未开启事务
    function transaction()
    {
        !self::$transTimes && $this->run('SET AUTOCOMMIT=0'); //设置为不自动提交
        self::$transTimes++; //计数器

        //第一次的事务才执行开启
        if(self::$transTimes==1){
            return $this->run('START TRANSACTION');
        }

        return true;
    }

    //事务提交
    function commit()
    {
        //嵌套时，最外层的提交才真正提交
        if(self::$transTimes==1){
            self::$transTimes = 0; //清0
            $bool = $this->run('COMMIT');
            $bool && $this->run('SET AUTOCOMMIT=1'); //默认为自动提交
            return $bool;
        }

        self::$transTimes && self::$transTimes--; //计数器

        return true;
    }

    //事务回滚
    function rollback()
    {
        self::$transTimes = 0; //清0
        $bool = $this->run('ROLLBACK');
        $bool && $this->run('SET AUTOCOMMIT=1'); //默认为自动提交

        return $bool;
    }


    /*
     * 事务封锁 - 排它锁(封锁后，其它事务不能读，也不能写)
     * 参数:
     * $id 数据表中的主键值
     */
    function xLock($id)
    {
        if(empty($id)){
            return false;
        }

        $tableInfo = $this->tableInfo($this->table);
        $pk = $tableInfo['pk'][0];
        if(empty($pk)){
            return false;
        }

        $sqlArr = $this->subSql(array(
            "SELECT * FROM `{$this->table}` WHERE `{$pk}`=? LIMIT 1 FOR UPDATE",
            'values'=>array($id),
        ));

        //执行
        $rs = $this->query($sqlArr);
        $r = is_array($rs[0])?$rs[0]:array();

        return $r;
    }

    //事务封锁 - 共享锁(封锁后，其它事务可以读，但不能写)
    function sLock($id)
    {
        if(empty($id)){
            return false;
        }

        $tableInfo = $this->tableInfo($this->table);
        $pk = $tableInfo['pk'][0];
        if(empty($pk)){
            return false;
        }

        $sqlArr = $this->subSql(array(
            "SELECT * FROM `{$this->table}` WHERE `{$pk}`=? LIMIT 1 LOCK IN SHARE MODE",
            'values'=>array($id),
        ));

        //执行
        $rs = $this->query($sqlArr);
        $r = is_array($rs[0])?$rs[0]:array();

        return $r;
    }

//内部方法
//=================================

    //处理sql数组
    //返回: array('sql'=>'sql语句模板'[,'data'=>array(参数列表)])
    private function sql($sqlArr)
    {
        if(!is_array($sqlArr)){
            return array('sql'=>trim($sqlArr),'data'=>array());
        }

        $sql = array_shift($sqlArr);
        return array(
            'sql'=>trim($sql),
            'data'=>is_array($sqlArr)?array_values($sqlArr):array()
        );
    }

    //处理sql子句
    private function subSql($subSqlArr)
    {
        if(!is_array($subSqlArr)){
            return array('select'=>'*');
        }
        $_sqlArr = array();

        //选择字段
        if(isset($subSqlArr['select'])){
            is_array($subSqlArr['select']) && $subSqlArr['select'] = implode(',',$subSqlArr['select']); //兼容数组
            $_sqlArr['select'] = trim($subSqlArr['select']);

            unset($subSqlArr['select']);
        }
        empty($_sqlArr['select']) && $_sqlArr['select'] = '*';

        //条件
        if(isset($subSqlArr['where'])){
            $subSqlArr['where'] = trim($subSqlArr['where']);
            !empty($subSqlArr['where']) && $_sqlArr['where'] = 'WHERE '.$subSqlArr['where'];

            unset($subSqlArr['where']);
        }
        //分组
        if(isset($subSqlArr['group'])){
            !is_array($subSqlArr['group']) && $subSqlArr['group'] = explode(',',$subSqlArr['group']); //兼容数组
            $subSqlArr['group'] = array_flip($this->filterFields(array_flip($subSqlArr['group'])));
            !empty($subSqlArr['group']) && $_sqlArr['group'] = 'GROUP BY '.implode(',',$subSqlArr['group']);

            unset($subSqlArr['group']);
        }
        //分组条件(如果分组group为空，则此项也无效)
        if(isset($subSqlArr['having']) && !empty($_sqlArr['group'])){
            $subSqlArr['having'] = trim($subSqlArr['having']);
            !empty($subSqlArr['having']) && $_sqlArr['having'] = 'HAVING '.$subSqlArr['having'];

            unset($subSqlArr['having']);
        }
        //排序
        if(isset($subSqlArr['order'])){
            $subSqlArr['order'] = trim($subSqlArr['order']);
            !empty($subSqlArr['order']) && $_sqlArr['order'] = 'ORDER BY '.$subSqlArr['order'];

            unset($subSqlArr['order']);
        }
        //分页
        if(isset($subSqlArr['limit'])){
            !is_array($subSqlArr['limit']) && $subSqlArr['limit'] = explode(',',$subSqlArr['limit']);
            $page = (int)array_shift($subSqlArr['limit']);
            $page<0 && $page = 0;
            $size = (int)array_shift($subSqlArr['limit']);
            $_sqlArr['limit'] = 'LIMIT '.($size?"{$page},{$size}":$page);

            unset($subSqlArr['limit']);
        }

        return $_sqlArr + array('values'=>$subSqlArr);
    }

    //过滤数据表的字段
    private function filterFields($data)
    {
        if(empty($data) || !is_array($data)){
            return array();
        }
        //获取表信息
        $tableInfo = $this->tableInfo($this->table);
        if(empty($tableInfo['fields'])){
            return array();
        }

        //过滤字段
        foreach($data as $key=>$val){
            if(!isset($tableInfo['fields'][$key])){
                unset($data[$key]);
            }
        }

        return $data;
    }

    //显示SQL执行错误
    private function showErr($errInfo)
    {
        //是否输出错误信息并停止程序
        if($this->config['isdebug']){
            echo __CLASS__." Error :\r\n";
            echo mb_convert_encoding(print_r($errInfo,1),'UTF-8','ASCII,UTF-8,GBK,ISO-8859-1');
            exit;
        }
    }

    //记录执行SQL的日志
    //参数: $sql 执行的sql语句, $redate 执行的结果, $startime 执行时间
    private function sqlLog($sql,$redata,$startime='')
    {
        if(!$this->config['isdebug']){
            return ;
        }

        self::$log[] = array(
            'time'=>empty($startime)?'':number_format(gettimeofday(true)-$startime,'6','.',''),//计算时间差
			'sql'=>$sql,
            'redata'=>$redata,
        );

    }

    //析构
    function __destruct()
    {
        //检查当前是否有事务未关闭,如果有则执行回滚操作
        self::$transTimes && $this->rollback();
    }

}
?>