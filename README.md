# 🍗 dbhelper 🍗

dbhelper is a small php wrapper for mysql/pgsql databases.

## Installation

```
composer require vielhuber/dbhelper
```

## Usage

```php
<?php
require __DIR__.'/vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();

$db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'database', 3306);

$db->insert('tablename', ['id' => 1, 'name' => 'foo']);

$db->update('tablename', ['col1' => 'foo', 'col2' => 'bar'], ['id' => 1]);

$db->delete('tablename', ['id' => 1]);

$db->fetch_all('SELECT * FROM table WHERE ID > ?', 1);
$db->fetch_all('SELECT * FROM table WHERE name = ? AND number > ?', 'david', 5);
$db->fetch_all('SELECT * FROM table WHERE col = ?', NULL);

$db->fetch_row('SELECT * FROM smd_brand WHERE ID = ?', 1);

$db->fetch_col('SELECT col FROM smd_brand WHERE ID > ?', 1);

$db->fetch_var('SELECT item FROM table WHERE ID = ?', 1);

$id = $db->query('INSERT INTO table(`row1`, `row2`) VALUES(?, ?, ?)', 1, 2, 3);
$db->query('UPDATE table SET `row1` = ? WHERE ID = ?', 1, 2);
$db->query('DELETE FROM table WHERE ID = ?', 1);

// generate a combined update query (perform multiple updates in one request)
print_r(dbhelper::get_combined_query(['table',
    [['col1' => 'var1', 'col2' => 1], ['id' => 1, 'key' => '1']],
    [['col1' => 'var2', 'col2' => 2], ['id' => 2, 'key' => '2']],
    [['col1' => 'var3', 'col2' => 3], ['id' => 3, 'key' => '3']]
]));
/*
UPDATE table SET
col1 = CASE
WHEN (id = 1 AND key = '1') THEN 'var1'
WHEN (id = 2 AND key = '2') THEN 'var2'
WHEN (id = 3 AND key = '3') THEN 'var3'
END,
col2 = CASE
WHEN (id = 1 AND key = '1') THEN 1
WHEN (id = 2 AND key = '2') THEN 2
WHEN (id = 3 AND key = '3') THEN 3
END
WHERE id IN (1,2,3) AND key IN ('1','2','3');
*/
```

this also works for wordpress (using wpdb and prepared statements under the hood)
```
require __DIR__.'/vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();
$db->connect('wordpress');
$db->fetch_var('SELECT item FROM table WHERE ID = ?', 1);
```

there is also a static version with static function calls (if you only use a single instance of dbhelper)
```
require __DIR__.'/vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/vielhuber/dbhelper/src/static.php');
db_fetch_var('SELECT item FROM table WHERE ID = ?', 1);
```