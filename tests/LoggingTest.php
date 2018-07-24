<?php
use vielhuber\dbhelper\dbhelper;

class LoggingTest extends \PHPUnit\Framework\TestCase
{

    protected $db;

    protected function setUp()
    {
        $this->db = new dbhelper([
            'enable_logging' => true,
            'logging_table' => 'logs',
            'exclude' => [
                'tables' => ['test2'],
                'columns' => ['test' => ['col5']]
            ],
            'delete_older' => 12,
            'updated_by' => 42
        ]);
        $this->db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'dbhelper', 3306);
        $this->db->clear('dbhelper');
        $this->db->query('
            CREATE TABLE IF NOT EXISTS test
            (
              id int(255) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              col1 varchar(255),
              col2 varchar(255),
              col3 varchar(255),
              col4 blob,
              col5 varchar(255)
            )
        ');
        $this->db->query('
            CREATE TABLE IF NOT EXISTS test2
            (
              id int(255) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              col1 varchar(255),
              col2 varchar(255),
              col3 varchar(255),
              col4 blob,
              col5 varchar(255)
            )
        ');
        $this->db->setup_logging();
        $this->db->enable_auto_inject();
    }

    protected function tearDown()
    {
        $this->db->clear('dbhelper');
        $this->db->disconnect();
    }

    function test__insert()
    {
        $id = $this->db->insert('test', ['col1' => 'foo1']);
        $this->assertEquals($this->db->fetch_row('SELECT * FROM test WHERE id = ?', $id), ['id' => $id, 'col1' => 'foo1', 'col2' => null, 'col3' => null, 'col4' => null, 'col5' => null, 'updated_by' => 42]);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'insert', 'log_table' => 'test', 'log_column' => 'col3', 'log_value' => null, 'updated_by' => 42]);

        $id = $this->db->insert('test', ['col1' => 'foo2', 'updated_by' => 43]);
        $this->assertEquals($this->db->fetch_row('SELECT * FROM test WHERE id = ?', $id), ['id' => $id, 'col1' => 'foo2', 'col2' => null, 'col3' => null, 'col4' => null, 'col5' => null, 'updated_by' => 43]);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'insert', 'log_table' => 'test', 'log_column' => 'col3', 'log_value' => null, 'updated_by' => 43]);

        $this->db->query('INSERT INTO test(col1, col2, col3) VALUES(?,?,?)', ['foo3','foo3','foo3']);
        $this->assertEquals($this->db->fetch_row('SELECT * FROM test ORDER BY id DESC LIMIT 1'), ['id' => ++$id, 'col1' => 'foo3', 'col2' => 'foo3', 'col3' => 'foo3', 'col4' => null, 'col5' => null, 'updated_by' => 42]);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'insert', 'log_table' => 'test', 'log_column' => 'col3', 'log_value' => 'foo3', 'updated_by' => 42]);

        $this->db->query('
            insert into
            test (`col1`, `col2`, `col3`) VALUES (?, ?, ?)
        ', ['foo4','foo4','foo4']);
        $this->assertEquals($this->db->fetch_row('SELECT * FROM test ORDER BY id DESC LIMIT 1'), ['id' => ++$id, 'col1' => 'foo4', 'col2' => 'foo4', 'col3' => 'foo4', 'col4' => null, 'col5' => null, 'updated_by' => 42]);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'insert', 'log_table' => 'test', 'log_column' => 'col3', 'log_value' => 'foo4', 'updated_by' => 42]);

        $id = $this->db->insert('test2', ['col1' => 'foo1']);
        $this->assertEquals($this->db->fetch_row('SELECT * FROM test2 WHERE id = ?', $id), ['id' => $id, 'col1' => 'foo1', 'col2' => null, 'col3' => null, 'col4' => null, 'col5' => null]);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'insert', 'log_table' => 'test', 'log_column' => 'col3', 'log_value' => 'foo4', 'updated_by' => 42]);

        $id = $this->db->insert('test', ['col1' => 'foo1']);
        $this->assertEquals($this->db->fetch_row('SELECT * FROM test WHERE id = ?', $id), ['id' => $id, 'col1' => 'foo1', 'col2' => null, 'col3' => null, 'col4' => null, 'col5' => null, 'updated_by' => 42]);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'insert', 'log_table' => 'test', 'log_column' => 'col3', 'log_value' => null, 'updated_by' => 42]);
    }

    function test__update()
    {
        $id = $this->db->insert('test', ['col1' => 'foo', 'updated_by' => 43]);

        $this->db->update('test', ['col1' => 'bar'], ['id' => $id]);
        $this->assertEquals( $this->db->fetch_var('SELECT updated_by FROM TEST ORDER BY id DESC LIMIT 1'), 42 );
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'update', 'log_table' => 'test', 'log_column' => 'col1', 'log_value' => 'bar', 'updated_by' => 42]);

        $this->db->update('test', ['col1' => 'foo', 'updated_by' => 43], ['id' => $id]);
        $this->assertEquals( $this->db->fetch_var('SELECT updated_by FROM TEST ORDER BY id DESC LIMIT 1'), 43 );
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'update', 'log_table' => 'test', 'log_column' => 'col1', 'log_value' => 'foo', 'updated_by' => 43]);

        $this->db->query('UPDATE test SET col1 = ?, col2 = ?, col3 = ?', ['foo3','foo3','foo3']);
        $this->assertEquals($this->db->fetch_row('SELECT * FROM test ORDER BY id DESC LIMIT 1'), ['id' => $id, 'col1' => 'foo3', 'col2' => 'foo3', 'col3' => 'foo3', 'col4' => null, 'col5' => null, 'updated_by' => 42]);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['log_key']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'update', 'log_table' => 'test', 'log_column' => 'col3', 'log_value' => 'foo3', 'updated_by' => 42]);
    }

    function test__delete()
    {
        $id = $this->db->insert('test', ['col1' => 'lorem1']);
        $this->db->insert('test', ['col1' => 'lorem2']);
        $this->db->insert('test', ['col1' => 'lorem3']);
        $this->db->insert('test', ['col1' => 'lorem4']);
        $this->db->insert('test', ['col1' => 'lorem5']);
        $this->db->insert('test', ['col1' => 'ipsum1']);

        $this->db->delete('test', ['col1' => 'lorem1']);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'delete', 'log_table' => 'test', 'log_key' => $id, 'log_column' => null, 'log_value' => null, 'updated_by' => 42]);

        $this->db->query('DELETE FROM test WHERE col1 IN (?)', ['lorem2','lorem3']);
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'delete', 'log_table' => 'test', 'log_key' => $id+2, 'log_column' => null, 'log_value' => null, 'updated_by' => 42]);

        $this->db->query(' delete from `test` WHERE col1 LIKE ? ', '%lorem%');
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'delete', 'log_table' => 'test', 'log_key' => $id+4, 'log_column' => null, 'log_value' => null, 'updated_by' => 42]);

        $this->db->query('DELETE FROM test');
        $row = $this->db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1'); unset($row['id']); unset($row['updated_at']);
        $this->assertEquals($row, ['log_action' => 'delete', 'log_table' => 'test', 'log_key' => $id+5, 'log_column' => null, 'log_value' => null, 'updated_by' => 42]);
    }

}