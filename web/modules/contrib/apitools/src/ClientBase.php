<?php

namespace Drupal\apitools;

use Drupal\Core\Http\ClientFactory as HttpClient;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TempStore\SharedTempStore;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apitools\Utility\ParameterBag;

/**
 * Class ClientBase.
 */
abstract class ClientBase extends PluginBase implements ClientInterface {

  /**
   * @var SharedTempStore
   */
  protected $tempStore;

  /**
   * @var ParameterBag
   */
  protected $params;

  /**
   * @var ParameterBag
   */
  protected $options;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var ModelManagerInterface
   */
  protected $modelManager;

    /**
     * @var ClientManagerInterface
     */
  protected $manager;

  protected $apiName;

  protected $controllers;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientManagerInterface $client_manager, ModelManagerInterface $model_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->manager = $client_manager;
    $this->modelManager = $model_manager;
    $this->apiName = !empty($plugin_definition['api']) ? $plugin_definition['api'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.apitools_client'),
      $container->get('plugin.manager.apitools_model')
    );
  }

  /**
   * Initialize variables and functionality when client is loaded.
   */
  public function init(array $options = []) {
    return $this;
  }

  public function setTempStore(SharedTempStore $shared_temp_store) {
    $this->tempStore = $shared_temp_store;
    return $this;
  }

  public function __get($prop) {
    if (method_exists($this, $prop)) {
      return call_user_func_array([$this, $prop], []);
    }
    if ($controller = $this->getModelController($prop)) {
       return $controller;
    }
    return FALSE;
  }

  protected function getModelController($prop) {
    // TODO: Figure out if we still need apiName.
    if (isset($this->controllers[$prop])) {
      return $this->controllers[$prop];
    }
    if (!$class = $this->modelManager->getModelControllerByMethod($prop, $this->apiName)) {
      return NULL;
    }
    $this->controllers[$prop] = clone $class;
    return $this->controllers[$prop]->setClient($this)->setCallerClientProperty($prop);
  }

  /**
   * Authenticate the client and set tokens.
   *
   * @return $this
   */
  abstract protected function auth();

  protected function postRequest($response) {
    return $response;
  }

  protected function request($method, $path, $options = []) {
    $response = NULL;
    $path = $this->options->get('base_path') . '/' . $path;
    $this->options->add($options);
    try {
      $response = $this->httpClient->{$method}($path, $this->options->all());
      $response = $response->getBody()->getContents();
    }
    catch (\Exception $e) {
      watchdog_exception('apitools', $e);
    }
    return $this->postRequest($response);
  }

  public function put($path, $options = []) {
    return $this->auth()->request('put', $path, $options);
  }

  public function patch($path, $options = []) {
    return $this->auth()->request('patch', $path, $options);
  }

  public function get($path, $options = []) {
    return $this->auth()->request('get', $path, $options);
  }

  public function post($path, $options = []) {
    return $this->auth()->request('post', $path, $options);
  }

  public function delete($path, $options = []) {
    return $this->auth()->request('delete', $path, $options);
  }

  public function url($path) {
    $url = $this->options->get('base_uri');
    $url .= $this->options->get('base_path');
    return $url . '/' . $path;
  }
}
