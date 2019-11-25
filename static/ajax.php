<?
session_start();
if(basename(__FILE__) == basename($_SERVER['PHP_SELF']) && !$_SERVER['HTTP_X_REQUESTED_WITH']) { header('Location: /'); }
include('../static/connect.php');
include_once('../static/iniclass.php');
$ini = new TIniFileEx('../static/config.ini');
$lang = parse_ini_file('../lang/'.$ini->read("main","language").'.lang');

// Загрузка комментариев ajax в записях
if(isset($_GET['num'])) {
$num = $_GET['num'];
$id = $_GET['id'];

if($ini->read('second','commentmoderate') == "enabled") {
$sql = "SELECT * FROM comments WHERE note=".$id." AND moderate=1";
} else {
$sql = "SELECT * FROM comments WHERE note=".$id."";
}
if($ini->read('second','sortcomments') == "new") {
$sql .= " ORDER BY id DESC LIMIT ".$num.", ".$ini->read('second','commentsview')."";
} else {
$sql .= " ORDER BY id ASC LIMIT ".$num.", ".$ini->read('second','commentsview')."";
}

$result = $mysqli->query($sql);
$total = $result->num_rows;
$num = $num+$ini->read("second","commentsview");

if($total > 0) {          
while($row = $result->fetch_assoc()) {
?>
<div class="box">
<div class="headercomment clearfix">
<div class="titlecomment"><b><?= htmlspecialchars($row['name'], ENT_QUOTES) ?></b> <?= $lang['writes'] ?></div>

<div class="datecomment">
<? if(isset($_SESSION['login'])) { ?>
<label class="hintleft clabel" id="<?= $row['id'] ?>">
<div id="#<?= $row['id'] ?>" class="dotted"><?= $row['userip']; ?></div>
</label>
&ensp;|&ensp;
<?
}
echo date('d.m.Y', strtotime($row['date']));
?>
</div>
</div>

<div class="text"><?= htmlspecialchars($row['text'], ENT_QUOTES) ?></div>

<div class="funccomment">
<? if(isset($_SESSION['login'])) { ?>
<form method="post">
<input name="cremove" type="hidden" value="<?= $row['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<? } ?>

<input type="button" id="re" data-id="<?= $row['name'] ?>" value="<?= $lang['re'] ?>" class="cleanbutton">
</div>
</div>
<?
}
} else { echo 0; }

if($ini->read('second','commentmoderate') == "enabled") {
$sql = "SELECT * FROM comments WHERE note=".$id." AND moderate=1";
} else {
$sql = "SELECT * FROM comments WHERE note=".$id."";
}
if($ini->read('second','sortcomments') == "new") {
$sql .= " ORDER BY id DESC LIMIT ".$num.", ".$ini->read('second','commentsview')."";
} else {
$sql .= " ORDER BY id ASC LIMIT ".$num.", ".$ini->read('second','commentsview')."";
}

$result = $mysqli->query($sql);
$total = $result->num_rows;
if($total == 0) { ?><script>document.getElementById('load').style.display = "none"</script><? }
}

// Авторизация в панеле управления
if(isset($_POST['adminlogin'])) {
if($_SESSION['token'] == $_REQUEST['token']) {
if(CRYPT_MD5 == 1) {
define('CRYPTSALT','$1$rounds5$'.'asdsyGdJ*&');
} else {
die();
}

define('USERPASSHASH',$ini->read('admin','hash'));

function auth($login,$password) {
global $ini;
$hash = crypt($password,CRYPTSALT);
$nickname = $ini->read('admin','nickname');
if(($hash === USERPASSHASH) && ($login == $nickname)) return true;
return false;
}

if(!empty($_POST['login']) && !empty($_POST['password'])) {
if(auth($_POST['login'],$_POST['password'])) {
$_SESSION['login'] = $_POST['login'];
$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
exit(yes);
} else {
echo "<div class='error'>". $lang['error'] ."</div>";
}
} else {
echo "<div class='error'>". $lang['notsaved'] ."</div>";
}
}
}

// Определение местоположения по ip-адресу
if(isset($_POST['ip'])) {
$ip_data = @json_decode(file_get_contents("http://free.ipwhois.io/json/".$_POST['ip']));
$result = $ip_data->country;
$result .= ", ".$ip_data->city;
echo $result;
}

// Сохранение настроек ajax в панеле управления
if(isset($_SESSION['login'])) {
if(isset($_POST['mainset'])) {
if($_SESSION['token'] == $_REQUEST['token']) {
if(!empty($_POST['name']) && !empty($_POST['desc']) && !empty($_POST['metadesc']) && !empty($_POST['metawords']) && !empty($_POST['copy'])) {
$ini->write('main','sitename',$_POST['name']);
$ini->write('main','desc',$_POST['desc']);
$ini->write('main','metadesc',$_POST['metadesc']);
$ini->write('main','metawords',$_POST['metawords']);
$ini->write('main','liveinternet',$_POST['liveinternet']);
$ini->write('main','about',$_POST['about']);
$ini->write('main','language',$_POST['lang']);
$ini->write('main','copyrights',$_POST['copy']);
$ini->updateFile();
echo "<div class='done'>". $lang['saved'] ."</div>";
} else {
echo "<div class='error'>". $lang['notsaved'] ."</div>";
}
}
}

if(isset($_POST['secondset'])) {
if($_SESSION['token'] == $_REQUEST['token']) {
if(!empty($_POST['notesview']) && !empty($_POST['commentsview']) && !empty($_POST['commentssize'])) {
$ini->write('second','notesview',$_POST['notesview']);
$ini->write('second','sortnotes',$_POST['sortnotes']);
$ini->write('second','num',$_POST['num']);
$ini->write('second','views',$_POST['views']);
$ini->write('second','comments',$_POST['comments']);
$ini->write('second','sortcomments',$_POST['sortcomments']);
$ini->write('second','commentsview',$_POST['commentsview']);
$ini->write('second','commentssize',$_POST['commentssize']);
$ini->write('second','commentmoderate',$_POST['moderate']);
$ini->updateFile();
echo "<div class='done'>". $lang['saved'] ."</div>";
} else {
echo "<div class='error'>". $lang['notsaved'] ."</div>";
}
}
}

if(isset($_POST['adminset'])) {
if($_SESSION['token'] == $_REQUEST['token']) {
if(!empty($_POST['nickname'])) {
if(CRYPT_MD5==1) {
define('CRYPTSALT','$1$rounds5$'.'asdsyGdJ*&');
}

$ini->write('admin','nickname',$_POST['nickname']);
if(!empty($_POST['newpass'])) {
$ini->write('admin','hash',crypt($_POST['newpass'],CRYPTSALT));
}
$ini->updateFile();
echo "<div class='done'>". $lang['saved'] ."</div>";
} else {
echo "<div class='error'>". $lang['notsaved'] ."</div>";
}
}
}
}
?>