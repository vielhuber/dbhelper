<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

trait CredentialsPostgres
{
    public static function getCredentials()
    {
        return (object) [
            'driver' => 'pdo',
            'engine' => 'postgres',
            'host' => '127.0.0.1',
            'username' => 'postgres',
            'password' => 'root',
            'port' => 5432,
            'database' => 'dbhelper'
        ];
    }
}
