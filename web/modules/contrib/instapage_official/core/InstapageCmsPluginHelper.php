<?php

/**
 * Helper containing commonly used static functions.
 */
class InstapageCmsPluginHelper {

  /**
   * Loads content of a given template and prints it or returns it as a string.
   *
   * @param string $tmpl Name of the template to load.
   * @param bool $print If set to true, content of the template will be printed to stdio. In other case it will be returned. Default: true.
   *
   * @throws \Exception if template file is not found.
   *
   * @return string|void Content of the template as a string or void, if print === true.
   */
  public static function loadTemplate($tmpl, $print = true) {
    $templateFile = INSTAPAGE_PLUGIN_PATH . '/views/' . $tmpl . '.php';

    if (file_exists($templateFile)) {
      if (!$print) {
        ob_start();
      }

      require($templateFile);

      if (!$print) {
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
      }
    } else {
      throw new Exception(InstapageCmsPluginConnector::lang('Template file (%s) not found', $templateFile));
    }
  }

  /**
   * Loads 'messages' template to initiate a container for plugin system messages.
   */
  public static function initMessagesSystem() {
    self::loadTemplate('messages');
  }

  /**
   * Returns a string representation of Instapage icon in SVG format.
   *
   * @return string string representation of SVG icon
   */
  public static function getMenuIcon() {
    return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTUiIGhlaWdodD0iMTUuOTY5IiB2aWV3Qm94PSIwIDAgMTUgMTUuOTY5Ij4NCiAgPGRlZnM+DQogICAgPHN0eWxlPg0KICAgICAgLmNscy0xIHsNCiAgICAgICAgZmlsbDogI2ZmZjsNCiAgICAgICAgZmlsbC1ydWxlOiBldmVub2RkOw0KICAgICAgfQ0KICAgIDwvc3R5bGU+DQogIDwvZGVmcz4NCiAgPHBhdGggaWQ9Il8xNi1sb2dvLnN2ZyIgZGF0YS1uYW1lPSIxNi1sb2dvLnN2ZyIgY2xhc3M9ImNscy0xIiBkPSJNMTIuMDEyLDkuMzE4YTAuODM5LDAuODM5LDAsMCwwLS45NTguODVWMjEuNjk0YTAuODM4LDAuODM4LDAsMCwwLC45ODcuODQ1bDAuMDkxLS4wMTlWOS4zMzdaTTkuOTU3LDEwLjU5M0EwLjgzOSwwLjgzOSwwLDAsMCw5LDExLjQ0M3Y5YTAuODM3LDAuODM3LDAsMCwwLC45ODcuODQ1bDAuMDkxLS4wMThWMTAuNjEyWk0yMy4yNjMsOS41MTNsLTkuMDgyLTEuNWEwLjg4OCwwLjg4OCwwLDAsMC0xLjAxNC45VjIzLjA4N2EwLjg4NywwLjg4NywwLDAsMCwxLjA0NC44OTRsOS4wODEtMS42OTRBMC45LDAuOSwwLDAsMCwyNCwyMS4zOTRWMTAuNDEzYTAuOSwwLjksMCwwLDAtLjczOC0wLjlNMjIuNDc2LDIwLjNhMC42NzcsMC42NzcsMCwwLDEtLjUuNjc5bC01Ljk1NiwxYTAuNjQyLDAuNjQyLDAsMCwxLS43MzctMC42NzlWMTAuNzM5QTAuNjQ1LDAuNjQ1LDAsMCwxLDE2LDEwLjA1NWw1Ljk1NiwwLjc4OWEwLjY3NCwwLjY3NCwwLDAsMSwuNTIxLjY4NFYyMC4zWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTkgLTgpIi8+DQo8L3N2Zz4=';
  }

  /**
   * Sets an AJAX endpoint for Instapage plugin.
   */
  public static function initAjaxURL() {
    echo '<script>var INSTAPAGE_AJAXURL = \'' . InstapageCmsPluginConnector::getAjaxURL() . '\';</script>';
  }

  /**
   * Gets plugin's options stored in the database.
   *
   * @param bool $configOnly Return only configuration, ommit JSON options.
   *
   * @return object Options object.
   */
  public static function getOptions($configOnly = false) {
    $db = InstapageCmsPluginDBModel::getInstance();

    if ($configOnly) {
      $sql = 'SELECT config FROM ' . $db->optionsTable;
      $row = $db->getRow($sql);

      if (isset($row->config)) {
        return json_decode($row->config);
      }

      return new stdClass;
    } else {
      $sql = 'SELECT * FROM ' . $db->optionsTable;
      $options = $db->getRow($sql);

      if ($options === false) {
        return new stdClass;
      }

      if (isset($options->config)) {
        $options->config = json_decode($options->config);
      }

      return $options;
    }
  }

  /**
   * Gets a single value from plugin's options.
   *
   * @param string $name Option name.
   * @param mixed $default Default value of the option. Defaulf: false.
   *
   * @uses \InstapageHelper::getOptions().
   * @uses \InstapageHelper::getVar() to check the existence of the option.
   *
   * @return mixed Option value or default value passed to the function.
   */
  public static function getOption($name, $default = false) {
    $options = self::getOptions();

    if (in_array($name, array('plugin_hash', 'user_name', 'api_keys'))) {
      return self::getVar($options->$name, $default);
    } else {
      return self::getVar($options->config->$name, $default);
    }
  }

  /**
   * Udated the plugin's options in database.
   *
   * @param $data Options object with updated values.
   *
   * @uses \InstapageCmsPluginDBModel::query to update the database.
   *
   * @return mixed Query result of false on query of false on query error. Exception message is logged in standard error log.
   */
  public static function updateOptions($data) {
    $userName = @self::getVar($data->userName, null);
    $userToken = @self::getVar($data->userToken, null);

    if ($userName === null) {
      $userName = @self::getVar($data->user_name, null);
    }

    if ($userToken === null) {
      $userToken = @self::getVar($data->plugin_hash, null);
    }

    $configJson = !empty($data->config) ? json_encode($data->config) : '';
    $metadataJson = !empty($data->metadata) ? json_encode($data->metadata) : '';
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'INSERT INTO ' . $db->optionsTable . '(id, plugin_hash, api_keys, user_name, config, metadata) VALUES(1, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE plugin_hash = %s, api_keys = %s, user_name = %s, config = %s, metadata=%s';

    return $db->query($sql, $userToken, '', $userName, $configJson, $metadataJson, $userToken, '', $userName, $configJson, $metadataJson);
  }

  /**
   * Updates plugin's options, stored an JSON.
   *
   * @param string $key Name of the param to update.
   * @param string $value Value of the param to update.
   */
  public static function updateMetadata($key, $value) {
    $metadata = self::getMetadata();
    $metadata[$key] = $value;
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'INSERT INTO ' . $db->optionsTable. '(id, metadata) VALUES(1, %s) ON DUPLICATE KEY UPDATE metadata = %s';
    $metadataJson = !empty($metadata) ? json_encode($metadata) : '';

    return $db->query($sql, $metadataJson, $metadataJson);
  }

  /**
   * Gest an option value from plugin's metadata.
   *
   * @param string $key Name of the param to get.
   * @param string $default Default value.
   *
   * @return mixed Option value.
   */
  public static function getMetadata($key = '', $default = null) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT metadata FROM ' . $db->optionsTable;
    $row = $db->getRow($sql);
    $metadata = array();

    if (isset($row->metadata) && $row->metadata) {
      $metadata = (array) json_decode($row->metadata);
    }

    if (!empty($key)) {
      return isset($metadata[$key]) ? $metadata[$key] : $default;
    }

    return $metadata;
  }

  /**
   * Gets API tokens stored in the DB.
   *
   * @return array Stored tokens.
   */
  public static function getTokens() {
    $config = self::getOptions(true);
    $tokens = array();

    if (!isset($config->tokens) || !is_array($config->tokens)) {
      return array();
    }

    foreach ($config->tokens as $token) {
      $tokens[] = $token->value;
    }

    return $tokens;
  }

  /**
   * Returns the given variable if it's set, or default value otherwise.
   *
   * @param mixed $value. Value to be checked.
   * @param mixed $default. Default value. Default: false.
   *
   * @return mxed if value is set, returns the value. In other case returns default value or false.
   */
  public static function getVar(&$value, $default = false) {
    return isset($value) ? $value : $default;
  }

  /**
   * Checks if one of the custom params, stored in plugin's settings, are present in curent URL.
   *
   * @return boolean
   */
  public static function isCustomParamPresent() {
    $slug = self::extractSlug(InstapageCmsPluginConnector::getHomeUrl());
    $defaultExcludedParams = array
    (
      's' => null,
      'post_type' => null,
      'preview' => 'true'
   );

    $customParamsOption = explode('|', stripslashes(self::getOption('customParams', '')));
    $customParams = array();
    $paramArr = null;
    $key = null;
    $value = null;

    foreach ($customParamsOption as $param) {
      $paramArr = explode('=', $param);
      $key = isset($paramArr[0]) ? $paramArr[0] : null;
      $value = isset($paramArr[1]) ? str_replace('"', '', $paramArr[1]) : null;
      $customParams[$key] = $value;
    }

    if (count($customParams)) {
      $excludedParams = array_merge($defaultExcludedParams, $customParams);
    }

    foreach ($excludedParams as $key => $value) {
      $isDefaultParam = array_key_exists($key, $defaultExcludedParams) ? true : false;

      if (
        (!empty($key) && $value == null && (isset($_GET[$key]) || (!$isDefaultParam && strpos($slug, $key) !== false))) ||
        (!empty($key) && $value !== null && isset($_GET[$key]) && $_GET[$key] == $value)
     )
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Cleans an URL for sedning to Instapage app.
   *
   * @param string $url URL to prepare.
   *
   * @return string Prepared URL.
   */
  public static function prepareUrlForUpdate($url) {
    return trim(str_replace(array('http://', 'https://'), '', $url), '/');
  }

  /**
   * Extracts a slug from current URL. Slug will be compared with values stored in plugin's database to find a landing page to display.
   *
   * @param string $homeUrl URL of the home page.
   *
   * @return string Trimmed slug, ready to compare with values stored in the DB.
   */
  public static function extractSlug($homeUrl) {
    $uriSegments = explode('?', $_SERVER['REQUEST_URI']);
    self::writeDiagnostics($uriSegments, 'checkCustomUrl: $uriSegments');
    $path = trim(parse_url($homeUrl, PHP_URL_PATH), '/');
    $segment = trim($uriSegments[0], '/');

    if ($path) {
      $pos = strpos($segment, $path );

      if ($pos !== false) {
        $segment = substr_replace($segment, '', $pos, strlen($path));
      }
    }

    $slug = trim($segment, '/');
    self::writeDiagnostics($slug, 'checkCustomUrl: $slug');

    return $slug;
  }

  /**
   * Decodes data passed as a JSON.
   *
   * @return object Object passed via POST method.
   */
  public static function getPostData() {
    return isset($_POST['data']) ? json_decode(urldecode($_POST['data'])) : null;
  }

  /**
   * Encodes a message as a JSON object.
   *
   * @param string $text Text of the message
   * @param string $status. Status of a message. Typically 'OK' or 'ERROR'.
   *
   * @return string encoded JSON message object.
   */
  public static function formatJsonMessage($text, $status = 'OK') {
    self::writeDiagnostics($text, 'Message');

    return json_encode((object) array('status' => $status, 'message' => $text));
  }

  /**
   * Checks Instapage API response.
   *
   * @param object $response Response object.
   * @param string $message A message to store in debug log or display to user after checking.
   * @param bool $print If message should be shown to user a a system message.
   *
   * @return bool Returns true on success, false on failure.
   */
  public static function checkResponse($response, $message = '', $print = true) {

    if (is_null($response)) {
      $msgText = InstapageCmsPluginConnector::lang('Can\'t decode API response. %s', $message);

      if ($print) {
        echo self::formatJsonMessage($msgText, 'ERROR');
      } else {
        self::writeDiagnostics($msgText, 'message');
      }

      return false;
    }

    if (!$response->success) {
      $text = @self::getVar($response->message, '');

      if ($print) {
        if ($text) {
          echo self::formatJsonMessage(InstapageCmsPluginConnector::lang($text), 'ERROR');
        } else {
          echo self::formatJsonMessage(InstapageCmsPluginConnector::lang('API returned an error. %s', $message), 'ERROR');
        }
      } else {
        self::writeDiagnostics($text, 'message');
      }

      return false;
    }

    return true;
  }

  /**
   * Writes an entry in debug log, if diagnostic mode is on.
   *
   * @param mixed $value Value to be stored.
   * @param string $name Name of the value. Default: ''.
   */
  public static function writeDiagnostics($value, $name = '') {
    $log = InstapageCmsPluginDebugLogModel::getInstance();

    if ($log->isDiagnosticMode()) {
      $log->write($value, $name);
    }
  }

  /**
   * Writes an entry in debug log.
   *
   * @param mixed $value Value to be stored.
   * @param string $name Name of the value. Default: ''.
   */
  public static function writeLog($value, $name = '') {
    $log = InstapageCmsPluginDebugLogModel::getInstance();
    $log->write($value);
  }

  /**
   * Prepares an Auth header for API requests.
   *
   * @param array $tokens Tokens to encode in Auth header.
   * @return string Encoded Auth header.
   */
  public static function getAuthHeader($tokens) {
    self::writeDiagnostics($tokens, 'Decoded tokens');

    return base64_encode(json_encode($tokens));
  }

  /**
   * Gets a variant of a landing page to display.
   *
   * @param string $cookieString A cookie string.
   *
   * @return string|null A variant to display, or null if a variant is not present.
   */
  public static function getVariant($cookieString) {
    $pattern = '/instapage-variant-\d*?=(.*?);/';
    $matches = array();
    preg_match($pattern, $cookieString, $matches);

    return isset($matches[1]) ? $matches[1] : null;
  }

  /**
   * Sets a proper header with response code.
   *
   * @param int $code HTTP Response Code. Default: 200.
   */
  public static function httpResponseCode($code = 200) {

    if (function_exists('http_response_code')) {
      http_response_code($code);

      return;
    }

    if ($code === null) {
      $code = 200;
    }

    switch ($code) {
      case 100: $text = 'Continue'; break;
      case 101: $text = 'Switching Protocols'; break;
      case 200: $text = 'OK'; break;
      case 201: $text = 'Created'; break;
      case 202: $text = 'Accepted'; break;
      case 203: $text = 'Non-Authoritative Information'; break;
      case 204: $text = 'No Content'; break;
      case 205: $text = 'Reset Content'; break;
      case 206: $text = 'Partial Content'; break;
      case 300: $text = 'Multiple Choices'; break;
      case 301: $text = 'Moved Permanently'; break;
      case 302: $text = 'Moved Temporarily'; break;
      case 303: $text = 'See Other'; break;
      case 304: $text = 'Not Modified'; break;
      case 305: $text = 'Use Proxy'; break;
      case 400: $text = 'Bad Request'; break;
      case 401: $text = 'Unauthorized'; break;
      case 402: $text = 'Payment Required'; break;
      case 403: $text = 'Forbidden'; break;
      case 404: $text = 'Not Found'; break;
      case 405: $text = 'Method Not Allowed'; break;
      case 406: $text = 'Not Acceptable'; break;
      case 407: $text = 'Proxy Authentication Required'; break;
      case 408: $text = 'Request Time-out'; break;
      case 409: $text = 'Conflict'; break;
      case 410: $text = 'Gone'; break;
      case 411: $text = 'Length Required'; break;
      case 412: $text = 'Precondition Failed'; break;
      case 413: $text = 'Request Entity Too Large'; break;
      case 414: $text = 'Request-URI Too Large'; break;
      case 415: $text = 'Unsupported Media Type'; break;
      case 500: $text = 'Internal Server Error'; break;
      case 501: $text = 'Not Implemented'; break;
      case 502: $text = 'Bad Gateway'; break;
      case 503: $text = 'Service Unavailable'; break;
      case 504: $text = 'Gateway Time-out'; break;
      case 505: $text = 'HTTP Version not supported'; break;
      default: $code = 200; $text = 'OK'; break;
    }

    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1');

    if (!headers_sent()) {
      header($protocol . ' ' . $code . ' ' . $text);
    }
    $GLOBALS['http_response_code'] = $code;
  }

  /**
  * Sets a proper headers to disable caching by browsers and CDNs.
  */
  public static function disableCaching() {
    if (!headers_sent()) {
      header('Cache-Control: no-cache, no-store, must-revalidate');
      header('Pragma: no-cache');
      header('Expires: 0');
      header('Set-Cookie: no-cache=true');
    }
  }
}
