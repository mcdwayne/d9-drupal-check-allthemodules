<?php

namespace Drupal\odoo_api\OdooApi;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactory;
use Drupal\odoo_api\Event\OdooApiFailedCallEvent;
use Drupal\odoo_api\Event\OdooApiSuccessCallEvent;
use Drupal\odoo_api\OdooApi\Exception\AuthException;
use fXmlRpc\Client as XmlRpcClient;
use fXmlRpc\Transport\HttpAdapterTransport;
use GuzzleHttp\Client as GuzzleHttpClient;
use Http\Adapter\Guzzle6\Client as GuzzleClientAdapter;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class OdooApiClient.
 */
class Client implements ClientInterface {

  const XMLRPC_ENDPOINT_COMMON = 'xmlrpc/2/common';
  const XMLRPC_ENDPOINT_OBJECT = 'xmlrpc/2/object';

  const XMLRPC_COMMON_METHOD_VERSION = 'version';
  const XMLRPC_COMMON_METHOD_AUTHENTICATE = 'authenticate';

  const XMLRPC_OBJECT_METHOD_SEARCH = 'search';
  const XMLRPC_OBJECT_METHOD_SEARCH_COUNT = 'search_count';
  const XMLRPC_OBJECT_METHOD_READ = 'read';
  const XMLRPC_OBJECT_METHOD_FIELDS_GET = 'fields_get';
  const XMLRPC_OBJECT_METHOD_SEARCH_READ = 'search_read';
  const XMLRPC_OBJECT_METHOD_CREATE = 'create';
  const XMLRPC_OBJECT_METHOD_WRITE = 'write';
  const XMLRPC_OBJECT_METHOD_UNLINK = 'unlink';

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Http adapter object.
   *
   * @var \fXmlRpc\Transport\HttpAdapterTransport
   * @see https://packagist.org/packages/lstrojny/fxmlrpc
   */
  protected $httpTransport;

  /**
   * XMLRPC client objects cache.
   *
   * @var \fXmlRpc\Client[]
   */
  protected $xmlRpcClient;

  /**
   * The date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Constructs a new OdooApiClient object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The date time service.
   */
  public function __construct(ConfigFactory $config_factory, EventDispatcherInterface $event_dispatcher, TimeInterface $date_time) {
    $this->config = $config_factory->get('odoo_api.api_client');
    $this->eventDispatcher = $event_dispatcher;
    $this->dateTime = $date_time;

    $this->httpTransport = new HttpAdapterTransport(
      new GuzzleMessageFactory(),
      new GuzzleClientAdapter(new GuzzleHttpClient())
    );
  }

  /**
   * Gets API connection URL.
   *
   * @return string
   *   Base connection URL without trailing slash.
   */
  protected function getBaseRequestUrl() {
    if ($url = $this->config->get('url')) {
      return $url;
    }

    return 'https://' . $this->config->get('database') . '.odoo.com';
  }

  /**
   * Gets API method call URL.
   *
   * @param string $endpoint
   *   XMLRPC endpoint, like 'xmlrpc/2/object'.
   *
   * @return string
   *   XMLRPC request URL.
   */
  protected function getRequestUrl($endpoint) {
    return $this->getBaseRequestUrl() . '/' . $endpoint;
  }

  /**
   * Gets XMLRPC client instance.
   *
   * @param string $endpoint
   *   XMLRPC endpoint, like 'xmlrpc/2/object'.
   *
   * @return \fXmlRpc\Client
   *   XMLRPC client object.
   */
  protected function getXmlRpcClient($endpoint) {
    if (!isset($this->xmlRpcClient[$endpoint])) {
      $this->xmlRpcClient[$endpoint] = new XmlRpcClient(
        $this->getRequestUrl($endpoint),
        $this->httpTransport
      );
    }
    return $this->xmlRpcClient[$endpoint];
  }

  /**
   * Executes XMLRPC call.
   *
   * @param string $endpoint
   *   XMLRPC endpoint, like 'xmlrpc/2/object'.
   * @param string $method
   *   API method.
   * @param array $params
   *   API params.
   *
   * @return mixed
   *   API response.
   */
  protected function runXmlRpcCall($endpoint, $method, array $params = []) {
    return $this->getXmlRpcClient($endpoint)->call($method, $params);
  }

  /**
   * Gets Odoo user ID.
   *
   * @return int|false
   *   Integer UID or FALSE.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\AuthException
   *   Authentication exception.
   */
  protected function getUid() {
    if (!isset($this->uid)) {
      $this->uid = $this->runXmlRpcCall(static::XMLRPC_ENDPOINT_COMMON, static::XMLRPC_COMMON_METHOD_AUTHENTICATE, [
        $this->config->get('database'),
        $this->config->get('username'),
        $this->config->get('password'),
        // Not sure why there's an empty array but Odoo requires it.
        // @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#logging-in
        [],
      ]);
    }

    if (!$this->uid) {
      throw new AuthException();
    }

    return $this->uid;
  }

  /**
   * Runs Odoo XMLRPC object endpoint call.
   *
   * @param string $model_name
   *   Odoo model name, like 'res.partner'.
   * @param string $method
   *   Odoo model method name; a 'search', 'read' or similar.
   * @param array $arguments
   *   Odoo model method arguments.
   * @param array $named_arguments
   *   Odoo model method named arguments. If supplied, they will be mapped to
   *   Python function named arguments on Odoo side.
   *
   * @return mixed
   *   Odoo API returned value.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\AuthException
   *
   * @see https://www.odoo.com/documentation/11.0/webservices/odoo.html#calling-methods
   * @see https://www.odoo.com/documentation/11.0/reference/orm.html#common-orm-methods
   */
  protected function runOdooObjectCall($model_name, $method, array $arguments = [], array $named_arguments = []) {
    $params = [
      $this->config->get('database'),
      $this->getUid(),
      $this->config->get('password'),
      $model_name,
      $method,
      $arguments,
      $named_arguments,
    ];

    // Track a time of a call request.
    $timer_key = __FUNCTION__ . '-' . $this->dateTime->getCurrentMicroTime();
    Timer::start($timer_key);

    try {
      $output = $this->runXmlRpcCall(static::XMLRPC_ENDPOINT_OBJECT, 'execute_kw', $params);
      Timer::stop($timer_key);
    }
    catch (\Exception $e) {
      // Trigger failed Odoo API Call event.
      Timer::stop($timer_key);
      $this->eventDispatcher->dispatch(OdooApiFailedCallEvent::EVENT_NAME, new OdooApiFailedCallEvent($model_name, $method, $this->getUid(), $arguments, $named_arguments, Timer::read($timer_key), $e));

      throw $e;
    }

    // Trigger success Odoo API Call event.
    $this->eventDispatcher->dispatch(OdooApiSuccessCallEvent::EVENT_NAME, new OdooApiSuccessCallEvent($model_name, $method, $this->getUid(), $arguments, $named_arguments, $output, Timer::read($timer_key)));

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersionInfo() {
    return $this->runXmlRpcCall(static::XMLRPC_ENDPOINT_COMMON, static::XMLRPC_COMMON_METHOD_VERSION);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    try {
      return $this->getUid();
    }
    catch (AuthException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search($model_name, array $filter = [], $offset = NULL, $limit = NULL, $order = NULL) {
    // Append offset and limit arguments.
    $named_arguments = [];
    if (isset($offset)) {
      $named_arguments['offset'] = $offset;
    }
    if (isset($limit)) {
      $named_arguments['limit'] = $limit;
    }
    if (isset($order)) {
      $named_arguments['order'] = $order;
    }

    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_SEARCH, [$filter], $named_arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function count($model_name, array $filter = []) {
    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_SEARCH_COUNT, [$filter]);
  }

  /**
   * {@inheritdoc}
   */
  public function read($model_name, array $ids, $fields = NULL) {
    $named_arguments = [];

    if (isset($fields)) {
      $named_arguments['fields'] = $fields;
    }

    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_READ, [$ids], $named_arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldsGet($model_name, array $fields = [], $attributes = NULL) {
    $default = ['type', 'string', 'help'];
    $named_attributes = [
      'attributes' => isset($attributes) ? $attributes : $default,
    ];

    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_FIELDS_GET, [$fields], $named_attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function searchRead($model_name, array $filter = [], $fields = NULL, $offset = NULL, $limit = NULL, $order = NULL) {
    // Append fields, offset and limit arguments.
    $named_arguments = [];
    if (isset($fields)) {
      $named_arguments['fields'] = $fields;
    }
    if (isset($offset)) {
      $named_arguments['offset'] = $offset;
    }
    if (isset($limit)) {
      $named_arguments['limit'] = $limit;
    }
    if (isset($order)) {
      $named_arguments['order'] = $order;
    }

    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_SEARCH_READ, [$filter], $named_arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function searchReadIterate($model_name, array $filter = [], $fields = NULL, $page_size = 50, $order = NULL) {
    $offset = 0;
    while (TRUE) {
      $results = $this->searchRead($model_name, $filter, $fields, $offset, $page_size, $order);
      if (empty($results)) {
        // No more results.
        break;
      }
      foreach ($results as $row) {
        yield $row;
      }
      if (count($results) < $page_size) {
        // That was the last page.
        return;
      }
      $offset += $page_size;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function create($model_name, array $fields = []) {
    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_CREATE, [$fields]);
  }

  /**
   * {@inheritdoc}
   */
  public function write($model_name, array $ids, array $fields) {
    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_WRITE, [$ids, $fields]);
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($model_name, array $ids) {
    return $this->runOdooObjectCall($model_name, static::XMLRPC_OBJECT_METHOD_UNLINK, [$ids]);
  }

  /**
   * {@inheritdoc}
   */
  public function rawModelApiCall($model_name, $method, array $arguments = [], array $named_arguments = []) {
    return $this->runOdooObjectCall($model_name, $method, $arguments, $named_arguments);
  }

}
