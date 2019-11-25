<?
if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) { header('Location: /'); }

$host='localhost';
$name='';
$user='';
$pass='';

$mysqli = new mysqli($host, $user, $pass, $name);
mysqli_set_charset($mysqli, "utf8");
date_default_timezone_set('Europe/Moscow');
?>