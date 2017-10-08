<?php
require_once('dbhelper.php');
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();
$db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'sbit', 3306);

$db->update('wp_usermeta', [
    [['user_id' => 1337, 'meta_key' => 'foo'], ['umeta_id' => 87, 'umeta_id' => 87]],
    [['user_id' => 1337, 'meta_key' => 'bar'], ['umeta_id' => 86, 'umeta_id' => 86]],
    [['user_id' => 1337, 'meta_key' => 'baz'], ['umeta_id' => 85, 'umeta_id' => 85]]
]);