<?
session_start();

// Загружаем необходимые файлы
require('./static/connect.php');
require_once('./static/iniclass.php');
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

function check_length($value = "", $min, $max) {
$result = (mb_strlen($value) < $min || mb_strlen($value) > $max);
return !$result;
}

function number_name($number) {
$count = array("", "K", "M", "G", "T");
$i = 0;
while(abs($number) > 1000) {
$number /= 1000;
$number = round($number,1);
$i++;
}
return $number.$count[$i];
}

$client = @$_SERVER['HTTP_CLIENT_IP'];
$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
$remote = @$_SERVER['REMOTE_ADDR'];
 
if(filter_var($client, FILTER_VALIDATE_IP)) {
$ip = $client;
} elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
$ip = $forward;
} else {
$ip = $remote;
}

// Запросы в базу данных
$id=(int)$_GET['id'];
if(!isset($_SESSION['view'][$id])) {
$_SESSION['view'][$id] = 1;
$sql = $mysqli->query("UPDATE blog SET visits=visits+1 WHERE id='$id'");
}

$sql = $mysqli->query("SELECT * FROM blog WHERE id='$id'");
$row = $sql->fetch_array();

if(isset($_SESSION['login'])) {
if(isset($_POST['nremove']) && ($nremove = (int)$_POST['nremove']) && $nremove > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM blog WHERE id=?");
$sql->bind_param("i", $nremove);
$sql->execute();

$sql = $mysqli->prepare("DELETE FROM comments WHERE note=?");
$sql->bind_param("i", $nremove);
$sql->execute();
header('Location: note'.$id);
exit();
}

if(isset($_POST['cremove']) && ($cremove = (int)$_POST['cremove']) && $cremove > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM comments WHERE id=?");
$sql->bind_param("i", $cremove);
$sql->execute();
header('Location: note'.$id);
exit();
}

if(!empty($_POST['name']) && !empty($_POST['input']) && check_token()) {
$stmt = $mysqli->prepare("UPDATE blog SET name=?, text=?, tags=? WHERE id=?");
$name = $_POST['name'];
$text = str_replace("&lt;!--more--&gt;","<!--more-->",$_POST['input']);
$tags = chop($_POST['tags'], ',.');
$stmt->bind_param('sssi', $name, $text, $tags, $id);
$stmt->execute();
header("Location: note".$id);
exit();
}
}

if(!empty($_POST['namecomment']) && !empty($_POST['inputcomment'])) {
if(check_length($_POST["namecomment"], 2, 25) && check_length($_POST['inputcomment'], 2, 500)) {
$stmt = $mysqli->prepare("INSERT INTO comments (`note`, `name`, `userip`, `text`, `date`, `moderate`) VALUES (?,?,?,?,?,?)");
$name = $_POST['namecomment'];
$text = $_POST['inputcomment'];
$date = date('Y-m-d');
if(isset($_SESSION['login']) || $ini->read('second','commentmoderate') == "disabled") {
$moderate = "1";
} else {
$moderate = "0";
}
$stmt->bind_param('issssi', $id, $name, $ip, $text, $date, $moderate);
$stmt->execute();
header('Location: note'.$id);
exit();
}
}

// Редиректы в случае отсутствия или неверного id
if(!isset($_GET['id'])){
header('Location: /');
exit();
}

if(!$row['id']) {
header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', 404);
require('./404.php');
exit();
}

$arr = array("<div" => " <div","<br>" => " ");
$description = strip_tags(strtr($row['text'],$arr));
$description = preg_replace('|[\s]+|s', ' ', $description);
?>
<!doctype html>
<html lang="ru">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<? $description = substr($description, 0, 320); $description = substr($description, 0, strrpos($description, ' ')); $description = rtrim($description, '.,!-;:?'); echo $description."..."; ?>">
<? if($row['tags']) { ?><meta name="keywords" content="<?= $row['tags'] ?>"><? } ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= $ini->read('main','sitename') ?>">
<meta property="og:title" content="<?= $row['name'] ?>">
<meta property="og:description" content="<?= $description."..." ?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:image" content="/images/social.jpg">

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">

<title><?= $row['name'] ?></title>

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
<a class="naviboxlink" href="/admin"><?= $lang['naviadmin'] ?></a> » <?= $lang['naviedit'] ?>
</div>

<div class="box clearfix">
<form autocomplete="off" method="post">
<input name="name" type="text" maxlength="150" placeholder="<?= $lang['notetitle'] ?>" value="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>" class="fieldtitle">
<textarea name="input" id="area"><?= htmlspecialchars($row['text'], ENT_QUOTES) ?></textarea>
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input name="tags" type="text" maxlength="70" placeholder="<?= $lang['notetags'] ?>" value="<?= htmlspecialchars($row['tags'], ENT_QUOTES) ?>" class="fieldtags">
<input type="submit" class="blackbutton" value="<?= $lang['buttonedit'] ?>">
<div class="added right"><?= $lang['notsaved'] ?></div>
</form>
</div>
<? } else { ?>
<div class="box">
<div class="head clearfix">
<h1><? if($ini->read('second','num') == "enabled") { echo '#'.$row['id'].' '; } echo htmlspecialchars($row['name'], ENT_QUOTES); ?></h1>

<div class="info"><? if($ini->read('second','views') == "enabled") { ?><div data-hint="<?= $lang['visit'] ?>" class="visitinfo hintleft"><?= number_name($row['visits']) ?></div><? } ?><div data-hint="<?= $lang['date'] ?>" class="dateinfo hintleft"><?= date('d.m.Y', strtotime($row['date'])) ?></div></div>
</div>

<div class="text"><?= $row['text'] ?></div>

<div class="func">
<div class="tags"><? if($row['tags']) { echo $row['tags']; } else { echo $lang['tags']; } ?></div>
<? if(isset($_SESSION['login'])) { ?>
<div class="links">
<form method="post">
<input name="nremove" type="hidden" value="<?= $row['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<form action="/note" method="get">
<input name="id" type="hidden" value="<?= $row['id'] ?>">
<input name="act" type="hidden" value="edit">
<input type="submit" value="<?= $lang['edit'] ?>" class="cleanbutton">
</form>
</div>
<? } ?>
</div>
</div>

<? if($ini->read('second','comments') == "enabled") { ?>
<div class="boxcomment clearfix">

<a class="linkcomment">
<div class="headcomment">
<?
if($ini->read('second','commentmoderate') == "enabled") {
$sql = "SELECT * FROM comments WHERE note=$id AND moderate=1";
} else {
$sql = "SELECT * FROM comments WHERE note=$id";
}
$total = $mysqli->query($sql)->num_rows;

if($total) {
echo $lang['notecomments'];
} else {
echo $lang['nocomments'];
}
?>
</div>

<div class="addcomment"><?= $lang['newcomment'] ?></div>
</a>

<div class="formcomment">
<form autocomplete="off" method="post">
<input name="namecomment" type="text" placeholder="<?= $lang['name'] ?>" <? if(isset($_SESSION['login'])){ ?> style="background:#e4e4e4 !important;" value="<?= $ini->read('admin','nickname') ?>" readonly <? } ?> maxlength="25" class="nickcomment">
<textarea name="inputcomment" placeholder="<?= $lang['comment'] ?>" maxlength='<?= $ini->read('second','commentssize') ?>' class="textcomment"></textarea>
<div class="footercomment"><div class="charcount"></div><div class="buttoncomment"><input type="submit" class="blackbutton" value="<?= $lang['add'] ?>"><div class="added right"><?= $lang['notsaved'] ?></div></div></div>
</form>
</div>
</div>
<? } elseif($ini->read('second','comments') == "disabled") { ?>
<div class="boxcomment clearfix">
<div class="headcomment">
<?= $lang['disablecomments'] ?>
</div>
</div>
<?
}

if($ini->read('second','comments') == "enabled") {
?>
<div id="boxcomment">
<?
if($ini->read('second','commentmoderate') == "enabled") {
$sql = "SELECT * FROM comments WHERE note=".$id." AND moderate=1";
} else {
$sql = "SELECT * FROM comments WHERE note=".$id."";
}
if($ini->read('second','sortcomments') == "new") {
$sql .= " ORDER BY id DESC LIMIT ".$ini->read('second','commentsview')."";
} else {
$sql .= " ORDER BY id ASC LIMIT ".$ini->read('second','commentsview')."";
}

$result = $mysqli->query($sql);

if($total) {
while($rowc = $result->fetch_assoc()) {
?>
<div class="box">
<div class="headercomment clearfix">
<div class="titlecomment"><?= '<b>'.htmlspecialchars($rowc['name'], ENT_QUOTES).'</b> '.$lang['writes'] ?></div>

<div class="datecomment">
<? if(isset($_SESSION['login'])) { ?>
<label class="hintleft clabel" id="<?= $rowc['id'] ?>">
<div id="#<?= $rowc['id'] ?>" class="dotted"><?= $rowc['userip']; ?></div>
</label>
&ensp;|&ensp;
<?
}
echo date('d.m.Y', strtotime($rowc['date']));
?>
</div>
</div>

<div class="text" data-id="<?= $rowc['id'] ?>"><?= htmlspecialchars($rowc['text'], ENT_QUOTES) ?></div>

<div class="funccomment">
<? if(isset($_SESSION['login'])) { ?>
<form method="post">
<input name="cremove" type="hidden" value="<?= $rowc['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<? } ?>

<? if(!(isset($_SESSION['login']) && ($ini->read('admin','nickname') == $rowc['name']))) { ?>
<input type="button" id="re" data-id="<?= $rowc['name'] ?>" value="<?= $lang['re'] ?>" class="cleanbutton">
<? } ?>
</div>
</div>
<? } ?>
</div>

<? if($total > $ini->read('second','commentsview')) { ?>
<div id="load" data-id='<?= $id ?>' class="navipage"><?= $lang['load'] ?></div>
<?
}
}
}
}
?>
</div>

<? require('./static/footer.php') ?>
</body>

</html>