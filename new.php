<?
session_start();

// Загружаем необходимые файлы
require('./static/connect.php');
require('./static/iniclass.php');
$ini = new TIniFileEx('./static/config.ini');
$lang = parse_ini_file('./lang/'.$ini->read("main","language").'.lang');

// Редирект неавторизованных пользователей
if(!isset($_SESSION['login'])) {
header('Location: /');
exit();
}

// Используемые функции
function check_token() {
$result = false;
if($_SESSION['token'] == $_REQUEST['token']) {
$result = true;
}
$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
return $result;
}

// Запросы в базу данных
if(!empty($_POST['name']) && !empty($_POST['input']) && !isset($_POST['draft']) && check_token()) {
$stmt = $mysqli->prepare("INSERT INTO blog (`name`, `text`, `tags`, `date`) VALUES (?,?,?,?)");
$name = $_POST['name'];
$input = str_replace("&lt;!--more--&gt;","<!--more-->",$_POST['input']);
$tags = chop($_POST['tags'], ',.');
$date = date('Y-m-d');
$stmt->bind_param('ssss', $name, $input, $tags, $date);
$stmt->execute();
header('Location: note'.$mysqli->insert_id);
exit();
}

if(!empty($_POST['name']) && !empty($_POST['input']) && isset($_POST['draft']) && check_token()) {
$stmt = $mysqli->prepare("INSERT INTO draft (`name`, `text`, `tags`, `date`) VALUES (?,?,?,?)");
$name = $_POST['name'];
$input = str_replace("&lt;!--more--&gt;","<!--more-->",$_POST['input']);
$tags = chop($_POST['tags'], ',.');
$date = date('Y-m-d');
$stmt->bind_param('ssss', $name, $input, $tags, $date);
$stmt->execute();
header('Location: draft'.$mysqli->insert_id);
exit();
}
$jlang =  json_encode($lang,JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="ru">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="robots" content="noindex,nofollow">

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">

<title><?= $ini->read('main','sitename').' | '.$lang['navinote'] ?></title>

<link href="/css/style.css?ver=<?= filemtime('./css/style.css') ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<script src="/js/jquery.js?ver=<?= filemtime('./js/jquery.js') ?>"></script>
<script src="/js/admin.js?ver=<?= filemtime('./js/admin.js')+filemtime('./static/config.ini') ?>"></script>
<script src="/js/editor.js?ver=<?= filemtime('./static/config.ini') ?>"></script>
</head>

<body>
<? require('./static/header.php') ?>

<div class="content">

<div class="navibox">
<a class="naviboxlink" href="/admin"><?= $lang['naviadmin'] ?></a> » <?= $lang['navinote'] ?>
</div>

<div class="box clearfix">
<form autocomplete="off" method="post">
<input name="name" type="text" maxlength="150" placeholder="<?= $lang['notetitle'] ?>" class="fieldtitle">
<textarea name="input" id="area"></textarea>
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input name="tags" type="text" maxlength="70" placeholder="<?= $lang['notetags'] ?>" class="fieldtags">

<input name="draft" type="submit" value="<?= $lang['save'] ?>" class="lightbutton">
<input type="submit" value="<?= $lang['share'] ?>" class="blackbutton">
<div class="added right"><?= $lang['notsaved'] ?></div>
</form>
</div>
</div>

<? require('./static/footer.php') ?>
</body>

</html>