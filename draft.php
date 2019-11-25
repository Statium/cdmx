<?
session_start();

// Загружаем необходимые файлы
require('./static/connect.php');
require_once('./static/iniclass.php');
$ini = new TIniFileEx('./static/config.ini');
$lang = parse_ini_file('./lang/'.$ini->read("main","language").'.lang');

// Редирект неавторизованных пользователей
if(!isset($_SESSION['login'])) {
header('Location: /');
exit();
}

// Редирект, если не установлен id
if(!isset($_GET['id'])){
header('Location: admin');
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
$id=(int)$_GET['id'];
$row = $mysqli->query("SELECT * FROM draft WHERE id='$id'")->fetch_array();

if(isset($_POST['delete']) && ($delete = (int)$_POST['delete']) && $delete > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM draft WHERE id=?");
$sql->bind_param("i", $delete);
$sql->execute();
header('Location: admin?act=drafts');
exit();
}

if(!empty($_POST['name']) && !empty($_POST['input']) && isset($_POST['save']) && check_token()) {
$stmt = $mysqli->prepare("UPDATE draft SET name=?, text=?, tags=? WHERE id=?");
$name = $_POST['name'];
$text = str_replace("&lt;!--more--&gt;","<!--more-->",$_POST['input']);;
$tags = chop($_POST['tags'], ',.');
$stmt->bind_param('sssi', $name, $text, $tags, $id);
$stmt->execute();
header("Location: draft".$id);
exit();
}

if(isset($_POST['draft']) && check_token()) {
$stmt = $mysqli->prepare("INSERT INTO blog (`name`, `text`, `tags`, `date`) SELECT name, text, tags, ? FROM draft WHERE id=?");
$date = date('Y-m-d');
$stmt->bind_param('si', $date, $id);
$stmt->execute();
$noteid = $mysqli->insert_id;

$sql = $mysqli->prepare("DELETE FROM draft WHERE id=?");
$sql->bind_param("i", $id);
$sql->execute();
header("Location: note".$noteid);
exit();
}

if(!empty($_POST['name']) && !empty($_POST['input']) && isset($_POST['draftedit']) && check_token()) {
$stmt = $mysqli->prepare("INSERT INTO blog (`name`, `text`, `tags`, `date`) VALUES (?,?,?,?)");
$name = $_POST['name'];
$input = str_replace("&lt;!--more--&gt;","<!--more-->",$_POST['input']);
$tags = chop($_POST['tags'], ',.');
$date = date('Y-m-d');
$stmt->bind_param('ssss', $name, $input, $tags, $date);
$stmt->execute();
$noteid = $mysqli->insert_id;

$sql = $mysqli->prepare("DELETE FROM draft WHERE id=?");
$sql->bind_param("i", $id);
$sql->execute();
header("Location: note".$noteid);
exit();
}

// Редирект на 404.php если id не найден
if(!$row['id']) {
header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', 404);
require('./404.php');
exit();
}
?>
<!doctype html>
<html lang="ru">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="robots" content="noindex,nofollow">

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">

<title><?= $ini->read('main','sitename').' | '.$row['name'] ?></title>

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
<a class="naviboxlink" href="/admin"><?= $lang['naviadmin'] ?></a> » <a class="naviboxlink" href="/admin?act=drafts"><?= $lang['drafts'] ?></a> » <?= $lang['navidraft'] ?>
</div>

<div class="box clearfix">
<form autocomplete="off" method="post">
<input name="name" type="text" maxlength="150" placeholder="<?= $lang['notetitle'] ?>" value="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>" class="fieldtitle">
<textarea name="input" id="area"><?= htmlspecialchars($row['text'], ENT_QUOTES); ?></textarea>
<input name="token" type="hidden" value="<?= $_SESSION['token']; ?>">
<input name="tags" type="text" maxlength="70" placeholder="<?= $lang['notetags'] ?>" value="<?= htmlspecialchars($row['tags'], ENT_QUOTES) ?>" class="fieldtags">

<input name="save" type="submit" value="<?= $lang['save'] ?>" class="lightbutton">
<input name="draftedit" type="submit" value="<?= $lang['share'] ?>" class="blackbutton">
<div class="added right"><?= $lang['notsaved'] ?></div>
</form>
</div>
<? } else { ?>
<div class="navibox">
<a class="naviboxlink" href="/admin"><?= $lang['naviadmin'] ?></a> » <a class="naviboxlink" href="/admin?act=drafts"><?= $lang['drafts'] ?></a> » <?= $lang['navidraftview'] ?>
</div>

<div class="box">
<div class="head clearfix">
<h1 class="titlenote"><? if($ini->read('second','num') == "enabled") { echo '#'.$row['id'].' '; } echo htmlspecialchars($row['name'], ENT_QUOTES); ?></h1>

<div class="info"><div data-hint="<?= $lang['createdate'] ?>" class="dateinfo hintleft"><?= date('d.m.Y', strtotime($row['date'])) ?></div></div>
</div>

<div class="text"><?= $row['text'] ?></div>

<div class="func">
<div class="tags"><? if($row['tags']) { echo $row['tags']; } else { echo $lang['tags']; } ?></div>
<? if(isset($_SESSION['login'])) { ?>
<div class="links">
<form method="post">
<input name="delete" type="hidden" value="<?= $row['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<form action="/draft" method="get">
<input name="id" type="hidden" value="<?= $row['id'] ?>">
<input name="act" type="hidden" value="edit">
<input type="submit" value="<?= $lang['edit'] ?>" class="cleanbutton">
</form>
<form method="post">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input name="draft" type="submit" value="<?= $lang['published'] ?>" class="cleanbutton">
</form>
</div>
<? } ?>
</div>
</div>

<div class="boxcomment clearfix">
<div class="headcomment">
<?= $lang['nodraftcomments'] ?>
</div>
</div>
<? } ?>
</div>

<? require('./static/footer.php') ?>
</body>

</html>