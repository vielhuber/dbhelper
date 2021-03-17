<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

trait LogTest
{
    function test__insert()
    {
        $id = self::$db->insert('test', ['col1' => 'foo1']);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => 'foo1',
            'col2' => null,
            'col3' => null,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => null,
            'updated_by' => 42
        ]);

        $id = self::$db->insert('test', ['col1' => 'foo2', 'updated_by' => 43]);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => 'foo2',
            'col2' => null,
            'col3' => null,
            'col4' => null,
            'updated_by' => 43
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => null,
            'updated_by' => 43
        ]);

        self::$db->query('INSERT INTO test(col1, col2, col3) VALUES(?,?,?)', ['foo3', 'foo3', 3]);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test ORDER BY id DESC LIMIT 1'), [
            'id' => ++$id,
            'col1' => 'foo3',
            'col2' => 'foo3',
            'col3' => 3,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => 3,
            'updated_by' => 42
        ]);

        self::$db->query(
            '
            insert into
            test (col1, col2, col3) VALUES (?, ?, ?)
        ',
            ['foo4', 'foo4', 4]
        );
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test ORDER BY id DESC LIMIT 1'), [
            'id' => ++$id,
            'col1' => 'foo4',
            'col2' => 'foo4',
            'col3' => 4,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => 4,
            'updated_by' => 42
        ]);

        $id = self::$db->insert('test2', ['col1' => 'foo1']);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test2 WHERE id = ?', $id), [
            'id' => $id,
            'col1' => 'foo1',
            'col2' => null,
            'col3' => null,
            'col4' => null
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => 4,
            'updated_by' => 42
        ]);

        $id = self::$db->insert('test', ['col1' => 'foo1']);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => 'foo1',
            'col2' => null,
            'col3' => null,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => null,
            'updated_by' => 42
        ]);

        $id = self::$db->insert('test', ['col2' => str_repeat('x', 5000)]);
        $this->assertEquals(self::$db->fetch_var('SELECT SUBSTRING(col2, 1, 3) FROM test WHERE id = ?', $id), 'xxx');
        $this->assertEquals(
            self::$db->fetch_var(
                'SELECT SUBSTRING(log_value, 1, 3) FROM logs WHERE log_column = ? ORDER BY id DESC LIMIT 1',
                'col2'
            ),
            'xxx'
        );
    }

    function test__update()
    {
        $id = self::$db->insert('test', ['col1' => 'foo', 'updated_by' => 43]);

        self::$db->update('test', ['col1' => 'bar'], ['id' => $id]);
        $this->assertEquals(self::$db->fetch_var('SELECT updated_by FROM test ORDER BY id DESC LIMIT 1'), 42);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'update',
            'log_table' => 'test',
            'log_column' => 'col1',
            'log_value' => 'bar',
            'updated_by' => 42
        ]);

        self::$db->update('test', ['col1' => 'foo', 'updated_by' => 43], ['id' => $id]);
        $this->assertEquals(self::$db->fetch_var('SELECT updated_by FROM test ORDER BY id DESC LIMIT 1'), 43);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'update',
            'log_table' => 'test',
            'log_column' => 'col1',
            'log_value' => 'foo',
            'updated_by' => 43
        ]);

        self::$db->query('UPDATE test SET col1 = ?, col2 = ?, col3 = ?', ['foo3', 'foo3', 3]);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test ORDER BY id DESC LIMIT 1'), [
            'id' => $id,
            'col1' => 'foo3',
            'col2' => 'foo3',
            'col3' => 3,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'update',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => 3,
            'updated_by' => 42
        ]);
    }

    function test__delete()
    {
        $id = self::$db->insert('test', ['col1' => 'lorem1']);
        self::$db->insert('test', ['col1' => 'lorem2']);
        self::$db->insert('test', ['col1' => 'lorem3']);
        self::$db->insert('test', ['col1' => 'lorem4']);
        self::$db->insert('test', ['col1' => 'lorem5']);
        self::$db->insert('test', ['col1' => 'ipsum1']);

        self::$db->delete('test', ['col1' => 'lorem1']);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'delete',
            'log_table' => 'test',
            'log_key' => $id,
            'log_column' => null,
            'log_value' => null,
            'updated_by' => 42
        ]);

        self::$db->query('DELETE FROM test WHERE col1 IN (?)', ['lorem2', 'lorem3']);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'delete',
            'log_table' => 'test',
            'log_key' => $id + 2,
            'log_column' => null,
            'log_value' => null,
            'updated_by' => 42
        ]);

        self::$db->query(' delete from test WHERE col1 LIKE ? ', '%lorem%');
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'delete',
            'log_table' => 'test',
            'log_key' => $id + 4,
            'log_column' => null,
            'log_value' => null,
            'updated_by' => 42
        ]);

        self::$db->query('DELETE FROM test');
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'delete',
            'log_table' => 'test',
            'log_key' => $id + 5,
            'log_column' => null,
            'log_value' => null,
            'updated_by' => 42
        ]);
    }

    function test__enable_disable()
    {
        $id = self::$db->insert('test', ['col3' => 9991]);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => null,
            'col2' => null,
            'col3' => 9991,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => 9991,
            'updated_by' => 42
        ]);
        self::$db->disable_logging();
        $id = self::$db->insert('test', ['col3' => 9992]);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => null,
            'col2' => null,
            'col3' => 9992,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => 9991,
            'updated_by' => 42
        ]);
        self::$db->enable_logging();
        $id = self::$db->insert('test', ['col3' => 9993]);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => null,
            'col2' => null,
            'col3' => 9993,
            'col4' => null,
            'updated_by' => 42
        ]);
        $row = self::$db->fetch_row('SELECT * FROM logs ORDER BY id DESC LIMIT 1');
        unset($row['id']);
        unset($row['log_key']);
        unset($row['log_uuid']);
        unset($row['updated_at']);
        $this->assertEquals($row, [
            'log_event' => 'insert',
            'log_table' => 'test',
            'log_column' => 'col3',
            'log_value' => 9993,
            'updated_by' => 42
        ]);
    }
}
