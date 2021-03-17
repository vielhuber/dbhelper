<?php

namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

trait BasicTest
{
    function test__insert()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(self::$db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id), 'foo');
        $id = self::$db->insert('test', [
            'id' => 2,
            'col1' => 'foo',
            'col2' => 'bar',
            'col3' => null
        ]);
        $this->assertEquals(self::$db->fetch_var('SELECT col3 FROM test WHERE id = ?', $id), null);
    }

    function test__update()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        self::$db->update('test', ['col1' => 'bar'], ['col1' => 'foo']);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'foo'), 0);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'bar'), 1);
        $this->assertEquals(self::$db->update('test', ['col1' => 'foo'], ['col1' => 'bar']), 1);
        $this->assertEquals(self::$db->update('test', ['col1' => 'foo'], ['col1' => 'bar']), 0);
    }

    function test__delete()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'foo'), 1);
        $id = self::$db->insert('test', ['col1' => 'bar']);
        $this->assertEquals(
            self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ? OR col1 = ?', 'foo', 'bar'),
            2
        );
        $this->assertEquals(self::$db->delete('test', ['col1' => 'foo']), 1);
        $this->assertEquals(
            self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ? OR col1 = ?', 'foo', 'bar'),
            1
        );
        $this->assertEquals(self::$db->query('DELETE FROM test WHERE col1 = ?', 'bar'), 1);
        $this->assertEquals(
            self::$db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ? OR col1 = ?', 'foo', 'bar'),
            0
        );
    }

    function test__fetch_all()
    {
        $id1 = self::$db->insert('test', ['col1' => 'foo']);
        $id2 = self::$db->insert('test', ['col1' => 'bar']);
        $this->assertEquals(self::$db->fetch_all('SELECT * FROM test WHERE col1 = ?', 'foo'), [
            ['id' => $id1, 'col1' => 'foo', 'col2' => null, 'col3' => null]
        ]);
        $this->assertEquals(self::$db->fetch_all('SELECT * FROM test WHERE col1 = ? OR col1 = ?', 'foo', 'bar'), [
            ['id' => $id1, 'col1' => 'foo', 'col2' => null, 'col3' => null],
            ['id' => $id2, 'col1' => 'bar', 'col2' => null, 'col3' => null]
        ]);
    }

    function test__fetch_row()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
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
        $this->assertEquals(self::$db->fetch_col('SELECT col1 FROM test'), ['foo', 'bar']);
    }

    function test__fetch_var()
    {
        $id1 = self::$db->insert('test', ['col1' => 'foo']);
        $id2 = self::$db->insert('test', ['col1' => 'bar']);
        $this->assertEquals(self::$db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id1), 'foo');
        $this->assertEquals(self::$db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id2), 'bar');
    }

    function test__count()
    {
        $this->assertEquals(self::$db->count('test'), 0);
        $this->assertEquals(self::$db->count('test', ['id' => 1]), 0);
        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(self::$db->count('test'), 1);
        $this->assertEquals(self::$db->count('test', ['id' => 1]), 1);
        self::$db->insert('test', ['col1' => 'bar']);
        $this->assertEquals(self::$db->count('test'), 2);
        $this->assertEquals(self::$db->count('test', ['id' => 1]), 1);
        self::$db->delete('test', ['id' => 2]);
        $this->assertEquals(self::$db->count('test'), 1);
        $this->assertEquals(self::$db->count('test', ['id' => 1]), 1);
        self::$db->delete('test', ['id' => 1]);
        $this->assertEquals(self::$db->count('test', ['id' => 1]), 0);
        $this->assertEquals(self::$db->count('test'), 0);
    }

    function test__flattened_args()
    {
        $id = self::$db->insert('test', ['col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']);
        $this->assertEquals(
            self::$db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', 'foo', 'bar', 'baz'),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertEquals(
            self::$db->fetch_row(
                'SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?',
                ['foo'],
                ['bar'],
                [[[['baz']]]]
            ),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertEquals(
            self::$db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', ['foo', 'bar'], 'baz'),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertEquals(
            self::$db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', 'foo', [
                'bar',
                ['baz']
            ]),
            ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']
        );
        $this->assertEquals(
            self::$db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', ['foo', 'bar', 'baz']),
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
        $this->assertEquals(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 = ? AND col2 IN (?)', 'foo', ['bar', 'baz']),
            [
                ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
                ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo']
            ]
        );
        $this->assertEquals(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 = ? AND col2 NOT IN (?)', 'foo', ['bar', 'baz']),
            [['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']]
        );
        $this->assertEquals(self::$db->fetch_all('SELECT * FROM test WHERE col1 IN (?)', ['foo', 'bar', 'baz']), [
            ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
            ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'],
            ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']
        ]);
        $this->assertEquals(
            self::$db->fetch_all(
                'SELECT * FROM test WHERE col1 IN (?) OR col2 IN (?) OR col3 IN (?)',
                'foo',
                'bar',
                'baz'
            ),
            [
                ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
                ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'],
                ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']
            ]
        );
        $this->assertEquals(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 IN (?) OR col2 = ? OR col3 = ?', ['foo'], 'bar', 'baz'),
            [
                ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
                ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'],
                ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']
            ]
        );
        $this->assertEquals(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 IN (?) OR col2 = ? OR col3 = ?', ['foo', 'bar', 'baz']),
            [
                ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
                ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'],
                ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']
            ]
        );
        $this->assertEquals(
            self::$db->fetch_all('SELECT * FROM test WHERE col1 IN (?,?) OR col2 = ?', ['foo', 'bar', 'baz']),
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
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
            'id' => $id,
            'col1' => null,
            'col2' => null,
            'col3' => 'bar'
        ]);
        $id = self::$db->insert('test', ['col1' => 'foo', 'col2' => null, 'col3' => 'bar']);
        self::$db->query('UPDATE test SET col1 = ? WHERE col2 = ? AND col3 != ?', null, null, null);
        $this->assertEquals(self::$db->fetch_row('SELECT * FROM test WHERE id = ?', $id), [
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
        $this->assertEquals(self::$db->fetch_all('SELECT * FROM test'), [
            ['id' => 1, 'col1' => 'foo', 'col2' => null, 'col3' => null],
            ['id' => 2, 'col1' => 'bar', 'col2' => null, 'col3' => null],
            ['id' => 3, 'col1' => 'baz', 'col2' => null, 'col3' => null]
        ]);
        self::$db->update('test', [
            [['col1' => 'foo1'], ['id' => 1]],
            [['col1' => 'bar1'], ['id' => 2]],
            [['col1' => 'baz1'], ['id' => 3]]
        ]);
        $this->assertEquals(self::$db->fetch_all('SELECT * FROM test'), [
            ['id' => 1, 'col1' => 'foo1', 'col2' => null, 'col3' => null],
            ['id' => 2, 'col1' => 'bar1', 'col2' => null, 'col3' => null],
            ['id' => 3, 'col1' => 'baz1', 'col2' => null, 'col3' => null]
        ]);
        self::$db->delete('test', [['id' => 1], ['id' => 2], ['id' => 3]]);
        $this->assertEquals(self::$db->fetch_all('SELECT * FROM test'), []);
    }

    function test__clear()
    {
        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(1, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->insert('test', ['col1' => 'foo']);
        self::$db->insert('test', ['col1' => 'foo']);
        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(4, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->clear('test');
        $this->assertEquals(0, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->clear();
        try {
            self::$db->fetch_var('SELECT COUNT(*) FROM test');
            $this->assertEquals(true, false);
        } catch (\Exception $e) {
            $this->assertEquals(true, true);
        }
    }

    function test__delete_table()
    {
        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(1, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->delete_table('test');
        try {
            self::$db->fetch_var('SELECT COUNT(*) FROM test');
            $this->assertEquals(true, false);
        } catch (\Exception $e) {
            $this->assertEquals(true, true);
        }
    }

    function test__create_table()
    {
        self::$db->create_table('test2', [
            'id' => 'SERIAL PRIMARY KEY',
            'col1' => 'varchar(255)',
            'col2' => 'varchar(255)',
            'col3' => 'varchar(255)'
        ]);
        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(1, self::$db->fetch_var('SELECT COUNT(*) FROM test'));
        self::$db->insert('test2', ['col1' => 'foo']);
        $this->assertEquals(1, self::$db->fetch_var('SELECT COUNT(*) FROM test2'));
    }

    function test__last_insert_id()
    {
        $id = self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals($id, self::$db->last_insert_id());
    }

    function test__get_tables()
    {
        $this->assertEquals(self::$db->get_tables(), ['test']);
    }

    function test__get_columns()
    {
        $this->assertEquals(self::$db->get_columns('test'), ['id', 'col1', 'col2', 'col3']);
    }

    function test__get_foreign_keys()
    {
        self::$db->create_table('test_foreign', [
            'id' => (self::$credentials->engine === 'sqlite' ? 'INTEGER' : 'SERIAL') . ' PRIMARY KEY',
            'col1' => 'varchar(255)',
            'col2' => 'varchar(255)',
            'col3' => ['mysql' => 'BIGINT UNSIGNED', 'postgres' => 'INTEGER', 'sqlite' => ''][
                self::$credentials->engine
            ],
            'FOREIGN KEY(col3)' => 'REFERENCES test(id)'
        ]);
        $this->assertEquals(self::$db->get_foreign_keys('test'), []);
        $this->assertEquals(self::$db->is_foreign_key('test', 'col1'), false);
        $this->assertEquals(self::$db->get_foreign_keys('test_foreign'), ['col3' => ['test', 'id']]);
        $this->assertEquals(self::$db->is_foreign_key('test_foreign', 'col3'), true);

        $this->assertEquals(self::$db->get_foreign_tables_out('test'), []);
        $this->assertEquals(self::$db->get_foreign_tables_out('test_foreign'), ['test' => [['col3', 'id']]]);
        $this->assertEquals(self::$db->get_foreign_tables_in('test_foreign'), []);
        $this->assertEquals(self::$db->get_foreign_tables_in('test'), ['test_foreign' => [['col3', 'id']]]);
    }

    function test__get_duplicates()
    {
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2']);
        $this->assertEquals(self::$db->get_duplicates('test'), ['count' => [], 'data' => []]);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2']);
        $this->assertEquals(self::$db->get_duplicates('test'), [
            'count' => ['test' => 2],
            'data' => ['test' => [['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2', 'MIN()' => 2, 'COUNT()' => 2]]]
        ]);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2']);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2']);
        $this->assertEquals(self::$db->get_duplicates('test'), [
            'count' => ['test' => 4],
            'data' => ['test' => [['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2', 'MIN()' => 2, 'COUNT()' => 4]]]
        ]);
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        $this->assertEquals(self::$db->get_duplicates('test'), [
            'count' => ['test' => 6],
            'data' => [
                'test' => [
                    ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1', 'MIN()' => 1, 'COUNT()' => 2],
                    ['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2', 'MIN()' => 2, 'COUNT()' => 4]
                ]
            ]
        ]);
    }

    function test__delete_duplicates()
    {
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2']);
        self::$db->delete_duplicates('test');
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 2);
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => 'bar2', 'col3' => 'baz2']);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 4);
        self::$db->delete_duplicates('test');
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 2);
        self::$db->insert('test', ['col1' => null, 'col2' => 'bar3', 'col3' => 'baz2']);
        self::$db->insert('test', ['col1' => null, 'col2' => 'bar4', 'col3' => 'baz2']);
        self::$db->delete_duplicates('test', ['col2']);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 4);
        self::$db->delete_duplicates('test', ['col1', 'col3']);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 3);
        self::$db->clear('test');

        self::$db->insert('test', ['col1' => null, 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->insert('test', ['col1' => null, 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->delete_duplicates('test', ['col1'], false);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 2);
        self::$db->delete_duplicates('test', ['col1'], true);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 1);
        self::$db->clear('test');

        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->delete_duplicates('test', ['col1'], true, ['id' => 'desc']);
        $this->assertEquals(self::$db->fetch_var('SELECT id FROM test LIMIT 1'), 2);
        self::$db->clear('test');

        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->delete_duplicates('test', ['col1'], true, ['id' => 'asc']);
        $this->assertEquals(self::$db->fetch_var('SELECT id FROM test LIMIT 1'), 1);
        self::$db->clear('test');

        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => null]);
        self::$db->insert('test', ['col1' => 'FOO1', 'col2' => 'BAR1', 'col3' => null]);
        self::$db->delete_duplicates('test');
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 2);
        self::$db->delete_duplicates('test', [], true, [], false);
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 1);
        self::$db->clear('test');

        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => null]);
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => null]);
        self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => null]);
        $id = self::$db->insert('test', ['col1' => 'foo1', 'col2' => 'bar1', 'col3' => null]);
        self::$db->delete_duplicates('test');
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 1);
        $this->assertEquals(self::$db->fetch_var('SELECT id FROM test LIMIT 1'), $id);
        self::$db->clear('test');
    }

    function test__trim_values()
    {
        self::$db->insert('test', ['col1' => 'foo1 ', 'col2' => 'bar1', 'col3' => 'baz1']);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => ' bar2', 'col3' => 'baz2']);
        self::$db->insert('test', ['col1' => 'foo2', 'col2' => ' bar2 ', 'col3' => ' baz2 ']);
        $this->assertEquals(self::$db->trim_values(), [
            ['table' => 'test', 'column' => 'col1', 'id' => 1, 'before' => 'foo1 ', 'after' => 'foo1'],
            ['table' => 'test', 'column' => 'col2', 'id' => 2, 'before' => ' bar2', 'after' => 'bar2'],
            ['table' => 'test', 'column' => 'col2', 'id' => 3, 'before' => ' bar2 ', 'after' => 'bar2'],
            ['table' => 'test', 'column' => 'col3', 'id' => 3, 'before' => ' baz2 ', 'after' => 'baz2']
        ]);
        $this->assertEquals(count(self::$db->trim_values()), 4);
        $this->assertEquals(count(self::$db->trim_values(false, ['test'])), 0);
        $this->assertEquals(count(self::$db->trim_values(false, ['test' => ['col1', 'col2']])), 1);
        $this->assertEquals(count(self::$db->trim_values(true)), 4);
        $this->assertEquals(count(self::$db->trim_values(true)), 0);
        $this->assertEquals(count(self::$db->trim_values()), 0);
        $this->assertEquals(self::$db->fetch_var('SELECT col1 FROM test WHERE id = 1'), 'foo1');
        $this->assertEquals(self::$db->fetch_var('SELECT col2 FROM test WHERE id = 2'), 'bar2');
        $this->assertEquals(self::$db->fetch_var('SELECT col2 FROM test WHERE id = 3'), 'bar2');
        $this->assertEquals(self::$db->fetch_var('SELECT col3 FROM test WHERE id = 3'), 'baz2');
        self::$db->clear('test');
    }

    function test__has_table()
    {
        $this->assertEquals(self::$db->has_table('test'), true);
        $this->assertEquals(self::$db->has_table('test2'), false);
    }

    function test__has_column()
    {
        $this->assertEquals(self::$db->has_column('test', 'col1'), true);
        $this->assertEquals(self::$db->has_column('test', 'col0'), false);
    }

    function test__get_datatype()
    {
        $this->assertEquals(
            in_array(self::$db->get_datatype('test', 'col1'), ['varchar', 'character varying', 'varchar(255)']),
            true
        );
        $this->assertEquals(self::$db->get_datatype('test', 'col0'), null);
    }

    function test__get_primary_key()
    {
        $this->assertEquals(self::$db->get_primary_key('test'), 'id');
        $this->assertEquals(self::$db->get_primary_key('test0'), null);
    }

    function test__uuid()
    {
        $uuid1 = self::$db->uuid();
        $uuid2 = self::$db->uuid();
        $this->assertEquals(strlen($uuid1) === 36, true);
        $this->assertEquals(strlen($uuid2) === 36, true);
        $this->assertEquals($uuid1 === $uuid2, false);
    }

    function test__multiple_statements()
    {
        self::$db->sql->exec('
            INSERT INTO test(col1,col2,col3) VALUES (\'foo\',\'bar\',\'baz\');
            INSERT INTO test(col1,col2,col3) VALUES (\'foo\',\'bar\',\'baz\');
        ');
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 2);
    }

    function test__errors()
    {
        try {
            self::$db->insert('test', ['id' => 1, 'col1' => (object) ['foo' => 'bar']]);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        try {
            self::$db->query('SELCET * FROM test');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        $this->assertEquals(self::$db->fetch_var('SELECT COUNT(*) FROM test'), 0);
    }

    function test__debug()
    {
        $this->assertEquals(
            self::$db->debug('SELECT * FROM foo WHERE bar = ?', 'baz'),
            'SELECT * FROM foo WHERE bar = \'baz\''
        );
        $this->assertEquals(
            self::$db->debug('SELECT * FROM foo WHERE bar = ?', null),
            'SELECT * FROM foo WHERE bar IS NULL'
        );
        $this->assertEquals(
            self::$db->debug('DELETE FROM tablename WHERE row1 = ?', null),
            'DELETE FROM tablename WHERE row1 IS NULL'
        );
    }
}
