<?php
/**
 * EasyAPI PHP Framework
 * Db操作类 (MYSQL PDO)
 * @author  Wigiesen <[<wigiesen.cn@gmail.com>]>
 * https://xinyu19.com
 */

class Db
{
	private static $instance;
	private $db;

	private function __construct($config){
		if (!empty($config['hostname'])) {
	        try {
	            $this->dsn = 'mysql:host='.$config['hostname'].';dbname='.$config['dbname'];
	            $this->db = new PDO($this->dsn, $config['username'], $config['password']);
	            $this->db->exec('SET character_set_connection='.$config['charset'].', character_set_results='.$config['charset'].', character_set_client=binary');
	            $this->db->exec("SET NAMES ".$config['charset']);
	        } catch (PDOException $e) {
	            $this->printError($e->getMessage());
	        }
		}
	}

	/**
	 * [getInstance 单例唯一实例化]
	 * @param  [type] $config [description]
	 * @return [type]         [description]
	 */
	public static function getInstance($config){
        if(!(self::$instance instanceof self)){
            self::$instance = new self($config);
        }
        return self::$instance;
	}

	/**
	 * [create 新增数据]
	 * @param  [type] $table [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	public function create($table, $data = []){
		$sql = "INSERT INTO `$table` (`".implode('`,`', array_keys($data))."`) VALUES ('".implode("','", $data)."')";
		$result = $this->db->exec($sql);
		$this->getError();
		return $result;
	}

	/**
	 * [update 删除数据]
	 * @param  [type] $table     [description]
	 * @param  array  $data      [description]
	 * @param  array  $condition [description]
	 * @return [type]            [description]
	 */
	public function update($table, $data = [], $condition = []){
		$sql = '';
        foreach ($data as $key => $value) {
            $sql .= ", `$key`='$value'";
        }
        $sql = substr($sql, 1);
		if (!empty($condition)) {
			$where  = $this->condition($condition);
            $sql = "UPDATE `$table` SET {$sql} {$where}";
		}else{
			$sql = "UPDATE `$table` SET {$sql}";
		}
		$result = $this->db->exec($sql);
		$this->getError();
		return $result;
	}

	/**
	 * [delete 删除数据]
	 * @param  [type] $table     [description]
	 * @param  array  $condition [description]
	 * @return [type]            [description]
	 */
	public function delete($table, $condition = []){
		if (!empty($condition)) {
			$where  = $this->condition($condition);
			$sql = "DELETE FROM `$table` {$where}";
			$result = $this->db->exec($sql);
			$this->getError();
			return $result;
		}else{
			$this->printError('condition is Null');
		}
	}

	/**
	 * [getColumn 获取但个字段数据]
	 * @param  [type] $table     [description]
	 * @param  [type] $column    [description]
	 * @param  array  $condition [description]
	 * @return [type]            [description]
	 */
	public function getColumn($table, $column, $condition = []){
		if (!empty($column)) {
			$where  = $this->condition($condition);
			$result = $this->db->query("SELECT {$column} FROM `{$table}` {$where} limit 1", PDO::FETCH_ASSOC);
			$result = $result->fetch();
			return $result[$column];
		}else{
			$this->printError('column is Null');
		}
	}

	/**
	 * [fetch 读取一行数据]
	 * @param  [type] $table     [description]
	 * @param  array  $fields    [description]
	 * @param  array  $condition [description]
	 * @return [type]            [description]
	 */
	public function fetch($table, $fields = [], $condition = []){
		$fields = !empty($fields) ? implode(",", $fields) : '*';
		$where  = $this->condition($condition);
		$result = $this->db->query("SELECT {$fields} FROM `{$table}` {$where} limit 1", PDO::FETCH_ASSOC);
		$result = $result->fetch();
		$this->getError();
		return $result;
	}

	/**
	 * [fetchAll 获取全部数据]
	 * @param  [type] $table     [description]
	 * @param  array  $fields    [description]
	 * @param  array  $condition [description]
	 * @return [type]            [description]
	 */
	public function fetchAll($table, $fields = [], $condition = []){
		$fields = !empty($fields) ? implode(",", $fields) : '*';
		$where  = $this->condition($condition);
		$result = $this->db->query("SELECT {$fields} FROM `{$table}` {$where}", PDO::FETCH_ASSOC);
		$result = $result->fetchAll();
		$this->getError();
		return $result;
	}

	public function query($sql, $mode = 'all'){
		$result = $this->db->query($sql, PDO::FETCH_ASSOC);
		if ($mode == 'all') {
			$result = $result->fetchAll();
		}elseif ($mode == 'row') {
			$result = $result->fetch();
		}else{
			$this->printError('mode is false');
		}
		$this->getError();
		return $result;
	}

	/**
	 * [condition WHERE条件处理]
	 * @param  [type] $condition [description]
	 * @return [type]            [description]
	 */
	private function condition($condition){
		$where = '';
		if (!empty($condition)) {
            foreach ($condition as $key => $value) {
                if($value == end($condition)){
                    $where .= "`".$key."` = '".$value."'";
                }else{
                    $where .= "`".$key."` = '".$value."' AND ";
                }
            }
            $where = "WHERE " . $where;
		}
		return $where;
	}

	/**
	 * getPDOError 捕获PDO错误信息
	 */
	private function getError()
	{
	    if ($this->db->errorCode() != '00000') {
	        $error = $this->db->errorInfo();
	        $this->printError($error[2]);
	    }
	}

	/**
	 * [printError 输出异常信息]
	 * @param  [type] $ErrMsg [description]
	 * @return [type]         [description]
	 */
    private function printError($ErrMsg)
    {
        throw new Exception('MySQL Error: '.$ErrMsg);
    }

    private function __clone(){}
}