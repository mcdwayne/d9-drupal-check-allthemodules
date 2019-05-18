<?php

namespace Drupal\link_partners\vendor\Trustlink;

use Drupal\link_partners\vendor\OpDbAbstract;

class TrustlinkClient extends OpDbAbstract{

  var $tl_version = 'T0.4.7';

  var $tl_verbose = FALSE;

  var $tl_cache = FALSE;

  var $tl_cache_size = 10;

  var $tl_cache_dir = 'cache/';

  var $tl_cache_filename = 'trustlink.links';

  var $tl_cache_cluster = 0;

  var $tl_cache_update = FALSE;

  var $tl_debug = FALSE;

  var $tl_isrobot = FALSE;

  var $tl_test = FALSE;

  var $tl_test_count = 4;

  var $tl_template = 'template';

  var $tl_charset = 'DEFAULT';

  var $tl_use_ssl = FALSE;

  var $tl_server = 'db.trustlink.ru';

  var $tl_cache_lifetime = 3600;

  var $tl_cache_reloadtime = 300;

  var $tl_links_db_file = '';

  var $tl_links = [];

  var $tl_links_page = [];

  var $tl_error = '';

  var $tl_host = '';

  var $tl_request_uri = '';

  var $tl_fetch_remote_type = '';

  var $tl_socket_timeout = 6;

  var $tl_force_show_code = FALSE;

  var $tl_multi_site = FALSE;

  var $tl_is_static = FALSE;

  public function __construct($options = NULL) {
    $host = '';

    if (is_array($options)) {
      if (isset($options['host'])) {
        $host = $options['host'];
      }
    }
    elseif (strlen($options) != 0) {
      $host = $options;
      $options = [];
    }
    else {
      $options = [];
    }

    if (strlen($host) != 0) {
      $this->tl_host = $host;
    }
    else {
      $this->tl_host = $_SERVER['HTTP_HOST'];
    }

    $this->tl_host = preg_replace('{^https?://}i', '', $this->tl_host);
    $this->tl_host = preg_replace('{^www\.}i', '', $this->tl_host);
    $this->tl_host = strtolower($this->tl_host);

    if (isset($options['is_static']) && $options['is_static']) {
      $this->tl_is_static = TRUE;
    }

    if (isset($options['request_uri']) && strlen($options['request_uri']) != 0) {
      $this->tl_request_uri = $options['request_uri'];
    }
    else {
      if ($this->tl_is_static) {
        $this->tl_request_uri = preg_replace('{\?.*$}', '', $_SERVER['REQUEST_URI']);
        $this->tl_request_uri = preg_replace('{/+}', '/', $this->tl_request_uri);
      }
      else {
        $this->tl_request_uri = $_SERVER['REQUEST_URI'];
      }
    }

    $this->tl_request_uri = rawurldecode($this->tl_request_uri);

    if (isset($options['multi_site']) && $options['multi_site'] == TRUE) {
      $this->tl_multi_site = TRUE;
    }

    if ((isset($options['verbose']) && $options['verbose']) ||
      isset($this->tl_links['__trustlink_debug__'])
    ) {
      $this->tl_verbose = TRUE;
    }

    if (isset($options['charset']) && strlen($options['charset']) != 0) {
      $this->tl_charset = $options['charset'];
    }

    if (isset($options['fetch_remote_type']) && strlen($options['fetch_remote_type']) != 0) {
      $this->tl_fetch_remote_type = $options['fetch_remote_type'];
    }

    if (isset($options['socket_timeout']) && is_numeric($options['socket_timeout']) && $options['socket_timeout'] > 0) {
      $this->tl_socket_timeout = $options['socket_timeout'];
    }

    if ((isset($options['force_show_code']) && $options['force_show_code']) ||
      isset($this->tl_links['__trustlink_debug__'])
    ) {
      $this->tl_force_show_code = TRUE;
    }

    #Cache options
    if (isset($options['use_cache']) && $options['use_cache']) {
      $this->tl_cache = TRUE;
    }

    if (isset($options['cache_clusters']) && $options['cache_clusters']) {
      $this->tl_cache_size = $options['cache_clusters'];
    }

    if (isset($options['cache_dir']) && $options['cache_dir']) {
      $this->tl_cache_dir = $options['cache_dir'];
    }

    if (!defined('TRUSTLINK_USER')) {
      return $this->raise_error("Constant TRUSTLINK_USER is not defined.");
    }

    if (isset($_SERVER['HTTP_TRUSTLINK']) && $_SERVER['HTTP_TRUSTLINK'] == TRUSTLINK_USER) {
      $this->tl_test = TRUE;
      $this->tl_isrobot = TRUE;
      $this->tl_verbose = TRUE;
    }

    if (isset($_GET['trustlink_test']) && $_GET['trustlink_test'] == TRUSTLINK_USER) {
      $this->tl_force_show_code = TRUE;
      $this->tl_verbose = TRUE;
    }

    $this->load_links();
  }

  function setup_datafile($filename) {
    if (!is_file($filename)) {
      if (@touch($filename, time() - $this->tl_cache_lifetime)) {
        @chmod($filename, 0666);
      }
      else {
        return $this->raise_error("There is no file " . $filename . ". Fail to create. Set mode to 777 on the folder.");
      }
    }

    if (!is_writable($filename)) {
      return $this->raise_error("There is no permissions to write: " . $filename . "! Set mode to 777 on the folder.");
    }
    return TRUE;
  }

  function load_links() {

    $trustlink_id = \Drupal::config('link_partners.settings')
      ->get('trustlink.id');
    $host = $this->tl_host;

    if ($this->tl_multi_site) {
      $this->tl_links_db_file = \Drupal::service('file_system')
        ->realpath(file_default_scheme() . "://link_partners/trustlink/$trustlink_id/trustlink.$host.links.db");
    }
    else {
      $this->tl_links_db_file = \Drupal::service('file_system')
        ->realpath(file_default_scheme() . "://link_partners/trustlink/$trustlink_id/trustlink.links.db");
    }

    if (!$this->setup_datafile($this->tl_links_db_file)) {
      return FALSE;
    }

    //cache
    if ($this->tl_cache) {
      //check dir
      if (!is_dir(dirname(__FILE__) . '/' . $this->tl_cache_dir)) {
        if (!@mkdir(dirname(__FILE__) . '/' . $this->tl_cache_dir)) {
          return $this->raise_error("There is no dir " . dirname(__FILE__) . '/' . $this->tl_cache_dir . ". Fail to create. Set mode to 777 on the folder.");
        }
      }
      //check dir rights
      if (!is_writable(dirname(__FILE__) . '/' . $this->tl_cache_dir)) {
        return $this->raise_error("There is no permissions to write to dir " . $this->tl_cache_dir . "! Set mode to 777 on the folder.");
      }

      for ($i = 0; $i < $this->tl_cache_size; $i++) {
        $filename = $this->cache_filename($i);
        if (!$this->setup_datafile($filename)) {
          return FALSE;
        }
      }
    }

    @clearstatcache();

    //Load links
    if (filemtime($this->tl_links_db_file) < (time() - $this->tl_cache_lifetime) ||
      (filemtime($this->tl_links_db_file) < (time() - $this->tl_cache_reloadtime) && filesize($this->tl_links_db_file) == 0)
    ) {

      @touch($this->tl_links_db_file, time());

      $path = '/' . TRUSTLINK_USER . '/' . strtolower($this->tl_host) . '/' . strtoupper($this->tl_charset);

      if ($links = $this->fetch_remote_file($this->tl_server, $path)) {
        if (substr($links, 0, 12) == 'FATAL ERROR:' && $this->tl_debug) {
          $this->raise_error($links);
        }
        else {
          if (@unserialize($links) !== FALSE) {
            $this->lc_write($this->tl_links_db_file, $links);
            $this->tl_cache_update = TRUE;
          }
          else {
            if ($this->tl_debug) {
              $this->raise_error("Cans't unserialize received data.");
            }
          }
        }
      }
    }

    if ($this->tl_cache && !$this->lc_is_synced_cache()) {
      $this->tl_cache_update = TRUE;
    }

    if ($this->tl_cache && !$this->tl_cache_update) {
      $this->tl_cache_cluster = $this->page_cluster($this->tl_request_uri, $this->tl_cache_size);
      $links = $this->lc_read($this->cache_filename($this->tl_cache_cluster));
    }
    else {
      $links = $this->lc_read($this->tl_links_db_file);
    }

    $this->tl_file_change_date = gmstrftime("%d.%m.%Y %H:%M:%S", filectime($this->tl_links_db_file));
    $this->tl_file_size = strlen($links);

    if (!$links) {
      $this->tl_links = [];
      if ($this->tl_debug) {
        $this->raise_error("Empty file.");
      }
    }
    else {
      if (!$this->tl_links = @unserialize($links)) {
        $this->tl_links = [];
        if ($this->tl_debug) {
          $this->raise_error("Can't unserialize data from file.");
        }
      }
    }


    if (isset($this->tl_links['__trustlink_delimiter__'])) {
      $this->tl_links_delimiter = $this->tl_links['__trustlink_delimiter__'];
    }

    if ($this->tl_test) {
      if (isset($this->tl_links['__test_tl_link__']) && is_array($this->tl_links['__test_tl_link__'])) {
        for ($i = 0; $i < $this->tl_test_count; $i++) {
          $this->tl_links_page[$i] = $this->tl_links['__test_tl_link__'];
        }
      }
      if ($this->tl_charset != 'DEFAULT') {
        $this->tl_links_page[$i]['text'] = iconv("UTF-8", $this->tl_charset, $this->tl_links_page[$i]['text']);
        $this->tl_links_page[$i]['anchor'] = iconv("UTF-8", $this->tl_charset, $this->tl_links_page[$i]['anchor']);
      }
    }
    else {

      $tl_links_temp = [];
      foreach ($this->tl_links as $key => $value) {
        $tl_links_temp[rawurldecode($key)] = $value;
      }
      $this->tl_links = $tl_links_temp;

      if ($this->tl_cache && $this->tl_cache_update) {
        $this->lc_write_cache($this->tl_links);
      }

      $this->tl_links_page = [];
      if (array_key_exists($this->tl_request_uri, $this->tl_links) && is_array($this->tl_links[$this->tl_request_uri])) {
        $this->tl_links_page = array_merge($this->tl_links_page, $this->tl_links[$this->tl_request_uri]);
      }
    }

    $this->tl_links_count = count($this->tl_links_page);
  }

  function fetch_remote_file($host, $path) {
    $user_agent = 'Trustlink Client PHP ' . $this->tl_version;

    @ini_set('allow_url_fopen', 1);
    @ini_set('default_socket_timeout', $this->tl_socket_timeout);
    @ini_set('user_agent', $user_agent);

    if (
      $this->tl_fetch_remote_type == 'file_get_contents' || (
        $this->tl_fetch_remote_type == '' && function_exists('file_get_contents') && ini_get('allow_url_fopen') == 1
      )
    ) {
      if ($data = @file_get_contents('http://' . $host . $path)) {
        return $data;
      }
    }
    elseif (
      $this->tl_fetch_remote_type == 'curl' || (
        $this->tl_fetch_remote_type == '' && function_exists('curl_init')
      )
    ) {
      if ($ch = @curl_init()) {
        @curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
        @curl_setopt($ch, CURLOPT_HEADER, FALSE);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->tl_socket_timeout);
        @curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

        if ($data = @curl_exec($ch)) {
          return $data;
        }

        @curl_close($ch);
      }
    }
    else {
      $buff = '';
      $fp = @fsockopen($host, 80, $errno, $errstr, $this->tl_socket_timeout);
      if ($fp) {
        @fputs($fp, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\n");
        @fputs($fp, "User-Agent: {$user_agent}\r\n\r\n");
        while (!@feof($fp)) {
          $buff .= @fgets($fp, 128);
        }
        @fclose($fp);

        $page = explode("\r\n\r\n", $buff);

        return $page[1];
      }
    }

    return $this->raise_error("Can't connect to server: " . $host . $path);
  }

  function lc_read($filename) {
    $fp = @fopen($filename, 'rb');
    @flock($fp, LOCK_SH);
    if ($fp) {
      clearstatcache();
      $length = @filesize($filename);
      if (version_compare(PHP_VERSION, '5.3.0', '<')) {
        if (get_magic_quotes_gpc()) {
          $mqr = get_magic_quotes_runtime();
          set_magic_quotes_runtime(0);
        }
      }
      if ($length) {
        $data = @fread($fp, $length);
      }
      else {
        $data = '';
      }
      if (version_compare(PHP_VERSION, '5.3.0', '<')) {
        if (isset($mqr)) {
          set_magic_quotes_runtime($mqr);
        }
      }
      @flock($fp, LOCK_UN);
      @fclose($fp);

      return $data;
    }

    return $this->raise_error("Can't get data from the file: " . $filename);
  }

  function lc_write($filename, $data) {
    $fp = @fopen($filename, 'wb');
    if ($fp) {
      @flock($fp, LOCK_EX);
      $length = strlen($data);
      @fwrite($fp, $data, $length);
      @flock($fp, LOCK_UN);
      @fclose($fp);

      if (md5($this->lc_read($filename)) != md5($data)) {
        return $this->raise_error("Integrity was violated while writing to file: " . $filename);
      }

      return TRUE;
    }

    return $this->raise_error("Can't write to file: " . $filename);
  }


  function page_cluster($path, $n) {
    $size = strlen($path);
    $sum = 0;
    for ($i = 0; $i < $size; $i++) {
      $sum += ord($path[$i]);
    }
    return $sum % $n;
  }

  function cache_filename($i) {
    $host = $this->tl_multi_site ? '.' . $this->tl_host : '';
    return dirname(__FILE__) . '/' . $this->tl_cache_dir . $this->tl_cache_filename . $host . '.db' . $i;
  }

  function lc_write_cache($data) {
    $common_keys = [
      '__trustlink_start__',
      '__trustlink_end__',
      '__trustlink_robots__',
      '__trustlink_delimiter__',
      '__trustlink_before_text__',
      '__trustlink_after_text__',
      '__test_tl_link__',
    ];

    $caches = [];

    foreach ($this->tl_links as $key => $value) {
      if (in_array($key, $common_keys)) {
        for ($i = 0; $i < $this->tl_cache_size; $i++) {
          if (empty($caches[$i])) {
            $caches[$i] = [];
          }
          $caches[$i][$key] = $value;
        }
      }
      else {
        if (empty($caches[$this->page_cluster($key, $this->tl_cache_size)])) {
          $caches[$this->page_cluster($key, $this->tl_cache_size)] = [];
        }
        $caches[$this->page_cluster($key, $this->tl_cache_size)][$key] = $value;
      }
    }

    for ($i = 0; $i < $this->tl_cache_size; $i++) {
      $this->lc_write($this->cache_filename($i), serialize($caches[$i]));
    }
  }

  function lc_is_synced_cache() {
    $db_mtime = filemtime($this->tl_links_db_file);
    for ($i = 0; $i < $this->tl_cache_size; $i++) {
      $filename = $this->cache_filename($i);
      $cache_mtime = filemtime($filename);
      //check file size
      if (filesize($filename) == 0) {
        return FALSE;
      }
      //check reload cache time
      if ($cache_mtime < (time() - $this->tl_cache_lifetime)) {
        return FALSE;
      }
      //check time relative to trustlink.links.db
      if ($cache_mtime < $db_mtime) {
        return FALSE;
      }
    }
    return TRUE;
  }

  function raise_error($e) {
    $this->tl_error = '<!--ERROR: ' . $e . '-->';
    return FALSE;
  }

  function build_links($n = NULL) {

    $total_page_links = count($this->tl_links_page);

    if (!is_numeric($n) || $n > $total_page_links) {
      $n = $total_page_links;
    }

    $links = [];

    for ($i = 0; $i < $n; $i++) {
      $links[] = array_shift($this->tl_links_page);
    }

    $result = '';
    if (isset($this->tl_links['__trustlink_start__']) && strlen($this->tl_links['__trustlink_start__']) != 0 &&
      (in_array($_SERVER['REMOTE_ADDR'], $this->tl_links['__trustlink_robots__']) || $this->tl_force_show_code)
    ) {
      $result .= $this->tl_links['__trustlink_start__'];
    }

    if (isset($this->tl_links['__trustlink_robots__']) && in_array($_SERVER['REMOTE_ADDR'], $this->tl_links['__trustlink_robots__']) || $this->tl_verbose) {

      if ($this->tl_error != '' && $this->tl_debug) {
        $result .= $this->tl_error;
      }

      $result .= '<!--REQUEST_URI=' . $_SERVER['REQUEST_URI'] . "-->\n";
      $result .= "\n<!--\n";
      $result .= 'L ' . $this->tl_version . "\n";
      $result .= 'REMOTE_ADDR=' . $_SERVER['REMOTE_ADDR'] . "\n";
      $result .= 'request_uri=' . $this->tl_request_uri . "\n";
      $result .= 'charset=' . $this->tl_charset . "\n";
      $result .= 'is_static=' . $this->tl_is_static . "\n";
      $result .= 'multi_site=' . $this->tl_multi_site . "\n";
      $result .= 'file change date=' . $this->tl_file_change_date . "\n";
      $result .= 'lc_file_size=' . $this->tl_file_size . "\n";
      $result .= 'lc_links_count=' . $this->tl_links_count . "\n";
      $result .= 'left_links_count=' . count($this->tl_links_page) . "\n";
      $result .= 'tl_cache=' . $this->tl_cache . "\n";
      $result .= 'tl_cache_size=' . $this->tl_cache_size . "\n";
      $result .= 'tl_cache_block=' . $this->tl_cache_cluster . "\n";
      $result .= 'tl_cache_update=' . $this->tl_cache_update . "\n";
      $result .= 'n=' . $n . "\n";
      $result .= '-->';
    }

    $tpl_filename = dirname(__FILE__) . "/" . $this->tl_template . ".tpl.html";
    $tpl = $this->lc_read($tpl_filename);
    if (!$tpl) {
      return $this->raise_error("Template file not found");
    }

    if (!preg_match("/<{block}>(.+)<{\/block}>/is", $tpl, $block)) {
      return $this->raise_error("Wrong template format: no <{block}><{/block}> tags");
    }

    $tpl = str_replace($block[0], "%s", $tpl);
    $block = $block[0];
    $blockT = substr($block, 9, -10);


    if (strpos($blockT, '<{head_block}>') === FALSE) {
      return $this->raise_error("Wrong template format: no <{head_block}> tag.");
    }
    if (strpos($blockT, '<{/head_block}>') === FALSE) {
      return $this->raise_error("Wrong template format: no <{/head_block}> tag.");
    }

    if (strpos($blockT, '<{link}>') === FALSE) {
      return $this->raise_error("Wrong template format: no <{link}> tag.");
    }
    if (strpos($blockT, '<{text}>') === FALSE) {
      return $this->raise_error("Wrong template format: no <{text}> tag.");
    }
    if (strpos($blockT, '<{host}>') === FALSE) {
      return $this->raise_error("Wrong template format: no <{host}> tag.");
    }

    if (!isset($text)) {
      $text = '';
    }

    foreach ($links as $i => $link) {
      if ($i >= $this->tl_test_count) {
        continue;
      }
      if (!is_array($link)) {
        return $this->raise_error("link must be an array");
      }
      elseif (!isset($link['text']) || !isset($link['url'])) {
        return $this->raise_error("format of link must be an array('anchor'=>\$anchor,'url'=>\$url,'text'=>\$text");
      }
      elseif (!($parsed = @parse_url($link['url'])) || !isset($parsed['host'])) {
        return $this->raise_error("wrong format of url: " . $link['url']);
      }
      if (($level = count(explode(".", $parsed['host']))) < 2) {
        return $this->raise_error("wrong host: " . $parsed['host'] . " in url " . $link['url']);
      }
      $host = strtolower(($level > 2 && strpos(strtolower($parsed['host']), 'www.') === 0) ? substr($parsed['host'], 4) : $parsed['host']);
      $block = str_replace("<{host}>", $host, $blockT);
      if (empty($link['anchor'])) {
        $block = preg_replace("/<{head_block}>(.+)<{\/head_block}>/is", "", $block);
      }
      else {
        $href = empty($link['punicode_url']) ? $link['url'] : $link['punicode_url'];
        $block = str_replace("<{link}>", '<a href="' . $href . '">' . $link['anchor'] . '</a>', $block);
        $block = str_replace("<{head_block}>", '', $block);
        $block = str_replace("<{/head_block}>", '', $block);
      }
      $block = str_replace("<{text}>", $link['text'], $block);
      $text .= $block;
    }
    if (is_array($links) && (count($links) > 0)) {
      $tpl = sprintf($tpl, $text);
      $result .= $tpl;
    }

    if (isset($this->tl_links['__trustlink_end__']) && strlen($this->tl_links['__trustlink_end__']) != 0 &&
      (in_array($_SERVER['REMOTE_ADDR'], $this->tl_links['__trustlink_robots__']) || $this->tl_force_show_code)
    ) {
      $result .= $this->tl_links['__trustlink_end__'];
    }

    $result = $result . $this->uptolike_tag();

    if ($this->tl_test && !$this->tl_isrobot) {
      $result = '<noindex>' . $result . '</noindex>';
    }
    return $result;
  }

  function uptolike_tag() {
    $uptolike_hash = 'tl' . sha1($this->tl_host);

    $result = '<script async="async" src="https://w.uptolike.com/widgets/v1/zp.js?pid=' . $uptolike_hash . '" type="text/javascript"></script>';

    return $result;
  }
}
