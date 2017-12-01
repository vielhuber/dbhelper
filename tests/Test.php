<?php
use vielhuber\dbhelper\dbhelper;

class Test extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $db = new dbhelper();
        $db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'dbhelper', 3306);
        $db->drop('dbhelper');
        $db->query('
            CREATE TABLE test
            (
              id int(255) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              col1 varchar(255),
              col2 varchar(255),
              col3 varchar(255)
            )
        ');

        $id = $db->insert('test', ['col1' => 'foo']);
        $this->assertEquals($id, 1);

        $id = $db->insert('test', ['col3' => null]);
        $value = $db->fetch_var('SELECT col3 FROM test WHERE id = ?', $id);
        $this->assertEquals($value, null);

        $count = $db->fetch_var('SELECT COUNT(*) FROM test');
        $this->assertEquals($count, 2);
        $db->delete('test', ['id' => 2]);
        $count = $db->fetch_var('SELECT COUNT(*) FROM test');
        $this->assertEquals($count, 1);

        $db->query('INSERT INTO test(col1, col2) VALUES(?, ?)', 1, 2);
        $db->query('INSERT INTO test(col1, col2) VALUES(?, ?)', 1, [2]);
        $db->query('INSERT INTO test(col1, col2) VALUES(?, ?)', [1, 1337]);
        $db->query('UPDATE test SET col1 = ? WHERE col2 = ?', 'foo', 1337);
        $db->query('DELETE FROM test WHERE col2 = ?', 2);
        $value = $db->fetch_var('SELECT col1 FROM test WHERE col2 = ?', 1337);
        $this->assertEquals($value, 'foo');

        $db->drop('dbhelper');
    }
}