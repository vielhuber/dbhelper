## installation 
composer require vielhuber/dbhelper

## usage
require __DIR__ . '/vendor/autoload.php';
use vielhuber\dbhelper\DBHelper;
$db = new DBHelper();
$db->connect('pdo','mysql','127.0.0.1','root','root','database');
echo '<pre>';
print_r($db->fetch_all('SELECT * FROM table WHERE ID > ?',1));
print_r($db->fetch_all('SELECT * FROM table WHERE name = ? AND number > ?','david',5));
print_r($db->fetch_row('SELECT ID FROM smd_brand WHERE ID = ?',1));
print_r($db->fetch_var('SELECT ID FROM table WHERE ID = ?',1));
print_r($db->query('INSERT INTO table(`row1`,`row2`) VALUES(?,?,?)',1,2,3));
print_r($db->query('DELETE FROM table WHERE ID = ?',1));
print_r($db->query('UPDATE table SET `row1` = ? WHERE ID = ?',1,2));
$db->insert('tablename',['id'=>1,'name'=>'foo']);
$db->update('tablename',['col1'=>'foo','col2'=>'bar'],['id'=>1]);
echo '</pre>';
