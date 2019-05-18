<?php

namespace Drupal\link_partners\vendor\Sape;

use Drupal\link_partners\vendor\OpDbAbstract;

/**
 * Основной класс, выполняющий всю рутину
 */
class SAPE_base extends OpDbAbstract {

  protected $_version = '1.3.8';

  protected $_verbose = FALSE;

  /**
   * Кодировка сайта
   *
   * @link http://www.php.net/manual/en/function.iconv.php
   * @var string
   */
  protected $_charset = '';

  protected $_sape_charset = '';

  protected $_server_list = [
    'dispenser-01.saperu.net',
    'dispenser-02.saperu.net',
  ];

  /**
   * Пожалейте наш сервер :о)
   *
   * @var int
   */
  protected $_cache_lifetime = 3600;

  /**
   * Если скачать базу ссылок не удалось, то следующая попытка будет через
   * столько секунд
   *
   * @var int
   */
  protected $_cache_reloadtime = 600;

  protected $_errors = [];

  protected $_host = '';

  protected $_request_uri = '';

  protected $_multi_site = FALSE;

  /**
   * Способ подключения к удалённому серверу [file_get_contents|curl|socket]
   *
   * @var string
   */
  protected $_fetch_remote_type = '';

  /**
   * Сколько ждать ответа
   *
   * @var int
   */
  protected $_socket_timeout = 6;

  protected $_force_show_code = FALSE;

  /**
   * Если наш робот
   *
   * @var bool
   */
  protected $_is_our_bot = FALSE;

  protected $_debug = FALSE;

  protected $_file_contents_for_debug = [];

  /**
   * Регистронезависимый режим работы, использовать только на свой страх и риск
   *
   * @var bool
   */
  protected $_ignore_case = FALSE;

  /**
   * Путь к файлу с данными
   *
   * @var string
   */
  protected $_db_file = '';

  /**
   * Откуда будем брать uri страницы: $_SERVER['REQUEST_URI'] или
   * getenv('REQUEST_URI')
   *
   * @var bool
   */
  protected $_use_server_array = FALSE;

  /**
   * Показывать ли код js отдельно от выводимого контента
   *
   * @var bool
   */
  protected $_show_counter_separately = FALSE;

  protected $_force_update_db = FALSE;

  protected $_user_agent = '';

  public function __construct($options = NULL) {

    // Поехали :o)

    $host = '';

    if (is_array($options)) {
      if (isset($options['host'])) {
        $host = $options['host'];
      }
    }
    elseif (strlen($options)) {
      $host = $options;
      $options = [];
    }
    else {
      $options = [];
    }

    if (isset($options['use_server_array']) && $options['use_server_array'] == TRUE) {
      $this->_use_server_array = TRUE;
    }

    // Какой сайт?
    if (strlen($host)) {
      $this->_host = $host;
    }
    else {
      $this->_host = $_SERVER['HTTP_HOST'];
    }

    $this->_host = preg_replace('/^http:\/\//', '', $this->_host);
    $this->_host = preg_replace('/^www\./', '', $this->_host);

    // Какая страница?
    if (isset($options['request_uri']) && strlen($options['request_uri'])) {
      $this->_request_uri = $options['request_uri'];
    }
    elseif ($this->_use_server_array === FALSE) {
      $this->_request_uri = getenv('REQUEST_URI');
    }

    if (strlen($this->_request_uri) == 0) {
      $this->_request_uri = $_SERVER['REQUEST_URI'];
    }

    // На случай, если хочется много сайтов в одной папке
    if (isset($options['multi_site']) && $options['multi_site'] == TRUE) {
      $this->_multi_site = TRUE;
    }

    // Выводить информацию о дебаге
    if (isset($options['debug']) && $options['debug'] == TRUE) {
      $this->_debug = TRUE;
    }

    // Определяем наш ли робот
    if (isset($_COOKIE['sape_cookie']) && ($_COOKIE['sape_cookie'] == _SAPE_USER)) {
      $this->_is_our_bot = TRUE;
      if (isset($_COOKIE['sape_debug']) && ($_COOKIE['sape_debug'] == 1)) {
        $this->_debug = TRUE;
        //для удобства дебега саппортом
        $this->_options = $options;
        $this->_server_request_uri = $_SERVER['REQUEST_URI'];
        $this->_getenv_request_uri = getenv('REQUEST_URI');
        $this->_SAPE_USER = _SAPE_USER;
      }
      if (isset($_COOKIE['sape_updatedb']) && ($_COOKIE['sape_updatedb'] == 1)) {
        $this->_force_update_db = TRUE;
      }
    }
    else {
      $this->_is_our_bot = FALSE;
    }

    // Сообщать об ошибках
    if (isset($options['verbose']) && $options['verbose'] == TRUE || $this->_debug) {
      $this->_verbose = TRUE;
    }

    // Кодировка
    if (isset($options['charset']) && strlen($options['charset'])) {
      $this->_charset = $options['charset'];
    }
    else {
      $this->_charset = 'windows-1251';
    }

    if (isset($options['fetch_remote_type']) && strlen($options['fetch_remote_type'])) {
      $this->_fetch_remote_type = $options['fetch_remote_type'];
    }

    if (isset($options['socket_timeout']) && is_numeric($options['socket_timeout']) && $options['socket_timeout'] > 0) {
      $this->_socket_timeout = $options['socket_timeout'];
    }

    // Всегда выводить чек-код
    if (isset($options['force_show_code']) && $options['force_show_code'] == TRUE) {
      $this->_force_show_code = TRUE;
    }

    if (!defined('_SAPE_USER')) {
      return $this->_raise_error('Не задана константа _SAPE_USER');
    }

    //Не обращаем внимания на регистр ссылок
    if (isset($options['ignore_case']) && $options['ignore_case'] == TRUE) {
      $this->_ignore_case = TRUE;
      $this->_request_uri = strtolower($this->_request_uri);
    }

    if (isset($options['show_counter_separately'])) {
      $this->_show_counter_separately = (bool) $options['show_counter_separately'];
    }
  }

  /**
   * Получить строку User-Agent
   *
   * @return string
   */
  protected function _get_full_user_agent_string() {
    return $this->_user_agent . ' ' . $this->_version;
  }

  /**
   * Вывести дебаг-информацию
   *
   * @param $data
   *
   * @return string
   */
  protected function _debug_output($data) {
    $data = '<!-- <sape_debug_info>' . @base64_encode(serialize($data)) . '</sape_debug_info> -->';

    return $data;
  }

  /**
   * Функция для подключения к удалённому серверу
   */
  protected function _fetch_remote_file($host, $path, $specifyCharset = FALSE) {

    $user_agent = $this->_get_full_user_agent_string();

    @ini_set('allow_url_fopen', 1);
    @ini_set('default_socket_timeout', $this->_socket_timeout);
    @ini_set('user_agent', $user_agent);
    if (
      $this->_fetch_remote_type == 'file_get_contents'
      ||
      (
        $this->_fetch_remote_type == ''
        &&
        function_exists('file_get_contents')
        &&
        ini_get('allow_url_fopen') == 1
      )
    ) {
      $this->_fetch_remote_type = 'file_get_contents';

      if ($specifyCharset && function_exists('stream_context_create')) {
        $opts = [
          'http' => [
            'method' => 'GET',
            'header' => 'Accept-Charset: ' . $this->_charset . "\r\n",
          ],
        ];
        $context = @stream_context_create($opts);
        if ($data = @file_get_contents('http://' . $host . $path, NULL, $context)) {
          return $data;
        }
      }
      else {
        if ($data = @file_get_contents('http://' . $host . $path)) {
          return $data;
        }
      }
    }
    elseif (
      $this->_fetch_remote_type == 'curl'
      ||
      (
        $this->_fetch_remote_type == ''
        &&
        function_exists('curl_init')
      )
    ) {
      $this->_fetch_remote_type = 'curl';
      if ($ch = @curl_init()) {

        @curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
        @curl_setopt($ch, CURLOPT_HEADER, FALSE);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_socket_timeout);
        @curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        if ($specifyCharset) {
          @curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Charset: ' . $this->_charset]);
        }

        $data = @curl_exec($ch);
        @curl_close($ch);

        if ($data) {
          return $data;
        }
      }
    }
    else {
      $this->_fetch_remote_type = 'socket';
      $buff = '';
      $fp = @fsockopen($host, 80, $errno, $errstr, $this->_socket_timeout);
      if ($fp) {
        @fputs($fp, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\n");
        if ($specifyCharset) {
          @fputs($fp, "Accept-Charset: {$this->_charset}\r\n");
        }
        @fputs($fp, "User-Agent: {$user_agent}\r\n\r\n");
        while (!@feof($fp)) {
          $buff .= @fgets($fp, 128);
        }
        @fclose($fp);

        $page = explode("\r\n\r\n", $buff);
        unset($page[0]);

        return implode("\r\n\r\n", $page);
      }
    }

    return $this->_raise_error('Не могу подключиться к серверу: ' . $host . $path . ', type: ' . $this->_fetch_remote_type);
  }

  /**
   * Функция чтения из локального файла
   */
  protected function _read($filename) {

    $fp = @fopen($filename, 'rb');
    @flock($fp, LOCK_SH);
    if ($fp) {
      clearstatcache();
      $length = @filesize($filename);

      if (version_compare(PHP_VERSION, '5.3.0', '<')) {
        $mqr = @get_magic_quotes_runtime();
        @set_magic_quotes_runtime(0);
      }

      if ($length) {
        $data = @fread($fp, $length);
      }
      else {
        $data = '';
      }

      if (version_compare(PHP_VERSION, '5.3.0', '<')) {
        @set_magic_quotes_runtime($mqr);
      }

      @flock($fp, LOCK_UN);
      @fclose($fp);

      return $data;
    }

    return $this->_raise_error('Не могу считать данные из файла: ' . $filename);
  }

  /**
   * Функция записи в локальный файл
   */
  protected function _write($filename, $data) {

    $fp = @fopen($filename, 'ab');
    if ($fp) {
      if (flock($fp, LOCK_EX | LOCK_NB)) {
        ftruncate($fp, 0);

        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
          $mqr = @get_magic_quotes_runtime();
          @set_magic_quotes_runtime(0);
        }

        @fwrite($fp, $data);

        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
          @set_magic_quotes_runtime($mqr);
        }

        @flock($fp, LOCK_UN);
        @fclose($fp);

        if (md5($this->_read($filename)) != md5($data)) {
          @unlink($filename);

          return $this->_raise_error('Нарушена целостность данных при записи в файл: ' . $filename);
        }
      }
      else {
        return FALSE;
      }

      return TRUE;
    }

    return $this->_raise_error('Не могу записать данные в файл: ' . $filename);
  }

  /**
   * Функция обработки ошибок
   */
  protected function _raise_error($e) {

    $this->_errors[] = $e;

    if ($this->_verbose == TRUE) {
      print '<p style="color: red; font-weight: bold;">SAPE ERROR: ' . $e . '</p>';
    }

    return FALSE;
  }

  /**
   * Получить имя файла с даными
   *
   * @return string
   */
  protected function _get_db_file() {
    return '';
  }

  /**
   * Получить URI к хосту диспенсера
   *
   * @return string
   */
  protected function _get_dispenser_path() {
    return '';
  }

  /**
   * Сохранить данные, полученные из файла, в объекте
   */
  protected function _set_data($data) {
  }

  /**
   * Загрузка данных
   */
  protected function _load_data() {
    $this->_db_file = $this->_get_db_file();

    if (!is_file($this->_db_file)) {
      // Пытаемся создать файл.
      if (@touch($this->_db_file)) {
        @chmod($this->_db_file, 0666); // Права доступа
      }
      else {
        return $this->_raise_error('Нет файла ' . $this->_db_file . '. Создать не удалось. Выставите права 777 на папку.');
      }
    }

    if (!is_writable($this->_db_file)) {
      return $this->_raise_error('Нет доступа на запись к файлу: ' . $this->_db_file . '! Выставите права 777 на папку.');
    }

    @clearstatcache();

    $data = $this->_read($this->_db_file);
    if (
      $this->_force_update_db
      || (
        !$this->_is_our_bot
        &&
        (
          filemtime($this->_db_file) < (time() - $this->_cache_lifetime)
          ||
          filesize($this->_db_file) == 0
          ||
          @unserialize($data) == FALSE
        )
      )
    ) {
      // Чтобы не повесить площадку клиента и чтобы не было одновременных запросов
      @touch($this->_db_file, (time() - $this->_cache_lifetime + $this->_cache_reloadtime));

      $path = $this->_get_dispenser_path();
      if (strlen($this->_charset)) {
        $path .= '&charset=' . $this->_charset;
      }

      foreach ($this->_server_list as $server) {
        if ($data = $this->_fetch_remote_file($server, $path)) {
          if (substr($data, 0, 12) == 'FATAL ERROR:') {
            $this->_raise_error($data);
          }
          else {
            // [псевдо]проверка целостности:
            $hash = @unserialize($data);
            if ($hash != FALSE) {
              // попытаемся записать кодировку в кеш
              $hash['__sape_charset__'] = $this->_charset;
              $hash['__last_update__'] = time();
              $hash['__multi_site__'] = $this->_multi_site;
              $hash['__fetch_remote_type__'] = $this->_fetch_remote_type;
              $hash['__ignore_case__'] = $this->_ignore_case;
              $hash['__php_version__'] = phpversion();
              $hash['__server_software__'] = $_SERVER['SERVER_SOFTWARE'];

              $data_new = @serialize($hash);
              if ($data_new) {
                $data = $data_new;
              }

              $this->_write($this->_db_file, $data);
              break;
            }
          }
        }
      }
    }

    // Убиваем PHPSESSID
    if (strlen(session_id())) {
      $session = session_name() . '=' . session_id();
      $this->_request_uri = str_replace([
        '?' . $session,
        '&' . $session,
      ], '', $this->_request_uri);
    }

    $this->_set_data(@unserialize($data));

    return TRUE;
  }

  protected function _return_obligatory_page_content() {
    $s_globals = new SAPE_globals();

    $html = '';
    if (isset($this->_page_obligatory_output) && !empty($this->_page_obligatory_output)
      && FALSE == $s_globals->page_obligatory_output_shown()
    ) {
      $s_globals->page_obligatory_output_shown(TRUE);
      $html = $this->_page_obligatory_output;
    }

    return $html;
  }

  /**
   * Вернуть js-код
   * - работает только когда параметр конструктора show_counter_separately =
   * true
   *
   * @return string
   */
  public function return_counter() {
    //если show_counter_separately = false и выполнен вызов этого метода,
    //то заблокировать вывод js-кода вместе с контентом
    if (FALSE == $this->_show_counter_separately) {
      $this->_show_counter_separately = TRUE;
    }

    return $this->_return_obligatory_page_content();
  }
}
