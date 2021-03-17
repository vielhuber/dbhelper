<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

trait CredentialsSqlite
{
    public static function getCredentials()
    {
        return (object) [
            'driver' => 'pdo',
            'engine' => 'sqlite',
            'host' => 'dbhelper.db',
            'username' => null,
            'password' => null,
            'port' => null,
            'database' => null
        ];
    }
}
