<?php
/* helper functions */
function db_connect($driver,$engine,$host,$username,$password,$database,$port = 3306) {
	switch($driver) {
		case 'pdo':
			if($engine == 'mysql') {
				$sql = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $username, $password, array(
  				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
  				PDO::ATTR_EMULATE_PREPARES => false,
  				PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
				));
			}
			if($engine == 'postgres') {
				$sql = new PDO('pgsql:host='.$host.';port='.$port.';dbname='.$database, $username, $password);
				$sql->query('SET NAMES UTF8');
			}
			break;

		case 'mysqli':
			$sql = new mysqli($host, $username, $password, $database, $port);
			mysqli_set_charset($sql,"utf8");
			if($sql->connect_errno) { die('SQL Connection failed: '.$sql->connect_error); }
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;
			
	}
	$sql->driver = $driver;
	$sql->engine = $engine;
	return $sql;
}
function db_fetch_all($query) {
	global $sql;
	$data = array();
    	$params = func_get_args(); unset($params[0]); $params = array_values($params);
	list($query,$params) = db_preparse_query($query,$params);

	switch($sql->driver) {

		case 'pdo':
		    $stmt = $sql->prepare($query);
		    $stmt->execute($params);
		    if($stmt->errorCode() != 0) {
			    $errors = $stmt->errorInfo();
			    echo($errors[2]);
			    return false;
		    }
		    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    break;

		case 'mysqli':
			// do not use mysqlnd
			$result = $sql->query($query);
			while($row = $result->fetch_assoc()) {
			    $data[] = $row;
			}
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;

	}

	return $data;
}
function db_fetch_row($query) {
	global $sql;
	$data = array();
        $params = func_get_args(); unset($params[0]); $params = array_values($params);
        list($query,$params) = db_preparse_query($query,$params);

	switch($sql->driver) {

		case 'pdo':
		    $stmt = $sql->prepare($query);
		    $stmt->execute($params);
		    if($stmt->errorCode() != 0) {
			    $errors = $stmt->errorInfo();
			    echo($errors[2]);
			    return false;
		    }
		    $data = $stmt->fetch(PDO::FETCH_ASSOC);
		    break;

		case 'mysqli':
			$data = $sql->query($query)->fetch_assoc();
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;

	}

	return $data;
}
function db_fetch_col($query) {
	global $sql;
	$data = array();
  $params = func_get_args(); unset($params[0]); $params = array_values($params);
	list($query,$params) = db_preparse_query($query,$params);

	switch($sql->driver) {

		case 'pdo':
		    $stmt = $sql->prepare($query);
		    $stmt->execute($params);
		    if($stmt->errorCode() != 0) {
			    $errors = $stmt->errorInfo();
			    echo($errors[2]);
			    return false;
		    }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(!empty($data)) {
            $data_tmp = array();
            foreach($data as $dat) {
                $data_tmp[] = $dat[array_keys($dat)[0]];
            }
            $data = $data_tmp;
        }
		    break;

		case 'mysqli':
			// TODO
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;

	}

	return $data;
}
function db_fetch_var($query) {
	global $sql;
	$data = array();
    	$params = func_get_args(); unset($params[0]); $params = array_values($params);
    	list($query,$params) = db_preparse_query($query,$params);

	switch($sql->driver) {

		case 'pdo':
		    $stmt = $sql->prepare($query);
		    $stmt->execute($params);
		    if($stmt->errorCode() != 0) {
			    $errors = $stmt->errorInfo();
			    echo($errors[2]);
			    return false;
		    }
		    $data = $stmt->fetchObject();
		    if(empty($data)) { return null; }
			$data = (Array)$data;
			$data = current($data);
		    break;

		case 'mysqli':
			$data = $sql->query($query)->fetch_object();
			if(empty($data)) { return null; }
			$data = (Array)$data;
			$data = current($data);
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;

	}

	return $data;
}
function db_query($query) {
	global $sql;
	$data = array();
    	$params = func_get_args(); unset($params[0]); $params = array_values($params);
    	list($query,$params) = db_preparse_query($query,$params);

	switch($sql->driver) {

		case 'pdo':
		    $stmt = $sql->prepare($query);
		    $stmt->execute($params);
		    if($stmt->errorCode() != 0) {
			    $errors = $stmt->errorInfo();
			    echo($errors[2]);
			    return false;
		    }
		    return $stmt->rowCount();
		    break;

		case 'mysqli':
			$sql->query($query);
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;

	}

}

function db_insert($table, $data) {
    global $sql;
    $query = "";
    $query .= "INSERT INTO ";
    $query .= "`".$table."`";
    $query .= "(";
    foreach ($data as $key => $value) {
        if($sql->engine == 'mysql') {
          $query .= '`'.$key.'`';
        }
        if($sql->engine == 'postgres') {
          $query .= '"'.$key.'"';
        }
        end($data); if ($key !== key($data)) { $query .= ','; }
    }
    $query .= ") VALUES(";
    $query .= str_repeat("?,", count($data) - 1) . "?";
    $query .= ")";
    $args = array();
    $args[] = $query;
    foreach ($data as $d) {
        // because pdo can't insert true/false values so easily, convert them to 1/0
        if( $d === true ) { $d = 1; }
        if( $d === false ) { $d = 0; }
        $args[] = $d;
    }
    call_user_func_array('db_query',$args);
    return db_last_insert_id();
}

function db_last_insert_id() {
	global $sql;

	$last_insert_id = null;
	switch($sql->driver) {

		case 'pdo':
      if( $sql->engine == 'mysql' ) { $last_insert_id = db_fetch_var("SELECT LAST_INSERT_ID();"); }
      if( $sql->engine == 'postgres' ) { $last_insert_id = db_fetch_var("SELECT LASTVAL();"); }
	    break;

		case 'mysqli':
			$last_insert_id = mysqli_insert_id($sql);
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;

	}

	return $last_insert_id;
}
function db_total_count() {
	global $sql;

	$total_count = 0;
	switch($sql->driver) {

		case 'pdo':
	    	$total_count = db_fetch_var("SELECT FOUND_ROWS();");
		    break;

		case 'mysqli':
			$total_count = db_fetch_var("SELECT FOUND_ROWS();");
			break;

		case 'wordpress':
			// TODO
			break;

		case 'joomla':
			// TODO
			break;

	}

	return $total_count;
}
function db_debug($query) {
 
    $params = func_get_args(); unset($params[0]); $params = array_values($params);

    $keys = array();
    $values = $params;
    foreach($params as $key=>$value) {
        // check if named parameters (':param') or anonymous parameters ('?') are used
        if (is_string($key)) {
            $keys[] = '/:'.$key.'/';
        } else {
            $keys[] = '/[?]/';
        }
        // bring parameter into human-readable format
        if (is_string($value)) {
            $values[$key] = "'" . $value . "'";
        } elseif (is_array($value)) {
            $values[$key] = implode(',', $value);
        } elseif (is_null($value)) {
            $values[$key] = 'NULL';
        }
    }
    $query = preg_replace($keys, $values, $query, 1, $count);
    echo '<pre>';
    print_r($query);
    echo '</pre>';
    die();

}
function db_preparse_query($query, $params) {

  $return = $query;
  	
	// NULL values are treated specially: modify the query
	if(strpos($query,"UPDATE") === false) {
  	$pos = 0;
  	foreach ($params as $x_Key => $param) {
  		// no more ?s are left
  		if (($pos = strpos($query, '= ?', $pos + 1)) === false) {
  		    break;
  		}
  		// if param is not null, nothing must be done
  		if (!is_null($param)) {
  		    continue;
  		}
  
  		// convert != ? to IS NOT NULL and = ? to IS NULL
  		$return = substr($return,0,($pos-5)) . preg_replace("/\!= \?/","IS NOT NULL",substr($return,($pos-5)),1);
  		$return = substr($return,0,($pos-5)) . preg_replace("/= \?/","IS NULL",substr($return,($pos-5)),1);
  
  		// delete param
  		unset($params[$x_Key]);
  
  	}
  	$params = array_values($params);
	}
	
	return array($return,$params);

}
function db_update($table,$data,$condition) {
    $query = "";
    $query .= "UPDATE ";
    $query .= "`".$table."`";
    $query .= " SET ";
    foreach ($data as $key => $value) {
        $query .= "`".$key."`";
        $query .= " = ";
        $query .= "?";
        end($data); if ($key !== key($data)) { $query .= ', '; }
    }
    $query .= " WHERE ";
    foreach ($condition as $key => $value) {
        $query .= "`".$key."`";
        $query .= " = ";
        $query .= "? ";
        end($condition); if ($key !== key($condition)) { $query .= ' AND '; }
    }
    $args = array();
    $args[] = $query;
    foreach ($data as $d) {
        if( $d === true ) { $d = 1; } if( $d === false ) { $d = 0; }
        $args[] = $d;
    }
    foreach ($condition as $c) {
        if( $c === true ) { $c = 1; } if( $c === false ) { $c = 0; }
        $args[] = $c;
    }
    return call_user_func_array('db_query',$args); // returns the affected row counts
}

/* connect to database (set driver) */
/*
if( strpos($_SERVER['HTTP_HOST'], ".dev") !== false ) {
	$sql = db_connect('pdo','mysql','127.0.0.1','username','password','dbname');
}
else {
	$sql = db_connect('pdo','postgres','127.0.0.1','username','password','dbname',3307);
}
*/

/* example code */
//echo '<pre>';
//print_r(db_fetch_all("SELECT * FROM table WHERE ID > ?",1));
//print_r(db_fetch_all("SELECT * FROM table WHERE name = ? AND number > ?","david",5));
//print_r(db_fetch_row("SELECT ID FROM smd_brand WHERE ID = ?",1));
//print_r(db_fetch_var("SELECT ID FROM table WHERE ID = ?",1));
//print_r(db_query("INSERT INTO table(`row1`,`row2`) VALUES(?,?,?)",1,2,3));
//print_r(db_query("DELETE FROM table WHERE ID = ?",1));
//print_r(db_query("UPDATE table SET `row1` = ? WHERE ID = ?",1,2));
//db_insert("tablename",['id'=>1,'name'=>'foo']);
//db_update("tablename",["col1"=>"foo","col2"=>"bar"],["id"=>1]);
//echo '</pre>';
?>