<?php

namespace Drupal\xero;

use Radcliffe\Xero\XeroClient;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Serializer\Serializer;
// use Drupal\xero\XeroQueryInterface;

/**
 * Provides a query builder service for HTTP requests to Xero.
 *
 * This matches functionality provided by Drupal 7 xero module and the old
 * PHP-Xero library.
 */
class XeroQuery /*implements XeroQueryInterface */ {

  static protected $operators = array('==', '!=', 'StartsWith', 'EndsWith', 'Contains', 'guid', 'NULL', 'NOT NULL');

  /**
   * The options to pass into guzzle.
   */
  protected $options;

  /**
   * The conditions for the where query parameter.
   */
  protected $conditions;

  /**
   * The output format. One of json, xml, or pdf.
   */
  protected $format = 'xml';

  /**
   * The xero method to use.
   */
  protected $method = 'get';

  /**
   * The xero UUID to use for a quick filter in get queries.
   */
  protected $uuid;

  /**
   * The xero type plugin id.
   */
  protected $type;

  /**
   * The xero data type type definition.
   */
  protected $type_definition;

  /**
   * The xero data object.
   */
  protected $data;

  /**
   * The xero client.
   */
  protected $client;

  /**
   * The serializer object.
   */
  protected $serializer;

  /**
   * The typed data manager.
   */
  protected $typed_data;

  /**
   * Logger factory
   */
  protected $logger;

  /**
   * @protected \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend interface for 'xero_query' bin.
   */
  protected $cache;

  /**
   * Construct a Xero Query object.
   *
   * @param NULL|XeroClient $client
   *   The xero client object to make requests.
   * @param Serializer $serializer
   *   The serialization service to handle normalization and denormalization.
   * @param TypedDataManagerInterface $typed_data
   *   The Typed Data manager for retrieving definitions of xero types.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service for error logging.
   * @param CacheBackendInterface $cache
   *   The cache backend for Xero Query cache.
   */
  public function __construct($client, Serializer $serializer, TypedDataManagerInterface $typed_data, LoggerChannelFactoryInterface $logger_factory, CacheBackendInterface $cache) {
    $this->client = $client;
    $this->serializer = $serializer;
    $this->typed_data = $typed_data;
    $this->logger = $logger_factory->get('xero');
    $this->cache = $cache;
  }

  /**
   * Get the xero type. Useful for unit tests.
   *
   * @return string
   *   The xero type set on this object.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set the xero type by plugin id.
   *
   * @param string $type
   *   The plugin id corresponding to a xero type i.e. xero_account.
   * @return XeroQuery
   *   The query object for chaining.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setType($type) {
    try {
      $this->type_definition = $this->typed_data->getDefinition($type);
      $this->type = $type;
    }
    catch (PluginNotFoundException $e) {
      throw $e;
    }

    return $this;
  }

  /**
   * Get the HTTP method to use. Useful for unit tests.
   *
   * @return string
   *   The HTTP Method: get or post.
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Set which http method to use for the query. This is "type" from xero_query().
   *
   * @param string $method
   *   The method to use, which is one of "get" or "post". The HTTP PUT method
   *   will be automatically used for updating records.
   * @return XeroQuery
   *   The query object for chaining.
   */
  public function setMethod($method) {
    if (!in_array($method, array('get', 'post', 'put'))) {
      throw new \InvalidArgumentException('Invalid method given.');
    }
    $this->method = $method;

    // The content type must be text/xml despite the Xero API specifically
    // stating it should be x-www-form-urlencoded because.
    if ($this->method === 'post' || $this->method === 'put') {
      $this->addHeader('Content-Type', 'text/xml;charset=UTF-8');
      $this->setFormat('xml');
    }

    return $this;
  }

  /**
   * Get the format to return. Useful for unit tests.
   *
   * @return string
   *   The format to return: json, xml, or pdf.
   */
  public function getFormat() {
    return $this->format;
  }

  /**
   * Set the format to use for the query. This is "method" from xero_query().
   *
   * @todo support pdf format.
   *
   * @param string $format
   *   The format ot use, which is one of "xml", "json", or "pdf".
   * @return XeroQuery
   *   The query object for chaining.
   */
  public function setFormat($format) {
    if (!in_array($format, array('json', 'xml', 'pdf'))) {
      throw new \InvalidArgumentException('Invalid format given.');
    }
    $this->format = $format;

    $this->addHeader('Accept', 'application/' . $this->format);

    return $this;
  }

  /**
   * Get the UUID that is set for the query. Useful for unit tests.
   *
   * @param string
   *   The Universally-Unique ID that is set on the object.
   */
  public function getId() {
    return $this->uuid;
  }

  /**
   * Set the Xero UUID for the request. Useful only in get method.
   *
   * @param string $uuid
   *   The universally-unique ID.
   * @return XeroQuery
   *   The query object for chaining.
   */
  public function setId($uuid) {
    if (!Uuid::isValid($uuid)) {
      throw new \InvalidArgumentException('UUID is not valid');
    }
    $this->uuid = $uuid;

    return $this;
  }

  /**
   * Set the modified-after filter.
   *
   * @param integer $timestamp
   *   A UNIX timestamp to use. Should be UTC.
   * @return $this
   *   The query object for chaining.
   */
  public function setModifiedAfter($timestamp) {
    $this->addHeader('If-Modified-Since', $timestamp);

    return $this;
  }

  /**
   * Get the data object that was set.
   *
   * @return \Drupal\Core\TypedData\ListInterface
   *   A xero data type or NULL.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Set the data object to send in the request.
   *
   * @param \Drupal\Core\TypedData\ListInterface $data
   *   The xero data object wrapped in a list interface.
   * @return XeroQuery
   *   The query object for chaining.
   */
  public function setData(ListInterface $data) {
    if (isset($this->type) && $this->type <> $data->getItemDefinition()->getDataType()) {
      throw new \InvalidArgumentException('The xero data type set for this query does not match the data.');
    }
    elseif (!isset($this->type)) {
      $this->type = $data->getItemDefinition()->getDataType();
    }

    $this->data = $data;

    return $this;
  }

  /**
   * Get the xero query options. Useful for unit tests.
   *
   * @return []
   *   An associative array of options to pass to Guzzle.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Get the type data definition property.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition class or NULL if not set.
   */
  public function getDefinition() {
    return $this->type_definition;
  }

  /**
   * Add a condition to the query.
   *
   * @param $field
   * @param $value
   * @param $op
   *   The operation to use, which is one of the following operators.
   *     - ==: Equal to the value
   *     - !=: Not equal to the value
   *     - StartsWith: Starts with the value
   *     - EndsWith: Ends with the value
   *     - Contains: Contains the value
   *     - guid: Equality for guid values. See Xero API.
   *     - NOT NULL: Not empty.
   * @return XeroQuery
   */
  public function addCondition($field, $value = '', $op = '==') {

    if (!in_array($op, self::$operators)) {
      throw new \InvalidArgumentException('Invalid operator');
    }

    // Change boolean into a string value of the same name.
    if (is_bool($value)) {
      $value = $value ? 'true' : 'false';
    }

    // Construction condition statement based on operator.
    if (in_array($op, array('==', '!='))) {
      $this->conditions[] = $field . $op . '"' . $value . '"';
    }
    elseif ($op == 'guid') {
      $this->conditions[] = $field . '= Guid("' . $value . '")';
    }
    elseif ($op == 'NULL') {
      $this->conditions[] = $field . '==null';
    }
    elseif ($op == 'NOT NULL') {
      $this->conditions[] = $field . '!=null';
    }
    else {
      $this->conditions[] = $field . '.' . $op . '("' . $value . '")';
    }

    return $this;
  }

  /**
   * Add a logical operator AND or OR to the conditions array.
   *
   * @param $op
   *   Operator AND or OR.
   * @return XeroQuery
   *   The query object for chaining.
   */
  public function addOperator($op = 'AND') {
    if (!in_array($op, array('AND', 'OR'))) {
      throw new \InvalidArgumentException('Invalid logical operator.');
    }

    $this->conditions[] = $op;

    return $this;
  }

  /**
   * Add an order by to the query.
   *
   * @param $field
   *   The full field name to use. See Xero API.
   * @param $dir
   *   The direction. either ASC or DESC.
   * @return XeroQuery
   *   The query object for chaining.
   */
  public function orderBy($field, $dir = 'ASC') {
    if ($dir == 'DESC') {
      $field .= ' ' . $dir;
    }

    $this->addQuery('order', $field);

    return $this;
  }

  /**
   * Add a query parameter. This will overwrite any other value set for the
   * key.
   *
   * @param $key
   *   The query parameter key.
   * @param $value
   *   The query parameter value.
   */
  protected function addQuery($key, $value) {
    if (!isset($this->options['query'])) {
      $this->options['query'] = array();
    }

    $this->options['query'][$key] = $value;
  }

  /**
   * Set a header option. This will overwrite any other value set.
   *
   * @param $name
   *   The header option name.
   * @param $value
   *   The header option valu.
   */
  protected function addHeader($name, $value) {
    if (!isset($this->options['headers'])) {
      $this->options['headers'] = array();
    }

    $this->options['headers'][$name] = $value;
  }

  /**
   * Explode the conditions into a query parameter.
   *
   * @todo Support query OR groups.
   */
  protected function explodeConditions() {
    if (!empty($this->conditions)) {
      $value = implode(' ', $this->conditions);
      $this->addQuery('where', $value);
    }
  }

  /**
   * Get the conditions array. Useful for unit tests.
   *
   * @return []
   *   An array of conditions.
   */
  public function getConditions() {
    return $this->conditions;
  }

  /**
   * Validate the query before execution to make sure that query parameters
   * make sense for the method for instance.
   *
   * @return boolean
   *   TRUE if the query should be validated. Otherwise an
   *   IllegalArgumentException will be thrown.
   */
  public function validate() {

    if ($this->type === NULL) {
      throw new \InvalidArgumentException('The query must have a type set.');
    }

    if (!in_array($this->method, ['post', 'put']) && $this->format <> 'xml') {
      throw new \InvalidArgumentException('The format must be XML for creating or updating data.');
    }

    if ($this->method == 'get' && $this->data !== NULL) {
      throw new \InvalidArgumentException('Invalid use of data object for fetching data.');
    }

    if ($this->format == 'pdf' && !in_array($this->type, array('xero_invoice', 'xero_credit_note'))) {
      throw new \InvalidArgumentException('PDF format may only be used for invoices or credit notes.');
    }

    return TRUE;
  }

  /**
   * Execute the Xero query.
   *
   * @return boolean|\Drupal\Core\TypedData\Plugin\DataType\ItemList
   *   The TypedData object in the response.
   */
  public function execute() {
    try {
      $this->validate();

      // @todo Add summarizeErrors for post if posting multiple objects.

      $this->explodeConditions();

      // Change to PUT if UUID is not set for a post.
      if ($this->method === 'post' && !$this->uuid) {
        $this->setMethod('put');
      }

      $data_class = $this->type_definition['class'];
      $endpoint = $data_class::$plural_name;
      $context = [
        'xml_root_node_name' => $endpoint,
        'plugin_id' => $this->type,
      ];

      if ($this->data !== NULL) {
        $this->options['body'] = $this->serializer->serialize($this->data, $this->format, $context);
      }

      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->client->{$this->method}($endpoint, $this->options);

      /** @var \Drupal\xero\Plugin\DataType\XeroItemList $data */
      $data = $this->serializer->deserialize($response->getBody()->getContents(), $data_class, $this->format, $context);

      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('%message: %uri %request %response', [
        '%message' => $e->getMessage(),
        '%uri' => $e->getRequest()->getUri(),
        '%request' => $e->getRequest()->getBody()->getContents(),
        '%response' => $e->getResponse()->getBody()->getContents()]);
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('%message', array('%message' => $e->getMessage()));
      return FALSE;
    }
  }

  /**
   * Fetch a given data type from cache, if it exists, or fetch it from Xero,
   * and store in cache.
   *
   * @todo Support filters.
   *
   * @param string $type
   *   The Xero data type plugin id.
   *
   * @return \Drupal\xero\Plugin\DataType\XeroItemList|bool
   *   The cached data normalized into a list data type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCache($type) {
    $data = NULL;
    $cid = $type;

    // Get the cached data.
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $this
      ->setType($type)
      ->setFormat('xml')
      ->setMethod('get');

    $data = $this->execute();

    if ($data) {
      $this->setCache($cid, $data);
    }

    return $data;
  }

  /**
   * Store the typed data into cache based on the plugin id.
   *
   * @param string $cid
   *   The cache identifier to store the data as
   * @param \Drupal\Core\TypedData\ListInterface $data
   *   The typed data to sets in cache.
   */
  protected function setCache($cid, ListInterface $data) {
    $tags = $this->getCacheTags($data);

    $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);

    // Invalidate the cache right away because there really is not a good time
    // to do this for 3rd party data. This will keep it in cache until the next
    // garbage collection period.
    $this->cache->invalidate($cid);
  }

  /**
   * Get the cache tag for the query.
   *
   * @param \Drupal\Core\TypedData\ListInterface $data
   *   The item list to extract type information from.
   * @return string[]
   *   Return the cache tags to use for the cache.
   */
  protected function getCacheTags(ListInterface $data) {
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
    $definition = $data->getItemDefinition();
    /** @var \Drupal\xero\TypedData\XeroTypeInterface $type_class */
    $type_class = $definition->getClass();

    return [$type_class::$plural_name];
  }

  /**
   * Confirm that the Xero Query object can make queries.
   *
   * @return boolean
   *   TRUE if the Xero Client is ready to go.
   */
  public function hasClient() {
    return $this->client !== FALSE;
  }
}
