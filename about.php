<?
session_start();

// Загружаем необходимые файлы
require('./static/connect.php');
require('./static/iniclass.php');
$ini = new TIniFileEx('./static/config.ini');
$lang = parse_ini_file('./lang/'.$ini->read("main","language").'.lang');

// Используемые функции
function check_token() {
$result = false;
if($_SESSION['token'] == $_REQUEST['token']) {
$result = true;
}
$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
return $result;
}

// Если страница отключена, выводить 404.php
if($ini->read('main','about') == "disabled") {
header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', 404);
require('./404.php');
exit();
}

// Запросы в базу данных
if(isset($_SESSION['login'])) {
if(isset($_POST['hidden']) && check_token()) {
$ini->write('main','about',$_POST['hidden']);
$ini->updateFile();
header('Location: /');
exit();
}

if(!empty($_POST['name']) && !empty($_POST['input']) && check_token()) {
$stmt = $mysqli->prepare("UPDATE static SET title=?, text=? WHERE id=1");
$stmt->bind_param('ss', $_POST['name'], $_POST['input']);
$stmt->execute();
header("Location: about");
exit();
}
}

$row = $mysqli->query("SELECT * FROM static WHERE id=1")->fetch_array();
?>
<!doctype html>
<html lang="ru">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?= $ini->read('main','metadesc') ?>">
<meta name="keywords" content="<?= $ini->read('main','metawords') ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= $ini->read('main','sitename') ?>">
<meta property="og:title" content="<?= $row['title'] ?>">
<meta property="og:description" content="<?= $ini->read('main','metadesc') ?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:image" content="/images/social.jpg">

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">

<title><?= $ini->read('main','sitename').' | '.$row['title'] ?></title>

<link href="/css/style.css?ver=<?= filemtime('./css/style.css') ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<script src="/js/jquery.js?ver=<?= filemtime('./js/jquery.js') ?>"></script>
<script src="/js/admin.js?ver=<?= filemtime('./js/admin.js')+filemtime('./static/config.ini') ?>"></script>
<? if(isset($_GET['act']) && ($_GET['act'] == "edit") && isset($_SESSION['login'])) { ?>
<script src="/js/editor.js?ver=<?= filemtime('./static/config.ini') ?>"></script>
<? } ?>
</head>

<body>
<? require('./static/header.php') ?>

<div class="content">
<? if(isset($_GET['act']) && ($_GET['act'] == "edit") && isset($_SESSION['login'])) { ?>
<div class="navibox">
<a class="naviboxlink" href="/admin"><?= $lang['naviadmin'] ?></a> » <?= $lang['editstatic'] ?>
</div>

<div class="box clearfix">
<form autocomplete="off" method="post">
<input name="name" type="text" maxlength="30" placeholder="<?= $lang['titlestatic'] ?>" value="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>" class="fieldtitle">
<textarea name="input" id="area"><?= htmlspecialchars($row['text'], ENT_QUOTES) ?></textarea>
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">

<input name="save" type="submit" class="indent lightbutton" value="<?= $lang['save'] ?>">
<div class="indent added right"><?= $lang['notsaved'] ?></div>
</form>
</div>
<? } else { ?>
<div class="box">
<div class="head clearfix"><h1 class="titlenote"><?= htmlspecialchars($row['title'], ENT_QUOTES) ?></h1></div>

<div class="static">
<?= $row['text'] ?>
</div>
<? if(isset($_SESSION['login'])) { ?>
<div class="func">
<div class="links">
<form method="post">
<input name="hidden" type="hidden" value="disabled">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['hidden'] ?>" class="cleanbutton">
</form>
<form method="get">
<input name="act" type="hidden" value="edit">
<input type="submit" value="<?= $lang['edit'] ?>" class="cleanbutton">
</form>
</div>
</div>
<? } ?>
</div>
<? } ?>
</div>

<? require('./static/footer.php') ?>
</body>

</html>