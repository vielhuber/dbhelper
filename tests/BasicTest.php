<?php
namespace Tests;

use vielhuber\dbhelper\dbhelper;

trait BasicTest
{
    function test__insert()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertSame(self::$db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id), 'foo');
        $id = self::$db->insert('test', [
            'id' => 2,
            'col1' => 'foo',
            'col2' => 'bar',
            'col3' => null
        ]);
        $this->assertSame(self::$db->fetch_var('SELECT col3 FROM test WHERE id = ?', $id), null);
    }

    function test__update()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        self::$db->update('test', ['col1' => 'bar'], ['col1' => 'foo']);
        $this->assertSame(
            self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'foo'),
            0
        );
        $this->assertSame(
            self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'bar'),
            1
        );
    }

    function test__delete()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertSame(
            self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'foo'),
            1
        );
        $id = self::$db->insert('test', ['col1' => 'bar']);
        $this->assertSame(
            self::$db->fetch_var(
                'SELECT COUNT(*) FROM test WHERE col1 = ? OR col1 = ?',
                'foo',
                'bar'
            ),
            2
        );
        self::$db->delete('test', ['col1' => 'foo']);
        $this->assertSame(
            self::$db->fetch_var(
                'SELECT COUNT(*) FROM test WHERE col1 = ? OR col1 = ?',
                'foo',
                'bar'
            ),
            1
        );
        self::$db->query('DELETE FROM test WHERE col1 = ?', 'bar');
        $this->assertSame(
            self::$db->fetch_var(
                'SELECT COUNT(*) FROM test WHERE col1 = ? OR col1 = ?',
                'foo',
                'bar'
            ),
            0
        );
    }

    function test__fetch_all()
    {
        $id1 = self::$db->insert('test', ['col1' => 'foo']);
        $id2 = self::$db->insert('test', ['col1' => 'bar']);
        $this->assertSame(self::$db->fetch_all('SELECT * FROM test WHERE col1 = ?', 'foo'), [
            ['id' => $id1, 'col1' => 'foo', 'col2' => null, 'col3' => null]
        ]);
        $this->assertSame(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 = ? OR col1 = ?', 'foo', 'bar'),
            [
                ['id' => $id1, 'col1' => 'foo', 'col2' => null, 'col3' => null],
                ['id' => $id2, 'col1' => 'bar', 'col2' => null, 'col3' => null]
            ]
        );
    }

    function test__fetch_row()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertSame(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => 'foo',
            'col2' => null,
            'col3' => null
        ]);
    }

    function test__fetch_col()
    {
        self::$db->insert('test', ['col1' => 'foo']);
        self::$db->insert('test', ['col1' => 'bar']);
        $this->assertSame(self::$db->fetch_col('SELECT col1 FROM test'), ['foo', 'bar']);
    }

    function test__fetch_var()
    {
        $id1 = self::$db->insert('test', ['col1' => 'foo']);
        $id2 = self::$db->insert('test', ['col1' => 'bar']);
        $this->assertSame(self::$db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id1), 'foo');
        $this->assertSame(self::$db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id2), 'bar');
    }

    function test__flattened_args()
    {
        $id = self::$db->insert('test', ['col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']);
        $this->assertSame(
            self::$db->fetch_row(
                'SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?',
                'foo',
                'bar',
                'baz'
            ),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertSame(
            self::$db->fetch_row(
                'SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?',
                ['foo'],
                ['bar'],
                [[[['baz']]]]
            ),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertSame(
            self::$db->fetch_row(
                'SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?',
                ['foo', 'bar'],
                'baz'
            ),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertSame(
            self::$db->fetch_row(
                'SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?',
                'foo',
                ['bar', ['baz']]
            ),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertSame(
            self::$db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', [
                'foo',
                'bar',
                'baz'
            ]),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
    }

    function test__in_expansion()
    {
        self::$db->insert('test', [
            ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
            ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'],
            ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']
        ]);
        $this->assertSame(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 = ? AND col2 IN (?)', 'foo', [
                'bar',
                'baz'
            ]),
            [
                ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
                ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo']
            ]
        );
        $this->assertSame(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 = ? AND col2 NOT IN (?)', 'foo', [
                'bar',
                'baz'
            ]),
            [['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']]
        );
        $this->assertSame(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 IN (?)', ['foo', 'bar', 'baz']),
            [
                ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
                ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'],
                ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']
            ]
        );
    }

    function test__null_values()
    {
        $id = self::$db->insert('test', ['col1' => 'foo', 'col2' => null, 'col3' => 'bar']);
        self::$db->query('UPDATE test SET col1 = NULL WHERE col2 IS NULL AND col3 IS NOT NULL');
        $this->assertSame(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => null,
            'col2' => null,
            'col3' => 'bar'
        ]);
        $id = self::$db->insert('test', ['col1' => 'foo', 'col2' => null, 'col3' => 'bar']);
        self::$db->query('UPDATE test SET col1 = ? WHERE col2 = ? AND col3 != ?', null, null, null);
        $this->assertSame(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => null,
            'col2' => null,
            'col3' => 'bar'
        ]);
    }

    function test__batch()
    {
        self::$db->insert('test', [
            ['id' => 1, 'col1' => 'foo'],
            ['id' => 2, 'col1' => 'bar'],
            ['id' => 3, 'col1' => 'baz']
        ]);
        $this->assertSame(self::$db->fetch_all('SELECT * FROM test'), [
            ['id' => 1, 'col1' => 'foo', 'col2' => null, 'col3' => null],
            ['id' => 2, 'col1' => 'bar', 'col2' => null, 'col3' => null],
            ['id' => 3, 'col1' => 'baz', 'col2' => null, 'col3' => null]
        ]);
        self::$db->update('test', [
            [['col1' => 'foo1'], ['id' => 1]],
            [['col1' => 'bar1'], ['id' => 2]],
            [['col1' => 'baz1'], ['id' => 3]]
        ]);
        $this->assertSame(self::$db->fetch_all('SELECT * FROM test'), [
            ['id' => 1, 'col1' => 'foo1', 'col2' => null, 'col3' => null],
            ['id' => 2, 'col1' => 'bar1', 'col2' => null, 'col3' => null],
            ['id' => 3, 'col1' => 'baz1', 'col2' => null, 'col3' => null]
        ]);
        self::$db->delete('test', [['id' => 1], ['id' => 2], ['id' => 3]]);
        $this->assertSame(self::$db->fetch_all('SELECT * FROM test'), []);
    }

    function test__clear()
    {
        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertSame(1, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->insert('test', ['col1' => 'foo']);
        self::$db->insert('test', ['col1' => 'foo']);
        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertSame(4, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->clear('test');
        $this->assertSame(0, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->clear();
        try {
            self::$db->fetch_var('SELECT COUNT(*) FROM test');
            $this->assertSame(true, false);
        } catch (\Exception $e) {
            $this->assertSame(true, true);
        }
    }

    function test__last_insert_id()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertSame($id, self::$db->last_insert_id());
    }

    function test__get_tables()
    {
        $this->assertSame(self::$db->get_tables(), ['test']);
    }

    function test__get_columns()
    {
        $this->assertSame(self::$db->get_columns('test'), ['id', 'col1', 'col2', 'col3']);
    }

    function test__has_column()
    {
        $this->assertSame(self::$db->has_column('test', 'col1'), true);
        $this->assertSame(self::$db->has_column('test', 'col0'), false);
    }

    function test__get_datatype()
    {
        $this->assertSame(
            in_array(self::$db->get_datatype('test', 'col1'), ['varchar', 'character varying']),
            true
        );
        $this->assertSame(self::$db->get_datatype('test', 'col0'), null);
    }

    function test__get_primary_key()
    {
        $this->assertSame(self::$db->get_primary_key('test'), 'id');
        $this->assertSame(self::$db->get_primary_key('test0'), null);
    }

    function test__uuid()
    {
        $uuid1 = self::$db->uuid();
        $uuid2 = self::$db->uuid();
        $this->assertSame(strlen($uuid1) === 36, true);
        $this->assertSame(strlen($uuid2) === 36, true);
        $this->assertSame($uuid1 === $uuid2, false);
    }

    function test__multiple_statements()
    {
        self::$db->sql->exec('
            INSERT INTO test(col1,col2,col3) VALUES (\'foo\',\'bar\',\'baz\');
            INSERT INTO test(col1,col2,col3) VALUES (\'foo\',\'bar\',\'baz\');
        ');
        $this->assertSame(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 2);
    }

    function test__errors()
    {
        try {
            self::$db->insert('test', ['id' => 1, 'col1' => (object) ['foo' => 'bar']]);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        try {
            self::$db->query('SELCET * FROM test');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        $this->assertSame(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 0);
    }
}
