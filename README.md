[![build status](https://github.com/vielhuber/dbhelper/actions/workflows/ci.yml/badge.svg)](https://github.com/vielhuber/dbhelper/actions)

# 🍗 dbhelper 🍗

dbhelper is a small php wrapper for mysql/postgres/sqlite databases.

## installation

install once with composer:

```
composer require vielhuber/dbhelper
```

then add this to your project:

```php
require __DIR__ . '/vendor/autoload.php';
use vielhuber\dbhelper\dbhelper;
$db = new dbhelper();
```

## usage

```php
/* connect to database */
$db->connect('pdo', 'mysql', '127.0.0.1', 'username', 'password', 'database', 3306);
$db->connect('pdo', 'postgres', '127.0.0.1', 'username', 'password', 'database', 5432);
$db->connect('pdo', 'sqlite', 'database.db');
$db->connect('pdo', 'sqlite', 'database.db', null, null, null, null, 120); // specify a manual timeout of 120 seconds
$db->connect('pdo', 'mysql', '127.0.0.1', 'username', 'password', null, 3306); // database must not be available

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

/* count */
$db->count('tablename') // 42
$db->count('tablename', ['col1' => 'foo']) // 7

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

/* delete table */
$db->delete_table('tablename');

/* create table */
$db->create_table('tablename', [
    'id' => 'SERIAL PRIMARY KEY', // use INTEGER instead of SERIAL on sqlite to get auto ids
    'col1' => 'varchar(255)',
    'col2' => 'varchar(255)',
    'col3' => 'varchar(255)'
]);

/* create if not exists and connect to database */
$db->connect_with_create('pdo', 'mysql', '127.0.0.1', 'username', 'password', 'database', 3306);
    // this is a shorthand for
    $db->connect('pdo', 'mysql', '127.0.0.1', 'username', 'password', null, 3306);
    $db->create_database('database');
    $db->disconnect();
    $db->connect('pdo', 'mysql', '127.0.0.1', 'username', 'password', 'database', 3306);

/* delete database */
$db->disconnect_with_delete();
    // this is a shorthand for
    $db->disconnect();
    $db->connect('pdo', 'mysql', '127.0.0.1', 'username', 'password', null, 3306);
    $db->delete_database('database');
    $db->disconnect();

/* raw queries */
$db->query('INSERT INTO tablename(row1, row2) VALUES(?, ?, ?)', 1, 2, 3);
$db->query('UPDATE tablename SET row1 = ? WHERE ID = ?', 1, 2);
$db->query('DELETE FROM tablename WHERE ID = ?', 1);

/* quickly debug raw queries */
$db->debug('DELETE FROM tablename WHERE row1 = ?', null); // DELETE FROM tablename WHERE row1 IS NULL

/* last insert id */
$db->insert('tablename', ['col1' => 'foo']);
$db->last_insert_id();

/* some more little helpers */
$db->get_tables() // ['tablename', ...]
$db->has_table('tablename') // true
$db->get_columns('tablename') // ['col1', 'col2', ...]
$db->has_column('tablename', 'col1') // true
$db->get_datatype('tablename', 'col1') // varchar
$db->get_primary_key('tablename') // id
$db->uuid() // generate uuid (v4) from inside the database
$db->get_foreign_keys('users') // [['address_id' => ['addresses','id'], ...]
$db->is_foreign_key('users', 'address_id') // true
$db->get_foreign_tables_out('users') // [['addresses' => [['address_id','id']], ...]
$db->get_foreign_tables_in('addresses') // [['users' => [['address_id','id']], ...]

/* handle duplicates */
$db->get_duplicates() // ['count' => ['tbl1' => 3, 'tbl2' => 17], 'data' => ['tbl1' => [...], 'tbl2' => [...]]
$db->delete_duplicates('tablename') // delete duplicates based on all columns except the primary key
$db->delete_duplicates('tablename', ['common_col1','common_col1','common_col1']) // based on specific columns
$db->delete_duplicates('tablename', ['common_col1','common_col1','common_col1'], false) // null values are considered equal by default; you can disable this untypical behaviour for sql with "false"
$db->delete_duplicates('tablename', ['common_col1','common_col1','common_col1'], true, ['id' => 'asc']) // keep row with lowest primary key "id" (normally this is 'id' => 'desc')
$db->delete_duplicates('tablename', ['common_col1','common_col1','common_col1'], true, ['id' => 'asc'], false) // case insensitive match (normally this is case sensitive)

/* globally trim values */
$db->trim_values() // [['table' => 'tbl1', 'column' => 'col1', 'id' => 1, 'before' => ' foo', 'after' => 'foo'], ...]
$db->trim_values(false) // by default trim_values does a dry run (no updates)
$db->trim_values(true) // do real updates
$db->trim_values(false, ['table1', 'table2' => ['col1', 'col2']]) // ignore tables and columns

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

dbhelper can support setting up a mature logging system on mysql/postgres databases.

```php
$db = new dbhelper([
    'logging_table' => 'logs',
    'exclude' => [
        'tables' => ['table1'],
        'columns' => ['table2' => ['col1', 'col2', 'col3']]
    ],
    'delete_older' => 12, // months
    'updated_by' => get_current_user_id()
]);
$db->connect('...');
$db->setup_logging();
```

`setup_logging()` does four things:

-   it creates a logging table (if not exists)
-   it appends a single column `updated_by` to every table in the database (if not exists)
-   it creates triggers for all insert/update/delete events (if not exists)
-   it deletes old logging entries based on the `delete_older` option

you should run this method after a schema change (e.g. in your migrations) and you can also run it on a daily basis via cron. it is recommened to exclude blob/bytea columns.

the logging table has the following schema:

-   `id`: unique identifier of that single change
-   `log_event`: insert/update/delete
-   `log_table`: name of the table of the modified row
-   `log_key`: key of the modified row
-   `log_column`: column of the modified row
-   `log_value`: value of the modified row
-   `log_uuid`: unique identifier of that row change
-   `updated_by`: who did make that change
-   `updated_at`: date and time of the event

we now have to adjust our queries. `updated_by` must be populated by the web application on all insert/update queries and our logging table must be manually populated before delete queries:

```php
$db->insert('tablename', ['col1' => 'foo', 'updated_by' => get_current_user_id()]);

$db->update('tablename', ['col1' => 'foo', 'updated_by' => get_current_user_id()], ['id' => 42]);

$db->insert('logs', [
    'log_event' => 'delete',
    'log_table' => 'tablename',
    'log_key' => 42,
    'log_uuid' => $db->uuid(),
    'updated_by' => get_current_user_id()
]);
$db->delete('tablename', ['id' => 42]);
```

instead of all this we can let dbhelper magically do the heavy lifting on every insert/update/delete for us:

```php
$db->enable_auto_inject();
```

dbhelper then automatically injects the `updated_by` column on all insert/update statements and inserts a log entry before every delete query (all queries are handled, even those who are sent with `$db->query`).

important note: if we manipulate data outside of our web application, the triggers also work, except with accurate values in `updated_by`. this is especially true for delete statements (they also work without the manual insert query upfront).

call the following helper functions, if you (temporarily) need to disable logging by triggers:

```php
$db->disable_logging();
$db->query('DELETE * FROM mega_big_table');
$db->enable_logging();
```

that's it – happy logging.

### wordpress support

this also works for wordpress (using wpdb, prepared statements and stripslashes_deep under the hood):

```php
$db->connect('wordpress');
$db->fetch_var('SELECT col FROM tablename WHERE ID = ?', 1);
```

### locking in sqlite

sqlite is nice but database locking can be tricky.\
dbhelper provides a default timeout of `60` seconds, which prevents most database locks.\
you can manually define a timeout in the `connect()` function.\
checkout the following sqlite lock tests:

-   `php tests/lock/run.php 1`: runs into database locking
-   `php tests/lock/run.php 120`: does not run into database locking

also consider enabling [wal](https://sqlite.org/wal.html) via `$db->query('PRAGMA journal_mode=WAL;');`.

### return values

as return values after fetching results dbhelper usually returns associative arrays.\
if you use it with wordpress, objects are returned.\
dbhelper throws exceptions on all occured errors.\
on an `insert` operation, the primary key (id) is returned.\
on any `delete`, `update` or even `query` operation, the number of affected rows are returned.

### static version

here is also a static version with static function calls (this makes sense, if you use a single instance of dbhelper):

```php
$db = new dbhelper();
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/vielhuber/dbhelper/src/static.php';
db_connect('pdo', 'mysql', '127.0.0.1', 'username', 'password', 'database', 3306);
db_fetch_var('SELECT col FROM tablename WHERE ID = ?', 1);
```
