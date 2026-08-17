<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

class BasicMysqlTest extends \PHPUnit\Framework\TestCase
{
    use CredentialsMysql;
    use BasicSetup;
    use BasicTest;

    function test__mysqli_transactions()
    {
        $database = new dbhelper();
        $database->connect(
            'mysqli',
            self::$credentials->engine,
            self::$credentials->host,
            self::$credentials->username,
            self::$credentials->password,
            self::$credentials->database,
            self::$credentials->port
        );

        $this->assertFalse($database->in_transaction());
        $this->assertTrue($database->begin_transaction());
        $this->assertTrue($database->in_transaction());
        $database->query("INSERT INTO test (col1) VALUES ('rollback')");
        $this->assertTrue($database->rollback());
        $this->assertFalse($database->in_transaction());
        $this->assertSame('0', $database->fetch_var("SELECT COUNT(*) FROM test WHERE col1 = 'rollback'"));

        $this->assertTrue($database->begin_transaction());
        $database->query("INSERT INTO test (col1) VALUES ('commit')");
        $this->assertTrue($database->commit());
        $this->assertFalse($database->in_transaction());
        $this->assertSame('1', $database->fetch_var("SELECT COUNT(*) FROM test WHERE col1 = 'commit'"));

        $this->assertTrue($database->begin_transaction());
        $database->query("INSERT INTO test (col1) VALUES ('disconnect')");
        $database->disconnect();
        $database->connect(
            'mysqli',
            self::$credentials->engine,
            self::$credentials->host,
            self::$credentials->username,
            self::$credentials->password,
            self::$credentials->database,
            self::$credentials->port
        );
        $this->assertFalse($database->in_transaction());
        $this->assertSame('0', $database->fetch_var("SELECT COUNT(*) FROM test WHERE col1 = 'disconnect'"));
        $database->disconnect();
    }
}
