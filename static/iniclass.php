<?
if(basename(__FILE__) == basename($_SERVER['PHP_SELF']) && !$_SERVER['HTTP_X_REQUESTED_WITH']) { header('Location: /'); }

if(!defined('_BR_'))
define('_BR_',chr(13).chr(10));
class TIniFileEx {
public $filename;
public $arr;

function __construct($file = false) {
if($file) $this->loadFromFile($file);
}
 
function initArray() {
$this->arr = parse_ini_file($this->filename, true);
}
    
function loadFromFile($file) {
$result = true;
$this->filename = $file;
if(file_exists($file) && is_readable($file)) {
$this->initArray();
}
else
$result = false;
return $result;
}

function read($section, $key, $def = '') {
if(isset($this->arr[$section][$key])) {
return stripslashes($this->arr[$section][$key]);
} else
return $def;
}

function write($section, $key, $value) {
if(is_bool($value)) $value = $value ? 1 : 0;
$this->arr[$section][$key] = $value;
}

function eraseSection($section) {
if(isset($this->arr[$section])) unset($this->arr[$section]);
}

function deleteKey($section, $key) {
if(isset($this->arr[$section][$key])) unset($this->arr[$section][$key]);
}

function readSections(&$array) {
$array = array_keys($this->arr);
return $array;
}

function readKeys($section, &$array) {
if(isset($this->arr[$section])) {
$array = array_keys($this->arr[$section]);
return $array;
}
return array();
}

function updateFile() {
$result = '';
foreach($this->arr as $sname=>$section) {
$result .= '['. $sname .']'. _BR_;
foreach($section as $key=>$value) {
$value = stripslashes($value);
$result .= $key .'="'. addslashes($value) .'"'. _BR_;
}
$result .= _BR_;
}
@file_put_contents($this->filename, $result);
return true;
}

function __destruct() {
$this->updateFile();
}
}
?>