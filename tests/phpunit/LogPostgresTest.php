<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

class LogPostgresTest extends \PHPUnit\Framework\TestCase
{
    use CredentialsPostgres;
    use LogSetup;
    use LogTest;

    function test__update_with_json_columns(): void
    {
        foreach (['json', 'jsonb'] as $datatype) {
            $table = 'test_' . $datatype;
            self::$db->create_table($table, [
                'id' => 'SERIAL PRIMARY KEY',
                'data' => $datatype
            ]);
            self::$db->setup_logging();
            $id = self::$db->insert($table, ['data' => '{"value":1}']);
            self::$db->query('TRUNCATE TABLE logs');

            self::$db->query('ALTER TABLE ' . $table . ' ADD COLUMN external_id UUID');
            self::$db->query('UPDATE ' . $table . ' SET external_id = gen_random_uuid() WHERE id = ?', $id);
            self::$db->query('UPDATE ' . $table . ' SET data = data WHERE id = ?', $id);
            $this->assertSame(0, (int) self::$db->fetch_var('SELECT COUNT(*) FROM logs'));
            $this->assertNotNull(self::$db->fetch_var('SELECT external_id FROM ' . $table . ' WHERE id = ?', $id));

            self::$db->update($table, ['data' => '{"value":2}'], ['id' => $id]);
            self::$db->update($table, ['data' => null], ['id' => $id]);
            self::$db->update($table, ['data' => null], ['id' => $id]);
            self::$db->update($table, ['data' => '{"value":1}'], ['id' => $id]);

            $this->assertSame(
                $datatype === 'json' ? ['{"value":2}', null, '{"value":1}'] : ['{"value": 2}', null, '{"value": 1}'],
                array_column(self::$db->fetch_all('SELECT log_value FROM logs ORDER BY id'), 'log_value')
            );
        }
    }
}
