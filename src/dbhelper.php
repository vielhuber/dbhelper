<?php

namespace vielhuber\dbhelper;

use PDO;

class dbhelper
{
    public $sql = null;

    public $connect = null;

    public $config = false;

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    public function connect(
        $driver,
        $engine = null,
        $host = null,
        $username = null,
        $password = null,
        $database = null,
        $port = 3306,
        $timeout = 60
    ) {
        $connect = (object) [];
        switch ($driver) {
            case 'pdo':
                if ($engine === 'mysql') {
                    $sql = new PDO(
                        'mysql:host=' .
                            $host .
                            ';port=' .
                            $port .
                            ($database !== null ? ';dbname=' . $database : ';charset=utf8mb4'),
                        $username,
                        $password,
                        [
                            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => $timeout
                        ]
                    );
                } elseif ($engine === 'postgres') {
                    $sql = new PDO(
                        'pgsql:host=' . $host . ';port=' . $port . ($database !== null ? ';dbname=' . $database : ''),
                        $username,
                        $password
                    );
                    //$sql->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
                } elseif ($engine === 'sqlite') {
                    $sql = new PDO('sqlite:' . $host, null, null, [
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => $timeout
                    ]);
                } else {
                    throw new \Exception('missing engine');
                }
                $connect->database = $database;
                break;

            case 'mysqli':
                $sql = new \mysqli($host, $username, $password, $database, $port);
                mysqli_set_charset($sql, 'utf8mb4');
                if ($sql->connect_errno) {
                    die('SQL Connection failed: ' . $sql->connect_error);
                }
                $connect->database = $database;
                break;

            case 'wordpress':
                global $wpdb;
                $engine = 'mysql';
                $wpdb->show_errors = true;
                $wpdb->suppress_errors = false;
                $sql = $wpdb;
                $connect->database = $wpdb->dbname;
                break;
        }
        $this->sql = $sql;

        $connect->driver = $driver;
        $connect->engine = $engine;
        $connect->host = $host;
        $connect->username = $username;
        $connect->password = $password;
        $connect->port = $port;
        $this->connect = $connect;
    }

    public function create_database($database)
    {
        switch ($this->connect->driver) {
            case 'pdo':
                if ($this->connect->engine === 'mysql') {
                    $this->sql->exec('CREATE DATABASE IF NOT EXISTS ' . $database . ';');
                } elseif ($this->connect->engine === 'postgres') {
                    $this->sql->exec('CREATE DATABASE ' . $database . ';');
                } elseif ($this->connect->engine === 'sqlite') {
                    @touch($database);
                }
                break;

            case 'mysqli':
                break;

            case 'wordpress':
                // TODO
                break;
        }
    }

    public function connect_with_create(
        $driver,
        $engine = null,
        $host = null,
        $username = null,
        $password = null,
        $database = null,
        $port = 3306,
        $timeout = 60
    ) {
        $this->connect($driver, $engine, $host, $username, $password, null, $port, $timeout);
        $this->create_database($database);
        $this->disconnect();
        $this->connect($driver, $engine, $host, $username, $password, $database, $port, $timeout);
    }

    public function delete_database($database)
    {
        switch ($this->connect->driver) {
            case 'pdo':
                if ($this->connect->engine === 'mysql') {
                    $this->sql->exec('DROP DATABASE ' . $database . ';');
                } elseif ($this->connect->engine === 'postgres') {
                    $this->sql->exec('DROP DATABASE ' . $database . ';');
                } elseif ($this->connect->engine === 'sqlite') {
                    @unlink($database);
                }
                break;
            case 'mysqli':
                break;
            case 'wordpress':
                break;
        }
    }

    public function disconnect_with_delete()
    {
        $driver = $this->connect->driver;
        $engine = $this->connect->engine;
        $host = $this->connect->host;
        $username = $this->connect->username;
        $password = $this->connect->password;
        $database = $this->connect->database;
        $port = $this->connect->port;
        $this->disconnect();
        if ($driver === 'pdo' && $engine === 'sqlite') {
            $this->sql = (object) [];
            $this->connect->driver = $driver;
            $this->connect->engine = $engine;
            $this->connect->host = $host;
            $this->delete_database($host);
        } else {
            $this->connect($driver, $engine, $host, $username, $password, null, $port);
            $this->delete_database($database);
            $this->disconnect();
        }
    }

    public function disconnect()
    {
        switch ($this->connect->driver) {
            case 'pdo':
                $this->sql = null;
                break;

            case 'mysqli':
                $this->sql->close();
                break;

            case 'wordpress':
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
        [$query, $params] = $this->preparse_query($query, $params);

        switch ($this->connect->driver) {
            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0) {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'mysqli':
                // do not use mysqlnd
                $result = $this->sql->query($query);
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                break;

            case 'wordpress':
                if (!empty($params)) {
                    $data = $this->sql->get_results($this->sql->prepare($query, $params));
                } else {
                    $data = $this->sql->get_results($query);
                }
                if ($this->sql->last_error) {
                    throw new \Exception($this->sql->last_error);
                }
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
        [$query, $params] = $this->preparse_query($query, $params);

        switch ($this->connect->driver) {
            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0) {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                break;

            case 'mysqli':
                $data = $this->sql->query($query)->fetch_assoc();
                break;

            case 'wordpress':
                if (!empty($params)) {
                    $data = $this->sql->get_row($this->sql->prepare($query, $params));
                } else {
                    $data = $this->sql->get_row($query);
                }
                if ($this->sql->last_error) {
                    throw new \Exception($this->sql->last_error);
                }
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
        [$query, $params] = $this->preparse_query($query, $params);

        switch ($this->connect->driver) {
            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0) {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($data)) {
                    $data_tmp = [];
                    foreach ($data as $dat) {
                        $data_tmp[] = $dat[array_keys($dat)[0]];
                    }
                    $data = $data_tmp;
                }
                break;

            case 'mysqli':
                // TODO
                break;

            case 'wordpress':
                if (!empty($params)) {
                    $data = $this->sql->get_col($this->sql->prepare($query, $params));
                } else {
                    $data = $this->sql->get_col($query);
                }
                if ($this->sql->last_error) {
                    throw new \Exception($this->sql->last_error);
                }
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
        [$query, $params] = $this->preparse_query($query, $params);

        switch ($this->connect->driver) {
            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0) {
                    $errors = $stmt->errorInfo();
                    throw new \Exception($errors[2]);
                }
                $data = $stmt->fetchObject();
                if (empty($data)) {
                    return null;
                }
                $data = (array) $data;
                $data = current($data);
                break;

            case 'mysqli':
                $data = $this->sql->query($query)->fetch_object();
                if (empty($data)) {
                    return null;
                }
                $data = (array) $data;
                $data = current($data);
                break;

            case 'wordpress':
                if (!empty($params)) {
                    $data = $this->sql->get_var($this->sql->prepare($query, $params));
                } else {
                    $data = $this->sql->get_var($query);
                }
                if ($this->sql->last_error) {
                    throw new \Exception($this->sql->last_error);
                }
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
        [$query, $params] = $this->preparse_query($query, $params);

        if (isset($this->config['auto_inject']) && $this->config['auto_inject'] === true) {
            $query = $this->handle_logging($query, $params);
        }

        switch ($this->connect->driver) {
            case 'pdo':
                // in general we use prepare/execute instead of exec or query
                // this works certainly in all cases, EXCEPT when doing something like CREATE TABLE, CREATE TRIGGER, DROP TABLE, DROP TRIGGER
                // that causes the error "Cannot execute queries while other unbuffered queries are active"
                // therefore we switch in those cases to exec
                if (
                    stripos($query, 'CREATE') === 0 ||
                    stripos($query, 'DROP') === 0 ||
                    stripos($query, 'PRAGMA') === 0 ||
                    stripos($query, 'BEGIN') === 0
                ) {
                    $this->sql->exec($query);
                } else {
                    $stmt = $this->sql->prepare($query);
                    $stmt->execute($params);
                    if ($stmt->errorCode() != 0) {
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
                if (!empty($params)) {
                    $data = $this->sql->query($this->sql->prepare($query, $params));
                } else {
                    $data = $this->sql->query($query);
                }
                if ($this->sql->last_error) {
                    throw new \Exception($this->sql->last_error);
                }
                break;
        }
    }

    public function insert($table, $data)
    {
        if (!isset($data[0]) && !is_array(array_values($data)[0])) {
            $data = [$data];
        }
        $query = '';
        $query .= 'INSERT INTO ';
        $query .= $this->quote($table);
        $query .= '(';
        foreach ($data[0] as $data__key => $data__value) {
            $query .= $this->quote($data__key);
            if (array_keys($data[0])[count(array_keys($data[0])) - 1] !== $data__key) {
                $query .= ',';
            }
        }
        $query .= ') ';
        $query .= 'VALUES ';
        foreach ($data as $data__key => $data__value) {
            $query .= '(';
            $query .= str_repeat('?,', count($data__value) - 1) . '?';
            $query .= ')';
            if (array_keys($data)[count(array_keys($data)) - 1] !== $data__key) {
                $query .= ',';
            }
        }
        $args = [];
        $args[] = $query;
        foreach ($data as $data__key => $data__value) {
            foreach ($data__value as $data__value__key => $data__value__value) {
                // because pdo can't insert true/false values so easily, convert them to 1/0
                if ($data__value__value === true) {
                    $data__value__value = 1;
                }
                if ($data__value__value === false) {
                    $data__value__value = 0;
                }
                $args[] = $data__value__value;
            }
        }
        $ret = call_user_func_array([$this, 'query'], $args);

        // mysql returns the last inserted id inside the current session, obviously ignoring triggers
        // on postgres we cannot use LASTVAL(), because it returns the last id of possibly inserted rows caused by triggers
        // see: https://stackoverflow.com/questions/51558021/mysql-postgres-last-insert-id-lastval-different-behaviour
        if ($this->connect->engine === 'mysql') {
            return $this->last_insert_id();
        }
        if ($this->connect->engine === 'postgres') {
            return $this->last_insert_id($table, $this->get_primary_key($table));
        }
        if ($this->connect->engine === 'sqlite') {
            return $this->last_insert_id();
        }
    }

    public function last_insert_id($table = null, $column = null)
    {
        $last_insert_id = null;
        switch ($this->connect->driver) {
            case 'pdo':
                if ($this->connect->engine == 'mysql') {
                    try {
                        $last_insert_id = $this->fetch_var('SELECT LAST_INSERT_ID();');
                    } catch (\Exception $e) {
                        $last_insert_id = null;
                    }
                }
                if ($this->connect->engine == 'postgres') {
                    try {
                        if ($table === null || $column === null) {
                            $last_insert_id = $this->fetch_var('SELECT LASTVAL();');
                        } else {
                            $last_insert_id = $this->fetch_var(
                                "SELECT CURRVAL(pg_get_serial_sequence('" . $table . "','" . $column . "'));"
                            );
                        }
                    } catch (\Exception $e) {
                        $last_insert_id = null;
                    }
                }
                if ($this->connect->engine == 'sqlite') {
                    try {
                        $last_insert_id = $this->fetch_var('SELECT last_insert_rowid();');
                    } catch (\Exception $e) {
                        $last_insert_id = null;
                    }
                }
                break;

            case 'mysqli':
                $last_insert_id = mysqli_insert_id($this->sql);
                break;

            case 'wordpress':
                $last_insert_id = $this->fetch_var('SELECT LAST_INSERT_ID();');
                break;
        }

        return $last_insert_id;
    }

    public function update($table, $data, $condition = null)
    {
        if (isset($data[0]) && is_array($data[0])) {
            return $this->update_batch($table, $data);
        }
        $query = '';
        $query .= 'UPDATE ';
        $query .= $this->quote($table);
        $query .= ' SET ';
        foreach ($data as $key => $value) {
            $query .= $this->quote($key);
            $query .= ' = ';
            $query .= '?';
            end($data);
            if ($key !== key($data)) {
                $query .= ', ';
            }
        }
        $query .= ' WHERE ';
        foreach ($condition as $key => $value) {
            $query .= $this->quote($key);
            $query .= ' = ';
            $query .= '? ';
            end($condition);
            if ($key !== key($condition)) {
                $query .= ' AND ';
            }
        }
        $args = [];
        $args[] = $query;
        foreach ($data as $d) {
            if ($d === true) {
                $d = 1;
            }
            if ($d === false) {
                $d = 0;
            }
            $args[] = $d;
        }
        foreach ($condition as $c) {
            if ($c === true) {
                $c = 1;
            }
            if ($c === false) {
                $c = 0;
            }
            $args[] = $c;
        }
        return call_user_func_array([$this, 'query'], $args); // returns the affected row counts
    }

    public function delete($table, $conditions)
    {
        if (!isset($conditions[0]) && !is_array(array_values($conditions)[0])) {
            $conditions = [$conditions];
        }
        $query = '';
        $query .= 'DELETE FROM ';
        $query .= $this->quote($table);
        $query .= ' WHERE ';
        $query .= '(';
        foreach ($conditions as $conditions__key => $conditions__value) {
            $query .= '(';
            foreach ($conditions__value as $conditions__value__key => $conditions__value__value) {
                $query .= $this->quote($conditions__value__key);
                $query .= ' = ';
                $query .= '?';
                if (
                    array_keys($conditions__value)[count(array_keys($conditions__value)) - 1] !==
                    $conditions__value__key
                ) {
                    $query .= ' AND ';
                }
            }
            $query .= ')';
            if (array_keys($conditions)[count(array_keys($conditions)) - 1] !== $conditions__key) {
                $query .= ' OR ';
            }
        }
        $query .= ')';
        $args = [];
        $args[] = $query;
        foreach ($conditions as $conditions__key => $conditions__value) {
            foreach ($conditions__value as $conditions__value__key => $conditions__value__value) {
                if ($conditions__value__value === true) {
                    $conditions__value__value = 1;
                }
                if ($conditions__value__value === false) {
                    $conditions__value__value = 0;
                }
                $args[] = $conditions__value__value;
            }
        }
        return call_user_func_array([$this, 'query'], $args); // returns the affected row counts
    }

    public function clear($table = null)
    {
        if ($table === null) {
            if ($this->connect->engine === 'mysql') {
                $this->query('SET FOREIGN_KEY_CHECKS = 0');
                $tables = $this->fetch_col(
                    'SELECT table_name FROM information_schema.tables WHERE table_schema = ?',
                    $this->connect->database
                );
                if (!empty($tables)) {
                    foreach ($tables as $tables__value) {
                        $this->query('DROP TABLE ' . $tables__value);
                    }
                }
                $this->query('SET FOREIGN_KEY_CHECKS = 1');
            } elseif ($this->connect->engine === 'postgres') {
                $this->query('DROP SCHEMA public CASCADE');
                $this->query('CREATE SCHEMA public');
            } elseif ($this->connect->engine === 'sqlite') {
                $db_driver = $this->connect->driver;
                $db_engine = $this->connect->engine;
                $db_file = $this->connect->host;
                $this->disconnect();
                unlink($db_file);
                $this->connect($db_driver, $db_engine, $db_file);
            }
        } else {
            if ($this->connect->engine === 'mysql') {
                $this->query('TRUNCATE TABLE ' . $table);
            } elseif ($this->connect->engine === 'postgres') {
                $this->query('TRUNCATE TABLE ' . $table . ' RESTART IDENTITY');
            } elseif ($this->connect->engine === 'sqlite') {
                $this->query('DELETE FROM ' . $table);
                $this->query('VACUUM');
            }
        }
    }

    public function delete_table($table)
    {
        $this->query('DROP TABLE ' . $table);
    }

    public function create_table($table, $cols)
    {
        $query = '';
        $query .= 'CREATE TABLE IF NOT EXISTS ';
        $query .= $table . ' ';
        $query .= '(';
        foreach ($cols as $cols__key => $cols__value) {
            $query .= $cols__key;
            $query .= ' ';
            $query .= $cols__value;
            $query .= ',';
        }
        $query = substr($query, 0, -1);
        $query .= ')';
        $this->query($query);
    }

    public function get_tables()
    {
        if ($this->connect->engine === 'mysql') {
            return $this->fetch_col(
                'SELECT table_name FROM information_schema.tables WHERE table_catalog = ? AND table_schema = ? ORDER BY table_name',
                'def',
                $this->connect->database
            );
        } elseif ($this->connect->engine === 'postgres') {
            return $this->fetch_col(
                'SELECT table_name FROM information_schema.tables WHERE table_catalog = ? AND table_schema = ? ORDER BY table_name',
                $this->connect->database,
                'public'
            );
        } elseif ($this->connect->engine === 'sqlite') {
            return $this->fetch_col('SELECT name FROM sqlite_master WHERE type = ?', 'table');
        }
    }

    public function get_columns($table)
    {
        if ($this->connect->engine === 'mysql') {
            return $this->fetch_col(
                'SELECT column_name FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ? ORDER BY ORDINAL_POSITION',
                'def',
                $this->connect->database,
                $table
            );
        } elseif ($this->connect->engine === 'postgres') {
            return $this->fetch_col(
                'SELECT column_name FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ? ORDER BY ORDINAL_POSITION',
                $this->connect->database,
                'public',
                $table
            );
        } elseif ($this->connect->engine === 'sqlite') {
            $pragma = $this->fetch_all('PRAGMA table_info(' . $this->quote($table) . ');');
            $cols = [];
            foreach ($pragma as $pragma__value) {
                $cols[] = $pragma__value['name'];
            }
            return $cols;
        }
    }

    public function get_foreign_keys($table)
    {
        if ($this->connect->engine === 'mysql') {
            $return = [];
            $cols = $this->fetch_all(
                '
                    SELECT
                        kcu.column_name AS column_name,
                        kcu.referenced_table_name as foreign_table_name,
                        kcu.referenced_column_name as foreign_column_name
                    FROM
                        information_schema.table_constraints AS tc
                        JOIN information_schema.key_column_usage AS kcu
                        ON tc.constraint_name = kcu.constraint_name
                        AND tc.table_schema = kcu.table_schema
                    WHERE
                        tc.constraint_type = ? AND
                        tc.table_schema = ? AND
                        tc.table_name = ?
                ',
                'FOREIGN KEY',
                $this->connect->database,
                $table
            );
            foreach ($cols as $cols__value) {
                $return[$cols__value['column_name']] = [
                    $cols__value['foreign_table_name'],
                    $cols__value['foreign_column_name']
                ];
            }
            return $return;
        } elseif ($this->connect->engine === 'postgres') {
            $return = [];
            $cols = $this->fetch_all(
                '
                    SELECT
                        kcu.column_name AS column_name,
                        ccu.table_name AS foreign_table_name,
                        ccu.column_name AS foreign_column_name
                    FROM
                        information_schema.table_constraints AS tc
                        JOIN information_schema.key_column_usage AS kcu
                        ON tc.constraint_name = kcu.constraint_name
                        AND tc.table_schema = kcu.table_schema
                        JOIN information_schema.constraint_column_usage AS ccu
                        ON ccu.constraint_name = tc.constraint_name
                        AND ccu.table_schema = tc.table_schema
                    WHERE
                        tc.constraint_type = ? AND
                        tc.table_catalog = ? AND
                        tc.table_schema = ? AND
                        tc.table_name = ?
                ',
                'FOREIGN KEY',
                $this->connect->database,
                'public',
                $table
            );
            foreach ($cols as $cols__value) {
                $return[$cols__value['column_name']] = [
                    $cols__value['foreign_table_name'],
                    $cols__value['foreign_column_name']
                ];
            }
            return $return;
        } elseif ($this->connect->engine === 'sqlite') {
            $pragma = $this->fetch_all('PRAGMA foreign_key_list(' . $this->quote($table) . ');');
            $return = [];
            foreach ($pragma as $pragma__value) {
                $return[$pragma__value['from']] = [$pragma__value['table'], $pragma__value['to']];
            }
            return $return;
        }
    }

    public function is_foreign_key($table, $column)
    {
        return array_key_exists($column, $this->get_foreign_keys($table));
    }

    public function get_foreign_tables_out($table)
    {
        $return = [];
        foreach ($this->get_foreign_keys($table) as $foreign_keys__key => $foreign_keys__value) {
            if (!array_key_exists($foreign_keys__value[0], $return)) {
                $return[$foreign_keys__value[0]] = [];
            }
            $return[$foreign_keys__value[0]][] = [$foreign_keys__key, $foreign_keys__value[1]];
        }
        return $return;
    }

    public function get_foreign_tables_in($table)
    {
        $return = [];
        $tables = $this->get_tables();
        foreach ($tables as $tables__value) {
            if ($tables__value === $table) {
                continue;
            }
            foreach ($this->get_foreign_tables_out($tables__value) as $foreign_tables__key => $foreign_tables__value) {
                if ($foreign_tables__key !== $table) {
                    continue;
                }
                if (!array_key_exists($tables__value, $return)) {
                    $return[$tables__value] = [];
                }
                $return[$tables__value] = array_merge($return[$tables__value], $foreign_tables__value);
            }
        }
        return $return;
    }

    public function has_table($table)
    {
        return in_array($table, $this->get_tables());
    }

    public function has_column($table, $column)
    {
        return in_array($column, $this->get_columns($table));
    }

    public function get_datatype($table, $column)
    {
        if ($this->connect->engine === 'mysql') {
            return $this->fetch_var(
                'SELECT data_type FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ? and column_name = ?',
                'def',
                $this->connect->database,
                $table,
                $column
            );
        } elseif ($this->connect->engine === 'postgres') {
            return $this->fetch_var(
                'SELECT data_type FROM information_schema.columns WHERE table_catalog = ? AND table_schema = ? AND table_name = ? and column_name = ?',
                $this->connect->database,
                'public',
                $table,
                $column
            );
        } elseif ($this->connect->engine === 'sqlite') {
            $pragma = $this->fetch_all('PRAGMA table_info(' . $this->quote($table) . ');');
            foreach ($pragma as $pragma__value) {
                if ($pragma__value['name'] === $column) {
                    return $pragma__value['type'];
                }
            }
            return null;
        }
    }

    public function get_primary_key($table)
    {
        try {
            if ($this->connect->engine === 'mysql') {
                return ((object) $this->fetch_row(
                    'SHOW KEYS FROM ' . $this->quote($table) . ' WHERE Key_name = ?',
                    'PRIMARY'
                ))->Column_name;
            }
            if ($this->connect->engine === 'postgres') {
                return $this->fetch_var(
                    'SELECT pg_attribute.attname FROM pg_index JOIN pg_attribute ON pg_attribute.attrelid = pg_index.indrelid AND pg_attribute.attnum = ANY(pg_index.indkey) WHERE pg_index.indrelid = \'' .
                        $table .
                        '\'::regclass AND pg_index.indisprimary'
                );
            }
            if ($this->connect->engine === 'sqlite') {
                $pragma = $this->fetch_all('PRAGMA table_info(' . $this->quote($table) . ');');
                foreach ($pragma as $pragma__value) {
                    if ($pragma__value['pk'] == 1) {
                        return $pragma__value['name'];
                    }
                }
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    public function count($table, $condition = [])
    {
        $query = '';
        $query .= 'SELECT COUNT(*) FROM ';
        $query .= $this->quote($table);
        if (!empty($condition)) {
            $query .= ' WHERE ';
            foreach ($condition as $key => $value) {
                $query .= $this->quote($key);
                $query .= ' = ';
                $query .= '? ';
                end($condition);
                if ($key !== key($condition)) {
                    $query .= ' AND ';
                }
            }
        }
        $args = [];
        if (!empty($condition)) {
            foreach ($condition as $c) {
                if ($c === true) {
                    $c = 1;
                }
                if ($c === false) {
                    $c = 0;
                }
                $args[] = $c;
            }
        }
        $ret = $this->fetch_var($query, $args);
        if (is_numeric($ret)) {
            $ret = intval($ret);
        }
        return $ret;
    }

    public function trim_values($update = false, $ignore = [])
    {
        if (!empty($ignore)) {
            $ignore_prev = $ignore;
            foreach ($ignore_prev as $ignore_prev__key => $ignore_prev__value) {
                if (is_string($ignore_prev__value)) {
                    $ignore[$ignore_prev__value] = null;
                } elseif (is_array($ignore_prev__value)) {
                    $ignore[$ignore_prev__key] = $ignore_prev__value;
                }
            }
        }
        $return = [];
        foreach ($this->get_tables() as $tables__value) {
            $query = '';
            $query .= 'SELECT * FROM ' . $tables__value . ' WHERE ';
            $query_or = [];
            foreach ($this->get_columns($tables__value) as $columns__value) {
                if ($this->connect->engine === 'sqlite') {
                    $query_or[] =
                        'CAST(' .
                        $this->quote($columns__value) .
                        ' AS TEXT) LIKE \' %\' OR CAST(' .
                        $this->quote($columns__value) .
                        ' AS TEXT) LIKE \'% \'';
                } else {
                    $query_or[] =
                        'CONCAT(' .
                        $this->quote($columns__value) .
                        ',\'\') LIKE \' %\' OR CONCAT(' .
                        $this->quote($columns__value) .
                        ',\'\') LIKE \'% \'';
                }
            }
            $query .= implode(' OR ', $query_or);
            $result = $this->fetch_all($query);
            if (!empty($result)) {
                foreach ($result as $result__value) {
                    $id = $result__value[$this->get_primary_key($tables__value)];
                    foreach ($result__value as $result__value__key => $result__value__value) {
                        if (
                            !preg_match('/^ .+$/', $result__value__value) &&
                            !preg_match('/^.+ $/', $result__value__value)
                        ) {
                            continue;
                        }
                        if (
                            !empty($ignore) &&
                            array_key_exists($tables__value, $ignore) &&
                            ($ignore[$tables__value] === null ||
                                (is_array($ignore[$tables__value]) &&
                                    in_array($result__value__key, $ignore[$tables__value])))
                        ) {
                            continue;
                        }
                        $return[] = [
                            'table' => $tables__value,
                            'column' => $result__value__key,
                            'id' => $id,
                            'before' => $result__value__value,
                            'after' => trim($result__value__value)
                        ];
                        if ($update === true) {
                            $this->update(
                                $tables__value,
                                [$result__value__key => trim($result__value__value)],
                                [
                                    $this->get_primary_key($tables__value) => $id,
                                    $result__value__key => $result__value__value
                                ]
                            );
                        }
                    }
                }
            }
        }
        usort($return, function ($a, $b) {
            return strcmp(
                $a['table'] . '.' . $a['column'] . '.' . $a['before'],
                $b['table'] . '.' . $b['column'] . '.' . $a['before']
            );
        });
        return $return;
    }

    public function get_duplicates()
    {
        $duplicates_data = [];
        $duplicates_count = [];
        $tables = $this->get_tables();
        foreach ($tables as $tables__value) {
            $primaryKey = $this->get_primary_key($tables__value);
            if ($primaryKey == '') {
                continue;
            }
            $columns = [];
            foreach ($this->get_columns($tables__value) as $columns__value) {
                if ($columns__value === $primaryKey) {
                    continue;
                }
                $columns[] = $this->quote($columns__value);
            }
            $duplicates_this = $this->fetch_all(
                'SELECT ' .
                    implode(', ', $columns) .
                    ', MIN(' .
                    $this->quote($primaryKey) .
                    ') as ' .
                    $this->quote('MIN()') .
                    ', COUNT(*) as ' .
                    $this->quote('COUNT()') .
                    ' FROM ' .
                    $tables__value .
                    ' GROUP BY ' .
                    implode(', ', $columns) .
                    ' HAVING COUNT(*) > 1'
            );
            if (!empty($duplicates_this)) {
                $duplicates_data[$tables__value] = $duplicates_this;
                $duplicates_count[$tables__value] = 0;
                foreach ($duplicates_this as $duplicates_this__value) {
                    $duplicates_count[$tables__value] += $duplicates_this__value['COUNT()'];
                }
            }
        }
        return ['count' => $duplicates_count, 'data' => $duplicates_data];
    }

    public function delete_duplicates(
        $table,
        $cols = [],
        $match_null_values = true,
        $primary = [],
        $case_sensitivity = true
    ) {
        if (empty($primary)) {
            $primary = [$this->get_primary_key($table) => 'desc'];
        }
        $primary_key = array_keys($primary)[0];
        $primary_order = $primary[$primary_key];

        if (empty($cols)) {
            $cols = $this->get_columns($table);
            $cols = array_filter($cols, function ($cols__value) use ($primary_key) {
                return $cols__value !== $primary_key;
            });
        }

        $ret = 1;
        while ($ret > 0) {
            $query = '';
            $query .= 'DELETE FROM ' . $this->quote($table) . ' ';
            $query .= 'WHERE ' . $this->quote($primary_key) . ' IN (';
            $query .= 'SELECT * FROM (';
            $query .=
                'SELECT ' .
                ($primary_order === 'desc' ? 'MIN' : 'MAX') .
                '(' .
                $this->quote($primary_key) .
                ') FROM ' .
                $this->quote($table) .
                ' GROUP BY ';
            $query .= implode(
                ', ',
                array_map(function ($cols__value) use ($match_null_values, $primary_key, $case_sensitivity) {
                    $ret = '';
                    if ($match_null_values === false) {
                        $ret .= 'COALESCE(CAST(';
                    }
                    // postgres and sqlite do a case sensitive group by by default
                    // on mysql we need the following modification
                    if ($this->connect->engine === 'mysql') {
                        // variant 1: Cast as binary (we use md5, because its neater)
                        //$ret .= 'CAST(';
                        // variant 2: MD5 trick
                        $ret .= 'MD5(';
                    }
                    if ($case_sensitivity === false) {
                        $ret .= 'LOWER(';
                    }
                    $ret .= $this->quote($cols__value);
                    if ($case_sensitivity === false) {
                        $ret .= ')';
                    }
                    if ($this->connect->engine === 'mysql') {
                        //$ret .= ' AS BINARY)';
                        $ret .= ')';
                    }
                    if ($match_null_values === false) {
                        $ret .= ' AS CHAR), CAST(' . $this->quote($primary_key) . ' AS CHAR))';
                    }
                    return $ret;
                }, $cols)
            );
            $query .= 'HAVING COUNT(*) > 1';
            $query .= ') as tmp';
            $query .= ')';
            $ret = $this->query($query);
        }

        // the approach has massive performance issues not work on some mariadb dbs
        /*
        $query = '';
        $query .= 'DELETE FROM ' . $this->quote($table) . ' ';
        $query .= 'WHERE ' . $this->quote($primary_key) . ' NOT IN (';
        $query .= 'SELECT * FROM (';
        $query .=
            'SELECT ' .
            ($primary_order === 'desc' ? 'MAX' : 'MIN') .
            '(' .
            $this->quote($primary_key) .
            ') FROM ' .
            $this->quote($table) .
            ' GROUP BY ';
        $query .= implode(
            ', ',
            array_map(function ($cols__value) use ($match_null_values, $primary_key, $case_sensitivity) {
                $ret = '';
                if ($match_null_values === false) {
                    $ret .= 'COALESCE(CAST(';
                }
                // postgres and sqlite do a case sensitive group by by default
                // on mysql we need the following modification
                if ($this->connect->engine === 'mysql') {
                    // variant 1: Cast as binary (we use md5, because its neater)
                    //$ret .= 'CAST(';
                    // variant 2: MD5 trick
                    $ret .= 'MD5(';
                }
                if ($case_sensitivity === false) {
                    $ret .= 'LOWER(';
                }
                $ret .= $this->quote($cols__value);
                if ($case_sensitivity === false) {
                    $ret .= ')';
                }
                if ($this->connect->engine === 'mysql') {
                    //$ret .= ' AS BINARY)';
                    $ret .= ')';
                }
                if ($match_null_values === false) {
                    $ret .= ' AS CHAR), CAST(' . $this->quote($primary_key) . ' AS CHAR))';
                }
                return $ret;
            }, $cols)
        );
        $query .= ') as tmp';
        $query .= ')';
        */
    }

    public function enable_auto_inject()
    {
        $this->config['auto_inject'] = true;
    }

    public function setup_logging()
    {
        if ($this->connect->engine === 'mysql') {
            $this->setup_logging_create_table_mysql();
            $this->setup_logging_add_column();
            $this->setup_logging_create_triggers_mysql();
        }
        if ($this->connect->engine === 'postgres') {
            $this->setup_logging_create_table_postgres();
            $this->setup_logging_add_column();
            $this->setup_logging_create_triggers_postgres();
        }

        $this->setup_logging_delete_older();
    }

    private function setup_logging_delete_older()
    {
        if (isset($this->config['delete_older']) && is_numeric($this->config['delete_older'])) {
            $this->query(
                'DELETE FROM ' . $this->config['logging_table'] . ' WHERE updated_at < ?',
                date('Y-m-d', strtotime('now - ' . $this->config['delete_older'] . ' months'))
            );
        }
    }

    private function setup_logging_create_table_mysql()
    {
        $this->query(
            '
            CREATE TABLE IF NOT EXISTS ' .
                $this->config['logging_table'] .
                ' (
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
        '
        );
    }

    private function setup_logging_create_table_postgres()
    {
        $this->query(
            '
            CREATE TABLE IF NOT EXISTS ' .
                $this->config['logging_table'] .
                ' (
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
        '
        );
        $this->query('
            CREATE OR REPLACE FUNCTION auto_update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = now();
                RETURN NEW;
            END;
            $$ language \'plpgsql\';
        ');
        $this->query(
            'DROP TRIGGER IF EXISTS ' .
                $this->quote('auto_update_updated_at_column_on_insert') .
                ' ON ' .
                $this->config['logging_table']
        );
        $this->query(
            'DROP TRIGGER IF EXISTS ' .
                $this->quote('auto_update_updated_at_column_on_update') .
                ' ON ' .
                $this->config['logging_table']
        );
        $this->query(
            'CREATE TRIGGER auto_update_updated_at_column_on_insert BEFORE INSERT ON ' .
                $this->config['logging_table'] .
                ' FOR EACH ROW EXECUTE PROCEDURE auto_update_updated_at_column();'
        );
        $this->query(
            'CREATE TRIGGER auto_update_updated_at_column_on_update BEFORE UPDATE ON ' .
                $this->config['logging_table'] .
                ' FOR EACH ROW EXECUTE PROCEDURE auto_update_updated_at_column();'
        );
    }

    private function setup_logging_add_column()
    {
        foreach ($this->get_tables() as $table__value) {
            if (
                isset($this->config['exclude']) &&
                isset($this->config['exclude']['tables']) &&
                in_array($table__value, $this->config['exclude']['tables'])
            ) {
                continue;
            }
            if ($table__value === $this->config['logging_table']) {
                continue;
            }
            if (!$this->has_column($table__value, 'updated_by')) {
                $this->query('ALTER TABLE ' . $table__value . ' ADD COLUMN updated_by varchar(50)');
            }
        }
    }

    public function disable_logging()
    {
        foreach ($this->get_tables() as $table__value) {
            if ($this->connect->engine === 'mysql') {
                $this->query('DROP TRIGGER IF EXISTS ' . $this->quote('trigger-logging-insert-' . $table__value));
                $this->query('DROP TRIGGER IF EXISTS ' . $this->quote('trigger-logging-update-' . $table__value));
                $this->query('DROP TRIGGER IF EXISTS ' . $this->quote('trigger-logging-delete-' . $table__value));
            }
            if ($this->connect->engine === 'postgres') {
                $this->query(
                    'DROP TRIGGER IF EXISTS ' .
                        $this->quote('trigger-logging-insert-' . $table__value) .
                        ' ON ' .
                        $this->quote($table__value)
                );
                $this->query(
                    'DROP TRIGGER IF EXISTS ' .
                        $this->quote('trigger-logging-update-' . $table__value) .
                        ' ON ' .
                        $this->quote($table__value)
                );
                $this->query(
                    'DROP TRIGGER IF EXISTS ' .
                        $this->quote('trigger-logging-delete-' . $table__value) .
                        ' ON ' .
                        $this->quote($table__value)
                );
            }
        }
    }

    public function enable_logging()
    {
        if ($this->connect->engine === 'mysql') {
            $this->setup_logging_create_triggers_mysql();
        }
        if ($this->connect->engine === 'postgres') {
            $this->setup_logging_create_triggers_postgres();
        }
    }

    private function setup_logging_create_triggers_mysql()
    {
        foreach ($this->get_tables() as $table__value) {
            $this->query('DROP TRIGGER IF EXISTS ' . $this->quote('trigger-logging-insert-' . $table__value));
            $this->query('DROP TRIGGER IF EXISTS ' . $this->quote('trigger-logging-update-' . $table__value));
            $this->query('DROP TRIGGER IF EXISTS ' . $this->quote('trigger-logging-delete-' . $table__value));

            if (
                isset($this->config['exclude']) &&
                isset($this->config['exclude']['tables']) &&
                in_array($table__value, $this->config['exclude']['tables'])
            ) {
                continue;
            }
            if ($table__value === $this->config['logging_table']) {
                continue;
            }

            $primary_key = $this->get_primary_key($table__value);

            /* note: we do not use DELIMITER $$ here, because php in mysql can handle that anyways because it does not execute multiple queries */

            $query =
                '
                CREATE TRIGGER ' .
                $this->quote('trigger-logging-insert-' . $table__value) .
                '
                AFTER INSERT ON ' .
                $table__value .
                ' FOR EACH ROW
                BEGIN
                    DECLARE uuid TEXT;
                    SET @uuid := ' .
                $this->uuid_query() .
                ';
                    ' .
                array_reduce($this->get_columns($table__value), function ($carry, $column) use (
                    $table__value,
                    $primary_key
                ) {
                    if (
                        $column === $primary_key ||
                        $column === 'updated_by' ||
                        $column === 'created_by' ||
                        $column === 'created_at' ||
                        $column === 'updated_at' ||
                        (isset($this->config['exclude']) &&
                            isset($this->config['exclude']['columns']) &&
                            isset($this->config['exclude']['columns'][$table__value]) &&
                            in_array($column, $this->config['exclude']['columns'][$table__value]))
                    ) {
                        return $carry;
                    }

                    $carry .=
                        '
                            INSERT INTO ' .
                        $this->config['logging_table'] .
                        '(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                            VALUES(\'insert\', \'' .
                        $table__value .
                        '\', NEW.' .
                        $this->quote($primary_key) .
                        ', \'' .
                        $column .
                        '\', NEW.' .
                        $this->quote($column) .
                        ', @uuid, NEW.updated_by);
                        ';
                    return $carry;
                }) .
                '
                END
            ';

            $this->query($query);

            $query =
                '
                CREATE TRIGGER ' .
                $this->quote('trigger-logging-update-' . $table__value) .
                '
                AFTER UPDATE ON ' .
                $table__value .
                ' FOR EACH ROW
                BEGIN
                    DECLARE uuid TEXT;
                    SET @uuid := ' .
                $this->uuid_query() .
                ';
                    ' .
                array_reduce($this->get_columns($table__value), function ($carry, $column) use (
                    $table__value,
                    $primary_key
                ) {
                    if (
                        $column === $primary_key ||
                        $column === 'updated_by' ||
                        $column === 'created_by' ||
                        $column === 'created_at' ||
                        $column === 'updated_at' ||
                        (isset($this->config['exclude']) &&
                            isset($this->config['exclude']['columns']) &&
                            isset($this->config['exclude']['columns'][$table__value]) &&
                            in_array($column, $this->config['exclude']['columns'][$table__value]))
                    ) {
                        return $carry;
                    }
                    $carry .=
                        '
                            IF (OLD.' .
                        $this->quote($column) .
                        ' <> NEW.' .
                        $this->quote($column) .
                        ') OR (OLD.' .
                        $this->quote($column) .
                        ' IS NULL AND NEW.' .
                        $this->quote($column) .
                        ' IS NOT NULL) OR (OLD.' .
                        $this->quote($column) .
                        ' IS NOT NULL AND NEW.' .
                        $this->quote($column) .
                        ' IS NULL) THEN
                                INSERT INTO ' .
                        $this->config['logging_table'] .
                        '(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                                VALUES(\'update\', \'' .
                        $table__value .
                        '\', NEW.' .
                        $this->quote($primary_key) .
                        ', \'' .
                        $column .
                        '\', NEW.' .
                        $this->quote($column) .
                        ', @uuid, NEW.updated_by);
                            END IF;
                        ';
                    return $carry;
                }) .
                '
                END
            ';

            $this->query($query);

            $query =
                '
                CREATE TRIGGER ' .
                $this->quote('trigger-logging-delete-' . $table__value) .
                '
                AFTER DELETE ON ' .
                $table__value .
                ' FOR EACH ROW
                BEGIN
                    DECLARE uuid TEXT;
                    SET @uuid := ' .
                $this->uuid_query() .
                ';
                    IF( NOT EXISTS( SELECT * FROM ' .
                $this->config['logging_table'] .
                ' WHERE log_event = \'delete\' AND log_table = \'' .
                $table__value .
                '\' AND log_key = OLD.' .
                $this->quote($primary_key) .
                ' ) ) THEN
                        INSERT INTO ' .
                $this->config['logging_table'] .
                '(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                        VALUES(\'delete\', \'' .
                $table__value .
                '\', OLD.' .
                $this->quote($primary_key) .
                ', NULL, NULL, @uuid, OLD.updated_by);
                    END IF;
                END
            ';

            $this->query($query);
        }
    }

    private function setup_logging_create_triggers_postgres()
    {
        foreach ($this->get_tables() as $table__value) {
            $this->query(
                'DROP TRIGGER IF EXISTS ' .
                    $this->quote('trigger-logging-insert-' . $table__value) .
                    ' ON ' .
                    $this->quote($table__value)
            );
            $this->query(
                'DROP TRIGGER IF EXISTS ' .
                    $this->quote('trigger-logging-update-' . $table__value) .
                    ' ON ' .
                    $this->quote($table__value)
            );
            $this->query(
                'DROP TRIGGER IF EXISTS ' .
                    $this->quote('trigger-logging-delete-' . $table__value) .
                    ' ON ' .
                    $this->quote($table__value)
            );

            if (
                isset($this->config['exclude']) &&
                isset($this->config['exclude']['tables']) &&
                in_array($table__value, $this->config['exclude']['tables'])
            ) {
                continue;
            }
            if ($table__value === $this->config['logging_table']) {
                continue;
            }

            $primary_key = $this->get_primary_key($table__value);

            $query =
                '
                CREATE OR REPLACE FUNCTION trigger_logging_insert_' .
                $table__value .
                '()
                RETURNS TRIGGER AS $$
                DECLARE
                    uuid TEXT;
                BEGIN
                    uuid := ' .
                $this->uuid_query() .
                ';
                    ' .
                array_reduce($this->get_columns($table__value), function ($carry, $column) use (
                    $table__value,
                    $primary_key
                ) {
                    if (
                        $column === $primary_key ||
                        $column === 'updated_by' ||
                        $column === 'created_by' ||
                        $column === 'created_at' ||
                        $column === 'updated_at' ||
                        (isset($this->config['exclude']) &&
                            isset($this->config['exclude']['columns']) &&
                            isset($this->config['exclude']['columns'][$table__value]) &&
                            in_array($column, $this->config['exclude']['columns'][$table__value]))
                    ) {
                        return $carry;
                    }

                    $carry .=
                        '
                            INSERT INTO ' .
                        $this->config['logging_table'] .
                        '(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                            VALUES(\'insert\', \'' .
                        $table__value .
                        '\', NEW.' .
                        $this->quote($primary_key) .
                        '::text, \'' .
                        $column .
                        '\', NEW.' .
                        $this->quote($column) .
                        '::text, uuid, NEW.updated_by);
                        ';
                    return $carry;
                }) .
                '
                    RETURN NULL;
                END;
                $$ language \'plpgsql\';
            ';
            $this->query($query);
            $query =
                '
                CREATE TRIGGER ' .
                $this->quote('trigger-logging-insert-' . $table__value) .
                '
                AFTER INSERT ON ' .
                $table__value .
                ' FOR EACH ROW
                EXECUTE PROCEDURE trigger_logging_insert_' .
                $table__value .
                '();
            ';
            $this->query($query);

            $query =
                '
                CREATE OR REPLACE FUNCTION trigger_logging_update_' .
                $table__value .
                '()
                RETURNS TRIGGER AS $$
                DECLARE
                    uuid TEXT;
                BEGIN
                    uuid := ' .
                $this->uuid_query() .
                ';
                    ' .
                array_reduce($this->get_columns($table__value), function ($carry, $column) use (
                    $table__value,
                    $primary_key
                ) {
                    if (
                        $column === $primary_key ||
                        $column === 'updated_by' ||
                        $column === 'created_by' ||
                        $column === 'created_at' ||
                        $column === 'updated_at' ||
                        (isset($this->config['exclude']) &&
                            isset($this->config['exclude']['columns']) &&
                            isset($this->config['exclude']['columns'][$table__value]) &&
                            in_array($column, $this->config['exclude']['columns'][$table__value]))
                    ) {
                        return $carry;
                    }
                    $carry .=
                        '
                            IF (OLD.' .
                        $this->quote($column) .
                        ' <> NEW.' .
                        $this->quote($column) .
                        ') OR (OLD.' .
                        $this->quote($column) .
                        ' IS NULL AND NEW.' .
                        $this->quote($column) .
                        ' IS NOT NULL) OR (OLD.' .
                        $this->quote($column) .
                        ' IS NOT NULL AND NEW.' .
                        $this->quote($column) .
                        ' IS NULL) THEN
                                INSERT INTO ' .
                        $this->config['logging_table'] .
                        '(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                                VALUES(\'update\', \'' .
                        $table__value .
                        '\', NEW.' .
                        $this->quote($primary_key) .
                        '::text, \'' .
                        $column .
                        '\', NEW.' .
                        $this->quote($column) .
                        '::text, uuid, NEW.updated_by);
                            END IF;
                        ';
                    return $carry;
                }) .
                '
                    RETURN NULL;
                END;
                $$ language \'plpgsql\';
            ';
            $this->query($query);
            $query =
                '
                CREATE TRIGGER ' .
                $this->quote('trigger-logging-update-' . $table__value) .
                '
                AFTER UPDATE ON ' .
                $table__value .
                ' FOR EACH ROW
                EXECUTE PROCEDURE trigger_logging_update_' .
                $table__value .
                '();
            ';
            $this->query($query);

            $query =
                '
                CREATE OR REPLACE FUNCTION trigger_logging_delete_' .
                $table__value .
                '()
                RETURNS TRIGGER AS $$
                DECLARE
                    uuid TEXT;
                BEGIN
                    uuid := ' .
                $this->uuid_query() .
                ';
                    IF( NOT EXISTS( SELECT * FROM ' .
                $this->config['logging_table'] .
                ' WHERE log_event = \'delete\' AND log_table = \'' .
                $table__value .
                '\' AND log_key = OLD.' .
                $this->quote($primary_key) .
                '::text ) ) THEN
                        INSERT INTO ' .
                $this->config['logging_table'] .
                '(log_event,log_table,log_key,log_column,log_value,log_uuid,updated_by)
                        VALUES(\'delete\', \'' .
                $table__value .
                '\', OLD.' .
                $this->quote($primary_key) .
                '::text, NULL, NULL, uuid, OLD.updated_by);
                    END IF;
                    RETURN NULL;
                END;
                $$ language \'plpgsql\';
            ';
            $this->query($query);
            $query =
                '
                CREATE TRIGGER ' .
                $this->quote('trigger-logging-delete-' . $table__value) .
                '
                AFTER DELETE ON ' .
                $table__value .
                ' FOR EACH ROW
                EXECUTE PROCEDURE trigger_logging_delete_' .
                $table__value .
                '();
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
        if (strpos($query, 'IN (') !== false || strpos($query, 'IN(') !== false) {
            if (!empty($params)) {
                $in_index = 0;
                foreach ($params as $params__key => $params__value) {
                    if (
                        is_array($params__value) &&
                        count($params__value) > 0 &&
                        ((count($params) === 1 && substr_count($query, '?') === 1) || count($params) >= 2)
                    ) {
                        $in_occurence = $this->find_nth_occurence($return, '?', $in_index);
                        if (substr($return, $in_occurence - 1, 3) == '(?)') {
                            $return =
                                substr($return, 0, $in_occurence - 1) .
                                '(' .
                                (str_repeat('?,', count($params__value) - 1) . '?') .
                                ')' .
                                substr($return, $in_occurence + 2);
                        }
                        foreach ($params__value as $params__value__value) {
                            $in_index++;
                        }
                    } else {
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
        if (!empty($params)) {
            $params_flattened = [];
            array_walk_recursive($params, function ($a) use (&$params_flattened) {
                $params_flattened[] = $a;
            });
            $params = $params_flattened;
        }

        // try to sort out bad queries
        foreach ($params as $params__key => $params__value) {
            if (is_object($params__value)) {
                throw new \Exception('object in query');
            }
        }

        // NULL values are treated specially: modify the query
        $pos = 0;
        $delete_keys = [];
        foreach ($params as $params__key => $params__value) {
            // no more ?s are left
            if (($pos = strpos($return, '?', $pos + 1)) === false) {
                break;
            }

            // if param is not null, nothing must be done
            if (!is_null($params__value)) {
                continue;
            }

            // case 1: if query contains WHERE before ?, then convert != ? to IS NOT NULL and = ? to IS NULL
            if (strpos(substr($return, 0, $pos), 'WHERE') !== false) {
                if (strpos(substr($return, $pos - 5, 6), '<> ?') !== false) {
                    $return =
                        substr($return, 0, $pos - 5) .
                        preg_replace('/<> \?/', 'IS NOT NULL', substr($return, $pos - 5), 1);
                } elseif (strpos(substr($return, $pos - 5, 6), '!= ?') !== false) {
                    $return =
                        substr($return, 0, $pos - 5) .
                        preg_replace('/\!= \?/', 'IS NOT NULL', substr($return, $pos - 5), 1);
                } elseif (strpos(substr($return, $pos - 5, 6), '= ?') !== false) {
                    $return =
                        substr($return, 0, $pos - 5) . preg_replace('/= \?/', 'IS NULL', substr($return, $pos - 5), 1);
                }
            }
            // case 2: in all other cases, convert ? to NULL
            else {
                $return = substr($return, 0, $pos) . 'NULL' . substr($return, $pos + 1);
            }

            // delete param
            $delete_keys[] = $params__key;
        }
        if (!empty($delete_keys)) {
            foreach ($delete_keys as $delete_keys__value) {
                unset($params[$delete_keys__value]);
            }
        }
        $params = array_values($params);

        // WordPress: replace ? with %s
        if ($this->connect->driver == 'wordpress') {
            foreach ($params as $params__key => $params__value) {
                // replace next occurence
                if (strpos($return, '?') !== false) {
                    $directive = '%s';
                    if (
                        (is_int($params__value) || ctype_digit((string) $params__value)) &&
                        // prevent strings like "00001" to be catched as integers
                        ((strlen($params__value) === 1 && $params__value == '0') || strpos($params__value, '0') !== 0)
                    ) {
                        $directive = '%d';
                    } elseif (is_float($params__value)) {
                        $directive = '%f';
                    }
                    $return = substr_replace($return, $directive, strpos($return, '?'), strlen('?'));
                }
            }
        }

        // WordPress: pass stripslashes_deep to all parameters (wordpress always adds slashes to them)
        if ($this->connect->driver == 'wordpress') {
            $params = stripslashes_deep($params);
        }

        // trim final result
        $return = trim($return);

        return [$return, $params];
    }

    public function debug($query)
    {
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        [$query, $params] = $this->preparse_query($query, $params);

        $keys = [];
        $values = $params;
        foreach ($params as $key => $value) {
            // check if named parameters (':param') or anonymous parameters ('?') are used
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
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
        return $query;
    }

    private function find_occurences($haystack, $needle)
    {
        $positions = [];
        $pos_last = 0;
        while (($pos_last = strpos($haystack, $needle, $pos_last)) !== false) {
            $positions[] = $pos_last;
            $pos_last = $pos_last + strlen($needle);
        }
        return $positions;
    }

    private function find_nth_occurence($haystack, $needle, $index)
    {
        $positions = $this->find_occurences($haystack, $needle);
        if (empty($positions) || $index > count($positions) - 1) {
            return null;
        }
        return $positions[$index];
    }

    private function update_batch($table, $input)
    {
        $query = '';
        $args = [];
        $query = 'UPDATE ' . $table . ' SET' . PHP_EOL;
        foreach ($input[0][0] as $col__key => $col__value) {
            $query .= $col__key . ' = CASE' . PHP_EOL;
            foreach ($input as $input__key => $input__value) {
                $query .= 'WHEN (';
                $where = [];
                foreach ($input__value[1] as $where__key => $where__value) {
                    $where[] = $where__key . ' = ?';
                    $args[] = $where__value;
                }
                $query .= implode(' AND ', $where) . ')' . ' THEN ?' . PHP_EOL;
                $args[] = $input__value[0][$col__key];
            }
            $query .= 'END';
            if (array_keys($input[0][0])[count(array_keys($input[0][0])) - 1] !== $col__key) {
                $query .= ',';
            }
            $query .= PHP_EOL;
        }
        $query .= 'WHERE ';
        $where = [];
        foreach ($input[0][1] as $where__key => $where__value) {
            $where_values = [];
            foreach ($input as $input__key => $input__value) {
                $where_values[] = $input__value[1][$where__key];
            }
            $where_values = array_unique($where_values);
            $where[] = $where__key . ' IN (' . str_repeat('?,', count($where_values) - 1) . '?)';
            $args = array_merge($args, $where_values);
        }
        $query .= implode(' AND ', $where) . ';';
        array_unshift($args, $query);
        return call_user_func_array([$this, 'query'], $args);
    }

    private function handle_logging($query, $params)
    {
        $table = $this->get_table_name_from_query($query);

        if (
            isset($this->config['exclude']) &&
            isset($this->config['exclude']['tables']) &&
            in_array($table, $this->config['exclude']['tables'])
        ) {
            return $query;
        }
        if ($table === $this->config['logging_table']) {
            return $query;
        }

        if (stripos($query, 'INSERT') === 0) {
            $pos1 = strpos($query, ')');
            $pos2 = strrpos($query, ')');
            $pos3 = stripos($query, 'INTO') + strlen('INTO');
            $pos4 = strpos($query, '(');
            if (
                $pos1 === false ||
                $pos2 === false ||
                $pos2 != strlen($query) - 1 ||
                strpos(substr($query, 0, $pos1), 'updated_by') !== false
            ) {
                return $query;
            }
            $query =
                substr($query, 0, $pos1) .
                ',updated_by' .
                substr($query, $pos1, $pos2 - $pos1) .
                ',\'' .
                $this->config['updated_by'] .
                '\'' .
                substr($query, $pos2);
        } elseif (stripos($query, 'UPDATE') === 0) {
            $pos1 = stripos($query, 'SET') + strlen('SET');
            if ($pos1 !== false && strpos(substr($query, $pos1), 'updated_by') === false) {
                $query =
                    substr($query, 0, $pos1) .
                    ' updated_by = \'' .
                    $this->config['updated_by'] .
                    '\', ' .
                    substr($query, $pos1);
            }
        } elseif (stripos($query, 'DELETE') === 0) {
            // fetch all ids that are affected
            $ids = $this->fetch_col(
                'SELECT ' . $this->get_primary_key($table) . ' ' . substr($query, stripos($query, 'FROM')),
                $params
            );
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $this->insert('logs', [
                        'log_event' => 'delete',
                        'log_table' => $table,
                        'log_key' => $id,
                        'log_uuid' => $this->uuid(),
                        'updated_by' => $this->config['updated_by']
                    ]);
                }
            }
        }

        return $query;
    }

    private function get_table_name_from_query($query)
    {
        $table = '';

        if (stripos($query, 'INSERT') === 0) {
            $pos1 = stripos($query, 'INTO') + strlen('INTO');
            $pos2 = strpos($query, '(');
            $table = substr($query, $pos1, $pos2 - $pos1);
        } elseif (stripos($query, 'UPDATE') === 0) {
            $pos1 = stripos($query, 'UPDATE') + strlen('UPDATE');
            $pos2 = stripos($query, 'SET');
            $table = substr($query, $pos1, $pos2 - $pos1);
        } elseif (stripos($query, 'DELETE') === 0) {
            $pos1 = stripos($query, 'FROM') + strlen('FROM');
            $pos2 = stripos($query, 'WHERE');
            if ($pos2 === false) {
                $pos2 = strlen($query);
            }
            $table = substr($query, $pos1, $pos2 - $pos1);
        }

        $table = str_replace('`', '', $table);
        $table = str_replace('"', '', $table);
        $table = trim($table);

        return $table;
    }

    public function quote($name)
    {
        if ($this->connect->engine === 'mysql') {
            return '`' . $name . '`';
        }
        if ($this->connect->engine === 'postgres') {
            return '"' . $name . '"';
        }
        if ($this->connect->engine === 'sqlite') {
            return '"' . $name . '"';
        }
    }

    public function uuid()
    {
        return $this->fetch_var('SELECT ' . $this->uuid_query());
    }

    private function uuid_query()
    {
        if ($this->connect->engine === 'mysql') {
            return 'UUID()';
        }
        if ($this->connect->engine === 'postgres') {
            return 'uuid_in(md5(random()::text || now()::text)::cstring)';
        }
        if ($this->connect->engine === 'sqlite') {
            return "substr(u,1,8)||'-'||substr(u,9,4)||'-4'||substr(u,13,3)||'-'||v||substr(u,17,3)||'-'||substr(u,21,12) from (select lower(hex(randomblob(16))) as u, substr('89ab',abs(random()) % 4 + 1, 1) as v)";
        }
    }
}
