<?php
namespace Tests;

use vielhuber\dbhelper\dbhelper;

trait CredentialsMysql
{

    function getCredentials()
    {
        return (object)[
            'driver' => 'pdo',
            'engine' => 'mysql',
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'root',
            'port' => 3306
        ];
    }

}