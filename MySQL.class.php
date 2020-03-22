<?php
/**
 * file         MySQL.class.php
 * description  Database independent query interface
 *
 * @author      Peilin Sun <sunpeilin@hotmail.com>
 * @version     4.0
 * @package     include
 *
 */
/* 连接 本地服务器 */
//	define('DBHOST', '192.168.75.230');//搭建到了192.168.75.230 北肿了 localhost	
//	define('DBUSER', 'phenome_user');
//	define('DBPW', 'phenome_pass_20191011');
//	define('DBNAME', 'iphenome');
	
	
	define('DBHOST', 'localhost');
	define('DBUSER', 'root');
	define('DBPW', '');
	define('DBNAME', 'genowis');
	
class MySQL {
	
	var $conn; // 数据库连接句柄

/**
 * 构造器 实例化对象 并联好数据库
 *
 */
function __construct() {
	
	 $mysqli = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
	
	/* check connection */ 
	if (mysqli_connect_errno()) {
	    printf("Connect failed: %s\n", mysqli_connect_error());
	    exit();
	}

	
	$mysqli->query("SET NAMES 'utf8'"); // 设定连接字符集
	$this->conn = $mysqli;

}

	/**
   *  
   *  执行一个SQL语句
   *
   *  @param $sql    SQL语句
   * 
   *  @return 结果集 | true/false
   *
   */
 	function query($sql) {
		 
		if ($res = $this->conn->query($sql)){
			
			return $res;
		
		}else {
			throw new Exception('query error: '.$this->conn->error."\n SQL: ".$sql);
		}
		
	}
	/**
	 * 对将要进入数据库中的数据进行过滤
	 * 目前没有做更多的过滤，将来可以增加
	 *
	 * @param string $str 需要过滤的内容
	 * @return 过滤后的内容
	 */
	function db_var($str)
	{
		$str = addslashes($str);
		$str = trim($str);
		
		return $str;
	}
	/**
	 * 插入一条记录
	 *
	 * @param string $table 要插入的表名
	 * @param array $fields 要插入的数据，以数组形式
	 * @return 所插入的行的自增ID
	 * 举例：
	 * $fields['username'] = 'admin';
	 * $fields['pass'] = '12345';
	 * $db->insert('w_admin',$fields);
	 */
	function insert($table, $fields=array()){
		$kid    = 0;
		$params = array();
		$keys = array();
		
		$sql = 'INSERT INTO '.$table.' (';
		$ext = ') VALUES (';
		
		foreach ($fields as $k => $v) {
			if ('=' == substr($v,0,1)) {
				$params[] = substr($v,1);
			} else {
				$params[] = "'".$this->db_var($v)."'";
			}
			$keys[] = '`'.$k.'`';
		}
		$sql .= implode(',',$keys).$ext.implode(',',$params).')';
		//echo($sql);
		$this->query($sql);
		$kid = $this->conn->insert_id;
		   
		return $kid;
	}
	
	function insert_sql($table, $fields=array()){
		$kid    = 0;
		$params = array();
		$keys = array();
		
		$sql = 'INSERT INTO '.$table.' (';
		$ext = ') VALUES (';
		
		foreach ($fields as $k => $v) {
			if ('=' == substr($v,0,1)) {
				$params[] = substr($v,1);
			} else {
				$params[] = "'".$this->db_var($v)."'";
			}
			$keys[] = '`'.$k.'`';
		}
		$sql .= implode(',',$keys).$ext.implode(',',$params).');';
		
		return $sql;
	}
	/**
	 * 更新一条记录
	 *
	 * @param string $table 表名
	 * @param array $fields 要更新的数据
	 * @param string $cause where 后的过滤条件
	 * 举例：
	 * $fields['username'] = 'admin';
	 * $fields['pass'] = '12345';
	 * $db->update('w_admin',$fields);
	 */
	function update($table, $fields=array(), $cause){
		$sql = 'UPDATE '.$table.' SET ';
		$params = array();
		foreach ($fields as $k => $v) {
			$k = '`'.$k.'`';
			if ('=' == substr($v,0,1)) {
				$params[] = $k.$v;
			} else {
				$params[] = $k."='".$this->db_var($v)."'";
			}
		}
		$sql .= implode(',',$params).' WHERE '.$cause;
		//echo($sql);
		$this->query($sql);
	}
	/**
	 * 删除一条记录(慎用)
	 *
	 * @param string $table 表名
	 * @param stirng $cause where 条件
	 * $db->delete('w_admin','admin_id=1');
	 */
	function delete($table, $cause){
		$sql = 'DELETE from '.$table.' WHERE '.$cause;
		$this->query($sql);
	}
	/**
	 * 获得所有的查询结果，以数组形式返回
	 *
	 * @param string $query SQL语句
	 * @param int $n 所要返回的行数，如果不提供，则全部行
	 * @param int $start 返回的起始行号
	 * @return 结果数组，一维下标表示行号
	 */
	function getall($query, $n=0, $start=0){
		if($n > 0){
			$query .= ' LIMIT '.$start.','.$n;
		}
		$result = array();	
		$res = $this->query($query);
		
		while($row = $res->fetch_array(MYSQLI_ASSOC)){
			$result[] = $row;
		}	
			
		return $result;
	}
	/**
	 * 为当前分页提供数据
	 *
	 * @param string $query SQL语句
	 * @param int $total 总行数
	 * @param int $page 当前的页码
	 * @param int $rows 每页显示的行数
	 * @return 当前页的结果数组
	 */
	function getpage($query, $total, $page, $rows=10){
		$start_row  = ($page - 1) * $rows;
		$start_row  = $start_row > $total ? 0 : $start_row;
		
		$query      = $query . " limit $start_row, $rows";
		return $this->getall($query);
	}
	/**
	 * 获取查询结果的第一行
	 *
	 * @param string $query SQL语句
	 * @return 第一行数据
	 */
	function getrow($query){
		$res = $this->query($query);
		$row = $res->fetch_array(MYSQLI_ASSOC);
			
		return $row;
	}
	/**
	 * 获得查询结果的第一行第一列的值
	 *
	 * @param string $query SQL语句
	 * @return 第一行第一列的值
	 */
	function getone($query){
		$res = $this->query($query);		
		$row = $res->fetch_array(MYSQLI_BOTH);	
		return (isset($row[0]))? $row[0] : false;
	}
	/**
     * 获取一个查询的总行数
     *
     * @param   string  $sql  查询语句
     *
     * @return  integer  总行数
     */
	function getcount($sql){
			
			$pos = strripos($sql,'from');
			$sub_sql = substr($sql, $pos); 

			$subSQL = 'select count(*) '. $sub_sql;

			return $this->getone($subSQL);
	}
	function getids($sql,$id){
			
			$pos = strripos($sql,'from');
			$sub_sql = substr($sql, $pos); 

			$subSQL = "select {$id} ". $sub_sql;

			return $this->getall($subSQL);
	}
	/**
	 * 根据id返回字段值
	 *
	 * @param string $table 表名
	 * @param string $field 字段名
	 * @param string $pk 主键名
	 * @param integer $id id编号
	 * @return stirng 字段值
	 */
	function getfield($table,$field,$pk,$id){
			if(empty($id)){
				return '';
			}
			$sql = "select $field from $table where $pk=".$id;	
			return $this->getone($sql);
	}


}
?>