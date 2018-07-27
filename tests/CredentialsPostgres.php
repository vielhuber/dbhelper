<?php
namespace Tests;

use vielhuber\dbhelper\dbhelper;

trait CredentialsPostgres
{

    function getCredentials()
    {
        return (object)[
            'driver' => 'pdo',
            'engine' => 'postgres',
            'host' => '127.0.0.1',
            'username' => 'postgres',
            'password' => 'root',
            'port' => 5432
        ];
    }

}