<?php

namespace Drupal\odata_client\Odata;

use Drupal\Core\DependencyInjection\Container;
use SaintSystems\OData\ODataClient;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Class InputOutputService.
 */
class InputOutputService implements InputOutputServiceInterface {

  /**
   * Drupal\Core\DependencyInjection\ContainerBuilder definition.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $serviceContainer;

  /**
   * SaintSystems\OData\ODataClient definition.
   *
   * @var \SaintSystems\OData\ODataClient
   */
  protected $odataClient;

  /**
   * The OData type definition.
   *
   * @var string
   */
  protected $odataType;

  /**
   * The default collection definition.
   *
   * @var string
   */
  protected $defaultCollectionName;

  /**
   * The \SaintSystems\OData\Query\Builder definition.
   *
   * @var SaintSystems\OData\Query\Builder
   */
  protected $collection;

  /**
   * Drupal\odata_client\Entity\OdataServerInterface definition.
   *
   * @var \Drupal\odata_client\Entity\OdataServerInterface
   */
  protected $config;

  /**
   * League\OAuth2\Client\Token\AccessTokenInterface Bearer token definition.
   *
   * @var \League\OAuth2\Client\Token\AccessTokenInterface
   *   The Bearer token.
   */
  protected $token;

  /**
   * Constructs a new InputOutputService object.
   */
  public function __construct(Container $service_container) {
    $this->serviceContainer = $service_container;
  }

  /**
   * {@inheritdoc}
   */
  public function connect(string $config_name): InputOutputServiceInterface {
    $this->config = $this->serviceContainer
      ->get('entity_type.manager')
      ->getStorage('odata_server')
      ->load($config_name);
    if ($this->config) {
      $this->access();
      if (!empty($this->odataClient)) {
        $this->defaultCollectionName = $this->config->getDefaultCollection();
        if (!empty($this->defaultCollectionName)) {
          $this->collection = $this->odataClient->from($this->defaultCollectionName);
        }
        $this->odataType = $this->config->getOdataType();
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOdataType(string $type) {
    $this->odataType = $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getOdataType() {
    return $this->odataType;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultCollectionName(string $collection_name) {
    $this->defaultCollectionName = $collection_name;
    $this->collection = $this->odataClient->from($collection_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCollectionName() {
    return $this->defaultCollectionName;
  }

  /**
   * {@inheritdoc}
   */
  public function get(array $chain = []) {
    if (!empty($chain)) {
      $this->chain($chain);
    }
    try {
      $result = $this->collection->get();
      return $result;
    }
    catch (\Throwable $t) {
      $this->serviceContainer->get('logger.factory')
        ->get('odata_client')
        ->error($t->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function find(string $id) {
    try {
      $result = $this->collection->find($id);
      return $result;
    }
    catch (\Throwable $t) {
      $this->serviceContainer->get('logger.factory')
        ->get('odata_client')
        ->error($t->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    try {
      $result = $this->collection->count();
      return $result;
    }
    catch (\Throwable $t) {
      $this->serviceContainer->get('logger.factory')
        ->get('odata_client')
        ->error($t->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function post(array $data) {
    $json = json_encode(array_merge($this->getDefaults(), $data));
    try {
      $result = $this->collection->post($json);
      return $result;
    }
    catch (\Throwable $t) {
      $this->serviceContainer->get('logger.factory')
        ->get('odata_client')
        ->error($t->getMessage());
    }

    return NULL;
  }

  /**
   * Access to OData server.
   */
  protected function access() {
    $authentication_method = $this->config->getAuthenticationMethod();
    $url = $this->config->getUrl();
    try {
      switch ($authentication_method) {
        case 'oauth':
          $access_token = $this->getAccessToken();
          $this->odataClient = new ODataClient($url, function ($request) use ($access_token) {
            if (!empty($access_token)) {
              // OAuth Bearer Token Authentication.
              $values = $access_token->getValues();
              $request->headers['Authorization'] = $values['token_type'] . ' ' . $access_token->getToken();
            }
          });
          break;

        case 'basic':
          $user_name = $this->config->getUserName();
          $password = $this->config->getPassword();
          $this->odataClient = new ODataClient($url, function ($request) use ($user_name, $password) {
            // Basic Authentication.
            $request->headers['Authorization'] = 'Basic ' . base64_encode($user_name . ':' . $password);
          });
          break;

        default:
          $this->odataClient = new ODataClient($url);
      }
    }
    catch (\Throwable $t) {
      $this->serviceContainer->get('logger.factory')
        ->get('odata_client')
        ->warning($t->getMessage());
    }
  }

  /**
   * Prepare Oauth authentication token.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface
   *   The access token.
   */
  protected function getAccessToken(): AccessTokenInterface {
    // Check unexpired access token.
    if (!empty($this->token) &&
      $this->token->getExpires() > \Drupal::time()->getCurrentTime()) {
      return $this->token;
    }
    // Prepare new access token.
    $type = $this->serviceContainer->get('plugin.manager.odata_auth_plugin');
    $plugin = $type->createInstance($this->config->getTokenProvider());
    $this->token = $plugin->getAccessToken($this->config, $this->serviceContainer);

    return $this->token;
  }

  /**
   * The default Odata type.
   *
   * @return array
   *   The default Odata type array.
   */
  protected function getDefaults(): array {
    return !empty($this->odataType) ? [
      '@odata.type' => $this->odataType,
    ] : [];
  }

  /**
   * Prepare collection option to get data.
   *
   * @param array $chain
   *   The data for add parameters to query.
   */
  protected function chain(array $chain) {
    foreach ($chain as $key => $values) {
      if (is_array($values) & ($key === 'where' || $key === 'order')) {
        $this->setParameterArray($key, $values);
      }
      elseif (is_array($values)) {
        call_user_func_array([$this->collection, $key], $values);
      }
      else {
        call_user_func([$this->collection, $key], $values);
      }
    }
  }

  /**
   * Set collection parameters.
   *
   * @param string $key
   *   The function name.
   * @param array $values
   *   The parameters of function.
   */
  protected function setParameterArray(string $key,
    array $values) {
    foreach ($values as $row) {
      call_user_func_array([$this->collection, $key], $row);
    }
  }

}
