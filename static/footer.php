<? if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])) { header('Location: /'); } ?>
<div class="footer">
<div class="right"><?= $ini->read('main','copyrights').' &copy; '.date("Y") ?></div>
<div class="left"><?= $ini->read('main','liveinternet') ?></div>
</div>