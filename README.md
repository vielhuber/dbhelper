# 🍗 dbhelper 🍗

dbhelper is a small php wrapper for mysql/postgres databases.

## installation

install once with composer:
```
composer require vielhuber/dbhelper
```

then add this to your project:
```php
require __DIR__.'/vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();
```

## usage

```php
/* connect to database */
$db->connect('pdo', 'mysql', '127.0.0.1', 'username', 'password', 'database', 3306);
$db->connect('pdo', 'postgres', '127.0.0.1', 'username', 'password', 'database', 3306);

/* disconnect from database */
$db->disconnect();

/* insert/update/delete */
$id = $db->insert('tablename', ['col1' => 'foo']);
$db->update('tablename', ['col1' => 'bar'], ['id' => $id]);
$db->delete('tablename', ['id' => $id]);

/* select */
$db->fetch_all('SELECT * FROM tablename WHERE name = ? AND number > ?', 'foo', 42);
$db->fetch_row('SELECT * FROM tablename WHERE ID = ?', 1);
$db->fetch_col('SELECT col FROM tablename WHERE ID > ?', 1);
$db->fetch_var('SELECT col FROM tablename WHERE ID = ?', 1);

/* automatic flattened arguments */
$db->fetch_all('SELECT * FROM tablename WHERE ID = ?', [1], 2, [3], [4,[5,6]]);
// gets transformed to
$db->fetch_all('SELECT * FROM tablename WHERE ID = ?', 1, 2, 3, 4, 5, 6);

/* automatic in-expansion */
$db->fetch_all('SELECT * FROM tablename WHERE col1 = ? AND col2 IN (?)', 1, [2,3,4]);

/* support for null values */
$db->query('UPDATE tablename SET col1 = ? WHERE col2 = ? AND col3 != ?', null, null, null);
// gets transformed to
$db->query('UPDATE tablename SET col1 = NULL WHERE col2 IS NULL AND col3 IS NOT NULL');

/* clean up */ 
$db->clear(); // delete all tables (without dropping the whole database)
$db->clear('tablename'); // delete all rows in a table

/* raw queries */
$id = $db->query('INSERT INTO tablename(row1, row2) VALUES(?, ?, ?)', 1, 2, 3);
$db->query('UPDATE tablename SET row1 = ? WHERE ID = ?', 1, 2);
$db->query('DELETE FROM tablename WHERE ID = ?', 1);

/* last insert id */
$db->insert('tablename', ['col1' => 'foo']);
$db->last_insert_id();

/* some more little helpers */
$db->get_tables() // ['tablename', ...]
$db->get_columns('tablename') // ['col1', 'col2', ...]
$db->has_column('tablename', 'col1') // true
$db->get_datatype('tablename', 'col1') // varchar 
$db->get_primary_key('tablename') // id
$db->uuid() // generate uuid (v4) from inside the database

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

### logging

dbhelper can support setting up a mature logging system.

```php
$db = new dbhelper([
    'logging_table' => 'logs',
    'exclude' => [
        'tables' => ['table1'],
        'columns' => ['table2' => ['col1','col2','col3']]
    ],
    'delete_older' => 12, // months
    'updated_by' => get_current_user_id()
]);
$db->connect('...');
$db->setup_logging();
```

```setup_logging()``` does four things:

- it creates a logging table (if not exists)
- it appends a single column ```updated_by``` to every table in the database (if not exists)
- it creates triggers for all insert/update/delete events (if not exists)
- it deletes old logging entries based on the ```delete_older``` option

you should run this method after a schema change (e.g. in your migrations) and you can also run it on a daily basis via cron. it is recommened to exclude blob/bytea columns.

the logging table has the following schema:

- ```id```: unique identifier of that single change
- ```log_event```: insert/update/delete
- ```log_table```: name of the table of the modified row
- ```log_key```: key of the modified row
- ```log_column```: column of the modified row
- ```log_value```: value of the modified row
- ```log_uuid```: unique identifier of that row change
- ```updated_by```: who did make that change
- ```updated_at```: date and time of the event

we now have to adjust our queries. ```updated_by``` must be populated by the web application on all insert/update queries and our logging table must be manually populated before delete queries:

```php
$db->insert('tablename', ['col1' => 'foo', 'updated_by' => get_current_user_id()]);

$db->update('tablename', ['col1' => 'foo', 'updated_by' => get_current_user_id()], ['id' => 42]);

$db->insert('logs', ['log_event' => 'delete', 'log_table' => 'tablename', 'log_key' => 42, 'log_uuid' => $db->uuid(), 'updated_by' => get_current_user_id()]);
$db->delete('tablename', ['id' => 42]);
```

instead of all this we can let dbhelper magically do the heavy lifting on every insert/update/delete for us:

```php
$db->enable_auto_inject();
```

dbhelper then automatically injects the ```updated_by``` column on all insert/update statements and inserts a log entry before every delete query (all queries are handled, even those who are sent with ```$db->query```).

important note: if we manipulate data outside of our web application, the triggers also work, except with accurate values in ```updated_by```. this is especially true for delete statements (they also work without the manual insert query upfront).

that's it – happy logging.

### wordpress support

this also works for wordpress (using wpdb, prepared statements and stripslashes_deep under the hood):
```php
$db->connect('wordpress');
$db->fetch_var('SELECT col FROM tablename WHERE ID = ?', 1);
```

### return values

as return values dbhelper usually returns associative arrays. if you use it with wordpress, objects are returned. dbhelper throws exceptions on all occured errors.

### static version

here is also a static version with static function calls (makes sense, if you use a single instance of dbhelper):
```php
require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/vielhuber/dbhelper/src/static.php');
db_fetch_var('SELECT col FROM tablename WHERE ID = ?', 1);
```