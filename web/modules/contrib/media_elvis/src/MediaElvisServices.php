<?php

namespace Drupal\media_elvis;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;

/**
 * Class MediaElvisServices.
 */
class MediaElvisServices implements MediaElvisServicesInterface  {

  /**
   * Session id keyvalue key name.
   *
   * @var string
   */
  const ELVIS_SESSION_ID_KEY = 'media_elvis_session_id';

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger channel for Media Elvis.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The expirable key value store service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValue;

  /**
   * Session id.
   *
   * @var string
   */
  protected $sessionId = '';

  /**
   * Base server uri.
   *
   * @var string
   */
  protected $baseUri = '';

  /**
   * Array with username and password keys.
   *
   * @var array
   */
  protected $credentialsData = [];

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Http client service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value
   *   Expirable storage factory service.
   */
  public function __construct(Client $http_client, LoggerChannelFactoryInterface $logger_factory, KeyValueExpirableFactoryInterface $key_value) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('media_elvis');
    $this->keyValue = $key_value->get('media_elvis');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Investigate if (and how long) can we cache this session id.
   */
  public function login() {
    if ($session_id = $this->keyValue->get(self::ELVIS_SESSION_ID_KEY, NULL)) {
      return $session_id;
    }

    try {
      $credentials = $this->getCredentials();
      $options['query']['cred'] = $credentials;
      $response_object = $this->doRequest($this->baseUri, 'services/login', $options);
    }
    catch (\Exception $e) {
      $this->logger->error('Login error: %e', ['%e' => $e->getMessage()]);
      drupal_set_message('Failed to login to Media Elvis instance. Please check your credentials', 'error');
      return '';
    }

    if ($response_object->loginSuccess !== TRUE) {
      $this->logger->warning('Failed to log in to Media Elvis instance with error: %e.', ['%e' => $response_object->loginFaultMessage]);
      drupal_set_message('Failed to login to Media Elvis instance. Please check your credentials', 'warning');
      return '';
    }

    $session_id = $response_object->sessionId;
    $this->keyValue->setWithExpire(self::ELVIS_SESSION_ID_KEY, $session_id, 60);
    return $session_id;
  }

  /**
   * {@inheritdoc}
   */
  public function search($query, $offset = 0, $per_page = 50, $sort = 'assetCreated-desc', array $additional = []) {
    $results = [];

    $query_options = [
      'query' => $additional + [
        'q' => $query,
        'start' => $offset,
        'num' => $per_page,
        'sort' => $sort,
        'appendRequestSecret' => TRUE,
      ],
    ];

    // We only support images atm.
    $query_options['query']['facets'] = (!empty($additional['facets'])) ? $query_options['query']['facets'] . ',assetDomain' : 'assetDomain';
    $query_options['query']['facet.assetDomain.selection'] = 'image';

    try {
      $session_id = $this->login();
      $results = $this->doRequest($this->baseUri, 'services/search', $query_options, $session_id);
    }
    catch (\Exception $e) {
      $this->logger->error('Search error: %e', ['%e' => $e->getMessage()]);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function browse($path = '', $from_root = '') {
    $folders = [];

    $query_options['query']['path'] = $path;
    if (!empty($from_root)) {
      $query_options['query']['fromRoot'] = $from_root;
    }

    try {
      $session_id = $this->login();
      $folders = $this->doRequest($this->baseUri, 'services/browse', $query_options, $session_id);
    }
    catch (\Exception $e) {
      $this->logger->error('Browse error: %e', ['%e' => $e->getMessage()]);
    }


    return $folders;
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    // @todo Out of scope for now.
  }

  /**
   * Constructs and issues a request to the Elvis service.
   *
   * @param string $base_uri
   *   The base server uri that includes the trailing slash.
   * @param string $service
   *   The service part ie services/login.
   * @param array $options
   *   (optional) Array of options to pass along the request.
   * @param string $session_id
   *   (optional) The session id for this request.
   * @param string $method
   *   (optional) HTTP Request Method: GET, POST,...
   *
   * @throws \Exception
   *   If error code is returned.
   *
   * @return \stdClass
   *   The response returned from the http client.
   */
  public function doRequest($base_uri, $service, array $options = [], $session_id = '', $method = 'GET') {
    $uri = $base_uri . $service;

    if ($session_id) {
      $uri = $uri . ';jsessionid=' . $session_id;
    }

    $response = $this->httpClient->request($method, $uri, $options);
    $json = $response->getBody()->getContents();
    $response_object = json_decode($json);

    if (isset($response_object->errorcode)) {
      throw new \Exception($response_object->message);
    }

    return $response_object;
  }

  /**
   * {@inheritdoc}
   */
  public function setBaseUri($base_uri) {
    $this->baseUri = $base_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setCredentialsData($username, $password) {
    $this->credentialsData = [
      'username' => $username,
      'password' => $password,
    ];
  }

  /**
   * Get credentials.
   *
   * @return string
   *   The cred string obtained by base64Encoding username:password.
   */
  protected function getCredentials() {
    return base64_encode("{$this->credentialsData['username']}:{$this->credentialsData['password']}");
  }
}
