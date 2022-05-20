<?php

/**
 * Class TableData 二维数据表操作类
 */

class TableData
{
    //列表 型数据
    protected $_cursorIndex = -1; //游标
    protected $_columns = []; //列名
    protected $_data = []; //源数据
    protected $_indexs = []; //索引 [indexKey=>[index,...]]

    //key-value 型数据
    protected $_config = []; //[key=>val,...]

    //缓存
    protected $_cacheKey = ''; //缓存key

    //开启自动更新索引
    protected $_autoRefreshIndex = true;

    /**
     * 获取一个实例
     * @param array $data 数据
     * @param array ...$args 其它参数
     * @return mixed
     */
    public static function getInterface(array $data = [], ...$args)
    {
        $class = get_called_class();
        $table = new $class(...$args);

        //载入数据
        !empty($data) && $table->load($data);

        return $table;
    }

    /**
     * 载入数据
     * @param array $data
     * @return $this
     */
    function load(array $data)
    {
        foreach ($data as $row) {
            if (is_array($row)) {
                //取得列名
                $keys = array_keys($row);
                $this->_columns = array_unique(array_merge($this->_columns, $keys));

                //添加记录
                $index = $this->count();
                $this->_data[$index] = $row;
            }
        }

        //自动刷新索引
        if ($this->_autoRefreshIndex) {
            $this->refreshIndex();
        }

        return $this;
    }

    /**
     * 获取全部列名
     * @return array
     */
    function getColumns()
    {
        return $this->_columns;
    }

    /**
     * 是否为空
     * @return bool
     */
    function isEmpty()
    {
        return empty($this->_data);
    }

    //索引
    //===============================

    /**
     * 为指定列建立索引
     * @param string $column
     * @return bool|int
     */
    function setIndex(string $column)
    {
        $indexData = [];

        //实际存在的字段才建立真实索引记录
        if (in_array($column, $this->_columns)) {
            foreach ($this->_data as $i => $d) {
                $key = $d[$column];
                $indexData[$key][] = $i;
            }
        }
        $this->_indexs[$column] = $indexData;

        //返回新建索引的元素数量
        return count($indexData);
    }

    /**
     * 设置是否自动刷新索引
     * @param bool $isAuto
     * @return bool
     */
    function autoRefreshIndex(bool $isAuto = true)
    {
        $this->_autoRefreshIndex = false;

        if ($isAuto) {
            //刷新
            $this->refreshIndex();
        }

        return $this->_autoRefreshIndex;
    }

    /**
     * 刷新索引数据
     */
    function refreshIndex()
    {
        //取得索引名称列表
        $keys = array_keys($this->_indexs);

        //新建索引
        foreach ($keys as $key) {
            $this->setIndex($key);
        }
    }

    /**
     * 删除索引
     * @param string $column
     */
    function delIndex(string $column)
    {
        unset($this->_indexs[$column]);
    }

    /**
     * 列名是否是索引
     * @param string $column
     * @return bool
     */
    function isIndex(string $column)
    {
        if (empty($column)) {
            return false;
        }

        return isset($this->_indexs[$column]);
    }

    /**
     * 获取索引
     * @param string $column
     * @return bool|mixed
     */
    function getIndex(string $column)
    {
        if (!$this->isIndex($column)) {
            return false;
        }

        return $this->_indexs[$column];
    }


    /**
     * 根据索引值，返回记录
     * @param mixed $indexValue 索引值
     * @param string|NULL $column 索引名称
     * @return array|bool|mixed
     */
    function getByIndex($indexValue, string $column = NULL)
    {
        //未指定时，使用默认索引
        if (empty($column)) {
            return $this->_data[$indexValue];
        }

        //取得对应的索引列表
        if (!$this->isIndex($column)) {
            return false;
        }

        //取得索引中对应的数据
        $indexs = $this->_indexs[$column][$indexValue];
        if (empty($indexs)) {
            return false;
        }

        $data = [];
        foreach ($indexs as $i) {
            $data[$i] = $this->_data[$i];
        }

        return $data;
    }

    //获取数据
    //===============================

    /**
     * 取得符合规则的全部数据，如果参数为空 返回全部原始数据
     * @param string $rule 规则表达式串：'1,68,666:888,999'
     * @return array
     */
    function getAll(string $rule = '')
    {
        if (empty($rule)) {
            return $this->_data;
        }

        return [];
    }

    /**
     * 根据字段名查找记录
     * @param string $column
     * @param $val
     * @return array|bool|mixed
     */
    function find(string $column, $val)
    {
        if (!in_array($column, $this->_columns)) {
            return false;
        }

        //优先使用索引
        if (isset($this->_indexs[$column])) {
            return $this->getByIndex($val, $column);
        }

        //没有索引时遍历
        $data = [];
        foreach ($this->_data as $index => $d) {
            if ($d[$column] == $val) {
                $data[$index] = $d;
            }
        }

        return $data;
    }

    /**
     * 根据列名，获得一个子表对象
     * @param array $columns
     * @return bool|TableData
     */
    function getSubTable(array $columns)
    {
        $data = $this->getByColumns($columns);
        if (empty($data)) {
            return false;
        }

        $table = new TableData();
        $table->load($data);

        return $table;
    }

    /**
     * 取得多列名数据(二维)
     * @param array $columns
     * @return array|bool
     */
    function getByColumns(array $columns)
    {
        if (empty($columns)) {
            return false;
        }

        $data = [];
        foreach ($this->_data as $row) {
            $r = [];
            foreach ($columns as $col) {
                $r[$col] = $row[$col];
            }
            $data[] = $r;
        }

        return $data;
    }

    /**
     * 获取一列数据(一维)
     * @param string $column
     * @return array
     */
    function getByColumn(string $column)
    {
        return array_column($this->_data, $column);
    }

    /**
     * 获得行记录
     * @param int $index
     * @param int $num
     * @return array|bool
     */
    function getRows(int $index, int $num = 1)
    {
        if ($this->isEmpty()) {
            return false;
        }

        return array_slice($this->_data, $index, $num);
    }

    /**
     * 取得指定列的值区间范围内的记录
     * @param $column
     * @param $start
     * @param $end
     * @return array|bool
     */
    function getByBetween($column, $start, $end)
    {
        if (!in_array($column, $this->_columns)) {
            return false;
        }

        $data = [];
        foreach ($this->_data as $index => $d) {
            if (between($d[$column], $start, $end)) {
                $data[$index] = $d;
            }
        }

        return $data;
    }

    /**
     * 获取最后一条记录
     * @return bool|mixed
     */
    function getLast()
    {
        if ($this->isEmpty()) {
            return false;
        }

        return $this->_data[$this->count() - 1];
    }

    /**
     * @param array $format 格式规则，见DV()函数的参数
     * @return bool|TableData
     */
    function getByFormat(array $format)
    {
        if (empty($format)) {
            return false;
        }

        $data = [];
        foreach ($this->_data as $d) {
            $data[] = DV($format, $d);
        }

        $table = new TableData();
        $table->load($data);

        return $table;
    }


    //用游标操作数据(一般用于时间序列的数据遍历)
    //===============================

    /**
     * 设置游标.返回设置后的游标正确位置
     * @param int $index
     * @return $this|bool
     */
    function setCursorIndex(int $index = -1)
    {
        //控制游标在正确的范围之内
        if (!between($index, -1, $this->count() - 1)) {
            return false;
        }

        //设置游标
        $this->_cursorIndex = $index;

        return $this;
    }

    /**
     * 获取当前游标值
     * @return int
     */
    function getCursorIndex()
    {
        return $this->_cursorIndex;
    }

    /**
     * 获取下一个元素,如果到达结尾就返回 false
     * @return bool
     */
    function next()
    {
        $index = $this->_cursorIndex + 1;
        //越界检测
        if (empty($this->_data[$index])) {
            return false;
        }

        //游标下移
        $this->_cursorIndex = $index;

        return true;
    }

    /**
     * 根据偏移量获取记录
     * @param int $offset
     * @return mixed
     */
    function get(int $offset = 0)
    {
        if ($this->_cursorIndex < 0) {
            $index = $offset;
        } else {
            $index = $this->_cursorIndex + $offset;
        }

        return $this->_data[$index];
    }

    //数据处理
    //===============================

    /**
     * 添加一条记录
     * @param array $row
     * @return int
     */
    function add(array $row)
    {
        //合并列名
        $keys = array_keys($row);
        $this->_columns = array_unique(array_merge($this->_columns, $keys));

        //添加记录
        $index = $this->count();
        $this->_data[$index] = $row;

        //自动刷新索引
        if ($this->_autoRefreshIndex) {
            $this->refreshIndex();
        }

        return $index;
    }

    /**
     * 更新一条记录
     * @param int $index
     * @param array $row
     * @return $this|bool
     */
    function update(int $index, array $row)
    {
        if (!isset($this->_data[$index])) {
            return false;
        }
        $this->_data[$index] = $row;

        //合并列名
        $keys = array_keys($row);
        $this->_columns = array_unique(array_merge($this->_columns, $keys));

        //自动刷新索引
        if ($this->_autoRefreshIndex) {
            $this->refreshIndex();
        }

        return $this;
    }

    /**
     * 删除一条记录
     * @param int $index
     * @return $this
     */
    function del(int $index)
    {
        unset($this->_data[$index]);

        //重新建立主索引
        $this->_data = array_values($this->_data);

        //自动刷新索引
        if ($this->_autoRefreshIndex) {
            $this->refreshIndex();
        }

        return $this;
    }

    /**
     * 清空数据
     * @return $this
     */
    function clear()
    {
        if (!empty($this->_cacheKey)) {
            $this->clearCache();
        }
        $this->_data = [];

        return $this;
    }

    /**
     * 统计记录总数
     * @return int
     */
    function count()
    {
        return count($this->_data);
    }

    /**
     * 求平均值
     * @param string $column
     * @return bool|float|int
     */
    function avg(string $column)
    {
        if ($this->isEmpty()) {
            return false;
        }

        return $this->sum($column) / $this->count();
    }

    /**
     * 求和
     * @param string $column
     * @return float|int
     */
    function sum(string $column)
    {
        if ($this->isEmpty()) {
            return 0;
        }

        $data = array_column($this->_data, $column);
        return array_sum($data);
    }

    /**
     * 最大值
     * @param string $column
     * @return mixed
     */
    function max(string $column)
    {
        $data = array_column($this->_data, $column);
        return max($data);
    }

    /**
     * 最小值
     * @param string $column
     * @return mixed
     */
    function min(string $column)
    {
        $data = array_column($this->_data, $column);
        return min($data);
    }

    /**
     * 排序
     * @param string $sortRule
     * @return $this
     */
    function sort(string $sortRule)
    {
        $this->_data = arraySort($this->_data, $sortRule);

        //刷新各索引
        $this->refreshIndex();

        return $this;
    }

    /**
     * 添加一列数据
     * @param string $column
     * @param array $arr
     * @return $this
     */
    function addColumn(string $column, array $arr)
    {
        //添加列名
        if (!in_array($column, $this->_columns)) {
            $this->_columns[] = $column;
        }

        //添加数据
        foreach ($this->_data as $i => $r) {
            $this->_data[$i][$column] = $arr[$i];
        }

        return $this;
    }

    //缓存处理
    //===============================

    /**
     * 从缓存中获取一个对象
     * @param string $key
     * @return $this
     */
    public static function LoadByCache(string $key)
    {
        return Cache($key);
    }

    /**
     * 缓存数据
     * @param string $key
     * @param int $exptime
     * @return mixed|null
     */
    function cache(string $key, int $exptime = 0)
    {
        return cache($key, $this, $exptime);
    }

    /**
     * 清除缓存数据
     * @return mixed|null
     */
    function clearCache()
    {
        return cache($this->_cacheKey, NULL, 1);
    }

    //输出数据
    //===============================

    /**
     * 输出json格式
     * @return string
     */
    function toJSON()
    {
        return json_encode($this->_data);
    }

    /**
     * 将数据按表格样式输出
     * @return array
     */
    function toTable()
    {
        $data = [];

        //第一行为列名
        $data[0] = $this->_columns;

        //从第二行开始，为数据
        foreach ($this->_data as $d) {
            $r = [];
            foreach ($this->_columns as $col) {
                $r[] = $d[$col];
            }
            $data[] = $r;
        }

        return $data;
    }

    /**
     * 输出一个xls对象
     * @return xls
     */
    function toXLS()
    {
        $data = $this->toTable();

        $xls = new xls();
        $xls->create();
        $xls->putSheetRs($data);

        return $xls;
    }

    /**
     * 输出csv文件
     * @param string $file
     * @return string
     */
    function toCSV(string $file)
    {
        $data = $this->toTable();

        $csv = '';
        foreach ($data as $d) {
            $csv .= implode(',', $d) . " \n";
        }

        return $csv;
    }

    //config数据
    //====================================

    /**
     * 设置config数据
     * @param array $config
     */
    function setConfig(array $config)
    {
        foreach ($config as $key => $val) {
            $this->_config[$key] = $val;
        }
    }

    /**
     * 获取所有config数据
     * @return array
     */
    function getConfig()
    {
        return $this->_config;
    }

    //属性重载
    //====================================

    /**
     * 在给不可访问属性赋值时会被调用
     * @param string $name
     * @param $value
     */
    function __set(string $name, $value)
    {
        $this->_config[$name] = $value;
    }

    /**
     * 读取不可访问属性的值时会被调用
     * @param string $name
     * @return mixed
     */
    function __get(string $name)
    {
        return $this->_config[$name];
    }
}
