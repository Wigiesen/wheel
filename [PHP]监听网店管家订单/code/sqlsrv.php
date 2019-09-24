<?php
/**

* SqlServer操作类(sqlsrv)

* Class SQLSrv

*/

class SQLSrv
{
    private $dbhost;

    private $dbuser;

    private $dbpw;

    private $dbname;

    private $port;

    private $result;

    private $connid = 0;

    private $insertid = 0;

    private $cursor = 0;

    public static $instance = null;

 

    public function __construct($db)
    {
        //function_exists("sqlsrv_connect") or die("<pre>请先安装 sqlsrv 扩展。");

 

        $this->dbhost = !empty($db['hostname']) ? $db['hostname'] : 'localhost';

        $this->dbuser = $db['username'];

        $this->dbpw = $db['password'];

        $this->dbname = $db['dbname'];

        $this->port = !empty($db['port']) ? $db['port'] : 1433;

        $this->connect();
    }

 

    public static function getdatabase($db)
    {
        if (empty(self::$instance)) {
            self::$instance = new SQLSrv($db);
        }

        return self::$instance;
    }

 

    /**

     * 连接数据库

     * @return int

     */

    private function connect()
    {
        $serverName = "{$this->dbhost}, {$this->port}";

        $connectionInfo = array( "Database"=>$this->dbname, "UID"=>$this->dbuser, "PWD"=>$this->dbpw);

        if (!$this->connid = @sqlsrv_connect($serverName, $connectionInfo)) {
            $this->halt(print_r(sqlsrv_errors(), true));
        }

 

        return $this->connid;
    }

 

    /**

     * 执行sql

     * @param $sql

     * @return mixed

     */

    public function query($sql)
    {
        if (empty($sql)) {
            $this->halt('SQL IS NULL!');
        }

 

        $result = sqlsrv_query($this->connid, $sql);

 

        if (!$result) {  //调试用，sql语句出错时会自动打印出来

            $this->halt('MsSQL Query Error', $sql);
        }

 

        $this->result = $result;

 

        return $this->result;
    }

 

    /**

     * 获取一条数据（一维数组）

     * @param $sql

     * @return array|bool

     */

    public function find($sql)
    {
        $this->result = $this->query($sql);

        $args = $this->fetch_array($this->result);

        return $args ;
    }

 

    /**

     * 获取多条（二维数组）

     * @param $sql

     * @param string $keyfield

     * @return array

     */

    public function findAll($sql, $keyfield = '')
    {
        $array = array();

        $this->result = $this->query($sql);

        while ($r = $this->fetch_array($this->result)) {
            if ($keyfield) {
                $key = $r[$keyfield];

                $array[$key] = $r;
            } else {
                $array[] = $this->objectToArray($r);
            }
        }

        return $array;
    }

 

    /**

     * 对象转数组

     * @param $obj

     * @return array

     */

    private function objectToArray($obj)
    {
        $ret = array();

        foreach ($obj as $key => $value) {
            if (gettype($value) == "array" || gettype($value) == "object") {
                $ret[$key] =  $this->objectToArray($value);
            } else {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

 

    public function fetch_array($query, $type = SQLSRV_FETCH_ASSOC)
    {
        if (is_resource($query)) {
            return sqlsrv_fetch_array($query, $type);
        }

        if ($this->cursor < count($query)) {
            return $query[$this->cursor++];
        }

        return false;
    }

 

    public function affected_rows()
    {
        return sqlsrv_rows_affected($this->connid);
    }

 

    public function num_rows($query)
    {
        return is_array($query) ? count($query) : sqlsrv_num_rows($query);
    }

 

    public function num_fields($query)
    {
        return sqlsrv_num_fields($query);
    }

 

    /**

     * 释放连接资源

     * @param $query

     */

    public function free_result($query)
    {
        if (is_resource($query)) {
            @sqlsrv_free_stmt($query);
        }
    }

 

    public function insert_id()
    {
        return $this->insertid;
    }

 

    public function fetch_row($query)
    {
        return sqlsrv_num_rows($query);
    }

 

    /**

     * 关闭数据库连接

     * @return bool

     */

    public function close()
    {
        return sqlsrv_close($this->connid);
    }

 

    /**

     * 抛出错误

     * @param string $message

     * @param string $sql

     */

    public function halt($message = '', $sql = '')
    {
        $_sql = !empty($sql) ? "MsSQL Query:$sql <br>" : '';

        exit("<pre>{$_sql}Message:$message");
    }

 

    /**

     * 开始一个事务.

     */

    public function begin()
    {
        return sqlsrv_begin_transaction($this->connid);
    }

 

    /**

     * 提交一个事务.

     */

    public function commit()
    {
        return sqlsrv_commit($this->connid);
    }

 

    /**

     * 回滚一个事务.

     */

    public function rollback()
    {
        return sqlsrv_rollback($this->connid);
    }

 

    /**

     * 返回服务器信息

     * @return array

     */

    public static function serverInfo()
    {
        return sqlsrv_server_info($this->connid);
    }

 

    /**

     * 返回客户端信息

     * @return array|null

     */

    public static function clientInfo()
    {
        return sqlsrv_client_info($this->connid);
    }

 

    /**

     * 析构函数,关闭数据库,垃圾回收

     */

    public function __destruct()
    {
        if (!is_resource($this->connid)) {
            return;
        }

 

        $this->free_result($this->result);

        $this->close();
    }
}
