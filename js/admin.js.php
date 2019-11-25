<?
header('Content-Type: application/javascript');
include('../static/iniclass.php');
$ini = new TIniFileEx('../static/config.ini');
$lang = parse_ini_file('../lang/'.$ini->read("main","language").'.lang');
?>

$(document).ready(function() {
// Изменение стилей активной ссылки
function getQueryParams(qs) {
qs = qs.split('+').join(' ');
var params = {},
tokens,
re = /[?&]?([^=]+)=([^&]*)/g;
while(tokens = re.exec(qs)) {
params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
}
return params;
}

$('.adminlink').each(function () {
var location = getQueryParams(window.location.search);
var link = getQueryParams(this.search);
if(link.act == location.act) {
$(this).addClass('act');
}
});

// Валидация формы (подсветка пустых полей)
$(function() {
$('form').submit(function(e) {
$(this).find('[type=text], [type=password], textarea, .nicedit-main').each(function() {
if(!$(this).val().length && !$(this).hasClass('fieldtags')) {
this.focus();
return false;
}
});

$(this).find('[type=text], [type=password], textarea').each(function() {
if(!$(this).val().length && !$(this).hasClass('fieldtags')) {
$('.added').css('display','block');
setTimeout("$('.added').fadeOut('slow');", 1000);
$(this).css({'background': '#fbe0e0', 'transition': '1s'});
setTimeout(function(){$(this).css({'background': '', 'transition': '1s'});}.bind(this), 1000);
e.preventDefault();
}
});

$(this).find('.nicedit-main').each(function() {
if(!$(this).html().length) {
$('.added').css('display','block');
setTimeout("$('.added').fadeOut('slow');", 1000);
$(this).css({'background': '#fbe0e0', 'transition': '1s'});
setTimeout(function(){$(this).css({'background': '', 'transition': '1s'});}.bind(this), 1000);
e.preventDefault();
}
});
});
});

// Копирование в буфер обмена
window.copy = copy;
function copy(element) {
$temp = $("<input>");
$("body").append($temp);
$temp.val($(element).text()).select();
document.execCommand("copy");
$temp.remove();
}

$(document).on('click', function(e) {
value = e.target;
$(value.parentElement).children('.success').css('display', 'block');
setTimeout("$('.success').fadeOut('slow');", 1000);
});

// Валидация формы (проверка на числа)
$(function() {
$('.minfield').on('change keyup input click', function() {
if(this.value.match(/[^0-9]/g)) {this.value = this.value.replace(/[^0-9]/g, '');}
});
});

// Определение местоположения по ip-адресу
$(document).on('mouseenter', '.dotted', function () {
var id = $(this).attr("id");
$.ajax({
url: "./static/ajax",
type: "POST",
data: {ip:$(this).html()},
success: function (data) {
$(id).attr('data-hint', data);
}
});
});

// Авторизация пользователя
window.loginform = loginform;
function loginform(result_id) {
$.ajax({
url: "./static/ajax",
type: "POST",
dataType: "html",
data: $("form").serialize(),
success: function(response) {
if(response == 'yes') {
window.location.href = '/admin';
} else {
document.getElementById(result_id).innerHTML = response;
setTimeout("$('.error').fadeOut('slow');", 1000);
}
},
});

$(function() {
$('.adminform').each(function() {
if($(this).val().length == 0) {
this.focus();
return false;
}
});

$('.adminform').each(function() {
if($(this).val().length == 0) {
$(this).css({'background': '#fbe0e0', 'transition': '1s'});
setTimeout (function(){$(this).css({'background': '', 'transition': '1s'});}.bind(this), 1000);
}
});
});
}

// Проверка надежности пароля
function passwordStrength(password,username) {
score = 0;
if(password.length == 0) return false;
if(password.length <= 6) return "<label class='error'><?= $lang['adminshort'] ?></label>";
if(password.toLowerCase()==username.toLowerCase()) return "<label class='error'><?= $lang['adminbad'] ?></label>";

score += password.length*3
score += (checkRepetition(1,password).length - password.length)*1
score += (checkRepetition(2,password).length - password.length)*1
score += (checkRepetition(3,password).length - password.length)*1
score += (checkRepetition(4,password).length - password.length)*1

if(password.match(/(.*[0-9].*[0-9].*[0-9])/)) score += 5;
if(password.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/)) score += 5;
if(password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) score += 5;
if(password.match(/([a-zA-Z])/) && password.match(/([0-9])/)) score += 5;
if(password.match(/([!,@,#,$,%,^,&,*,?,_,~])/) && password.match(/([0-9])/)) score += 10;
if(password.match(/([!,@,#,$,%,^,&,*,?,_,~])/) && password.match(/([a-zA-Z])/)) score += 10;
if(password.match(/^\w+$/) || password.match(/^\d+$/)) score -= 10;

if(score < 0) score = 0;
if(score > 100) score = 100;
if(score < 34) return "<label class='error'><?= $lang['adminbad'] ?></label>";
if(score < 68) return "<label class='done'><?= $lang['admingood'] ?></label>";
return "<label class='done'><?= $lang['adminstrong'] ?></label>";
}

function checkRepetition(pLen,str) {
res = "";
for(i=0; i<str.length; i++) {
repeated=true
for(j=0; j<pLen && (j+i+pLen)<str.length; j++)
repeated=repeated && (str.charAt(j+i)==str.charAt(j+i+pLen))
if(j<pLen) repeated=false
if(repeated) {
i+=pLen-1
repeated=false
} else {
res+=str.charAt(i)
}
}
return res
}

$('#password').on("change keyup input click", function() {
$('#result').html(passwordStrength($('#password').val(),$('#nickname').val()))
});

// Сохранение настроек без перезагрузки
window.ajaxform = ajaxform;
function ajaxform(result_id, form_id) {
$.ajax({
url: "./static/ajax",
type: "POST",
dataType: "html",
data: $("#"+form_id).serialize(),
success: function(response) {
document.getElementById(result_id).innerHTML = response;
setTimeout("$('.done, .error').fadeOut('slow');", 1000);
},
});

$(function() {
$('.field, #metadesc, #metawords, .minfield, #nickname').each(function() {
if($(this).val().length == 0) {
this.focus();
return false;
}
});

$('.field, #metadesc, #metawords, .minfield, #nickname').each(function() {
if($(this).val().length == 0) {
$(this).css({'background': '#fbe0e0', 'transition': '1s'});
setTimeout(function(){$(this).css({'background': '', 'transition': '1s'});}.bind(this), 1000);
}
});
});
}

// Показать/скрыть форму комментариев
$('.linkcomment').on('click',function() {
$(this).siblings('.formcomment').toggle();
if($('.nickcomment').is('[readonly]')) {
$('textarea').focus();
} else {
$('.nickcomment').focus();
}
});

// Счетчик оставшихся символов в комментариях
size = <?= $ini->read('second','commentssize') ?>;
$('.charcount').html('<?= $lang['char'] ?> '+size);
$('.textcomment').on('focus change keyup input click', function() {
revText = this.value.length;
if(this.value.length > size) {
this.value = this.value.substr(0, size);
}
cnt = (size-revText);
if(cnt <= 0) {
$('.charcount').html('<?= $lang['char'] ?> 0');
} else {
$('.charcount').html('<?= $lang['char'] ?> '+cnt);
}
});

// Ответить на комментарий
$(document).on('click', '#re', function() {
name = $(this).data('id');
$('.formcomment').show();		
$('textarea').val(name+', ');

destination = $('.addcomment').offset().top-50;
if($.browser) {
$('body').animate({scrollTop: destination}, 1000);
} else {
$('html').animate({scrollTop: destination}, 1000);
}

$('textarea').focus();
return false;
});

// Ajax загрузка комментариев
num = <?= $ini->read('second','commentsview') ?>;
id = $('#load').data('id');
$("#load").on('click',function() {
$.ajax({
url: "./static/ajax",
type: "GET",
data: {num:num, id:id},
cache: false,
success: function(response) {
if(response != 0) {
$("#boxcomment").append(response);
num = num + <?= $ini->read('second','commentsview') ?>;
}
}
});
});
});