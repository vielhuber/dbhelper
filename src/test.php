<?php
require_once('dbhelper.php');
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();
$db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'sbit', 3306);
print_r($db->insert('wp_usermeta', [
    ['user_id' => 1, 'meta_key' => 'foo1', 'meta_value' => 'bar'],
    ['user_id' => 1, 'meta_key' => 'foo2', 'meta_value' => 'bar'],
    ['user_id' => 1, 'meta_key' => 'foo3', 'meta_value' => 'bar']
]));