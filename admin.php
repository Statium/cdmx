<?
ob_start();
session_start();

// Загружаем необходимые файлы
require('./static/connect.php');
require('./static/iniclass.php');
require('./static/navigation.php');
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

function digit_case($count,$firststr,$secondstr,$thirdstr) {
$ost = $count % 100;
if($ost > 9 && $ost < 20) {
return $thirdstr;
} else {
$ost = $ost % 10;
if($ost == 1) {
return $firststr;
} elseif($ost > 1 && $ost < 5) {
return $secondstr;
} else {
return $thirdstr;
}
}
}

function get_filesize($file) {
global $ini, $lang;
$filesize = filesize($file);
if($filesize > 1024) {
$filesize = ($filesize/1024);
if($filesize > 1024) {
$filesize = ($filesize/1024);
if($filesize > 1024) {
$filesize = ($filesize/1024);
$filesize = round($filesize, 1);
return $filesize.' '.$lang['gb'];
} else {
$filesize = round($filesize, 1);
return $filesize.' '.$lang['mb'];
}
} else {
$filesize = round($filesize, 1);
return $filesize.' '.$lang['kb'];
}
} else {
$filesize = round($filesize, 1);
return $filesize.' '.$lang['b'];
}
}

// Выход из панели управления
if(isset($_GET['logout'])) {
unset($_SESSION['login']);
unset($_SESSION['token']);

header('Location: admin');
exit();
}

// Запросы в базу данных
$paging = new Paging($mysqli);
$mrecords = $paging->get_page("SELECT * FROM blog WHERE id ORDER BY id DESC");

$cpaging = new Paging($mysqli);
$mcomments = $cpaging->get_page("SELECT * FROM comments WHERE id ORDER BY moderate=0 DESC, id DESC");

$dpaging = new Paging($mysqli);
$mdraft = $dpaging->get_page("SELECT * FROM draft WHERE id ORDER BY id DESC");

$fpaging = new Paging($mysqli);
$mfiles = $fpaging->get_page("SELECT * FROM files WHERE id ORDER BY id DESC");

if(isset($_SESSION['login'])) {
if(isset($_POST['rremove']) && ($rremove = (int)$_POST['rremove']) && $rremove > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM blog WHERE id=?");
$sql->bind_param("i", $rremove);
$sql->execute();
if($paging->get_cur_page() != 1) {
header('Location: admin?act=records&page='.$paging->get_cur_page());
} else {
header('Location: admin?act=records');
}
}

if(isset($_POST['cremove']) && ($cremove = (int)$_POST['cremove']) && $cremove > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM comments WHERE id=?");
$sql->bind_param("i", $cremove);
$sql->execute();
if($cpaging->get_cur_page() != 1) {
header('Location: admin?act=comments&page='.$cpaging->get_cur_page());
} else {
header('Location: admin?act=comments');
}
}

if(isset($_POST['dremove']) && ($dremove = (int)$_POST['dremove']) && $dremove > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM draft WHERE id=?");
$sql->bind_param("i", $dremove);
$sql->execute();
if($dpaging->get_cur_page() != 1) {
header('Location: admin?act=drafts&page='.$dpaging->get_cur_page());
} else {
header('Location: admin?act=drafts');
}
}

if(isset($_POST['fremove']) && ($fremove = (int)$_POST['fremove']) && $fremove > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM files WHERE id=?");
$sql->bind_param("i", $fremove);
$sql->execute();
unlink($_POST['flink']);
if($fpaging->get_cur_page() != 1) {
header('Location: admin?act=files&page='.$fpaging->get_cur_page());
} else {
header('Location: admin?act=files');
}
}

if(isset($_POST['csave']) && ($csave = (int)$_POST['csave']) && $csave > 0 && check_token()) {
$sql = $mysqli->prepare("UPDATE comments SET moderate=1 WHERE id=?");
$sql->bind_param("i", $csave);
$sql->execute();
header('Location: admin?act=comments');
exit();
}

if(isset($_POST['upload'])) {
$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$name = uniqid().'.'.mb_strtolower($ext);
$filename = "./uploads/".$name;

$stmt = $mysqli->prepare("INSERT INTO files (`name`, `date`) VALUES (?,?)");
$date = date('Y-m-d H:i:s');
$stmt->bind_param('ss', $name, $date);
$stmt->execute();

if(move_uploaded_file($_FILES['file']['tmp_name'], $filename)) {
header('Location: admin?act=files');
exit();
}
}
}

// Счетчики главной страницы панели управления
$total = $mysqli->query("SELECT * FROM blog")->num_rows;
$comments = $mysqli->query("SELECT * FROM comments")->num_rows;
$drafts = $mysqli->query("SELECT * FROM draft")->num_rows;
$moderate = $mysqli->query("SELECT * FROM comments WHERE moderate=0")->num_rows;
$row = $mysqli->query("SELECT SUM(visits) FROM blog")->fetch_array();
$files = $mysqli->query("SELECT * FROM files")->num_rows;

// Алгоритмы редиректа постраничной навигации
if(isset($_GET['act']) && $_GET['act'] == "records" && isset($_GET['page'])) {
if($_GET['page'] > $paging->get_total_pages()) {
header('Location: admin?act=records&page='.$paging->get_cur_page());
} elseif($_GET['page'] < "1" || !is_numeric($_GET['page'])) {
header('Location: admin?act=records');
}
}

if(isset($_GET['act']) && $_GET['act'] == "comments" && isset($_GET['page'])) {
if($_GET['page'] > $cpaging->get_total_pages()) {
header('Location: admin?act=comments&page='.$cpaging->get_cur_page());
} elseif($_GET['page'] < "1" || !is_numeric($_GET['page'])) {
header('Location: admin?act=comments');
}
}

if(isset($_GET['act']) && $_GET['act'] == "drafts" && isset($_GET['page'])) {
if($_GET['page'] > $dpaging->get_total_pages()) {
header('Location: admin?act=drafts&page='.$dpaging->get_cur_page());
} elseif($_GET['page'] < "1" || !is_numeric($_GET['page'])) {
header('Location: admin?act=drafts');
}
}

if(isset($_GET['act']) && $_GET['act'] == "files" && isset($_GET['page'])) {
if($_GET['page'] > $fpaging->get_total_pages()) {
header('Location: admin?act=files&page='.$fpaging->get_cur_page());
} elseif($_GET['page'] < "1" || !is_numeric($_GET['page'])) {
header('Location: admin?act=files');
}
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

<title><?= $ini->read('main','sitename').' | '.$lang['naviadmin'] ?></title>

<link href="/css/style.css?ver=<?= filemtime('./css/style.css') ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<script src="/js/jquery.js?ver=<?= filemtime('./js/jquery.js') ?>"></script>
<script src="/js/admin.js?ver=<?= filemtime('./js/admin.js')+filemtime('./static/config.ini') ?>"></script>
</head>

<body>
<? require('./static/header.php') ?>

<div class="content">
<? if(isset($_SESSION['login'])) { ?>
<div class="navibox">
<a class="adminlink" href="/admin"><?= $lang['basic'] ?></a>
<a class="adminlink" href="/admin?act=records"><?= $lang['records'] ?></a>
<a class="adminlink" href="/admin?act=comments"><? echo $lang['admincomments']; if($moderate > 0 && $ini->read('second','commentmoderate') == "enabled") { ?> <span class="admincount"><? echo $moderate; ?></span><? } ?></a>
<a class="adminlink" href="/admin?act=drafts"><?= $lang['admindrafts'] ?></a>
<a class="adminlink" href="/admin?act=files"><?= $lang['files'] ?></a>
<a class="adminlink" href="/admin?act=settings"><?= $lang['settings'] ?></a>
</div>

<? if(!isset($_GET['act'])) { ?>
<div class="box clearfix">
<div class="adminnote"><?= $lang['maininfo'] ?></div>

<div class="linkone">
<a class="labellink" href="/">
<div class="labelindex"><?= $lang['main'] ?></div>
</a>
</div>
<div class="linkone">
<a class="labellink" href="/new">
<div class="labelnew"><?= $lang['new'] ?></div>
</a>
</div>
<div class="linkone">
<a class="labellink" href="/admin?act=comments">
<div class="labelcomm"><?= $lang['admincomments'] ?></div>
</a>
</div>
<div class="linkone">
<a class="labellink" href="/admin?act=settings">
<div class="labelsettings"><?= $lang['settings'] ?></div>
</a>
</div>
<div class="linkone">
<a class="labellink" href="/admin?logout">
<div class="labellogout"><?= $lang['logout'] ?></div>
</a>
</div>
</div>

<div class="box clearfix">
<div class="adminnote"><?= $lang['mainstats'] ?></div>

<div class="countone">
<div class="count"><?= $total ?></div>
<div class="label"><?= digit_case($total,$lang['countnote'],$lang['countnotes'],$lang['countnotestwo']) ?></div>
</div>
<div class="countone">
<div class="count"><?= $drafts ?></div>
<div class="label"><?= digit_case($drafts,$lang['countdraft'],$lang['countdrafts'],$lang['countdraftstwo']) ?></div>
</div>
<div class="countone">
<div class="count"><?= $comments ?></div>
<div class="label"><?= digit_case($comments,$lang['countcomment'],$lang['countcomments'],$lang['countcommentstwo']) ?></div>
</div>
<div class="countone">
<div class="count"><? if($ini->read('second','commentmoderate') == "enabled") { echo $moderate; } else { echo "0"; } ?></div>
<div class="label"><?= $lang['verified'] ?></div>
</div>
<div class="countone">
<div class="count"><?= number_name($row[0]) ?></div>
<div class="label"><? if($row[0] > 1000) { echo $lang['countviewstwo']; } else { echo digit_case($row[0],$lang['countview'],$lang['countviews'],$lang['countviewstwo']); } ?></div>
</div>
</div>
<? } elseif(isset($_GET['act']) && $_GET['act'] == "records") { ?>
<div class="box">
<div class="adminnote"><?= $lang['recordinfo'] ?></div>
<? if(!$total) { ?>
<div class="moderate">
<?= $lang['norecord'] ?>
</div>
<? } ?>
</div>
<?
if($total) {
while($rowr = $mrecords->fetch_assoc()) {
?>
<div class="box">
<div class="headercomment clearfix">
<div class="moderatetitle">
<a class="moderatelink" href="/note<?= $rowr['id'] ?>">
<?
if($ini->read('second','num') == "enabled") { echo '#'.$rowr['id'].' '; }
echo htmlspecialchars($rowr['name'], ENT_QUOTES);
?>
</a>
</div> 
</div>
<div class="text">
<?
$arr = array("<div" => " <div","<br>" => " ");
$string = strip_tags(strtr($rowr['text'],$arr));
$string = preg_replace('|[\s]+|s', ' ', $string);

if(!$string) {
echo "<div class='isnull'>". $lang['isnull'] ."</div>";
} elseif(strlen($string) > 500) {
$string = substr($string, 0, 500); $string = substr($string, 0, strrpos($string, ' ')); $string = rtrim($string, '.,!-;:?'); echo $string."…"; 
} else { echo $string; }
?>
</div>
<div class="funccomment">
<form method="post">
<input name="rremove" type="hidden" value="<?= $rowr['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<form action="/note" method="get">
<input name="id" type="hidden" value="<?= $rowr['id'] ?>">
<input name="act" type="hidden" value="edit">
<input type="submit" value="<?= $lang['edit'] ?>" class="cleanbutton">
</form>
</div>
</div>
<?
}
}
if($paging->get_total_pages() > 1) {
?>
<div class="navibox">
<?= $paging->get_prev_page_link().' '.$paging->get_page_links().' '.$paging->get_next_page_link() ?>
</div>
<?
}
} elseif(isset($_GET['act']) && $_GET['act'] == "comments") { ?>
<div class="box">
<div class="adminnote"><?= $lang['commentinfo'] ?></div>
<? if(!$comments) { ?>
<div class="moderate">
<?= $lang['nocomment'] ?>
</div>
<? } ?>
</div>
<?
if($comments) {
while($rowc = $mcomments->fetch_assoc()) {
?>
<div class="box <? if($rowc['moderate'] == 0 && $ini->read('second','commentmoderate') == "enabled") { echo "check"; } ?>">
<div class="headercomment clearfix">
<div class="moderatetitle"><b><?= htmlspecialchars($rowc['name'], ENT_QUOTES) ?></b> <?= $lang['tonote'] ?> <a class="naviboxlink" href="./note<?= $rowc['note'] ?>">#<?= $rowc['note'] ?></a> <? if($rowc['moderate'] == 0 && $ini->read('second','commentmoderate') == "enabled") { ?><label class="new"><?= $lang['cnew'] ?></label><? } ?></div>

<div class="draftdate"><label class="hintleft clabel" id="<?= $rowc['id'] ?>"> <div id="#<?= $rowc['id'] ?>" class="dotted"><?= $rowc['userip'].'</div></label>&ensp;|&ensp;'.date('d.m.Y', strtotime($rowc['date'])) ?></div>
</div>

<div class="text"><?= htmlspecialchars($rowc['text'], ENT_QUOTES) ?></div>

<div class="funccomment">
<form method="post">
<input name="cremove" type="hidden" value="<?= $rowc['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<? if($rowc['moderate'] == 0 && $ini->read('second','commentmoderate') == "enabled") { ?>
<form method="post">
<input name="csave" type="hidden" value="<?= $rowc['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['check'] ?>" class="cleanbutton">
</form>
<? } ?>
</div>
</div>
<?
}
}
if($cpaging->get_total_pages() > 1) {
?>
<div class="navibox">
<?= $cpaging->get_prev_page_link().' '.$cpaging->get_page_links().' '.$cpaging->get_next_page_link() ?>
</div>
<?
}
} elseif(isset($_GET['act']) && $_GET['act'] == "drafts") {
?>
<div class="box">
<div class="adminnote"><?= $lang['draftinfo'] ?></div>
<? if(!$drafts) { ?>
<div class="moderate">
<?= $lang['nodraft'] ?>
</div>
<? } ?>
</div>
<?
if($drafts) {
while($rowd = $mdraft->fetch_assoc()) {
?>
<div class="box">
<div class="headercomment clearfix">
<div class="moderatetitle"><a class="moderatelink" href="/draft<?= $rowd['id'] ?>">
<?
if($ini->read('second','num') == "enabled") { echo '#'.$rowd['id'].' '; }
echo htmlspecialchars($rowd['name'], ENT_QUOTES);
?>
</a></div> 
<div class="draftdate"><?= date('d.m.Y', strtotime($rowd['date'])) ?></div>
</div>
<div class="text">
<?
$arr = array("<div" => " <div","<br>" => " ");
$string = strip_tags(strtr($rowd['text'],$arr));
$string = preg_replace('|[\s]+|s', ' ', $string);

if(!$string) {
echo "<div class='isnull'>". $lang['isnull'] ."</div>";
} elseif(strlen($string) > 400) {
$string = substr($string, 0, 400); $string = substr($string, 0, strrpos($string, ' ')); $string = rtrim($string, '.,!-;:?'); echo $string."…"; 
} else { echo $string; }
?>
</div>
<div class="funccomment">
<form method="post">
<input name="dremove" type="hidden" value="<?= $rowd['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<form action="/draft" method="get">
<input name="id" type="hidden" value="<?= $rowd['id'] ?>">
<input name="act" type="hidden" value="edit">
<input type="submit" value="<?= $lang['edit'] ?>" class="cleanbutton">
</form>
</div>
</div>
<?
}
if($dpaging->get_total_pages() > 1) {
?>
<div class="navibox">
<?= $dpaging->get_prev_page_link().' '.$dpaging->get_page_links().' '.$dpaging->get_next_page_link() ?>
</div>
<?
}
}
} elseif(isset($_GET['act']) && $_GET['act'] == "files") {
?>
<div class="box">
<div class="adminnote clearfix">
<div class="filenote left"><?= $lang['fileinfo'] ?></div>

<label>
<form enctype="multipart/form-data" action="/admin?act=files" method="POST">
<input name="MAX_FILE_SIZE" type="hidden" value="3000000">
<input name="upload" type="hidden">
<input name="file" type="file" class="inputfile" onchange="javascript:this.form.submit();">
<div class="ubutton"><?= $lang['ubutton'] ?></div>
</form>
</label>
</div>
<? if(!$files) { ?>
<div class="moderate">
<?= $lang['nofile'] ?>
</div>
<? } ?>
</div>
<?
if($files) {
while($rowf = $mfiles->fetch_assoc()) {
?>
<div class="box">
<div class="headercomment clearfix">
<div class="moderatetitle"><div style="display:none" id="<?= $rowf['id'] ?>"><?= $_SERVER['SERVER_NAME']."/uploads/".$rowf['name'] ?></div><?= "<a class='filelink' href='./uploads/".$rowf['name']."'>".$rowf['name']."</a>" ?></div> 
<div class="draftdate">
<? echo get_filesize('./uploads/'.$rowf['name']).'&ensp;|&ensp;'.date("d.m.Y", strtotime($rowf['date'])) ?>
</div>
</div>
<div class="funccomment">
<form method="post">
<input name="fremove" type="hidden" value="<?= $rowf['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input name="flink" type="hidden" value="<?= './uploads/'. $rowf['name'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<button type="submit" class="cleanbutton" onclick="copy('#<?= $rowf['id'] ?>')"><?= $lang['copylink'] ?></button>
<div class="success"><?= $lang['success'] ?></div>
</div>
</div>
<?
}
if($fpaging->get_total_pages() > 1) {
?>
<div class="navibox">
<?= $fpaging->get_prev_page_link().' '.$fpaging->get_page_links().' '.$fpaging->get_next_page_link() ?>
</div>
<?
}
}
} elseif(isset($_GET['act']) && $_GET['act'] == "settings") {
?>
<div class="box clearfix">
<div class="adminnote"><?= $lang['mainset'] ?></div>
<form autocomplete="off" class="adminlogin" id="main" action="./admin?act=settings" method="post">
<div class="setting clearfix">
<div class="settingname"><?= $lang['mainname'] ?></div>
<div class="settinginput">
<input name="name" type="text" maxlength="30" placeholder="<?= $lang['maindescname'] ?>" value="<?= htmlspecialchars($ini->read('main','sitename')) ?>" class="field">
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['maindesc'] ?></div>
<div class="settinginput">
<input name="desc" type="text" maxlength="50" placeholder="<?= $lang['maindescdesc'] ?>" value="<?= htmlspecialchars($ini->read('main','desc')) ?>" class="field">
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['mainmetadesc'] ?></div>
<div class="settinginput">
<textarea name="metadesc" id="metadesc" class="area"><?= $ini->read('main','metadesc') ?></textarea>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['mainmetawords'] ?></div>
<div class="settinginput">
<textarea name="metawords" id="metawords" class="area"><?= $ini->read('main','metawords') ?></textarea>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['maincount'] ?></div>
<div class="settinginput">
<textarea name="liveinternet" class="area"><?= $ini->read('main','liveinternet') ?></textarea>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['mainabout'] ?></div>
<div class="settinginput">
<select name="about" class="select">
<option value="enabled" <? if($ini->read('main','about') == "enabled") { echo "selected"; } ?>><?= $lang['mainaboutyes'] ?></option>
<option value="disabled" <? if($ini->read('main','about') == "disabled") { echo "selected"; } ?>><?= $lang['mainaboutno'] ?></option>
</select>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['mainlang'] ?></div>
<div class="settinginput">
<select name="lang" class="select">
<?
foreach(scandir('./lang',1) as $file) {
if(preg_match('/\.(lang)/', $file)) {
$language = parse_ini_file('./lang/'.$file);
?>
<option <? if($ini->read("main","language") == substr($file,0,strrpos($file,"."))) { echo "selected"; } ?> value="<?= substr($file,0,strrpos($file,".")); ?>"><?= $language['language'] ?></option>
<?
}
}
?>
</select>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['maincopy'] ?></div>
<div class="settinginput">
<input name="copy" type="text" maxlength="50" placeholder="<?= $lang['maindesccopy'] ?>" class="field" value="<?= htmlspecialchars($ini->read('main','copyrights')) ?>">
</div>
</div>
<input name="mainset" type="hidden">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="button" class="lightbutton" value="<?= $lang['save'] ?>" onclick="ajaxform('mainsaved', 'main')">
<div id="mainsaved" class="saved right"></div>
</form>
</div>
<div class="box clearfix">
<div class="adminnote"><?= $lang['secondset'] ?></div>
<form autocomplete="off" class="adminlogin" id="second" action="./admin?act=settings" method="post">
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondnotes'] ?></div>
<div class="settinginput">
<input name="notesview" type="text" maxlength="2" value="<?= $ini->read('second','notesview') ?>" class="minfield"><label><?= $lang['secondnoteshint'] ?></label>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondsortnotes'] ?></div>
<div class="settinginput">
<select name="sortnotes" class="minselect">
<option value="new" <? if($ini->read('second','sortnotes') == "new") { echo "selected"; } ?>><?= $lang['secondnew'] ?></option>
<option value="old" <? if($ini->read('second','sortnotes') == "old") { echo "selected"; } ?>><?= $lang['secondold'] ?></option>
</select>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondnum'] ?></div>
<div class="settinginput">
<select name="num" class="minselect">
<option value="enabled" <? if($ini->read('second','num') == "enabled") { echo "selected"; } ?>><?= $lang['secondcheckyes'] ?></option>
<option value="disabled" <? if($ini->read('second','num') == "disabled") { echo "selected"; } ?>><?= $lang['secondcheckno'] ?></option>
</select>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondviews'] ?></div>
<div class="settinginput">
<select name="views" class="minselect">
<option value="enabled" <? if($ini->read('second','views') == "enabled") { echo "selected"; } ?>><?= $lang['secondviewsyes'] ?></option>
<option value="disabled" <? if($ini->read('second','views') == "disabled") { echo "selected"; } ?>><?= $lang['secondviewsno'] ?></option>
</select>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondcomments'] ?></div>
<div class="settinginput">
<select name="comments" class="minselect">
<option value="enabled" <? if($ini->read('second','comments') == "enabled") { echo "selected"; } ?>><?= $lang['secondviewsyes'] ?></option>
<option value="disabled" <? if($ini->read('second','comments') == "disabled") { echo "selected"; } ?>><?= $lang['secondviewsno'] ?></option>
</select>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondsortcomments'] ?></div>
<div class="settinginput">
<select name="sortcomments" class="minselect">
<option value="new" <? if($ini->read('second','sortcomments') == "new") { echo "selected"; } ?>><?= $lang['secondnew'] ?></option>
<option value="old" <? if($ini->read('second','sortcomments') == "old") { echo "selected"; } ?>><?= $lang['secondold'] ?></option>
</select>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondview'] ?></div>
<div class="settinginput">
<input name="commentsview" type="text" maxlength="2" value="<?= $ini->read('second','commentsview'); ?>" class="minfield"><label><?= $lang['secondviewhint'] ?></label>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondsize'] ?></div>
<div class="settinginput">
<input name="commentssize" type="text" maxlength="4" value="<?= $ini->read('second','commentssize') ?>" class="minfield"><label><?= $lang['secondsizehint'] ?></label>
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['secondcheck'] ?></div>
<div class="settinginput">
<select name="moderate" class="minselect">
<option value="enabled" <? if($ini->read('second','commentmoderate') == "enabled") { echo "selected"; } ?>><?= $lang['secondcheckyes'] ?></option>
<option value="disabled" <? if($ini->read('second','commentmoderate') == "disabled") { echo "selected"; } ?>><?= $lang['secondcheckno'] ?></option>
</select>
</div>
</div>

<input name="secondset" type="hidden">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="button" value="<?= $lang['save'] ?>" onclick="ajaxform('secondsaved', 'second')" class="lightbutton">
<div id="secondsaved" class="saved right"></div>
</form>
</div>

<div class="box clearfix">
<div class="adminnote"><?= $lang['adminset'] ?></div>
<form autocomplete="off" class="adminlogin" id="admin" action="./admin?act=settings" method="post">
<div class="setting clearfix">
<div class="settingname"><?= $lang['adminnick'] ?></div>
<div class="settinginput">
<input name="nickname" type="text" maxlength="20" id="nickname" value="<?= htmlspecialchars($ini->read('admin','nickname')) ?>" class="adminfield">
</div>
</div>
<div class="setting clearfix">
<div class="settingname"><?= $lang['adminkey'] ?></div>
<div class="settinginput">
<input name="newpass" id="password" type="text" maxlength="20" placeholder="<?= $lang['admindesckey'] ?>" value="" class="adminfield"><label id="result"></label>
</div>
</div>

<input name="adminset" type="hidden">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="button" value="<?= $lang['save'] ?>" onclick="ajaxform('adminsaved', 'admin')" class="lightbutton">
<div id="adminsaved" class="saved right"></div>
</form>
</div>
<?
} else {
header('Location: admin');
exit();
}
} else {
?>
<div class="box clearfix">
<div class="adminnote"><?= $lang['logininfo'] ?></div>
<form id="login" method="post">
<div class="adminlogin"><input placeholder="<?= $lang['loginname'] ?>" type="text" class="adminform" name="login"></div>
<div class="adminlogin"><input placeholder="<?= $lang['loginkey'] ?>" type="password" class="adminform" name="password"></div>

<input name="adminlogin" type="hidden">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<div class="adminfooter"><div class="login"><input type="button" value="<?= $lang['enter'] ?>" onclick="loginform('adminlogin')" class="blackbutton"></div><div id="adminlogin" class="saved left"></div></div>
</form>
</div>
<? } ?>
</div>

<?
require('./static/footer.php');
ob_end_flush();
?>
</body>

</html>