<?php

namespace Drupal\link_partners\vendor\Mainlink;

use Drupal\link_partners\vendor\OpDbAbstract;

class MLClient extends OpDbAbstract {

  var $file_code = 'ML_%code.php';

  var $transmiter = NULL;

  var $code_type = 'l';

  public function __construct($o = []) {

    // code type
    if (isset($o['CODE_TYPE']) && strlen($o['CODE_TYPE']) == 1) {
      $this->code_type = strtolower($o['CODE_TYPE']);
    }
    else {
      $this->code_type = 'l'; // links
    }

    $code_file = dirname(__FILE__) . '/data/' . str_replace('%', $this->code_type, $this->file_code);
    $username = $this->getUser($o);

    if (@strpos($_SERVER['HTTP_USER_AGENT'], 'mlbot.' . $username) !== FALSE && isset($_GET['ml_force_recovery'])) {
      @unlink($code_file);
      @unlink($code_file . '.lock');
      print '<!--<ml_force_recovery_result>true</ml_force_recovery_result>-->';
      return TRUE;
    }

    if (file_exists($code_file)) {
      // unlock if locked
      if (file_exists($code_file . '.lock')) {
        @unlink($code_file . '.lock');
      }

      // setup_code
      include_once $code_file;
    }
    else {
      // downloading latest version of code
      if ($this->getCode($o, $this->code_type)) {
        include_once $code_file;
      }
    }

    $transmiter_class = $this->code_type . 'Transmiter';
    if (class_exists($transmiter_class)) {
      $o['USERNAME'] = $this->getUser($o);
      $this->transmiter = new $transmiter_class($o);
    }
  }


  function build_links() {
    if ($this->transmiter != NULL) {
      return $this->transmiter->build_links();
    }
    return '';
  }


  function getCode($o = [], $code_type = 'l') {
    $username = $this->getUser($o);
    $code_file = dirname(__FILE__) . '/data/' . str_replace('%', $code_type, $this->file_code);
    if ((!file_exists($code_file) && !file_exists($code_file . '.lock'))
      || (strpos($userAgent, 'mlbot.' . $username) !== FALSE && isset($_GET['ml_request']))
    ) {

      if (!is_writable(dirname(__FILE__) . '/data')) {
        print 'Unable to load Mainlink code. Directory ' . realpath(dirname(__FILE__) . '/data') . ' is not writeable!';
        return FALSE;
      }

      if (file_exists($code_file . '.lock') && file_exists($code_file)) {
        $filetime = filectime($code_file . '.lock');

        if (time() - $filetime < 10) {
          print '<!--<ml_update>false: locked for ' . time() - $filetime . ' seconds</ml_update>-->';
          return FALSE;
        }
        else {
          @unlink(realpath(dirname(__FILE__) . '/data') . '/core_update.lock');
          print '<!--<ml_update_info>true: now ' . time() . ', locked at ' . $filetime . ' seconds</ml_update_info>-->';
          return FALSE;
        }
      }
    }

    $fp = fopen($code_file . '.lock', 'w+');
    fwrite($fp, time());
    fclose($fp);

    $code_type = isset($o['CODE_TYPE']) ? $o['CODE_TYPE'] : 'l';
    $service_call = isset($_GET['ml_request']) ? $_GET['ml_request'] : 'call';
    $content = $this->getApi([
      'getCode' => $code_type,
      'USERNAME' => $username,
      'codeBase' => 'php',
      $service_call => TRUE,
    ]);

    // unlocking update
    @unlink($code_file . '.lock');

    // report result


    $handle = fopen($code_file, 'w');
    $res = fwrite($handle, $content);
    fclose($handle);

    $result = $res !== FALSE ? 'true' : 'false';

    // let`s try again if unsucced
    if (!$result || $result == 'false') {
      $handle = fopen($code_file, 'wb');
      if ($handle) {
        @flock($handle, LOCK_EX);
        $result = fwrite($handle, $content);
        @flock($handle, LOCK_UN);
        fclose($handle);
      }
    }

    print '<!--<ml_code_setup_result>' . var_export($result, TRUE) . '</ml_code_setup_result>-->';
    return $result;
  }


  /*
   * getUser
   * Determine username
   */

  function getUser($o = []) {
    // in case username cont is preset
    if (isset($o['USERNAME']) && strlen($o['USERNAME']) == 32) {
      return $o['USERNAME'];
    }

    // search for key
    $dirop = opendir(realpath(dirname(__FILE__)));
    $secure = FALSE;
    if ($dirop) {
      while (gettype($file = readdir($dirop)) != 'boolean') {
        if ($file != "." && $file != ".." && $file != '.htaccess') {
          $ex = explode(".", $file);
          if (isset($ex[1]) and trim($ex[1]) == 'sec') {
            $secure = trim($ex[0]);
            return $secure;
          }
        }
      }
    }

    return $secure;
  }


  /*
   * getApi
   * Call API
   */

  function getApi($data) {
    // reserver servers
    $servers = [
      'main' => 'codes.mainlink.ru',
      'reserve' => 'dcodes.mainlinkads.com',
    ];

    return $this->request($servers, '/api.php', $data, 'GET');
  }

  /*
   * request
   * Do request
   */

  function request($servers, $file, $data = [], $method = 'GET', $timeout = 5) {
    // port
    $port = 80;
    foreach ($servers as $host) {
      $_data = $data;


      $tmp = [];
      foreach ($_data as $k => $v) {
        $tmp[] = $k . '=' . urlencode($v);
      }
      $_data = implode('&', $tmp);

      $path = $file;
      if ($method == 'GET' && $_data != '') {
        $path .= '?' . $_data;
      }

      $request = $method . " " . $path . " HTTP/1.0\r\n";
      $request .= "Host: " . $host . "\r\n";
      $request .= "User-Agent: MainLink code 6.0\r\n";
      $request .= "Connection: close\r\n\r\n";

      @ini_set('allow_url_fopen', 1);
      @ini_set('default_socket_timeout', $timeout);
      @ini_set('user_agent', 'MainLink init code v6');

      $answer = '';
      $response = '';
      if (function_exists('socket_create')) {
        @$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        @socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, [
          'sec' => $timeout,
          'usec' => 0,
        ]);
        @socket_connect($socket, $host, $port);
        @socket_write($socket, $request);

        while ($a = @socket_read($socket, 0xFFFF)) {
          $response .= $a;
        }

        $answer = ($response != '') ? explode("\r\n\r\n", $response, 2) : '';
        $response = '';
      }

      if (function_exists('fsockopen') && $answer == '') {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
          @fputs($fp, $request);
          while (!@feof($fp)) {
            $response .= @fgets($fp, 0xFFFF);
          }
          @fclose($fp);
        }

        $answer = ($response != '') ? explode("\r\n\r\n", $response, 2) : '';
        $response = '';
      }

      if (function_exists('curl_init') && $ch = @curl_init() && $answer == '') {
        @curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
        @curl_setopt($ch, CURLOPT_HEADER, TRUE);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        @curl_setopt($ch, CURLOPT_USERAGENT, 'MainLink init code v6');

        $response = @curl_exec($ch);

        $answer = ($response != '') ? explode("\r\n\r\n", $response, 2) : '';
        $response = '';
        @curl_close($ch);
      }

      if (function_exists('file_get_contents') && ini_get('allow_url_fopen') == 1 && $answer == '') {
        $response = @file_get_contents('http://' . $host . $path);
        $answer[1] = ($response != '') ? $response : '';
      }

      if ($answer[1] != '' && preg_match('/file:\'(.*?)\'/', $answer[1], $r)) {
        if (isset($r[1])) {
          $answer = $r[1];
          $c = base64_decode($answer);
          if ($c) {
            return $c;
          }
          return $answer;
        }
      }

      if ($answer[1] != '') {
        return $answer[1];
      }
    }

    return '<!--ERROR: Unable to use transport.-->';
  }
}
