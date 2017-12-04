<?php
use vielhuber\dbhelper\dbhelper;

class Test extends \PHPUnit\Framework\TestCase
{

    protected $db;

    protected function setUp()
    {
        $this->db = new dbhelper();
        $this->db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'dbhelper', 3306);
        $this->db->clear('dbhelper');
    }

    protected function tearDown()
    {
        $this->db->clear('dbhelper');
        $this->db->disconnect();
    }

    public function testAll()
    {
        $this->db->query('
            CREATE TABLE test
            (
              id int(255) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              col1 varchar(255),
              col2 varchar(255),
              col3 varchar(255)
            )
        ');

        $id = $this->db->insert('test', ['col1' => 'foo']);
        $this->assertEquals($id, 1);

        $id = $this->db->insert('test', ['col3' => null]);
        $value = $this->db->fetch_var('SELECT col3 FROM test WHERE id = ?', $id);
        $this->assertEquals($value, null);

        $count = $this->db->fetch_var('SELECT COUNT(*) FROM test');
        $this->assertEquals($count, 2);
        $this->db->delete('test', ['id' => 2]);
        $count = $this->db->fetch_var('SELECT COUNT(*) FROM test');
        $this->assertEquals($count, 1);

        $this->db->query('INSERT INTO test(col1, col2) VALUES(?, ?)', 1, 2);
        $this->db->query('INSERT INTO test(col1, col2) VALUES(?, ?)', 1, [2]);
        $this->db->query('INSERT INTO test(col1, col2) VALUES(?, ?)', [1, 1337]);
        $this->db->query('UPDATE test SET col1 = ? WHERE col2 = ?', 'foo', 1337);
        $this->db->query('DELETE FROM test WHERE col2 = ?', 2);
        $value = $this->db->fetch_var('SELECT col1 FROM test WHERE col2 = ?', 1337);
        $this->assertEquals($value, 'foo');

        $id = $this->db->insert('test', ['col1' => '424242']);
        $this->db->update('test', ['col1' => '434343'], ['col1' => '424242']);
        $this->assertEquals( $this->db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', '424242'), 0 );
        $this->assertEquals( $this->db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', '434343'), 1 );
        $this->db->delete('test', ['col1' => '434343']);
        $this->assertEquals( $this->db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', '434343'), 0 );

        $id = $this->db->insert('test', ['id' => 42, 'col1' => '424242', 'col2' => '434343']);
        $result = $this->db->fetch_all('SELECT * FROM test WHERE col1 = ? AND col2 IN (?)', '424242', ['414141','434343','454545']);
        $this->assertEquals( $result[0], ['id' => 42, 'col1' => '424242', 'col2' => '434343', 'col3' => null] );
        
        $id = $this->db->insert('test', ['id' => 43, 'col1' => 42, 'col2' => null, 'col3' => 'foo']);
        $this->db->fetch_all('UPDATE test SET col1 = ? WHERE col2 = ? AND col3 != ?', null, null, null);
        $this->assertNull( $this->db->fetch_var('SELECT col1 FROM test WHERE id = ?', 43) );
    }
}