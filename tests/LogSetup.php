<?php
namespace Tests;

use vielhuber\dbhelper\dbhelper;

trait LogSetup
{

    public $db;

    function setUp()
    {
        $this->db = new dbhelper([
            'enable_logging' => true,
            'logging_table' => 'logs',
            'exclude' => [
                'tables' => ['test2'],
                'columns' => ['test' => ['col4']]
            ],
            'delete_older' => 12,
            'updated_by' => 42
        ]);
        $credentials = $this->getCredentials();
        $this->db->connect($credentials->driver, $credentials->engine, $credentials->host, $credentials->username, $credentials->password, 'dbhelper', $credentials->port);
        $this->db->clear();
        $this->db->query('
            CREATE TABLE IF NOT EXISTS test
            (
              id SERIAL PRIMARY KEY,
              col1 varchar(255),
              col2 TEXT,
              col3 int,
              col4 varchar(255)
            )
        ');
        $this->db->query('
            CREATE TABLE IF NOT EXISTS test2
            (
              id SERIAL PRIMARY KEY,
              col1 varchar(255),
              col2 TEXT,
              col3 int,
              col4 varchar(255)
            )
        ');
        $this->db->setup_logging();
        $this->db->enable_auto_inject();
    }

    function tearDown()
    {
        //$this->db->clear();
        $this->db->disconnect();
    }

}