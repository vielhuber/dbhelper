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
use vielhuber\dbhelper\DBHelper;
$db = new DBHelper();

$db->connect('pdo','mysql','127.0.0.1','root','root','database');

$db->insert('tablename',['id'=>1,'name'=>'foo']);

$db->update('tablename',['col1'=>'foo','col2'=>'bar'],['id'=>1]);

$db->fetch_all('SELECT * FROM table WHERE ID > ?',1));
$db->fetch_all('SELECT * FROM table WHERE name = ? AND number > ?','david',5));
$db->fetch_all('SELECT * FROM table WHERE col = ?',NULL));

$db->fetch_row('SELECT * FROM smd_brand WHERE ID = ?',1));

$db->fetch_col('SELECT col FROM smd_brand WHERE ID > ?',1));

$db->fetch_var('SELECT item FROM table WHERE ID = ?',1));

$id = $db->query('INSERT INTO table(`row1`,`row2`) VALUES(?,?,?)',1,2,3));
$db->query('DELETE FROM table WHERE ID = ?',1));
$db->query('UPDATE table SET `row1` = ? WHERE ID = ?',1,2));
```