<?php
namespace Tests;

use vielhuber\dbhelper\dbhelper;

trait BasicSetup
{

    public $db;

    function setUp()
    {
        $this->db = new dbhelper();
        $credentials = $this->getCredentials();
        $this->db->connect($credentials->driver, $credentials->engine, $credentials->host, $credentials->username, $credentials->password, 'dbhelper', $credentials->port);
        $this->db->clear();
        $this->db->query('
            CREATE TABLE test
            (
              id SERIAL PRIMARY KEY,
              col1 varchar(255),
              col2 varchar(255),
              col3 varchar(255)
            )
        ');
    }

    function tearDown()
    {
        //$this->db->clear();
        $this->db->disconnect();
    }

}