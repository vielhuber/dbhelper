<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();

$db->connect_with_create('pdo', 'sqlite', __DIR__ . '/test.db', null, null, null, null, $argv[1]);
$db->clear();
$db->create_table('test', [
    'id' => 'INTEGER PRIMARY KEY',
    'col1' => 'TEXT',
    'col2' => 'TEXT'
]);
$iterations = 5;
for ($i = 1; $i <= $iterations; $i++) {
    if ($i == 2) {
        sleep(1);
    }
    shell_exec('php ' . __DIR__ . '/insert.php ' . $argv[1] . ' ' . $i . ' > ' . __DIR__ . '/' . $i . '.log 2>&1 &');
}
$finish = false;
while ($finish === false) {
    $finish = true;
    for ($i = 1; $i <= $iterations; $i++) {
        if (!file_exists(__DIR__ . '/' . $i . '.log') || trim(file_get_contents(__DIR__ . '/' . $i . '.log')) == '') {
            sleep(0.1);
            $finish = false;
        }
    }
}
for ($i = 1; $i <= $iterations; $i++) {
    $content = file_get_contents(__DIR__ . '/' . $i . '.log');
    echo $content . PHP_EOL;
    @unlink(__DIR__ . '/' . $i . '.log');
}
$db->disconnect_with_delete();
