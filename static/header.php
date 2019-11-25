<? if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) { header('Location: /'); } ?>
<div class="header">
<div class="center">
<a class="logo" href="/"><?= $ini->read('main','sitename') ?></a>
<div class="navi">
<? if($ini->read('main','about') == "enabled") { ?>
<a href="about"><?= $lang['about'] ?></a>
<?
}
if(isset($_SESSION['login'])) {
?>
<a href="admin"><?= $lang['admin'] ?></a>
<a href="new"><?= $lang['new'] ?></a>
<a href="admin?logout"><?= $lang['logout'] ?></a>
<? } ?>
</div>
<div class="desc"><?= $ini->read('main','desc') ?></div>
</div>
</div>