<?
if(!isset($_SESSION)) { session_start(); }

// Загружаем необходимые файлы
require('./static/connect.php');
require_once('./static/iniclass.php');
$ini = new TIniFileEx('./static/config.ini');
$lang = parse_ini_file('./lang/'.$ini->read("main","language").'.lang');
?>
<!doctype html>
<html lang="ru">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">

<title><?= $ini->read('main','sitename') ?> | Not found</title>

<link href="/css/style.css?ver=<?= filemtime('./css/style.css') ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
</head>

<body>
<? require('./static/header.php') ?>

<div class="content">
<div class="box">
<div class="head clearfix"><h1>404 Not found</h1></div>

<div class="static"><?= $lang['notfound'] ?></div>
</div>
</div>

<? require('./static/footer.php') ?>
</body>

</html>