<?php

namespace Drupal\tag1quo\Adapter\Core;

use Drupal\tag1quo\Adapter\Cache\Cache;
use Drupal\tag1quo\Adapter\Config\Config;
use Drupal\tag1quo\Adapter\Http\Client;
use Drupal\tag1quo\Adapter\Logger\Logger;
use Drupal\tag1quo\Adapter\Settings\Settings;
use Drupal\tag1quo\Adapter\State\State;
use Drupal\tag1quo\VersionedClass;

/**
 * Class Core.
 *
 * @internal This class is subject to change.
 */
abstract class Core extends VersionedClass {

  /**
   * The API base URI to fallback onto, if necessary.
   */
  const API_SERVER = 'https://quo.tag1consulting.com';

  /**
   * The API endpoint to use.
   */
  const API_ENDPOINT_HEARTBEAT = '/2.2/site';

  /**
   * The API endpoint to use.
   */
  const API_ENDPOINT_VALIDATE_TOKEN = '/api/validate-token';

  /**
   * The API version to use.
   */
  const API_VERSION = '2.2';

  /**
   * The machine name of Tag1 Quo.
   */
  const NAME = 'tag1quo';

  /**
   * The human readable title for Tag1 Quo.
   */
  const TITLE = 'Tag1 Quo';

  /**
   * The API server.
   *
   * @var string
   */
  protected $apiServer;

  /**
   * The API token used to authenticate with the server.
   *
   * @var string
   */
  protected $apiToken;

  /**
   * The API URI.
   *
   * @var string
   */
  protected $apiUri;

  /**
   * The API version.
   *
   * @var string
   */
  protected $apiVersion;

  /**
   * An array of Cache bins.
   *
   * @var \Drupal\tag1quo\Adapter\Cache\Cache[]
   */
  protected $cacheBins;

  /**
   * The core compatibility major.
   *
   * @var int
   */
  protected $compatibility;

  /**
   * A collection of Config adapters.
   *
   * @var \Drupal\tag1quo\Adapter\Config\Config[]
   */
  protected $config;

  /**
   * Flag indicating whether in debug mode.
   *
   * @var bool
   */
  protected $debugMode;

  /**
   * An array of Logger adapters.
   *
   * @var \Drupal\tag1quo\Adapter\Logger\Logger[]
   */
  protected $loggers;

  /**
   * The default favicon internal path.
   *
   * @var string
   */
  protected $defaultFaviconPath = 'misc/favicon.ico';

  /**
   * The default logo internal path.
   *
   * @var string
   */
  protected $defaultLogoPath = 'misc/druplicon.png';

  /**
   * The fallback admin theme for the core version.
   *
   * @var string
   */
  protected $fallbackThemeAdmin;

  /**
   * The fallback default theme for the core version.
   *
   * @var string
   */
  protected $fallbackThemeDefault;

  /**
   * The theme setting name to retrieve the favicon.
   *
   * @var string
   */
  protected $faviconThemeSetting = 'favicon';

  /**
   * The path to the git binary.
   *
   * @var string
   */
  protected $gitBinary;

  /**
   * @var \Drupal\tag1quo\Adapter\Http\Client
   */
  protected $httpClient;

  /**
   * The theme setting name to retrieve the logo.
   *
   * @var string
   */
  protected $logoThemeSetting = 'logo';

  /**
   * Provides a key/value map of routes to their internal paths.
   *
   * @var array
   */
  protected $routeMap;

  /**
   * The Settings adapter.
   *
   * @var \Drupal\tag1quo\Adapter\Settings\Settings
   */
  protected $settings;

  /**
   * The State adapter.
   *
   * @var \Drupal\tag1quo\Adapter\State\State
   */
  protected $state;

  /**
   * Core constructor.
   */
  public function __construct() {
  }

  /**
   * Creates a new instance.
   *
   * @return static
   */
  public static function create() {
    return static::createVersionedStaticInstance();
  }

  abstract public function absoluteUri($uri = '');

  /**
   * {@inheritdoc}
   */
  public function baseUrl() {
    global $base_url;
    return $base_url;
  }

  /**
   * Retrieves a cache bin.
   *
   * @param string $bin
   *   The cache bin to use. If not provided, the default bin for the
   *   version of core will be used.
   *
   * @return \Drupal\tag1quo\Adapter\Cache\Cache
   */
  public function cache($bin = NULL) {
    if ($bin === NULL) {
      $bin = '';
    }
    if (!isset($this->cacheBins[$bin])) {
      $this->cacheBins[$bin] = Cache::load($bin);
    }
    return $this->cacheBins[$bin];
  }

  /**
   * {@inheritdoc}
   */
  public function checkPlain($value = NULL) {
    $value = $value ? (string) $value : '';
    if (!empty($value)) {
      $value = \check_plain($value);
    }
    return $value;
  }

  /**
   * A Config adapter.
   *
   * @param string $name
   *   A config collection to retrieve.
   *
   * @return \Drupal\tag1quo\Adapter\Config\Config
   */
  public function config($name) {
    if (!isset($this->config[$name])) {
      $this->config[$name] = Config::load($name);
    }
    return $this->config[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function databaseHash() {
    global $db_url;

    // A hash of the db_url helps identify different deployment instances.
    if (is_array($db_url)) {
      if (isset($db_url['default'])) {
        $url_string = $db_url['default'];
      }
      else {
        $url_string = array_pop($db_url);
      }
    }
    else {
      $url_string = $db_url;
    }
    // No passwords are sent, just a hash.
    return hash('sha1', $url_string);
  }

  /**
   * {@inheritdoc}
   */
  public function adminTheme() {
    return $this->config('system.theme')->get('admin', $this->fallbackThemeAdmin);
  }

  public function buildElement(array $element = array()) {
    return $this->convertElement($element);
  }

  public function convertElement(array $element = array()) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultTheme() {
    return $this->config('system.theme')->get('default', $this->fallbackThemeDefault);
  }

  abstract public function elementInfo($type);

  /**
   * Return a UNIX timestamp of when this module was enabled.
   */
  public function enableTimestamp() {
    $timestamp = $this->state()->get('tag1quo_enable_timestamp', 0);
    if (empty($timestamp)) {
      $timestamp = $this->requestTime();
      $this->state()->set('tag1quo_enable_timestamp', $timestamp);
    }
    return $timestamp;
  }

  public function exec($command, array $args = array(), array $options = array(), &$result = NULL) {
    // Save the original current working directory.
    $original_cwd = @getcwd();

    // Change the current working directory.
    $cwd = !empty($options['cwd']) ? $options['cwd'] : FALSE;
    if ($cwd) {
      @chdir($cwd);
    }

    $command .= ' 2>&1';
    array_unshift($args, $command);

    $options['result'] = NULL;
    $output = array();
    @\exec(call_user_func_array('sprintf', $args), $output, $result);

    // Restore the original current working directory.
    if ($original_cwd !== $cwd) {
      @chdir($original_cwd);
    }

    return empty($options['array']) ? implode("\n", $output) : $output;
  }

  /**
   * @return array
   */
  abstract public function extensionList();

  /**
   * {@inheritdoc}
   */
  public function favicon() {
    $value = ltrim($this->themeSetting($this->faviconThemeSetting, ''), '/');

    // Ignore empty or default value.
    if (empty($value) || preg_match('/' . preg_quote($this->defaultFaviconPath, '/').  '$/', $value)) {
      return '';
    }

    return $this->getLocalQuoFile($value);
  }

  /**
   * Formats a time interval with the requested granularity.
   *
   * Note that for intervals over 30 days, the output is approximate: a "month"
   * is always exactly 30 days, and a "year" is always 365 days. It is not
   * possible to make a more exact representation, given that there is only one
   * input in seconds. If you are formatting an interval between two specific
   * timestamps, use \Drupal\Core\Datetime\DateFormatter::formatDiff() instead.
   *
   * @param int $interval
   *   The length of the interval in seconds.
   * @param int $granularity
   *   (optional) How many different units to display in the string (2 by
   *   default).
   * @param string|null $langcode
   *   (optional) langcode: The language code for the language used to format
   *   the date. Defaults to NULL, which results in the user interface language
   *   for the page being used.
   *
   * @return string
   *   A translated string representation of the interval.
   */
  public function formatInterval($interval, $granularity = 2, $langcode = NULL) {
    return \format_interval($interval, $granularity, $langcode);
  }

  /**
   * Formats a string containing a count of items.
   *
   * @param int $count
   *   The item count to display.
   * @param string $singular
   *   The string for the singular case. Make sure it is clear this is singular,
   *   to ease translation (e.g. use "1 new comment" instead of "1 new"). Do not
   *   use @count in the singular string.
   * @param string $plural
   *   The string for the plural case. Make sure it is clear this is plural, to
   *   ease translation. Use @count in place of the item count, as in
   *   "@count new comments".
   * @param array $args
   *   An associative array of replacements to make after translation. Instances
   *   of any key in this array are replaced with the corresponding value.
   *   Based on the first character of the key, the value is escaped and/or
   *   themed. See \Drupal\Component\Utility\SafeMarkup::format(). Note that you do
   *   not need to include @count in this array; this replacement is done
   *   automatically for the plural cases.
   * @param array $options
   *   An associative array of additional options. See t() for allowed keys.
   *
   * @return mixed
   *   A translated string.
   *
   */
  public function formatPlural($count, $singular, $plural, array $args = [], array $options = []) {
    $langcode = isset($options['langcode']) ? $options['langcode'] : NULL;
    return \format_plural($count, $singular, $plural, $args, $langcode);
  }

  public function getApiServer() {
    if ($this->apiServer === NULL) {
      $this->apiServer = rtrim($this->config('tag1quo.settings')->get('api.server', static::API_SERVER), '/');
    }
    return $this->apiServer;
  }


  public function getApiVersion() {
    if ($this->apiVersion === NULL) {
      $this->apiVersion = trim($this->config('tag1quo.settings')->get('api.version', static::API_VERSION), '/');
    }
    return $this->apiVersion;
  }

  public function getApiToken() {
    if ($this->apiToken === NULL) {
      $this->apiToken = $this->config('tag1quo.settings')->get('api.token', '');
    }
    return $this->apiToken;
  }

  public function getApiEndpoint($endpoint = self::API_ENDPOINT_HEARTBEAT) {
    return $this->getApiServer() . $endpoint;
  }

  public function gitBinary() {
    if ($this->gitBinary === NULL) {
      $this->gitBinary = FALSE;
      $paths = array_filter(array(
        $this->config('tag1.settings')->get('git.binary'),
        '/usr/local/bin/git',
        '/usr/bin/git',
        'git',
      ));
      foreach ($paths as $path) {
        if (is_executable($path)) {
          $this->gitBinary = $path;
          break;
        }
      }
    }
    return $this->gitBinary;
  }

  /**
   * Retrieves a local file that has "-quo" appended to the file name.
   *
   * @param string $uri
   *   The URI to test.
   *
   * @return string
   *   The Quo specific file or the original $uri.
   */
  protected function getLocalQuoFile($uri) {
    $regexp = '/\.([\w]{3,4})$/i';
    if (preg_match($regexp, $uri)) {
      $quo_uri = preg_replace($regexp, '-quo.$1', $uri);
      if (file_exists($quo_uri)) {
        return $quo_uri;
      }
    }
    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function &getNestedValue(array &$array, array $parents, &$key_exists = NULL) {
    $ref =& $array;
    foreach ($parents as $parent) {
      if (is_array($ref) && array_key_exists($parent, $ref)) {
        $ref =& $ref[$parent];
      }
      else {
        $key_exists = FALSE;
        $null = NULL;
        return $null;
      }
    }
    $key_exists = TRUE;
    return $ref;
  }

  public function getPath($type, $name) {
    return drupal_get_path($type, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function help($path, $arg) {
    return '<p>' . t('Reports details about your Drupal installation to Tag1 Quo to help you manage updates. For more help, see the README.md file included with the module.') . '</p>';
  }

  public function httpClient() {
    if ($this->httpClient === NULL) {
      $this->httpClient = Client::create($this);
    }
    return $this->httpClient;
  }

  /**
   * Indicates whether in debug mode.
   *
   * @return bool
   */
  public function inDebugMode() {
    if ($this->debugMode === NULL) {
      $this->debugMode = !!$this->config('tag1quo.settings')->get('debug.enabled', FALSE);
    }
    return $this->debugMode;
  }

  /**
   * Decodes a JSON string.
   *
   * @param string $raw
   *   The raw data string to decode.
   *
   * @return mixed
   *   The value encoded in JSON in appropriate PHP type. Values "true", "false"
   *   and "null" (case-insensitive) are returned as TRUE, FALSE and NULL
   *   respectively. NULL is returned if the JSON cannot be decoded or if the
   *   encoded data is deeper than the recursion limit.
   */
  public function jsonDecode($raw) {
    $value = \json_decode($raw, TRUE);
    return $value !== NULL ? $value : array();
  }

  /**
   * Returns the JSON representation of a value.
   *
   * @param mixed $data
   *   The data to encode.
   * @param bool $pretty_print
   *   Flag indicating whether to pretty print the JSON output.
   *
   * @return string|false
   *   A JSON encoded string on success or FALSE on failure.
   */
  public function jsonEncode($data, $pretty_print = FALSE) {
    // Encode <, >, ', &, and " using the json_encode() options parameter.
    $options = JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP;
    if ($pretty_print) {
      $options |= JSON_PRETTY_PRINT;
    }
    $value = \json_encode($data, $options);
    return $value !== FALSE ? $value : '[]';
  }

  /**
   * {@inheritdoc}
   */
  public function l($text, $route, $options = array()) {
    $path = $this->routeToPath($route);
    return \l($text, $path, $options);
  }

  /**
   * Return an array of all comments in an extension info file.
   *
   * @param string $filename
   *   The filename to an extension's .info[.yml] file.
   *
   * @return array
   *   Comments.
   */
  protected function loadInfoComments($filename) {
    $comments = array();
    if (file_exists($filename)) {
      // Load the info file.
      $data = file_get_contents($filename);
      // Extract only the comments.
      if (preg_match_all('@[^\s]*[;#][^\r\n]*$@mx', $data, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          $comments[] = $match[0];
        }
      }
    }
    return $comments;
  }

  /**
   * Acquire (or renew) a lock, but do not block if it fails.
   *
   * @param $name
   *   The name of the lock. Limit of name's length is 255 characters.
   * @param $timeout
   *   A number of seconds (float) before the lock expires (minimum of 0.001).
   *
   * @return bool
   *   TRUE if the lock was acquired, FALSE if it failed.
   */
  public function lockAcquire($name, $timeout = 30.0) {
    if (function_exists('lock_acquire')) {
      $lock = \lock_acquire($name, $timeout);
    }
    else {
      $lock = TRUE;
    }
    return $lock;
  }

  /**
   * Release a lock previously acquired by lock_acquire().
   *
   * This will release the named lock if it is still held by the current request.
   *
   * @param $name
   *   The name of the lock.
   */
  public function lockRelease($name) {
    if (function_exists('lock_release')) {
      \lock_release($name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logger($channel = Logger::CHANNEL) {
    if (!isset($this->loggers[$channel])) {
      $this->loggers[$channel] = Logger::create($this, $channel);
    }
    return $this->loggers[$channel];
  }

  /**
   * {@inheritdoc}
   */
  public function logo() {
    $value = ltrim($this->themeSetting($this->logoThemeSetting, ''), '/');
    // Ignore empty or default value.
    if (empty($value) || preg_match('/' . preg_quote($this->defaultLogoPath, '/').  '$/', $value)) {
      return '';
    }
    return $this->getLocalQuoFile($value);
  }

  /**
   * Merges multiple arrays, recursively, and returns the merged array.
   *
   * @param array $_
   *   Each parameter passed must be an array.
   *
   * @return array
   */
  public function mergeDeep($_) {
    $args = func_get_args();
    return $this->mergeDeepArray($args);
  }

  /**
   * Merges multiple arrays, recursively, and returns the merged array.
   *
   * Note: this is a direct backport of the Drupal 8
   * \Drupal\Component\Utility\NestedArray::mergeDeepArray() functionality.
   *
   * @param array[] $arrays
   *   An arrays of arrays to merge.
   * @param bool $preserve_integer_keys
   *   (optional) If given, integer keys will be preserved and merged instead
   *   of appended. Defaults to FALSE.
   *
   * @return array
   *   The merged $arrays.
   *
   * @see https://api.drupal.org/apis/NestedArray::mergeDeepArray
   */
  public function mergeDeepArray(array $arrays = array(), $preserve_integer_keys = FALSE) {
    $result = array();
    foreach ($arrays as $array) {
      foreach ($array as $key => $value) {
        // Renumber integer keys as array_merge_recursive() does unless
        // $preserve_integer_keys is set to TRUE. Note that PHP automatically
        // converts array keys that are integer strings (e.g., '1') to integers.
        if (is_int($key) && !$preserve_integer_keys) {
          $result[] = $value;
        }
        // Recurse when both values are arrays.
        elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
          $result[$key] = static::mergeDeepArray([$result[$key], $value], $preserve_integer_keys);
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $result[$key] = $value;
        }
      }
    }
    return $result;
  }

  abstract public function publicPath();

  abstract public function redirect($route_name, array $options = array(), $status = 302, array $route_parameters = array());

  /**
   * Retrieves the request time.
   *
   * @return int
   */
  public function requestTime() {
    static $requestTime;
    if ($requestTime === NULL) {
      $requestTime = (int) $this->server('REQUEST_TIME', time());
    }
    return $requestTime;
  }

  /**
   * Converts a route into an internal path (if possible).
   *
   * @param string $route
   *   The route name to convert.
   *
   * @return string
   *   The internal path of the route or the original route name if no internal
   *   path was found.
   */
  protected function routeToPath($route) {
    if ($this->routeMap === NULL) {
      $cid = 'tag1quo:routeMap';
      $cache = $this->cache()->get($cid);
      if ($cache && isset($cache->data)) {
        $this->routeMap = $cache->data;
      }
      else {
        $this->routeMap = array();
        if (function_exists('module_invoke_all')) {
          $items = module_invoke_all('menu');
          foreach ($items as $path => $info) {
            if (!empty($info['route'])) {
              $this->routeMap[$path] = $info['route'];
            }
          }
        }
        $this->cache()->set($cid, $this->routeMap);
      }
    }
    return isset($this->routeMap[$route]) ? $this->routeMap[$route] : $route;
  }

  /**
   * Backport of drupal_parse_url().
   *
   * @param string $url
   *   The URL to parse.
   *
   * @return array
   */
  public function parseUrl($url) {
    $options = array(
      'path' => NULL,
      'query' => array(),
      'fragment' => '',
    );

    // External URLs: not using parse_url() here, so we do not have to rebuild
    // the scheme, host, and path without having any use for it.
    if (strpos($url, '://') !== FALSE) {
      // Split off everything before the query string into 'path'.
      $parts = explode('?', $url);
      $options['path'] = $parts[0];

      // If there is a query string, transform it into keyed query parameters.
      if (isset($parts[1])) {
        $query_parts = explode('#', $parts[1]);
        parse_str($query_parts[0], $options['query']);

        // Take over the fragment, if there is any.
        if (isset($query_parts[1])) {
          $options['fragment'] = $query_parts[1];
        }
      }
    }
    else {
      // parse_url() does not support relative URLs, so make it absolute. E.g. the
      // relative URL "foo/bar:1" isn't properly parsed.
      $parts = parse_url('http://example.com/' . $url);

      // Strip the leading slash that was just added.
      $options['path'] = substr($parts['path'], 1);
      if (isset($parts['query'])) {
        parse_str($parts['query'], $options['query']);
      }
      if (isset($parts['fragment'])) {
        $options['fragment'] = $parts['fragment'];
      }
    }

    // The 'q' parameter contains the path of the current page if clean URLs are
    // disabled. It overrides the 'path' of the URL when present, even if clean
    // URLs are enabled, due to how Apache rewriting rules work. The path
    // parameter must be a string.
    if (isset($options['query']['q']) && is_string($options['query']['q'])) {
      $options['path'] = $options['query']['q'];
      unset($options['query']['q']);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function server($property, $default = '') {
    return $this->checkPlain(isset($_SERVER[$property]) ? $_SERVER[$property] : $default);
  }

  /**
   * {@inheritdoc}
   */
  public function setNestedValue(array &$array, array $parents, $value, $force = FALSE) {
    $ref =& $array;
    foreach ($parents as $parent) {

      // PHP auto-creates container arrays and NULL entries without error if $ref
      // is NULL, but throws an error if $ref is set, but not an array.
      if ($force && isset($ref) && !is_array($ref)) {
        $ref = array();
      }
      $ref =& $ref[$parent];
    }
    $ref = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message, $type = 'status', $repeat = TRUE) {
    \drupal_set_message($message, $type, $repeat);
    return $this;
  }

  /**
   * The Settings adapter.
   *
   * @return \Drupal\tag1quo\Adapter\Settings\Settings
   */
  public function settings() {
    if ($this->settings === NULL) {
      $this->settings = Settings::load();
    }
    return $this->settings;
  }

  /**
   * Return a unique site identifier.
   */
  public function siteIdentifier() {
    $siteId = $this->state()->get('tag1quo_siteid');
    if (empty($siteId)) {
      $siteId = hash('sha256', mt_rand() . $this->enableTimestamp());
      $this->state()->set('tag1quo_siteid', $siteId);
    }
    return $siteId;
  }

  /**
   * {@inheritdoc}
   */
  public function siteName() {
    $name = $this->config('system.site')->get('name', $this->server('SERVER_NAME', 'Drupal'));
    return $this->checkPlain($name);
  }

  /**
   * The State adapter.
   *
   * @return \Drupal\tag1quo\Adapter\State\State
   */
  public function state() {
    if (!isset($this->state)) {
      $this->state = State::load();
    }
    return $this->state;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $args
   *   (optional) An associative array of replacements to make after
   *   translation. Based on the first character of the key, the value is
   *   escaped and/or themed. See
   *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   details.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'langcode' (defaults to the current language): A language code, to
   *     translate to a language other than what is used to display the page.
   *   - 'context' (defaults to the empty context): The context the source
   *     string belongs to. See the
   *     @link i18n Internationalization topic @endlink for more information
   *     about string contexts.
   *
   * @return mixed
   *   The translated string.
   */
  public function t($string, array $args = array(), array $options = array()) {
    $langcode = isset($options['langcode']) ? $options['langcode'] : NULL;
    return \t($string, $args, $langcode);
  }

  abstract public function themeSetting($name, $default = NULL, $theme = NULL);

  /**
   * Retrieves the core version.
   *
   * @param bool $major_only
   *   Flag indicating whether to return just the major version.
   *
   * @return string|int
   *   The entire core version or just the major core version if $major_only
   *   was provided.
   */
  public function version($major_only = FALSE) {
    return _tag1quo_drupal_version($major_only);
  }

}
