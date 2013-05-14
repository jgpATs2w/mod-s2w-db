<?
namespace s2w\db;
Class DB{
	static $conn = null;
}
/**
 * Persistent connection to postgresql database specified in parameters
 * sets encoding to latin1
 */
function connect($HOST, $DATABASE, $USER, $PASS){
	\s2w\db\DB::$conn = pg_pconnect("host=$HOST dbname=$DATABASE user=$USER password=$PASS")
	or \s2w\error\trigger('psql.class.php#No se ha podido conectar: ' . pg_last_error());
	
	pg_set_client_encoding(\s2w\db\DB::$conn, 'LATIN1');
	
	return true;
}
if($section = \s2w\basic\config_get('DB'))
	connect($section['host'], $section['database'], $section['user'], $section['password']);
/**
 * @param string $q query to database
 * @param {RPCResponse} [response] object passed by reference for error stack
 * @return resource must be parsed with pg_fetch_? functions
 */
function query($q = null, &$response = null){
	if(!is_string($q)){
		$e_mesg = "first argument should be a string, passed "+$q;
		 \s2w\log\error($e_mesg);
		if(!is_null($response)) 
			$response->pushError(\s2w\rpc\RPCErrorCode::INTERNAL_ERROR, $e_mesg);	
		return false;
	}
	if(is_null(DB::$conn)){
		$e_mesg = "connection to DB not established.";
		 \s2w\log\error($e_mesg);
		if(!is_null($response)) 
			$response->pushError(\s2w\rpc\RPCErrorCode::DB_NOT_CONNECTED, $e_mesg);	
		return false;
	}
	\s2w\log\debug('query '.$q);
	
	$result = @pg_query(\s2w\db\DB::$conn,$q);

	if($result){
		if(pg_num_rows($result) > 0)
			return $result;
		
		return true; 
	}else{
		$e_mesg = "ERROR on query '$q'".pg_last_error();
		\s2w\log\error($e_mesg);
		
		if(!is_null($response)) 
			$response->pushError(\s2w\rpc\RPCErrorCode::SERVER_ERROR_SQL, $e_mesg);	
		
		return false;
	}
}
/**
 * @param string $q query
 * @param {RPCResponse} [response] object passed by reference for error stack
 * @return {array | null} empty array if no data from query
 */
function query2array($q, &$response = null){
	$r = \s2w\db\query($q, $response);
	
	if(is_resource($r)) return pg_fetch_all($r); 
	else if(is_bool($r)){
		if($r) return array();
		return null;
	}
	
	$e_mesg = "unknown type returned from query ".gettype($r);
	\s2w\log\error($e_mesg);
	if(!is_null($response)) 
		$response->pushError(\s2w\rpc\RPCErrorCode::SERVER_ERROR_SQL, $e_mesg);	
	return false;
}

function query2result($q){
	$r = \s2w\db\query($q);
	return pg_fetch_result($r,0,0);
}
/**
 * returns (integer) number of counts as specified in query.
 * Input must be a valid query, like: select count($column_name) ... 
 */
function query_count($q){
	$r = \s2w\db\query($q);
	
	if($r == false) trigger_error("no se pudo hacer la consulta");
	
	$l = pg_fetch_row($r);
	
	return (int)$l[0];
}

class SQLMatrix{
	private $_keys = array();
	private $_mainArray = array();
	public $rows = 0;
	public $cols = 0;
	function __construct($array){
		if(gettype($array) != "array"){
			trigger_error("no se puede construir SQLMatrix con tipo ".gettype($array));
			return;
		}
		$this->_mainArray = $array;
		
		foreach($this->_mainArray[0] as $key => $value)
			array_push($this->_keys, $key);
		
		$this->rows = count($this->_mainArray);
		$this->cols = count($this->_keys);
	}
	/**
	 * @param {number|string} [$col] name or position for column
	 * @param {number|string} [$row] position for row
	 *  
	 */
	public function get($col = null, $row = null){
		if($row !== null){
			return $this->_mainArray[$row][$col];
		}else if($col !== null){
			$return = array();
			foreach($this->_mainArray as $rowArray){
				array_push($return, $rowArray[$col]);
			}
			
			return $return;
		}
		
		return $this->_mainArray;
	}
	public function getKeys(){return $this->_keys;}
}
?>