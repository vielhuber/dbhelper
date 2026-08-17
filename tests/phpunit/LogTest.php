<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

trait LogTest
{
    public static dbhelper $db;

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

    function test__enable_logging_waits_for_setup_lock()
    {
        if (!function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is not available');
        }

        $lock_credentials = self::getCredentials();
        $lock_name = 'dbhelper-enable-logging-' . $lock_credentials->engine . '-' . $lock_credentials->database;
        $script = tempnam(sys_get_temp_dir(), 'dbhelper-lock-') . '.php';
        file_put_contents(
            $script,
            '<?php
            require ' .
                var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) .
                ';
            $credentials = (object) ' .
                var_export((array) $lock_credentials, true) .
                ';
            $db = new \vielhuber\dbhelper\dbhelper();
            $db->connect(
                $credentials->driver,
                $credentials->engine,
                $credentials->host,
                $credentials->username,
                $credentials->password,
                $credentials->database,
                $credentials->port
            );
            if ($credentials->engine === "mysql") {
                $db->fetch_var("SELECT GET_LOCK(?, 30)", ' .
                var_export($lock_name, true) .
                ');
            }
            if ($credentials->engine === "postgres") {
                $db->fetch_var("SELECT pg_advisory_lock(hashtext(?))", ' .
                var_export($lock_name, true) .
                ');
            }
            fwrite(STDOUT, "locked\n");
            usleep(700000);
            '
        );

        $process = proc_open(PHP_BINARY . ' ' . escapeshellarg($script), [1 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);

        try {
            $this->assertEquals("locked\n", fgets($pipes[1]));
            $started_at = microtime(true);
            self::$db->enable_logging();
            $this->assertGreaterThan(0.3, microtime(true) - $started_at);
            $this->assertEquals(0, proc_close($process));
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
            }
            @unlink($script);
        }
    }

    function test__disable_logging_can_be_scoped_to_single_tables()
    {
        self::$db->create_table('test3', ['id' => 'SERIAL PRIMARY KEY', 'col1' => 'varchar(255)']);
        self::$db->setup_logging();

        self::$db->disable_logging(['test']);
        self::$db->insert('test', ['col1' => 'foo']);
        self::$db->insert('test3', ['col1' => 'bar']);
        $this->assertEquals(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_table = ?', 'test'));
        $this->assertGreaterThan(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_table = ?', 'test3'));

        self::$db->enable_logging(['test']);
        self::$db->insert('test', ['col1' => 'foo2']);
        $this->assertGreaterThan(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_table = ?', 'test'));
    }

    function test__check_logging_detects_missing_triggers()
    {
        $this->assertEquals(['missing' => [], 'disabled' => []], self::$db->check_logging());

        self::$db->disable_logging(['test']);
        $check = self::$db->check_logging();
        $this->assertCount(3, $check['missing']);
        $this->assertCount(0, $check['disabled']);

        self::$db->enable_logging();
        $this->assertEquals(['missing' => [], 'disabled' => []], self::$db->check_logging());
    }

    function test__check_logging_detects_manually_disabled_triggers()
    {
        if (self::getCredentials()->engine !== 'postgres') {
            $this->markTestSkipped('only postgres can switch a trigger off without dropping it');
        }

        self::$db->query('ALTER TABLE "test" DISABLE TRIGGER "trigger-logging-insert-test"');
        $check = self::$db->check_logging();
        $this->assertCount(0, $check['missing']);
        $this->assertCount(1, $check['disabled']);

        self::$db->enable_logging();
        $this->assertEquals(['missing' => [], 'disabled' => []], self::$db->check_logging());

        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertGreaterThan(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_table = ?', 'test'));
    }

    function test__disable_logging_drops_the_trigger_functions_unless_told_otherwise()
    {
        if (self::getCredentials()->engine !== 'postgres') {
            $this->markTestSkipped('the trigger functions only exist on postgres');
        }

        self::$db->disable_logging(['test']);
        $this->assertCount(3, self::$db->check_logging()['missing']);
        $this->assertEquals(0, $this->countTriggerFunctions());

        self::$db->enable_logging(['test']);
        self::$db->disable_logging(['test'], delete: false);
        $this->assertCount(3, self::$db->check_logging()['missing']);
        $this->assertEquals(3, $this->countTriggerFunctions());

        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertEquals(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_table = ?', 'test'));

        self::$db->enable_logging(['test']);
        $this->assertEquals(['missing' => [], 'disabled' => []], self::$db->check_logging());
        self::$db->insert('test', ['col1' => 'foo2']);
        $this->assertGreaterThan(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_table = ?', 'test'));
    }

    private function countTriggerFunctions()
    {
        return self::$db->fetch_var(
            "SELECT count(*) FROM pg_proc p INNER JOIN pg_namespace n ON n.oid = p.pronamespace
             WHERE n.nspname = 'public' AND p.proname LIKE 'trigger\_logging\_%\_test'"
        );
    }

    function test__enable_logging_recreates_dropped_triggers()
    {
        if (self::getCredentials()->engine === 'postgres') {
            self::$db->query('DROP TRIGGER "trigger-logging-insert-test" ON "test"');
        }
        if (self::getCredentials()->engine === 'mysql') {
            self::$db->query('DROP TRIGGER `trigger-logging-insert-test`');
        }
        $this->assertCount(1, self::$db->check_logging()['missing']);

        self::$db->enable_logging();
        $this->assertEquals(['missing' => [], 'disabled' => []], self::$db->check_logging());

        self::$db->insert('test', ['col1' => 'foo']);
        $this->assertGreaterThan(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_table = ?', 'test'));
    }

    function test__purge_logging()
    {
        if (self::getCredentials()->engine === 'postgres') {
            self::$db->query('ALTER TABLE logs DISABLE TRIGGER auto_update_updated_at_column_on_insert');
        }

        try {
            foreach (['old-1', 'old-2', 'old-3'] as $log_key) {
                self::$db->insert('logs', [
                    'log_event' => 'update',
                    'log_table' => 'test',
                    'log_key' => $log_key,
                    'updated_at' => '2000-01-01'
                ]);
            }
            self::$db->insert('logs', [
                'log_event' => 'update',
                'log_table' => 'test',
                'log_key' => 'recent',
                'updated_at' => '2999-01-01'
            ]);
        } finally {
            if (self::getCredentials()->engine === 'postgres') {
                self::$db->query('ALTER TABLE logs ENABLE TRIGGER auto_update_updated_at_column_on_insert');
            }
        }

        $triggers = self::$db->check_logging();

        $this->assertSame(3, self::$db->purge_logging(2));
        $this->assertEquals(['recent'], self::$db->fetch_col('SELECT log_key FROM logs ORDER BY id'));
        $this->assertSame(0, self::$db->purge_logging(2));
        $this->assertEquals($triggers, self::$db->check_logging());
    }

    function test__setup_logging_still_purges_old_entries()
    {
        if (self::getCredentials()->engine === 'postgres') {
            self::$db->query('ALTER TABLE logs DISABLE TRIGGER auto_update_updated_at_column_on_insert');
        }

        try {
            self::$db->insert('logs', [
                'log_event' => 'update',
                'log_table' => 'test',
                'log_key' => 'old-setup',
                'updated_at' => '2000-01-01'
            ]);
        } finally {
            if (self::getCredentials()->engine === 'postgres') {
                self::$db->query('ALTER TABLE logs ENABLE TRIGGER auto_update_updated_at_column_on_insert');
            }
        }

        self::$db->setup_logging();

        $this->assertEquals(0, self::$db->fetch_var('SELECT count(*) FROM logs WHERE log_key = ?', 'old-setup'));
    }

    function test__setup_logging_creates_purge_index()
    {
        $this->assertTrue(self::$db->has_index('logs', 'updated_at_id_index'));

        if (self::getCredentials()->engine === 'mysql') {
            $this->assertSame(
                ['updated_at', 'id'],
                self::$db->fetch_col(
                    'SELECT column_name FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? ORDER BY seq_in_index',
                    self::$credentials->database,
                    'logs',
                    'updated_at_id_index'
                )
            );
        }
        if (self::getCredentials()->engine === 'postgres') {
            $index_definition = self::$db->fetch_var(
                'SELECT indexdef FROM pg_indexes WHERE schemaname = ? AND tablename = ? AND indexname = ?',
                'public',
                'logs',
                'logs_updated_at_id_index'
            );
            $this->assertStringContainsString('(updated_at, id)', $index_definition);
            $this->assertStringContainsString('WHERE (updated_at IS NOT NULL)', $index_definition);
        }

        self::$db->delete_index('logs', 'updated_at_id_index');
        $this->assertFalse(self::$db->has_index('logs', 'updated_at_id_index'));

        self::$db->setup_logging();
        $this->assertTrue(self::$db->has_index('logs', 'updated_at_id_index'));

        self::$db->setup_logging();
        $this->assertTrue(self::$db->has_index('logs', 'updated_at_id_index'));
    }

    function test__setup_logging_skips_purge_index_without_retention()
    {
        self::$db->delete_index('logs', 'updated_at_id_index');
        $delete_older = self::$db->config['delete_older'];
        unset(self::$db->config['delete_older']);

        try {
            self::$db->setup_logging();
            $this->assertFalse(self::$db->has_index('logs', 'updated_at_id_index'));
        } finally {
            self::$db->config['delete_older'] = $delete_older;
        }
    }

    function test__setup_logging_creates_purge_index_inside_postgres_transaction()
    {
        if (self::getCredentials()->engine !== 'postgres') {
            $this->expectNotToPerformAssertions();
            return;
        }

        self::$db->delete_index('logs', 'updated_at_id_index');
        self::$db->begin_transaction();

        try {
            self::$db->setup_logging();
            $this->assertTrue(self::$db->has_index('logs', 'updated_at_id_index'));
            self::$db->commit();
        } catch (\Throwable $exception) {
            if (self::$db->in_transaction()) {
                self::$db->rollback();
            }
            throw $exception;
        }
    }

    function test__purge_logging_rejects_invalid_batch_sizes()
    {
        $this->expectException(\InvalidArgumentException::class);
        self::$db->purge_logging(0);
    }

    function test__purge_logging_rejects_invalid_retention()
    {
        $delete_older = self::$db->config['delete_older'];
        self::$db->config['delete_older'] = 0;

        try {
            $this->expectException(\InvalidArgumentException::class);
            self::$db->purge_logging();
        } finally {
            self::$db->config['delete_older'] = $delete_older;
        }
    }
}
