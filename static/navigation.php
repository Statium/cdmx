<?
if(basename(__FILE__) == basename($_SERVER['PHP_SELF']) && !$_SERVER['HTTP_X_REQUESTED_WITH']) { header('Location: /'); }

class Paging {
private $page_size = 10;
private $pg = '';
private $link_padding = 3;
private $page_link_separator = ' ';
private $next_page_text = '&raquo;';
private $prev_page_text = '&laquo;';
private $result_text_pattern = '%s';
private $page_var = 'page';

private $db;
private $q;
private $total_rows;
private $total_pages;
private $cur_page;

public function __construct($db, $q='', $page_var='page') {
$this->db = $db;
if($q) $this->set_query($q);
$this->page_var = $page_var;
$this->cur_page = isset($_GET[$this->page_var]) && (int)$_GET[$this->page_var] > 0 ? (int)$_GET[$this->page_var] : 1;
}

public function set_query($q) {
$this->q = $q;
}

public function set_page_size($page_size) {
$this->page_size = abs((int)$page_size);
}

public function set_page($pg) {
$this->pg = $pg;
}

public function set_link_padding($padding) {
$this->link_padding = abs((int)$padding);
}

public function get_page($q='') {
if($q) $this->set_query($q);

$r = $this->db->query($this->query_paging($this->q));
$r1 = $this->db->query('SELECT FOUND_ROWS()')->fetch_row();
$this->total_rows = array_pop($r1);

if($this->page_size !== 0) $this->total_pages = ceil($this->total_rows/$this->page_size);

if($this->cur_page > $this->total_pages) {
$this->cur_page = $this->total_pages;
if($this->total_pages > 0) $r = $this->db->query($this->query_paging($this->q));
}
return $r;
}

public function get_result_text() {
$start = (($this->cur_page-1) * $this->page_size)+1;
$end = (($start-1+$this->page_size) >= $this->total_rows)? $this->total_rows:($start-1+$this->page_size);

return sprintf($this->result_text_pattern, /* $start, $end, */ $this->total_rows);
}

public function get_page_links() {
if(!isset($this->total_pages)) return '';
	
$page_link_list = array();

$start = $this->cur_page - $this->link_padding;
if($start < 1) $start = 1;
$end = $this->cur_page + $this->link_padding-1;
if($end > $this->total_pages) $end = $this->total_pages;
if($start > 1) $page_link_list[] = $this->get_page_link($start-1, $start - 2 > 0 ? '&lt;' : '');
for($i = $start; $i <= $end; $i++) $page_link_list[] = $this->get_page_link($i);
if($end + 1 < $this->total_pages) $page_link_list[] = $this->get_page_link($end +1, $end + 2 == $this->total_pages ? '' : '&gt;');
if($end + 1 <= $this->total_pages) $page_link_list[] = $this->get_page_link($this->total_pages);

return implode($this->page_link_separator, $page_link_list);
}

public function get_next_page_link() {
return isset($this->total_pages) && $this->cur_page < $this->total_pages ? $this->get_page_link($this->cur_page + 1, $this->next_page_text) : '';
}

public function get_prev_page_link() {
return isset($this->total_pages) && $this->cur_page > 1 ? $this->get_page_link($this->cur_page - 1, $this->prev_page_text) : '';
}

public function get_total_pages() {
return $this->total_pages;
}

public function get_cur_page() {
return $this->cur_page;
}

private function get_page_link($page, $text='') {
if(!$text) $text = $page;

if($page != $this->cur_page) {
if($this->pg == "index") {
$reg = '/((&|^)'.$this->page_var.')[^&#]*/';
$url = ''.(preg_match($reg, $_SERVER['QUERY_STRING']) ? preg_replace($reg, '${1}'.$page, $_SERVER['QUERY_STRING']) : ($_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'].'&' : '').$this->page_var.''.$page);
} else {
$reg = '/((&|^)'.$this->page_var.'=)[^&#]*/';
$url = '?'.(preg_match($reg, $_SERVER['QUERY_STRING']) ? preg_replace($reg, '${1}'.$page, $_SERVER['QUERY_STRING']) : ($_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'].'&' : '').$this->page_var.'='.$page);
}
return '<a class="pagelink" href="'.$url.'">'.$text.'</a>'; 
}
return '<span class="pagelinkact">'.$text.'</span>';
}

private function query_paging() {
$q = $this->q;

if($this->page_size != 0) {
$start = ($this->cur_page-1) * $this->page_size;
$q = preg_replace('/^SELECT\s+/i', 'SELECT SQL_CALC_FOUND_ROWS ', $this->q)." LIMIT {$start},{$this->page_size}";
}
return $q;
}
}
?>