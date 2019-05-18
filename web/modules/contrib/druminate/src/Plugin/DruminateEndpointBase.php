<?php

namespace Drupal\druminate\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\druminate\Luminate\DruminateApi;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use DateTime;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Druminate Endpoint plugins.
 */
abstract class DruminateEndpointBase extends PluginBase implements DruminateEndpointInterface, ContainerFactoryPluginInterface {

  /**
   * The Druminate Api.
   *
   * @var \Drupal\druminate\Luminate\DruminateApi
   */
  protected $druminateApi;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The api servlet.
   *
   * @var string
   */
  protected $servlet;

  /**
   * The api method.
   *
   * @var string
   */
  protected $method;

  /**
   * The api method.
   *
   * @var string
   */
  protected $isFrozen;

  /**
   * Determines whether or not an auth token should be added to request.
   *
   * @var bool
   */
  protected $authRequired;

  /**
   * Determines whether or not the api response should be stored.
   *
   * @var bool
   */
  protected $cacheLifetime;

  /**
   * Additional parameters that are passed to the API.
   *
   * @var array
   */
  protected $params;

  /**
   * The custom api url.
   *
   * @var string
   */
  protected $customUrl;

  /**
   * The HTTP request method.
   *
   * @var string
   */
  protected $httpRequestMethod;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $sandwich = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('druminate.druminate_api')
    );
    return $sandwich;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, DruminateApi $druminateApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->druminateApi = $druminateApi;
    $this->connection = $connection;
    $this->servlet = $this->getServlet();
    $this->method = $this->getMethod();
    $this->cacheLifetime = $this->cacheLifetime();
    $this->params = $this->getParams();
    $this->authRequired = $this->authRequired();
    $this->isFrozen = $this->isFrozen();
    $this->customUrl = $this->getCustomUrl();
    $this->httpRequestMethod = $this->getHttpRequestMethod();
  }

  /**
   * {@inheritdoc}
   */
  public function getServlet() {
    return isset($this->pluginDefinition['servlet']) ? $this->pluginDefinition['servlet'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->pluginDefinition['method'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParams() {
    $config = $this->configuration;
    if (isset($config['isFrozen'])) {
      unset($config['isFrozen']);
    }
    // Params may not have been set in the plugin annotation.
    return array_merge($this->pluginDefinition['params'] ?: [], $config);
  }

  /**
   * {@inheritdoc}
   */
  public function authRequired() {
    return $this->pluginDefinition['authRequired'];
  }

  /**
   * {@inheritdoc}
   */
  public function isFrozen() {
    return isset($this->configuration['isFrozen']) ? (int) $this->configuration['isFrozen'] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheLifetime() {
    return $this->pluginDefinition['cacheLifetime'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomUrl() {
    return isset($this->pluginDefinition['customUrl']) ? $this->pluginDefinition['customUrl'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpRequestMethod() {
    if (!empty($this->pluginDefinition['httpRequestMethod'])) {
      $method = $this->pluginDefinition['httpRequestMethod'];
      if (strtolower($method) === 'get' || strtolower($method) === 'post') {
        return $method;
      }
    }

    return 'GET';
  }

  /**
   * {@inheritdoc}
   */
  public function loadData() {
    // If plugin cacheLifetime() > 0 check the db for a stored response.
    // TODO: Add timeouts.
    if ($this->cacheLifetime() > 0 || $this->isFrozen) {
      $query = $this->connection->select('druminate_api_data', 'da');
      $query->fields('da', ['data', 'expired', 'frozen']);
      $query->condition('da.request_id', $this->generateRequestId());

      // Compared expired vs current time.
      $result = $query->execute()->fetchAll();
      if (!empty($result) && isset($result[0])) {
        $date = new DateTime();
        $current_timestamp = $date->getTimestamp();
        $expired = $result[0]->expired;
        $frozen = $result[0]->frozen;
        $data = $result[0]->data;

        // If data is frozen or the current time is less than the expired time
        // then return the stored data.
        if ($frozen || ($current_timestamp < $expired)) {
          return unserialize($data);
        }
      }
    }

    return $this->requestData();
  }

  /**
   * Function used to grab data from the Druminate Api and store it.
   *
   * Note the response is stored in the database instead of cache to allow
   * administrators the ability to "freeze" data or to take a snapshot in time.
   *
   * @return bool|mixed
   *   Api response data.
   *
   * @throws \Exception
   */
  public function requestData() {
    if ($data = $this->druminateApi->request($this->servlet, $this->method, $this->params, $this->httpRequestMethod, $this->authRequired, $this->customUrl)) {
      // If plugin cacheLifetime() > 0 then store response in database.
      if ($this->cacheLifetime > 0) {
        $this->storeData($data);
      }
      return $data;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Function used to post data to the Druminate Api.
   *
   * @return bool|mixed
   *   Api response data.
   */
  public function postData() {
    if ($data = $this->druminateApi->request($this->servlet, $this->method, $this->params, $this->httpRequestMethod, $this->authRequired, $this->customUrl)) {
      return $data;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Helper function used to save or update response data.
   *
   * @param object $data
   *   Druminate response object.
   *
   * @throws \Exception
   */
  public function storeData($data) {
    $date = new DateTime();

    $this->connection->merge('druminate_api_data')
      ->key(['request_id' => $this->generateRequestId()])
      ->fields([
        'plugin_id' => $this->getPluginId(),
        'params' => serialize($this->configuration),
        'data' => serialize($data),
        'frozen' => $this->isFrozen,
        'created' => $date->getTimestamp(),
        'expired' => $this->cacheLifetime > 0 ? $date->getTimestamp() + $this->cacheLifetime : $this->cacheLifetime,
      ])
      ->execute();
  }

  /**
   * Helper function used to build unique request_id.
   *
   * @return string
   *   Unique request_id.
   */
  public function generateRequestId() {
    return md5(serialize([$this->getPluginId(), $this->params]));
  }

  /**
   * Helper function used to get plugin settings for use on the client side.
   *
   * @return array|bool
   *   Array of settings for clientside calls.
   */
  public function getClientSideSettings() {
    if ($base_settings = $this->druminateApi->getSettings()) {
      if ($this->customUrl) {
        $url = $this->customUrl;
      }
      else {
        $url_obj = Url::fromUri('https://' . $base_settings['host'] . '/' . $base_settings['short_name'] . '/site/' . $this->servlet);
        $url = $url_obj->toString();
      }
      $base_settings['settings']['method'] = $this->method;

      return [
        'url' => $url,
        'data' => array_merge($base_settings['settings'], $this->params),
      ];
    }

    return FALSE;
  }

}
