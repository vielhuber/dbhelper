# 🍗 dbhelper 🍗

dbhelper is a small php wrapper for mysql/pgsql databases.

## Installation

Install once with composer:
```
composer require vielhuber/dbhelper
```

Then add this to your files:
```php
require __DIR__.'/vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();
```

## Usage

```php
/* connnect to database */
$db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'database', 3306);

/* create/update/delete */
$id = $db->insert('tablename', ['id' => 1, 'name' => 'foo']);
$db->update('tablename', ['col1' => 'foo', 'col2' => 'bar'], ['id' => 1]);
$db->delete('tablename', ['id' => 1]);

/* raw queries */
$id = $db->query('INSERT INTO table(row1, row2) VALUES(?, ?, ?)', 1, 2, 3);
$db->query('UPDATE table SET row1 = ? WHERE ID = ?', 1, 2);
$db->query('DELETE FROM table WHERE ID = ?', 1);

/* select queries */
$db->fetch_all('SELECT * FROM table WHERE ID > ?', 1);
$db->fetch_all('SELECT * FROM table WHERE name = ? AND number > ?', 'foo', 42);
$db->fetch_all('SELECT * FROM table WHERE col = ?', NULL);
$db->fetch_row('SELECT * FROM table WHERE ID = ?', 1);
$db->fetch_col('SELECT col FROM table WHERE ID > ?', 1);
$db->fetch_var('SELECT item FROM table WHERE ID = ?', 1);

/* automatic IN-expansion */
$db->fetch_all('SELECT * FROM table WHERE col1 = ? AND col2 IN (?)', 1, [2,3,4]);

/* automatic flattened arguments */
$db->fetch_all('SELECT * FROM table WHERE ID = ?', [1], 2, [3], [4,5,6]);
=>
$db->fetch_all('SELECT * FROM table WHERE ID = ?', 1, 2, 3, 4, 5, 6);

/* support for null values */
$db->fetch_all('SELECT * FROM table WHERE col1 = ?', null);
=>
$db->fetch_all('SELECT * FROM table WHERE col1 IS NULL');

/* batch functions (they create only one query) */
$db->insert('tablename', [
    ['id' => 1, 'name' => 'foo1'],
    ['id' => 2, 'name' => 'foo2'],
    ['id' => 3, 'name' => 'foo3']
]);
$db->delete('tablename', [
    ['id' => 1],
    ['id' => 7],
    ['id' => 42]
]);
$db->update('tablename', [
    [['col1' => 'var1', 'col2' => 1], ['id' => 1, 'key' => '1']],
    [['col1' => 'var2', 'col2' => 2], ['id' => 2, 'key' => '2']],
    [['col1' => 'var3', 'col2' => 3], ['id' => 3, 'key' => '3']]
]);
/*
this generates the following query:
UPDATE tablename SET
col1 = CASE WHEN (id = 1 AND key = '1') THEN 'var1' WHEN (id = 2 AND key = '2') THEN 'var2' WHEN (id = 3 AND key = '3') THEN 'var3' END,
col2 = CASE WHEN (id = 1 AND key = '1') THEN 1 WHEN (id = 2 AND key = '2') THEN 2 WHEN (id = 3 AND key = '3') THEN 3 END
WHERE id IN (1,2,3) AND key IN ('1','2','3');
*/
```

This also works for wordpress (using wpdb, prepared statements and stripslashes_deep under the hood):
```php
$db->connect('wordpress');
$db->fetch_var('SELECT item FROM table WHERE ID = ?', 1);
```

There is also a static version with static function calls (if you only use a single instance of dbhelper):
```php
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/vielhuber/dbhelper/src/static.php');
db_fetch_var('SELECT item FROM table WHERE ID = ?', 1);
```