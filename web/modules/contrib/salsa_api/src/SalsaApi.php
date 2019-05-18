<?php

/**
 * @file
 * Contains \Drupal\salsa_api\SalsaApi.
 */

namespace Drupal\salsa_api;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Service class to execute API calls to Salsa.
 */
class SalsaApi implements SalsaApiInterface {

  /**
   * URL to the Salsa API service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Connection bool flag.
   *
   * @var bool
   */
  protected $connected = FALSE;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The URL Generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The HTTP client to fetch the feed data with.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL Generator.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientFactory $http_client_factory, UrlGeneratorInterface $url_generator, LoggerChannelFactoryInterface $logger_factory, LanguageManagerInterface $language_manager) {
    $this->config = $config_factory->get('salsa_api.settings');
    $this->httpClient = $http_client_factory->fromOptions([
      'base_uri' => $this->config->get('url'),
      'cookies' => TRUE,
      'connect_timeout' => 10,
      'timeout' => $this->config->get('query_timeout')
    ]);
    $this->urlGenerator = $url_generator;
    $this->loggerFactory = $logger_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * Runs a query against Salsa using the HTTP client.
   *
   * @param string $script
   *   The name of the Salsa script to call, e.g. getObject.sjs
   * @param string $query
   *   Query arguments to send to Salsa.
   *
   * @return string
   *   The raw result of the HTTP query if successful.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function query($script, $query) {
    $request = $this->getRequest($script . '?' . $query);
    $response = $this->httpClient->send($request);
    return (string) $response->getBody();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest($path, $method = 'GET') {
    $this->connect();
    $request = new Request($method, $path, [
      'Referer' => $this->urlGenerator->generateFromRoute('<current>', [], ['absolute' => TRUE]),
    ]);
    return $request;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTag($object, $key, $tag) {
    $script = '/deleteTag';
    $query = 'table=' . $object . '&key=' . $key . '&tag=' . $tag;
    $this->query($script, $query);
  }

  /**
   * {@inheritdoc}
   */
  public function getCount($object, array $conditions = array(), $column_count = NULL) {
    if (!$column_count) {
      $column_count = $object . '_KEY';
    }
    $query[] = 'object=' . $object;
    if ($conditions) {
      $query[] = $this->buildConditionString($conditions);
    }
    $query[] = 'columnCount=' . $column_count;
    $result = $this->query('/api/getCount.sjs', implode('&', $query));
    $xml = simplexml_load_string($result);
    return (int) $xml->{$object}->count;
  }

  /**
   * {@inheritdoc}
   */
  public function getCounts($object, array $group_by = array(), array $conditions = array(), $column_count = NULL, array $order_by = array(), $limit = NULL) {
    if (!$column_count) {
      $column_count = $object . '_KEY';
    }
    $query[] = 'object=' . $object;
    if ($conditions) {
      $query[] = $this->buildConditionString($conditions);
    }
    if ($group_by) {
      $query[] = 'groupBy=' . rawurlencode(implode(',', $group_by));
    }
    if ($conditions) {
      $query[] = $this->buildConditionString($conditions);
    }
    $query[] = 'columnCount=' . $column_count;
    if ($order_by) {
      $query[] = 'orderBy=' . rawurlencode(implode(',', $order_by));
    }
    if ($limit) {
      $query[] = 'limit=' . rawurldecode($limit);
    }

    $result = $this->parseXml($this->query('/api/getCounts.sjs', implode('&', $query)));
    if (!isset($result[$object]['count'])) {
      throw new SalsaQueryException((string) $result['h3']);
    }
    else {
      return $result[$object]['count'];
    }

    return $result;
  }

  /**
   * Builds a condition string that can be sent to Salsa.
   *
   * @param array $conditions
   *   Array of conditions. The key is the column name, the value can be one of
   *   - A value: used as is, with the = operator.
   *   - An array of values: Imploded with , and the IN operator is used.
   *   - A value with a %: The LIKE operator is used.
   *   - An array with the keys #operator and #value. Supporter operators are
   *     =, >=, <=, <|>, LIKE, IN, NOT IN, IS NOT EMPTY, IS EMPTY. EMPTY is
   *     equal to an NULL OR empty value.
   *
   * @return null|string
   *   A conditions string.
   */
  public function buildConditionString(array $conditions) {
    $condition_string = NULL;
    $condition_strings = array();
    foreach ($conditions as $key => $condition) {
      $operator = '=';
      if (is_array($condition) && isset($condition['#operator'])) {
        $operator = $condition['#operator'];
        $condition = $condition['#value'];
      }
      // Default to IN operator when we have an array but no explicit operator.
      else {
        if (is_array($condition)) {
          if (count($condition) > 1) {
            $operator = 'IN';
          }
        }
        else {
          if (strpos($condition, '%') !== FALSE) {
            $operator = 'LIKE';
          }
        }
      }
      // Convert an array of values to a comma separated list.
      if (is_array($condition)) {
        $condition = implode(',', $condition);
      }
      $condition_strings[] = $key . $operator . urlencode($condition);
    }
    if (!empty($condition_strings)) {
      $condition_string = 'condition=' . implode('&condition=', $condition_strings);
    }
    return $condition_string;
  }

  /**
   * {@inheritdoc}
   */
  public function upload($file, $properties) {
    $boundary = "----image_upload_" . REQUEST_TIME;

    $data = "--$boundary\r\n";
    $data .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename($file->uri) . "\"\r\n";
    $data .= "Content-Type: " . $file->filemime . "\r\n\r\n";
    $data .= file_get_contents($file->uri) . "\r\n";

    foreach ($properties as $key => $value) {
      $data .= "--$boundary\r\n";
      $data .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n\r\n";
      $data .= $value . "\r\n";
    }
    $data .= "--$boundary--\r\n";

    $curl_arguments = array(CURLOPT_HTTPHEADER => array('Content-Type: multipart/form-data; boundary=' . $boundary));
    // @todo: Handle response. Requires manual header parsing, see
    // http://stackoverflow.com/questions/9183178/php-curl-retrieving-response-headers-and-body-in-a-single-request
    $this->query('/o/' . $properties['organization_KEY'] . '/p/salsa/upload', $data, $curl_arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function getLeftJoin($objects, array $conditions = array(), $limit = NULL, array $include = array(), array $order_by = array(), array $group_by = array()) {
    $query = array();
    if ($objects) {
      $query[] = 'object=' . rawurlencode($objects);
    }
    if ($conditions) {
      $query[] = $this->buildConditionString($conditions);
    }
    if ($limit) {
      $query[] = 'limit=' . rawurldecode($limit);
    }
    if ($include) {
      $query[] = 'include=' . rawurlencode(implode(',', $include));
    }
    if ($group_by) {
      $query[] = 'groupBy=' . rawurlencode(implode(',', $group_by));
    }
    if ($order_by) {
      $query[] = 'orderBy=' . rawurlencode(implode(',', $order_by));
    }

    $result = $this->parseXml($this->query('/api/getLeftJoin.sjs', implode('&', $query)));

    $object_list = str_replace(array('(', ')'), array('-', '-'), $objects);

    // If there are any items, convert them to objects and return.
    $salsaobjects = array();
    if ($result[$object_list]['count'] > 0) {
      // If count is 1, item is the object.
      if ($result[$object_list]['count'] == 1) {
        $result[$object_list]['item'] = array($result[$object_list]['item']);
      }
      $index = 0;
      foreach ($result[$object_list]['item'] as $item) {
        $salsaobjects[$index] = (object) $item;
        $index++;
      }
    }
    return $salsaobjects;
  }

  protected function processObject(array $values) {
    // Filter out unecessary stuff.
    foreach ($values as $key => $value) {
      if (strpos($key, 'BOOLVALUE') !== FALSE) {
        unset($values[$key]);
        continue;
      }

      // Convert false/true strings to actual false/true.
      if ($value === 'false') {
        $values[$key] = FALSE;
      }
      if ($value === 'true') {
        $values[$key] = TRUE;
      }

    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getObject($object, $key) {
    $query[] = 'json';
    $query[] = 'object=' . rawurldecode($object);
    $query[] = 'key=' . rawurldecode($key);

    return $this->processObject($this->parseJson($this->query('/api/getObject.sjs', implode('&', $query))));
  }

  /**
   * {@inheritdoc}
   */
  public function getObjects($object, array $conditions = array(), $limit = NULL, array $include = array(), array $order_by = array(), array $group_by = array()) {
    $script = '/api/getObjects.sjs';
    $query[] = 'json';
    $query[] = 'object=' . rawurldecode($object);
    if ($conditions) {
      $query[] = $this->buildConditionString($conditions);
    }
    if ($limit) {
      $query[] = 'limit=' . rawurldecode($limit);
    }
    if ($include) {
      $query[] = 'include=' . rawurlencode(implode(',', $include));
    }
    if ($group_by) {
      $query[] = 'groupBy=' . rawurlencode(implode(',', $group_by));
    }
    if ($order_by) {
      $query[] = 'orderBy=' . rawurlencode(implode(',', $order_by));
    }

    $results = $this->parseJson($this->query($script, implode('&', $query)));

    $objects = [];
    foreach ($results as $result) {
      $objects[$result['key']] = $this->processObject($result);
    }
    return $objects;
  }

  /**
   * {@inheritdoc}
   */
  public function getReport($key) {
    $query[] = 'report_KEY=' . rawurldecode($key);

    $result = $this->parseXml($this->query('/api/getReport.sjs', implode('&', $query)));

    $report = $result['report'];

    if (empty($report)) {
      throw new SalsaQueryException("Unable to retrieve report #" . $key . ". Does it exist?");
    }
    else {
      return $report;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function describe($table) {
    $query[] = 'object=' . rawurldecode($table);

    $result = $this->parseXml($this->query('/api/describe.sjs', implode('&', $query)));

    $schema = $result[$table]['item'];

    if (empty($schema)) {
      throw new SalsaQueryException("Unable to retrieve schema for table " . $table . ". Does it exist?");
    }
    else {
      return $schema;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function describe2($table) {
    $query[] = 'object=' . rawurldecode($table);

    $result = $this->parseXml($this->query('/api/describe2.sjs', implode('&', $query)));

    $schema = $result[$table]['item'];

    if (empty($schema)) {
      throw new SalsaQueryException("Unable to retrieve schema for table " . $table . ". Does it exist?");
    }
    else {
      return $schema;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save($object, array $fields = array(), array $links = array()) {

    // Unset the object key as that can result in the API expecting new values
    // for a new object.
    unset($fields['object']);

    // Special handling for the support language.
    if ($object == 'supporter') {
      // If there is no language code, set it to the current language, based
      // on the defined mapping.
      if (empty($fields['Language_Code'])) {
        $mapping_key = 'language_code_mapping.' . $this->languageManager->getCurrentLanguage()->getId();
        if ($this->config->get($mapping_key)) {
          $fields['Language_Code'] = $this->config->get($mapping_key);
        }
      }
      // If there is a language code but it is actually a Drupal/ISO-639-1 code
      // map it.
      elseif ($this->config->get('language_code_mapping.' . $fields['Language_Code'])) {
        $fields['Language_Code'] = $this->config->get('language_code_mapping.' . $fields['Language_Code']);
      }
    }

    $query[] = "xml";
    $query[] = 'object=' . rawurldecode($object);
    if ($fields) {
      foreach ($fields as $field => $value) {
        $query[] = rawurlencode($field) . "=" . rawurlencode($value);
      }
    }
    if ($links) {
      foreach ($links as $link) {
        $query[] = 'link=' . rawurlencode($link['link']);
        $query[] = 'linkKey=' . rawurlencode($link['linkkey']);
      }
    }

    // Parse the result, on success, there is a success tag that has an
    // attribute key.
    $result = simplexml_load_string($this->query("/save", implode('&', $query)));
    if (isset($result->success)) {
      return (int) $result->success['key'];
    }
    else {
      // The error message is in the error tag.
      throw new SalsaQueryException((string) $result->error);
    }
  }

  /**
   * Parse the query result returned by SalsaAPI::query().
   *
   * @param string $result
   *   The query result.
   *
   * @return array|null
   *   The XML response converted to an array structure or NULL in case of an
   *   error.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   */
  protected function parseXml($result) {
    if (preg_match("<div class='sjs error'>", $result)) {
      throw new SalsaQueryException(strip_tags($result));
    }
    else {
      return $this->convertObjectToArray(simplexml_load_string($result));
    }
  }

  /**
   * Parse the JSON query result returned by SalsaAPI::query().
   *
   * @param string $result
   *   The query result.
   *
   * @return array|null
   *   The JSON response converted to an array structure.
   *
   * @throws \Drupal\salsa_api\SalsaQueryException
   */
  protected function parseJson($result) {
    $json = Json::decode($result);
    if ($json === NULL) {
      throw new SalsaQueryException(strip_tags($result));
    }

    if (isset($json[0]['result']) && $json[0]['result'] == 'error') {
      throw new SalsaQueryException(implode(', ', $json[0]['messages']));
    }

    return $json;
  }

  /**
   * Connects and authenticates to Salsa API if there is no open connection yet.
   *
   * @return bool
   *   TRUE if the connection was successful.
   *
   * @throws \Drupal\salsa_api\SalsaConnectionException
   */
  public function connect() {
    // We already have a connection, just return TRUE.
    if ($this->connected) {
      return TRUE;
    }

    if (!$this->config->get('url') || !$this->config->get('username') || !$this->config->get('password')) {
      throw new SalsaConnectionException('URL, username and password are required to be configured.');
    }

    $response = $this->httpClient->request('GET', '/api/authenticate.sjs', [
      'query' => [
        'email' => $this->config->get('username'),
        'password' => $this->config->get('password'),
      ],
    ]);
    $response = simplexml_load_string((string) $response->getBody());

    if (isset($response->message) && (string) $response->message == 'Successful Login') {
      $this->connected = TRUE;
      return TRUE;
    }
    else {
      // Connection failed, write log message and disconnect.
      $this->loggerFactory->get('salsa')
        ->error('%url/api/authenticate.sjs?email=**&password=** call result: %reply', array(
          '%url' => $this->config->get('url'),
          '%reply' => $response->asXML(),
        ));
      throw new SalsaConnectionException((string) $response->error);
    }
  }


  /**
   * Convert an XML object into an array.
   *
   * Some XML KEYs returned from Salsa have
   * an "-" in them which makes navigating through an XML object difficult!
   *
   * @param object $object
   *   The xml object that needs to be converted into an array.
   *
   * @return array
   *   An array containing the converted xml object.
   */
  protected function convertObjectToArray($object) {
    $return = NULL;
    if (is_array($object)) {
      foreach ($object as $key => $value) {
        $return[$key] = $this->convertObjectToArray($value);
      }
    }
    elseif (is_object($object)) {
      $var = get_object_vars($object);
      if ($var) {
        foreach ($var as $key => $value) {
          $return[$key] = ($key && !$value) ? NULL : $this->convertObjectToArray($value);
        }
      }
      else {
        return $object;
      }
    }
    else {
      return $object;
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function testConnect($url, $username, $password) {
    try {
      $response = $this->httpClient->request('GET', $url . "/api/authenticate.sjs", [
        'query' => ['email' => $username, 'password' => $password],
      ]);
      $response = simplexml_load_string((string) $response->getBody());

      if (isset($response->message) && (string) $response->message == 'Successful Login') {
        return static::CONNECTION_OK;
      }
      elseif (isset($response->error) && (string) $response->error == 'Invalid login, please try again.') {
        return static::CONNECTION_AUTHENTICATION_FAILED;
      }
    } catch (RequestException $e) {
      return static::CONNECTION_WRONG_URL;
    }
    return static::CONNECTION_WRONG_URL;
  }

}
