<?
session_start();

// Загружаем необходимые файлы
require('./static/connect.php');
require('./static/iniclass.php');
require('./static/navigation.php');
$ini = new TIniFileEx('./static/config.ini');
$lang = parse_ini_file('./lang/'.$ini->read("main","language").'.lang');

// Редирект на страницу без названия файла
if(($_SERVER['REQUEST_URI'] == "/index.php") || ($_SERVER['REQUEST_URI'] == "/index")) {
header("Location: /", TRUE, 301);
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

function close_tags($content) {
$position = 0;
$opentags = array();
$ignoretags = array('br', 'hr', 'img');
while(($position = strpos($content, '<', $position)) !== FALSE) {
if(preg_match("|^<(/?)([a-z\d]+)\b[^>]*>|i", substr($content, $position), $match)) {
$tag = strtolower($match[2]);
if(in_array($tag, $ignoretags) == FALSE) {
if(isset($match[1]) AND $match[1] == '') {
if(isset($opentags[$tag])) {
$opentags[$tag]++;
} else {
$opentags[$tag] = 1;
}
}
if(isset($match[1]) AND $match[1] == '/') {
if(isset($opentags[$tag]))
$opentags[$tag]--;
}
}
$position += strlen($match[0]);
} else {
$position++;
}
}
foreach($opentags as $tag => $notclosed) {
$content .= str_repeat("</{$tag}>", $notclosed);
}
return $content;
}

// Запросы в базу данных
$total = $mysqli->query("SELECT * FROM blog")->num_rows;

$paging = new Paging($mysqli);
$paging->set_page_size($ini->read('second','notesview'));
$paging->set_page("index");

if(isset($_SESSION['login']) && isset($_POST['nremove']) && ($nremove = (int)$_POST['nremove']) && $nremove > 0 && check_token()) {
$sql = $mysqli->prepare("DELETE FROM blog WHERE id=?");
$sql->bind_param("i", $nremove);
$sql->execute();
if($paging->get_cur_page() != 1) {
header('Location: page'.$paging->get_cur_page());
exit();
} else {
header('Location: /');
exit();
}
}
if($ini->read('second','commentmoderate') == "enabled") {
$sql = "SELECT id, name, text, tags, date, visits, (SELECT COUNT(*) FROM comments WHERE note=blog.id AND moderate=1) FROM blog WHERE id";
} else {
$sql = "SELECT id, name, text, tags, date, visits, (SELECT COUNT(*) FROM comments WHERE note=blog.id) FROM blog WHERE id";
}
if($ini->read('second','sortnotes') == "new") {
$sql .= " ORDER BY id DESC";
} else {
$sql .= " ORDER BY id ASC";
}
$result = $paging->get_page($sql);
?>
<!doctype html>
<html lang="ru">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?= $ini->read('main','metadesc') ?>">
<meta name="keywords" content="<?= $ini->read('main','metawords') ?>">
<meta name="yandex-verification" content="158df90bf544dc10">
<meta name="google-site-verification" content="R2VtQ7Q3O7Qu7XHgdGbDEvOp7bqIRkz4ulFf4FGtN_E">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= $ini->read('main','sitename') ?>">
<meta property="og:title" content="<?= $ini->read('main','desc') ?>">
<meta property="og:description" content="<?= $ini->read('main','metadesc') ?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:image" content="/images/social.jpg">

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="icon" href="/favicon.ico" type="image/x-icon">

<title><?= $ini->read('main','sitename').' | '.$ini->read('main','desc') ?></title>

<link href="/css/style.css?ver=<?= filemtime('./css/style.css') ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<script src="/js/jquery.js?ver=<?= filemtime('./js/jquery.js') ?>" async></script>
</head>

<body>
<? require('./static/header.php') ?>

<div class="content">
<?
if($total) {
while($row = $result->fetch_assoc()) {
?>
<div class="box">
<div class="head clearfix">
<h1><a href="/note<?= $row['id'] ?>"><? if($ini->read('second','num') == "enabled") { echo '#'.$row['id'].' '; } echo htmlspecialchars($row['name'], ENT_QUOTES); ?></a></h1>

<div class="info"><? if($ini->read('second','views') == "enabled") { ?><div data-hint="<?= $lang['visit'] ?>" class="visitinfo hintleft"><?= number_name($row['visits']); ?></div><? } if($ini->read('second','comments') == "enabled") { ?><div data-hint="<?= $lang['comments'] ?>" class="commentinfo hintleft"><? if($ini->read('second','commentmoderate') == "enabled") { echo $row['(SELECT COUNT(*) FROM comments WHERE note=blog.id AND moderate=1)']; } else { echo $row['(SELECT COUNT(*) FROM comments WHERE note=blog.id)']; } ?></div><? } ?><div data-hint="<?= $lang['date'] ?>" class="dateinfo hintleft"><?= date('d.m.Y', strtotime($row['date'])) ?></div></div>
</div>

<div class="text"><? $text = explode('<!--more-->',$row['text']); echo close_tags($text[0]); ?></div>

<div class="func">
<div class="tags"><? if($row['tags']) { echo $row['tags']; } else { echo $lang['tags']; } ?></div>
<div class="links">
<a href="/note<?= $row['id'] ?>" data-hint="<?= $lang['more'] ?>" class="more hintleft"></a>
<? if(isset($_SESSION['login'])){ ?>
<form action="/<? if($paging->get_cur_page() != 1) { echo 'page'.$paging->get_cur_page(); } ?>" method="post">
<input name="nremove" type="hidden" value="<?= $row['id'] ?>">
<input name="token" type="hidden" value="<?= $_SESSION['token'] ?>">
<input type="submit" value="<?= $lang['remove'] ?>" class="cleanbutton">
</form>
<form action="/note" method="get">
<input name="id" type="hidden" value="<?= $row['id'] ?>">
<input name="act" type="hidden" value="edit">
<input type="submit" value="<?= $lang['edit'] ?>" class="cleanbutton">
</form>
<? } ?>
</div>
</div>
</div>
<?
}
if($paging->get_total_pages() > 1) {
?>
<div class="navibox">
<?= $paging->get_prev_page_link().' '.$paging->get_page_links().' '.$paging->get_next_page_link() ?>
</div>
<?
}
} else {
?>
<div class="box">
<div class="head clearfix">
<h1><?= $lang['nonote'] ?></h1>
</div>
<div class="text">
<?= $lang['nonotedesc'] ?>
</div>
</div>
<? } ?>
</div>

<? require('./static/footer.php') ?>
</body>

</html>