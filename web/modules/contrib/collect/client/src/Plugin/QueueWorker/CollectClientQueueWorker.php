<?php
/**
 * @file
 * Contains \Drupal\collect_client\Plugin\QueueWorker\CollectClientQueueWorker.
 */

namespace Drupal\collect_client\Plugin\QueueWorker;

use Drupal\collect_client\CollectItem;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class CollectClientQueueWorker
 *
 * @QueueWorker(
 *   id = "collect_client",
 *   title = @Translation("Submit Collect items"),
 *   cron = {}
 * )
 */
class CollectClientQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The queue name.
   *
   * The name of the queue used send the collected data to the server.
   */
  const QUEUE_NAME = 'collect_client';

  /**
   * The plugin manager for the queue item handle plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The sorted list of plugins.
   *
   * @var \Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface[]
   */
  protected $pluginInstances;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The config instance.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The injected current HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The cache interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a CollectClientQueueWorker.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $plugin_manager, LoggerChannelInterface $logger, ClientInterface $http_client, SerializerInterface $serializer, Config $config, Request $request, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $plugin_manager;
    $this->logger = $logger;
    $this->httpClient = $http_client;
    $this->serializer = $serializer;
    $this->config = $config;
    $this->request = $request;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.collect_client.item_handler'),
      $container->get('logger.channel.collect_client'),
      $container->get('http_client'),
      $container->get('serializer'),
      $container->get('config.factory')->get('collect_client.settings'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Drush cron without '--uri' option messes up the Origin URI for new
    // submissions.
    if ($this->request->getHost() == 'default') {
      throw new SuspendQueueException('Site host not set. Forgot to use --uri with Drush?');
    }

    $plugin_instance = $this->getSupportingPluginInstance($data);
    if ($plugin_instance) {
      $data = $plugin_instance->handle($data);
      $this->sendItem($data);
    }
    else {
      throw new \Exception('No handler defined supporting the queued item.');
    }
  }

  /**
   * Gets the sorted list of item plugins.
   *
   * @return \Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface[]
   *   The list of item handler plugin instances.
   */
  public function getPluginInstances() {
    if (!$this->pluginInstances) {
      $this->pluginInstances = array();
      $definitions = $this->pluginManager->getDefinitions();
      $instance_orders = array();
      if (!empty($definitions)) {
        foreach ($definitions as $id => $definition) {
          $weight = !empty($definition['weight']) ? $definition['weight'] : 0;
          $instance_orders[$weight][] = $this->pluginManager->createInstance($id, $definition);
        }
      }

      ksort($instance_orders);

      foreach ($instance_orders as $instances) {
        $this->pluginInstances = array_merge($this->pluginInstances, $instances);
      }
    }
    return $this->pluginInstances;
  }

  /**
   * Gets the plugin supporting the given item.
   *
   * @param mixed $item
   *   The item to get the supporting plugin.
   *
   * @return \Drupal\collect_client\Plugin\collect_client\ItemHandlerInterface|null
   *   The item handler plugin supporting the given item.
   */
  public function getSupportingPluginInstance($item) {
    foreach ($this->getPluginInstances() as $plugin_instance) {
      if ($plugin_instance->supports($item)) {
        return $plugin_instance;
      }
    }
    return NULL;
  }

  /**
   * Sends the item to the service.
   *
   * @param \Drupal\collect_client\CollectItem $item
   *   The item to send.
   *
   * @throws \Drupal\Core\Queue\SuspendQueueException
   *   In the event that the service can not be reached.
   * @throws \Exception
   *   In any other situation processing is not possible right now but might be
   *   successful at a later point.
   */
  protected function sendItem(CollectItem $item) {
    $url = $this->config->get('service.url');
    if ((isset($this->request->cookies) && $this->request->cookies->has('XDEBUG_SESSION')) || isset($_SERVER['XDEBUG_CONFIG'])) {
      $options['query']['XDEBUG_SESSION_START'] = 'phpstorm';
    }
    if (empty($url)) {
      // If errors on send happens, remove the cache key.
      if ($this->cache->get($item->cache_key)) {
        $this->cache->delete($item->cache_key);
      }
      throw new SuspendQueueException('Can not send item to service. No URL is defined.');
    }

    $username = $this->config->get('service.user');
    $password = $this->config->get('service.password');
    $options = array();
    $options['headers']['Content-Type'] = 'application/json';
    if ($username) {
      $options['auth'][] = $username;
      $options['auth'][] = $password;
    }

    // @todo explicitly check that data item is collectjson.
    // Special support for CollectJSON field definition.
    try {
      foreach ($this->getTransferObjects($item) as $item_values) {
        $response = $this->httpClient->post($url, array_merge($item_values, $options));
        if ($response->getStatusCode() != 201) {
          throw new \Exception('Expected status code 201 Created, got ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . ' when submitting item to ' . $url . '.', 0);
        }
        $this->logger->info('Successfully sent submission to {url}. The record was stored as {location}.', array(
          'url' => $url,
          'location' => $response->getHeader('Location'),
        ));
        if ($item->cache_key) {
          $this->cacheUuid($item->cache_key, $response);
        }
      }
    }
    catch (RequestException $exception) {
      $message = $exception->getMessage();
      // Get a response.
      if ($response = $exception->getResponse()) {
        // In case of server error, suspend further handling of the queue.
        if ($response->getStatusCode()[0] == '5') {
          $message = $this->parseResponse($response, 'message') ?: $message;
          throw new SuspendQueueException($message, 0, $exception);
        }
      }
      throw new \Exception($message, 0, $exception);
    }
  }

  /**
   * Caches received values collect container's UUID.
   *
   * @param string $cache_key
   *   The entity cache key.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response.
   */
  protected function cacheUuid($cache_key, ResponseInterface $response) {
    $cache_data = [];
    if ($cache_data['uuid'] = $this->parseResponse($response, 'uuid')) {
      $cached = $this->cache->get($cache_key);
      if ($cached) {
        $cache_data = array_merge($cached->data, $cache_data);
      }
      $this->cache->set($cache_key, $cache_data);
    }
  }

  /**
   * Helper for parsing raw response data.
   */
  protected function parseResponse(ResponseInterface $response, $property) {
    try {
      if (!$body = $response->getBody()) {
        return FALSE;
      }

      // Decode received data.
      $json = Json::decode($body->getContents());

      if ($property == 'uuid') {
        return isset($json['data'][$property]) ? $json['data'][$property] : FALSE;
      }
      if ($property == 'message') {
        return isset($json[$property]) ? $json[$property] : FALSE;
      }

      return $json;
    }
    catch (\RuntimeException $exception) {
      // Return false in case an error occurs during json decoding.
      return FALSE;
    }
  }

  /**
   * Returns CollectItem transfer objects.
   *
   * @param \Drupal\collect_client\CollectItem $collect_item
   *   The transfer object.
   *
   * @return array
   *   The array containing results of CollectItem serialization.
   */
  protected function getTransferObjects(CollectItem $collect_item) {
    $data = Json::decode($collect_item->data);

    if (json_last_error()) {
      return [];
    }

    $transfer_objects = [];
    if (isset($data['fields'])) {
      // Extract fields from the container and send separately.
      $values_container_uri = $collect_item->uri;
      $collect_item->uri = $collect_item->schema_uri;
      $collect_item->schema_uri = 'http://schema.md-systems.ch/collect/0.0.1/collectjson-definition/global/fields';
      $collect_item->data = Json::encode(['fields' => $data['fields']]);
      $options['body'] = $this->serializer->serialize($collect_item, 'json');
      $transfer_objects[] = $options;

      // Extract values and send separately.
      unset($data['fields']);
      $collect_item->schema_uri = $collect_item->uri;
      $collect_item->uri = $values_container_uri;
      $collect_item->data = Json::encode($data);
      $options['body'] = $this->serializer->serialize($collect_item, 'json');
      $transfer_objects[] = $options;
    }
    else {
      $options['body'] = $this->serializer->serialize($collect_item, 'json');
      $transfer_objects[] = $options;
    }

    return $transfer_objects;
  }

}
