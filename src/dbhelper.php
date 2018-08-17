<?php
namespace vielhuber\dbhelper;

use PDO;

class dbhelper
{

    public $sql = null;

    public $config = false;

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    public function connect($driver, $engine = null, $host = null, $username = null, $password = null, $database = null, $port = 3306)
    {
        switch ($driver)
        {
            case 'pdo':
                if ($engine === 'mysql')
                {
                    $sql = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database, $username, $password, [
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
                    ]);
                }
                else if ($engine === 'postgres')
                {
                    $sql = new PDO('pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database, $username, $password);
                    $sql->query('SET NAMES UTF8');
                }
                else
                {
                    throw new \Exception('missing engine');
                }
                $sql->database = $database;
                break;

            case 'mysqli':
                $sql = new mysqli($host, $username, $password, $database, $port);
                mysqli_set_charset($sql, "utf8");
                if ($sql->connect_errno)
                {
                    die('SQL Connection failed: ' . $sql->connect_error);
                }
                $sql->database = $database;
                break;

            case 'wordpress':
                global $wpdb;
                $engine = 'mysql';
                $wpdb->show_errors = true;
                $wpdb->suppress_errors = false;
                $sql = $wpdb;
                $sql->database = $wpdb->dbname;
                break;

            case 'joomla':
                // TODO
                break;

        }
        $sql->driver = $driver;
        $sql->engine = $engine;
        $this->sql = $sql;
    }

    public function disconnect()
    {
        switch ($this->sql->driver)
        {

            case 'pdo':
                $this->sql = null;
                break;

            case 'mysqli':
                $this->sql->close();
                break;

            case 'wordpress':
                // TODO
                break;

            case 'joomla':
                // TODO
                break;

        }
    }

    public function fetch_all($query)
    {
        $data = [];
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'mysqli':
                // do not use mysqlnd
                $result = $this->sql->query($query);
                while ($row = $result->fetch_assoc())
                {
                    $data[] = $row;
                }
                break;

            case 'wordpress':
                if( !empty($params) )
                {
                    $data = $this->sql->get_results($this->sql->prepare($query, $params));
                }
                else
                {
                    $data = $this->sql->get_results($query);
                }
                if( $this->sql->last_error )
                {
                    throw new \Exception($this->sql->last_error);
                }
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function fetch_row($query)
    {
        $data = [];
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                break;

            case 'mysqli':
                $data = $this->sql->query($query)->fetch_assoc();
                break;

            case 'wordpress':
                if( !empty($params) )
                {
                    $data = $this->sql->get_row($this->sql->prepare($query, $params));
                }
                else
                {
                    $data = $this->sql->get_row($query);
                }
                if( $this->sql->last_error )
                {
                    throw new \Exception($this->sql->last_error);
                }
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function fetch_col($query)
    {
        $data = [];
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($data))
                {
                    $data_tmp = [];
                    foreach ($data as $dat)
                    {
                        $data_tmp[] = $dat[ array_keys($dat)[0] ];
                    }
                    $data = $data_tmp;
                }
                break;

            case 'mysqli':
                // TODO
                break;

            case 'wordpress':
                if( !empty($params) )
                {
                    $data = $this->sql->get_col($this->sql->prepare($query, $params));
                }
                else
                {
                    $data = $this->sql->get_col($query);
                }
                if( $this->sql->last_error )
                {
                    throw new \Exception($this->sql->last_error);
                }
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function fetch_var($query)
    {
        $data = [];
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetchObject();
                if (empty($data))
                {
                    return null;
                }
                $data = (Array)$data;
                $data = current($data);
                break;

            case 'mysqli':
                $data = $this->sql->query($query)->fetch_object();
                if (empty($data))
                {
                    return null;
                }
                $data = (Array)$data;
                $data = current($data);
                break;

            case 'wordpress':
                if( !empty($params) )
                {
                    $data = $this->sql->get_var($this->sql->prepare($query, $params));
                }
                else
                {
                    $data = $this->sql->get_var($query);
                }
                if( $this->sql->last_error )
                {
                    throw new \Exception($this->sql->last_error);
                }
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function query($query)
    {
        $data = [];
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        if( isset($this->config['auto_inject']) && $this->config['auto_inject'] === true )
        {
            $query = $this->handle_logging($query, $params);
        }

        switch ($this->sql->driver)
        {

            case 'pdo':
                // in general we use prepare/execute instead of exec or query
                // this works certainly in all cases, EXCEPT when doing something like CREATE TABLE, CREATE TRIGGER, DROP TABLE, DROP TRIGGER
                // that causes the error "Cannot execute queries while other unbuffered queries are active"
                // therefore we switch in those cases to exec
                if( stripos($query,'CREATE') === 0 || stripos($query,'DROP') === 0 )
                {
                    $this->sql->exec($query);
                }
                else
                {
                    $stmt = $this->sql->prepare($query);
                    $stmt->execute($params);                
                    if ($stmt->errorCode() != 0)
                    {
                        $errors = $stmt->errorInfo();
                        throw new \Exception($errors[2]);
                    }
                    return $stmt->rowCount();    
                }           
                break;

            case 'mysqli':
                $this->sql->query($query);
                break;

            case 'wordpress':
                if( !empty($params) )
                {
                    $data = $this->sql->query($this->sql->prepare($query, $params));
                }
                else
                {
                    $data = $this->sql->query($query);
                }
                if( $this->sql->last_error )
                {
                    throw new \Exception($this->sql->last_error);
                }
                break;

            case 'joomla':
                // TODO
                break;

        }

    }

    public function insert($table, $data)
    {
        if( !isset($data[0]) && !is_array(array_values($data)[0]) )
        {
            $data = [$data];
        }
        $query = '';
        $query .= 'INSERT INTO ';
        $query .= $this->quote($table);
        $query .= '(';
        foreach($data[0] as $data__key=>$data__value)
        {
            $query .= $this->quote($data__key);
            if( array_keys($data[0])[count(array_keys($data[0]))-1] !== $data__key )
            {
                $query .= ',';
            }
        }
        $query .= ') ';
        $query .= 'VALUES ';
        foreach($data as $data__key=>$data__value)
        {
            $query .= '(';
                $query .= str_repeat('?,',count($data__value)-1).'?';
            $query .= ')';
            if( array_keys($data)[count(array_keys($data))-1] !== $data__key )
            {
                $query .= ',';
            }
        }
        $args = [];
        $args[] = $query;
        foreach($data as $data__key=>$data__value)
        {
            foreach($data__value as $data__value__key=>$data__value__value)
            {
                // because pdo can't insert true/false values so easily, convert them to 1/0
                if($data__value__value === true)
                {
                    $data__value__value = 1;
                }
                if($data__value__value === false)
                {
                    $data__value__value = 0;
                }
                $args[] = $data__value__value;
            }
        }
        $ret = call_user_func_array([$this, 'query'], $args);

        // mysql returns the last inserted id inside the current session, obviously ignoring triggers
        // on postgres we cannot use LASTVAL(), because it returns the last id of possibly inserted rows caused by triggers
        // see: https://stackoverflow.com/questions/51558021/mysql-postgres-last-insert-id-lastval-different-behaviour
        if( $this->sql->engine === 'mysql' )
        {
            return $this->last_insert_id();
        }
        if( $this->sql->engine === 'postgres' )
        {
            return $this->last_insert_id($table, $this->get_primary_key($table));
        }        
    }

    public function last_insert_id($table = null, $column = null)
    {
        $last_insert_id = null;
        switch ($this->sql->driver)
        {

            case 'pdo':
                if ($this->sql->engine == 'mysql')
                {
                    try {
                        $last_insert_id = $this->fetch_var("SELECT LAST_INSERT_ID();");
                    }
                    catch(\Exception $e)
                    {   
                        $last_insert_id = null;
                    }
                }
                if ($this->sql->engine == 'postgres')
                {
                    try {
                        if( $table === null || $column === null )
                        {
                            $last_insert_id = $this->fetch_var("SELECT LASTVAL();");
                        }
                        else
                        {
                            $last_insert_id = $this->fetch_var("SELECT CURRVAL(pg_get_serial_sequence('".$table."','".$column."'));");
                        }
                    }
                    catch(\Exception $e)
                    {   
                        $last_insert_id = null;
                    }
                }
                break;

            case 'mysqli':
                $last_insert_id = mysqli_insert_id($this->sql);
                break;

            case 'wordpress':
                $last_insert_id = $this->fetch_var("SELECT LAST_INSERT_ID();");
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $last_insert_id;
    }

    public function update($table, $data, $condition = null)
    {
        if( isset($data[0]) && is_array($data[0]) )
        {
            return $this->update_batch($table, $data);
        }
        $query = "";
        $query .= "UPDATE ";
        $query .= $this->quote($table);
        $query .= " SET ";
        foreach ($data as $key => $value)
        {
            $query .= $this->quote($key);
            $query .= " = ";
            $query .= "?";
            end($data);
            if ($key !== key($data))
            {
                $query .= ', ';
            }
        }
        $query .= " WHERE ";
        foreach ($condition as $key => $value)
        {
            $query .= $this->quote($key);
            $query .= " = ";
            $query .= "? ";
            end($condition);
            if ($key !== key($condition))
            {
                $query .= ' AND ';
            }
        }
        $args = [];
        $args[] = $query;
        foreach ($data as $d)
        {
            if ($d === true)
            {
                $d = 1;
            }
            if ($d === false)
            {
                $d = 0;
            }
            $args[] = $d;
        }
        foreach ($condition as $c)
        {
            if ($c === true)
            {
                $c = 1;
            }
            if ($c === false)
            {
                $c = 0;
            }
            $args[] = $c;
        }
        return call_user_func_array([$this, 'query'], $args); // returns the affected row counts
    }

    public function delete($table, $conditions)
    {
        if( !isset($conditions[0]) && !is_array(array_values($conditions)[0]) )
        {
            $conditions = [$conditions];
        }
        $query = '';
        $query .= 'DELETE FROM ';
        $query .= $this->quote($table);
        $query .= ' WHERE ';
        $query .= '(';
        foreach($conditions as $conditions__key=>$conditions__value)
        {
            $query .= '(';
            foreach($conditions__value as $conditions__value__key=>$conditions__value__value)
            {
                $query .= $this->quote($conditions__value__key);
                $query .= ' = ';
                $query .= '?';
                if( array_keys($conditions__value)[count(array_keys($conditions__value))-1] !== $conditions__value__key )
                {
                    $query .= ' AND ';
                }
            }
            $query .= ')';
            if( array_keys($conditions)[count(array_keys($conditions))-1] !== $conditions__key )
            {
                $query .= ' OR ';
            }
        }
        $query .= ')';
        $args = [];
        $args[] = $query;
        foreach($conditions as $conditions__key=>$conditions__value)
        {
            foreach($conditions__value as $conditions__value__key=>$conditions__value__value)
            {
                if($conditions__value__value === true)
                {
                    $conditions__value__value = 1;
                }
                if($conditions__value__value === false)
                {
                    $conditions__value__value = 0;
                }
                $args[] = $conditions__value__value;
            }
        }
        return call_user_func_array([$this, 'query'], $args); // returns the affected row counts
    }

    public function clear($table = null)
    {
        if( $table === null )
        {
            if( $this->sql->engine === 'mysql' )
            {
                $this->query('SET FOREIGN_KEY_CHECKS = 0');        
                $tables = $this->fetch_col('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', $this->sql->database);
                if( !empty($tables) )
                {
                    foreach($tables as $tables__value)
                    {
                        $this->query('DROP TABLE '.$tables__value);
                    }
                }
                $this->query('SET FOREIGN_KEY_CHECKS = 1');
            }
            else if( $this->sql->engine === 'postgres' )
            {
                $this->query('DROP SCHEMA public CASCADE');
                $this->query('CREATE SCHEMA public');
            }
        }
        else
        {
            $this->query('TRUNCATE TABLE '.$table);
        }
    }

    public function get_tables()
    {
        return $this->fetch_col(
            'SELECT table_name FROM information_schema.tables WHERE table_catalog = ? AND table_schema = ? ORDER BY table_name',
            (($this->sql->engine==='mysql')?('def'):(($this->sql->engine==='postgres')?($this->sql->database):(''))),
            (($this->sql->engine==='mysql')?($this->sql->database):(($this->sql->engine==='postgres')?('public'):('')))
        );
    }

    public function get_columns($table)
    {
        return $this->fetch_col(
            'SELECT column_name FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ?',
            (($this->sql->engine==='mysql')?('def'):(($this->sql->engine==='postgres')?($this->sql->database):(''))),
            (($this->sql->engine==='mysql')?($this->sql->database):(($this->sql->engine==='postgres')?('public'):(''))),
            $table
        );    
    }

    public function has_column($table, $column)
    {
        $count = $this->fetch_var(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ? AND column_name = ?',
            (($this->sql->engine==='mysql')?('def'):(($this->sql->engine==='postgres')?($this->sql->database):(''))),
            (($this->sql->engine==='mysql')?($this->sql->database):(($this->sql->engine==='postgres')?('public'):(''))),
            $table,
            $column
        );
        return $count > 0;
    }

    public function get_datatype($table, $column)
    {
        return $this->fetch_var(
            'SELECT data_type FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ? and column_name = ?',
            (($this->sql->engine==='mysql')?('def'):(($this->sql->engine==='postgres')?($this->sql->database):(''))),
            (($this->sql->engine==='mysql')?($this->sql->database):(($this->sql->engine==='postgres')?('public'):(''))),
            $table,
            $column
        );
    }

    public function get_primary_key($table)
    {
        try
        {
            if( $this->sql->engine === 'mysql' )
            {
                return ((object)$this->fetch_row('SHOW KEYS FROM '.$this->quote($table).' WHERE Key_name = ?', 'PRIMARY'))->Column_name;
            }
            if( $this->sql->engine === 'postgres' )
            {
                return $this->fetch_var('SELECT pg_attribute.attname FROM pg_index JOIN pg_attribute ON pg_attribute.attrelid = pg_index.indrelid AND pg_attribute.attnum = ANY(pg_index.indkey) WHERE pg_index.indrelid = \''.$table.'\'::regclass AND pg_index.indisprimary');
            }
        }
        catch(\Exception $e)
        {
            return null;
        }        
    }

    public function enable_auto_inject()
    {
        $this->config['auto_inject'] = true;
    }

    public function setup_logging()
    {

        if( $this->sql->engine === 'mysql' )
        {
            $this->setup_logging_create_table_mysql();
            $this->setup_logging_add_column();
            $this->setup_logging_create_triggers_mysql();
        }
        if( $this->sql->engine === 'postgres' )
        {
            $this->setup_logging_create_table_postgres();
            $this->setup_logging_add_column();
            $this->setup_logging_create_triggers_postgres();
        }
        
        $this->setup_logging_delete_older();

    }

    private function setup_logging_delete_older()
    {
        if( isset($this->config['delete_older']) && is_numeric($this->config['delete_older']) )
        {
            $this->query('DELETE FROM '.$this->config['logging_table'].' WHERE updated_at < ?', date('Y-m-d',strtotime('now - '.$this->config['delete_older'].' months')));
        }
    }

    private function setup_logging_create_table_mysql()
    {
        $this->query('
            CREATE TABLE IF NOT EXISTS '.$this->config['logging_table'].' (
              id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              log_event varchar(10) NOT NULL,
              log_table varchar(100) NOT NULL,
              log_key varchar(100) NOT NULL,
              log_column varchar(100) DEFAULT NULL,
              log_value LONGTEXT DEFAULT NULL,
              log_uuid varchar(36) DEFAULT NULL,
              updated_by varchar(100) DEFAULT NULL,
              updated_at datetime(0) DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) NOT NULL
            )
        ');
    }

    private function setup_logging_create_table_postgres()
    {
        $this->query('
            CREATE TABLE IF NOT EXISTS '.$this->config['logging_table'].' (
              id SERIAL NOT NULL PRIMARY KEY,
              log_event varchar(10) NOT NULL,
              log_table varchar(100) NOT NULL,
              log_key varchar(100) NOT NULL,
              log_column varchar(100) DEFAULT NULL,
              log_value TEXT DEFAULT NULL,
              log_uuid varchar(36) DEFAULT NULL,
              updated_by varchar(100) DEFAULT NULL,
              updated_at TIMESTAMP without time zone NULL
            )
        ');
        $this->query('
            CREATE OR REPLACE FUNCTION auto_update_updated_at_column() 
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = now();
                RETURN NEW; 
            END;
            $$ language \'plpgsql\';
        ');
        $this->query('CREATE TRIGGER auto_update_updated_at_column_on_insert BEFORE INSERT ON '.$this->config['logging_table'].' FOR EACH ROW EXECUTE PROCEDURE auto_update_updated_at_column();');
        $this->query('CREATE TRIGGER auto_update_updated_at_column_on_update BEFORE UPDATE ON '.$this->config['logging_table'].' FOR EACH ROW EXECUTE PROCEDURE auto_update_updated_at_column();');
    }

    private function setup_logging_add_column()
    {
        foreach( $this->get_tables() as $table__value )
        {
            if( isset($this->config['exclude']) && isset($this->config['exclude']['tables']) && in_array($table__value, $this->config['exclude']['tables']) )
            {
                continue;
            }
            if( $table__value === $this->config['logging_table'] )
            {
                continue;
            }
            if( !$this->has_column($table__value, 'updated_by') )
            {
                $this->query('ALTER TABLE '.$table__value.' ADD COLUMN updated_by varchar(50)');
            }
        }
    }

    private function setup_logging_create_triggers_mysql()
    {
        foreach( $this->get_tables() as $table__value )
        {

            $this->query('DROP TRIGGER IF EXISTS '.$this->quote('trigger-logging-insert-'.$table__value));
            $this->query('DROP TRIGGER IF EXISTS '.$this->quote('trigger-logging-update-'.$table__value));
            $this->query('DROP TRIGGER IF EXISTS '.$this->quote('trigger-logging-delete-'.$table__value));
            
            if( isset($this->config['exclude']) && isset($this->config['exclude']['tables']) && in_array($table__value, $this->config['exclude']['tables']) )
            {
                continue;
            }
            if( $table__value === $this->config['logging_table'] )
            {
                continue;
            }

            $primary_key = $this->get_primary_key($table__value);

            /* note: we do not use DELIMITER $$ here, because php in mysql can handle that anyways because it does not execute multiple queries */

            $query = '                
                CREATE TRIGGER '.$this->quote('trigger-logging-insert-'.$table__value).'
                AFTER INSERT ON '.$table__value.' FOR EACH ROW
                BEGIN
                    DECLARE uuid TEXT;
                    SET @uuid := '.$this->uuid_query().';
                    '.array_reduce($this->get_columns($table__value), function($carry, $column) use ($table__value, $primary_key) {
                        if(
                            $column === $primary_key ||
                            $column === 'updated_by' ||
                            $column === 'created_at' || $column === 'updated_at' || // laravel
                            (isset($this->config['exclude']) && isset($this->config['exclude']['columns']) && isset($this->config['exclude']['columns'][$table__value]) && in_array($column, $this->config['exclude']['columns'][$table__value]))
                        ) { return $carry; }

                        $carry .= '
                            INSERT INTO '.$this->config['logging_table'].'(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                            VALUES(\'insert\', \''.$table__value.'\', NEW.'.$this->quote($primary_key).', \''.$column.'\', NEW.'.$this->quote($column).', @uuid, NEW.updated_by);
                        ';
                        return $carry;
                    }).'
                END
            ';

            $this->query($query);

            $query = '
                CREATE TRIGGER '.$this->quote('trigger-logging-update-'.$table__value).'
                AFTER UPDATE ON '.$table__value.' FOR EACH ROW
                BEGIN
                    DECLARE uuid TEXT;
                    SET @uuid := '.$this->uuid_query().';
                    '.array_reduce($this->get_columns($table__value), function($carry, $column) use ($table__value, $primary_key) {
                        if(
                            $column === $primary_key ||
                            $column === 'updated_by' ||
                            $column === 'created_at' || $column === 'updated_at' || // laravel
                            (isset($this->config['exclude']) && isset($this->config['exclude']['columns']) && isset($this->config['exclude']['columns'][$table__value]) && in_array($column, $this->config['exclude']['columns'][$table__value]))
                        ) { return $carry; }
                        $carry .= '
                            IF (OLD.'.$this->quote($column).' <> NEW.'.$this->quote($column).') OR (OLD.'.$this->quote($column).' IS NULL AND NEW.'.$this->quote($column).' IS NOT NULL) OR (OLD.'.$this->quote($column).' IS NOT NULL AND NEW.'.$this->quote($column).' IS NULL) THEN
                                INSERT INTO '.$this->config['logging_table'].'(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                                VALUES(\'update\', \''.$table__value.'\', NEW.'.$this->quote($primary_key).', \''.$column.'\', NEW.'.$this->quote($column).', @uuid, NEW.updated_by);
                            END IF;
                        ';
                        return $carry;
                    }).'
                END
            ';

            $this->query($query);

            $query = '                
                CREATE TRIGGER '.$this->quote('trigger-logging-delete-'.$table__value).'
                AFTER DELETE ON '.$table__value.' FOR EACH ROW
                BEGIN
                    DECLARE uuid TEXT;
                    SET @uuid := '.$this->uuid_query().';
                    IF( NOT EXISTS( SELECT * FROM '.$this->config['logging_table'].' WHERE log_event = \'delete\' AND log_table = \''.$table__value.'\' AND log_key = OLD.'.$this->quote($primary_key).' ) ) THEN
                        INSERT INTO '.$this->config['logging_table'].'(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                        VALUES(\'delete\', \''.$table__value.'\', OLD.'.$this->quote($primary_key).', NULL, NULL, @uuid, OLD.updated_by);
                    END IF;
                END
            ';

            $this->query($query);
        }
    }


    private function setup_logging_create_triggers_postgres()
    {
        foreach( $this->get_tables() as $table__value )
        {

            $this->query('DROP TRIGGER IF EXISTS '.$this->quote('trigger-logging-insert-'.$table__value).' ON '.$this->quote($table__value));
            $this->query('DROP TRIGGER IF EXISTS '.$this->quote('trigger-logging-update-'.$table__value).' ON '.$this->quote($table__value));
            $this->query('DROP TRIGGER IF EXISTS '.$this->quote('trigger-logging-delete-'.$table__value).' ON '.$this->quote($table__value));
            
            if( isset($this->config['exclude']) && isset($this->config['exclude']['tables']) && in_array($table__value, $this->config['exclude']['tables']) )
            {
                continue;
            }
            if( $table__value === $this->config['logging_table'] )
            {
                continue;
            }

            $primary_key = $this->get_primary_key($table__value);


            $query = '
                CREATE OR REPLACE FUNCTION trigger_logging_insert_'.$table__value.'() 
                RETURNS TRIGGER AS $$
                DECLARE
                    uuid TEXT;
                BEGIN
                    uuid := '.$this->uuid_query().';
                    '.array_reduce($this->get_columns($table__value), function($carry, $column) use ($table__value, $primary_key) {
                        if(
                            $column === $primary_key ||
                            $column === 'updated_by' ||
                            $column === 'created_at' || $column === 'updated_at' || // laravel
                            (isset($this->config['exclude']) && isset($this->config['exclude']['columns']) && isset($this->config['exclude']['columns'][$table__value]) && in_array($column, $this->config['exclude']['columns'][$table__value]))
                        ) { return $carry; }

                        $carry .= '
                            INSERT INTO '.$this->config['logging_table'].'(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                            VALUES(\'insert\', \''.$table__value.'\', NEW.'.$this->quote($primary_key).'::text, \''.$column.'\', NEW.'.$this->quote($column).'::text, uuid, NEW.updated_by);
                        ';
                        return $carry;
                    }).'
                    RETURN NULL;
                END;
                $$ language \'plpgsql\';
            ';
            $this->query($query);
            $query = '
                CREATE TRIGGER '.$this->quote('trigger-logging-insert-'.$table__value).'
                AFTER INSERT ON '.$table__value.' FOR EACH ROW
                EXECUTE PROCEDURE trigger_logging_insert_'.$table__value.'();
            ';
            $this->query($query);


            $query = '
                CREATE OR REPLACE FUNCTION trigger_logging_update_'.$table__value.'() 
                RETURNS TRIGGER AS $$
                DECLARE
                    uuid TEXT;
                BEGIN
                    uuid := '.$this->uuid_query().';
                    '.array_reduce($this->get_columns($table__value), function($carry, $column) use ($table__value, $primary_key) {
                        if(
                            $column === $primary_key ||
                            $column === 'updated_by' ||
                            $column === 'created_at' || $column === 'updated_at' || // laravel
                            (isset($this->config['exclude']) && isset($this->config['exclude']['columns']) && isset($this->config['exclude']['columns'][$table__value]) && in_array($column, $this->config['exclude']['columns'][$table__value]))
                        ) { return $carry; }
                        $carry .= '
                            IF (OLD.'.$this->quote($column).' <> NEW.'.$this->quote($column).') OR (OLD.'.$this->quote($column).' IS NULL AND NEW.'.$this->quote($column).' IS NOT NULL) OR (OLD.'.$this->quote($column).' IS NOT NULL AND NEW.'.$this->quote($column).' IS NULL) THEN
                                INSERT INTO '.$this->config['logging_table'].'(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                                VALUES(\'update\', \''.$table__value.'\', NEW.'.$this->quote($primary_key).'::text, \''.$column.'\', NEW.'.$this->quote($column).'::text, uuid, NEW.updated_by);
                            END IF;
                        ';
                        return $carry;
                    }).'
                    RETURN NULL;
                END;
                $$ language \'plpgsql\';
            ';
            $this->query($query);
            $query = '
                CREATE TRIGGER '.$this->quote('trigger-logging-update-'.$table__value).'
                AFTER UPDATE ON '.$table__value.' FOR EACH ROW
                EXECUTE PROCEDURE trigger_logging_update_'.$table__value.'();
            ';
            $this->query($query);


            $query = '
                CREATE OR REPLACE FUNCTION trigger_logging_delete_'.$table__value.'() 
                RETURNS TRIGGER AS $$
                DECLARE
                    uuid TEXT;
                BEGIN
                    uuid := '.$this->uuid_query().';
                    IF( NOT EXISTS( SELECT * FROM '.$this->config['logging_table'].' WHERE log_event = \'delete\' AND log_table = \''.$table__value.'\' AND log_key = OLD.'.$this->quote($primary_key).'::text ) ) THEN
                        INSERT INTO '.$this->config['logging_table'].'(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                        VALUES(\'delete\', \''.$table__value.'\', OLD.'.$this->quote($primary_key).'::text, NULL, NULL, uuid, OLD.updated_by);
                    END IF;
                    RETURN NULL;
                END;
                $$ language \'plpgsql\';
            ';
            $this->query($query);
            $query = '
                CREATE TRIGGER '.$this->quote('trigger-logging-delete-'.$table__value).'
                AFTER DELETE ON '.$table__value.' FOR EACH ROW
                EXECUTE PROCEDURE trigger_logging_delete_'.$table__value.'();
            ';
            $this->query($query);

        }
    }

    private function preparse_query($query, $params)
    {

        $return = $query;

        /*
        expand IN-syntax
        fetch('SELECT * FROM table WHERE col1 IN (?) AND col2 IN (?) AND col3 IN (?,?) col4 IN (?)', [1], 2, [7,8], [3,4,5])
        gets to
        fetch('SELECT * FROM table WHERE col1 IN (?) AND col2 IN (?) AND col4 IN (?,?) AND col4 IN (?,?,?)', 1, 2, 3, 7, 8, 3, 4, 5)
        */
        if( strpos($query, 'IN (') !== false || strpos($query, 'IN(') !== false )
        {
            // find all ?s
            $in_positions = $this->find_occurences($query,'?');
            $in_index = 0;
            if( !empty($params) )
            {
                foreach($params as $params__key=>$params__value)
                {
                    if( is_array($params__value) && count($params__value) > 0 )
                    {
                        $in_occurence = $this->find_nth_occurence($return,'?',$in_index);
                        if( substr($return, $in_occurence-1, 3) == '(?)' )
                        {
                            $return = substr($return, 0, $in_occurence-1).
                                    '('.(str_repeat('?,',count($params__value)-1).'?').')'.
                                    substr($return, $in_occurence+2);
                        }
                        foreach($params__value as $params__value__value)
                        {
                            $in_index++;
                        }
                    }
                    else
                    {
                        $in_index++;
                    }
                }
            }
        }
        
        /*
        finally flatten all arguments
        example:
        fetch('SELECT * FROM table WHERE ID = ?', [1], 2, [3], [4,5,6])
        =>
        fetch('SELECT * FROM table WHERE ID = ?', 1, 2, 3, 4, 5, 6)
        */
        if( !empty($params) )
        {
            
            $params_flattened = [];
            array_walk_recursive($params, function($a) use (&$params_flattened) { $params_flattened[] = $a; });
            $params = $params_flattened;
        }

        // try to sort out bad queries
        foreach($params as $params__key=>$params__value)
        {
            if( is_object($params__value) )
            {
                throw new \Exception('object in query');
            }
        }

        // NULL values are treated specially: modify the query
        {
            $pos = 0;
            $delete_keys = [];
            foreach($params as $params__key=>$params__value)
            {
                // no more ?s are left
                if(($pos = strpos($return, '?', $pos + 1)) === false)
                {
                    break;
                }

                // if param is not null, nothing must be done
                if (!is_null($params__value))
                {
                    continue;
                }

                // case 1: if query contains WHERE before ?, then convert != ? to IS NOT NULL and = ? to IS NULL
                if( strpos( substr($return, 0, $pos), 'WHERE' ) !== false )
                {
                    if( strpos(substr($return, $pos-5, 6), '<> ?') !== false )
                    { 
                        $return = substr($return, 0, ($pos - 5)) . preg_replace('/<> \?/', 'IS NOT NULL', substr($return, ($pos - 5)), 1);
                    }
                    elseif( strpos(substr($return, $pos-5, 6), '!= ?') !== false )
                    {
                        $return = substr($return, 0, ($pos - 5)) . preg_replace('/\!= \?/', 'IS NOT NULL', substr($return, ($pos - 5)), 1);
                    }
                    elseif( strpos(substr($return, $pos-5, 6), '= ?') !== false )
                    {
                        $return = substr($return, 0, ($pos - 5)) . preg_replace('/= \?/', 'IS NULL', substr($return, ($pos - 5)), 1);
                    }
                }
                // case 2: in all other cases, convert ? to NULL
                else
                {
                    $return = substr($return, 0, $pos) . 'NULL' . substr($return, $pos+1);
                }

                // delete param
                $delete_keys[] = $params__key;

            }
            if( !empty($delete_keys) )
            {
                foreach($delete_keys as $delete_keys__value)
                {
                    unset($params[$delete_keys__value]);
                }
            }
            $params = array_values($params);
        }

        // WordPress: replace ? with %s
        if( $this->sql->driver == 'wordpress' )
        {
            foreach($params as $params__key=>$params__value)
            {
                // replace next occurence
                if( strpos($return, '?') !== false )
                {
                    $directive = '%s';
                    if( is_int($params__value) || ctype_digit((string)$params__value) ) { $directive = '%d'; }
                    else if( is_float($params__value) ) { $directive = '%f'; }                
                    $return = substr_replace($return, $directive, strpos($return, '?'), strlen('?'));
                }
            }
        }
        
        // WordPress: pass stripslashes_deep to all parameters (wordpress always adds slashes to them)
        if( $this->sql->driver == 'wordpress' )
        {
            $params = stripslashes_deep($params);
        }

        // trim final result
        $return = trim($return);

        return [$return, $params];

    }

    private function debug($query)
    {

        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);

        $keys = [];
        $values = $params;
        foreach ($params as $key => $value)
        {
            // check if named parameters (':param') or anonymous parameters ('?') are used
            if (is_string($key))
            {
                $keys[] = '/:' . $key . '/';
            }
            else
            {
                $keys[] = '/[?]/';
            }
            // bring parameter into human-readable format
            if (is_string($value))
            {
                $values[ $key ] = "'" . $value . "'";
            }
            elseif (is_array($value))
            {
                $values[ $key ] = implode(',', $value);
            }
            elseif (is_null($value))
            {
                $values[ $key ] = 'NULL';
            }
        }
        $query = preg_replace($keys, $values, $query, 1, $count);
        echo '<pre>';
        print_r($query);
        echo '</pre>';
        die();

    }

    private function find_occurences($haystack, $needle)
    {
        $positions = [];
        $pos_last = 0;
        while( ($pos_last = strpos($haystack, $needle, $pos_last)) !== false )
        {
            $positions[] = $pos_last;
            $pos_last = $pos_last + strlen($needle);
        }
        return $positions;
    }

    private function find_nth_occurence($haystack, $needle, $index)
    {
        $positions = $this->find_occurences($haystack, $needle);
        if(empty($positions) || $index > (count($positions)-1)) { return null; }
        return $positions[$index];
    }

    private function update_batch($table, $input)
    {
        $query = '';
        $args = [];
        $query = 'UPDATE '.$table.' SET'.PHP_EOL;
        foreach($input[0][0] as $col__key=>$col__value)
        {
            $query .= $col__key.' = CASE'.PHP_EOL;
            foreach($input as $input__key=>$input__value)
            {
                $query .= 'WHEN (';
                $where = [];
                foreach($input__value[1] as $where__key=>$where__value)
                {
                    $where[] = $where__key.' = ?';
                    $args[] = $where__value;
                }
                $query .= implode(' AND ',$where).')'.' THEN ?'.PHP_EOL;
                $args[] = $input__value[0][$col__key];
            }
            $query .= 'END';
            if( array_keys($input[0][0])[count(array_keys($input[0][0]))-1] !== $col__key ) $query .= ',';
            $query .= PHP_EOL;
        }
        $query .= 'WHERE ';
        $where = [];
        foreach($input[0][1] as $where__key=>$where__value)
        {
            $where_values = [];
            foreach($input as $input__key=>$input__value)
            {
                $where_values[] = $input__value[1][$where__key];
            }
            $where_values = array_unique($where_values);
            $where[] = $where__key.' IN ('.str_repeat('?,',count($where_values)-1).'?)';
            $args = array_merge($args, $where_values);
        }
        $query .= implode(' AND ', $where).';';
        array_unshift($args, $query);
        return call_user_func_array([$this, 'query'], $args);
    }

    private function handle_logging($query, $params)
    {
        $table = $this->get_table_name_from_query($query);

        if( isset($this->config['exclude']) && isset($this->config['exclude']['tables']) && in_array($table, $this->config['exclude']['tables']) )
        {
            return $query;
        }
        if( $table === $this->config['logging_table'] )
        {
            return $query;
        }

        if( stripos($query, 'INSERT') === 0 )
        {

            $pos1 = strpos($query,')');
            $pos2 = strrpos($query,')');
            $pos3 = stripos($query, 'INTO')+strlen('INTO');
            $pos4 = strpos($query, '(');
            if( $pos1 === false || $pos2 === false || $pos2 != strlen($query)-1 || strpos(substr($query, 0, $pos1),'updated_by') !== false )
            {
                return $query;
            }
            $query = substr($query, 0, $pos1) . ',updated_by' . substr($query, $pos1, $pos2-$pos1) . ',\''.$this->config['updated_by'].'\'' . substr($query, $pos2);
        }

        else if( stripos($query, 'UPDATE') === 0 )
        {
            $pos1 = stripos($query,'SET')+strlen('SET');
            if( $pos1 !== false && strpos(substr($query, $pos1),'updated_by') === false )
            {
                $query = substr($query, 0, $pos1) . ' updated_by = \''.$this->config['updated_by'].'\', ' . substr($query, $pos1);
            }
        }

        else if( stripos($query, 'DELETE') === 0 )
        {
            // fetch all ids that are affected
            $ids = $this->fetch_col('SELECT '.$this->get_primary_key($table).' '.substr($query, stripos($query,'FROM')), $params);
            if( !empty($ids) )
            {
                foreach($ids as $id)
                {
                    $this->insert('logs', ['log_event' => 'delete', 'log_table' => $table, 'log_key' => $id, 'log_uuid' => $this->uuid(), 'updated_by' => $this->config['updated_by']]);
                }
            }
        }

        return $query;

    }

    private function get_table_name_from_query($query)
    {
        $table = '';

        if( stripos($query, 'INSERT') === 0 )
        {
            $pos1 = stripos($query, 'INTO')+strlen('INTO');
            $pos2 = strpos($query, '(');
            $table = substr($query, $pos1, $pos2-$pos1);
        }


        else if( stripos($query, 'UPDATE') === 0 )
        {
            $pos1 = stripos($query,'UPDATE')+strlen('UPDATE');
            $pos2 = stripos($query,'SET');
            $table = substr($query, $pos1, $pos2-$pos1);
        }


        else if( stripos($query, 'DELETE') === 0 )
        {
            $pos1 = stripos($query,'FROM')+strlen('FROM');
            $pos2 = stripos($query,'WHERE');
            if( $pos2 === false ) { $pos2 = strlen($query); }
            $table = substr($query, $pos1, $pos2-$pos1);
        }

        $table = str_replace('`','',$table);
        $table = str_replace('"','',$table);
        $table = trim($table);

        return $table;
    }

    private function quote($name)
    {
        if( $this->sql->engine === 'mysql' )
        {
            return '`'.$name.'`';
        }
        if( $this->sql->engine === 'postgres' )
        {
            return '"'.$name.'"';
        }
    }

    public function uuid()
    {
        return $this->fetch_var('SELECT '.$this->uuid_query());
    }

    private function uuid_query()
    {
        if( $this->sql->engine === 'mysql' )
        {
            return 'UUID()';
        }
        if( $this->sql->engine === 'postgres' )
        {
            return 'uuid_in(md5(random()::text || now()::text)::cstring)';
        }
    }

}