<?
namespace s2w;
require_once '../../basic/php/s2w.basic.mod.php';
require_once 's2w.db.mod.php';
?>
<h1>Testing DB module</h1>
<h3>create table:</h3>
<?
$r = db\query("create table rpc (id serial, key text, value text);");
echo $r;
?>
<h3>insert:</h3>
<?
$r = db\query("insert into rpc (key, value) values ('llave', 'valor')");
echo $r;
?>
<h3>count:</h3>
<?

echo db\query_count("select count(key) from rpc where key='algo';");
?>
<h3>result:</h3>
<?
$M = new db\SQLMatrix(db\query2array("select * from rpc;"));

print_r($M->get("key"));
?>

<h3>insert data</h3>
<?
$r= @\db\query("select count(key) from rpc where key='algo'");

echo pg_num_rows($r);
?>