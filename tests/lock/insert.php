<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();

$db->connect('pdo', 'sqlite', __DIR__ . '/test.db', null, null, null, null, $argv[1]);
if ($argv[2] == '1') {
    $db->query('PRAGMA locking_mode = EXCLUSIVE;');
    $db->query('BEGIN EXCLUSIVE;');
    sleep(10);
    $db->query('COMMIT;');
} else {
    $db->insert('test', [
        'col1' => $argv[2]
    ]);
}
$db->disconnect();
die('OK');
