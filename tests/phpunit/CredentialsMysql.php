<?php
namespace Tests\Phpunit;

use vielhuber\dbhelper\dbhelper;

trait CredentialsMysql
{
    public static function getCredentials()
    {
        return (object) [
            'driver' => 'pdo',
            'engine' => 'mysql',
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'root',
            'port' => 3306,
            'database' => 'dbhelper'
        ];
    }
}
