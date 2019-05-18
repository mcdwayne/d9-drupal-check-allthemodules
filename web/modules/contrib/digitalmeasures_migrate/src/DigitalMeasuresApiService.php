<?php

namespace Drupal\digitalmeasures_migrate;

use Drupal\digitalmeasures_migrate\Form\DigitalMeasuresSettingsForm;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a gateway to the Digital Measures API.
 */
class DigitalMeasuresApiService implements DigitalMeasuresApiServiceInterface {

  const PROD_V4_URL = 'digitalmeasures.com/login/service/v4';

  const TEST_V4_URL = 'beta.digitalmeasures.com/login/service/v4';

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DigitalMeasuresApiService object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function query($options) {
    $url = $this->getApiUrl($options);

    $response = $this->httpClient->request('GET', $url, []);
    $xml = (string) $response->getBody();

    if (empty($xml)) {
      return FALSE;
    }

    return $xml;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser($username, $schema_key, $use_test = -1) {
    // Build the options array to query the API.
    $options = [
      'resource' => 'User',
      'schema_key' => $schema_key,
      'index_key' => 'USERNAME',
      'entry_key' => $username,
    ];

    // Override the API endpoint if specified.
    if ($use_test != -1) {
      $options['beta'] = $use_test;
    }

    // Query the API.
    $body = $this->query($options);

    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile($username, $schema_key, $use_test = -1) {
    // Build the options array to query the API.
    $options = [
      'resource' => 'SchemaData',
      'schema_key' => $schema_key,
      'index_key' => 'USERNAME',
      'entry_key' => $username,
    ];

    // Override the API endpoint if specified.
    if ($use_test != -1) {
      $options['beta'] = $use_test;
    }

    // Query the API.
    $body = $this->query($options);

    return $body;
  }

  /**
   * Generate the authentication string for use in a URL.
   *
   * @return string
   *   A URL fragment containing the authentication string.
   */
  protected function getAuthString() {
    $auth_string = '';

    $config = $this->configFactory->get(DigitalMeasuresSettingsForm::CONF_ID);

    $username = $config->get('username');
    $password = $config->get('password');

    if (!empty($username) && !empty($password)) {
      $auth_string = urlencode($username) . ':' . urlencode($password) . '@';
    }

    return $auth_string;
  }

  /**
   * Generate the API URL from configuration.
   *
   * @param array $options
   *   An array containing plugin configuration.
   *
   * @return string
   *   A string containing the API URL.
   */
  protected function getApiEndpointURL($options) {
    // Allow options to override global config.
    if (isset($options['beta'])) {
      if ($options['beta'] == TRUE) {
        return static::TEST_V4_URL;
      }
      else {
        return static::PROD_V4_URL;
      }
    }

    $config = $this->configFactory->get(DigitalMeasuresSettingsForm::CONF_ID);

    // If no override was set, get the value from the global config.
    $endpoint = $config->get('api_endpoint');
    switch ($endpoint) {
      case 'prod_v4':
        return static::PROD_V4_URL;

      case 'test_v4':
        return static::TEST_V4_URL;
    }

    // If no config was set, return the test domain as a default.
    return static::TEST_V4_URL;
  }

  /**
   * @param array $options
   *   The plugin configuration.
   *
   * @return string
   *   The resource path for the request.
   */
  protected function getResourcePath($options) {
    $path = '';

    // Get the resource to query, by default 'SchemaIndex'.
    if (isset($options['resource'])) {
      $path .= '/' . $options['resource'];
    }
    else {
      $path .= '/SchemaIndex';
    }

    // Get the schema_key, if any.
    if (isset($options['schema_key'])) {
      $path .= '/' . $options['schema_key'];
    }

    // Get the index and entry keys if set.
    if (!empty($options['index_key'])) {
      $path .= '/' . $options['index_key'];

      if (!empty($options['entry_key'])) {
        $path .= ':' . $options['entry_key'];
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiURL($options) {
    $url = 'http://';

    $url .= $this->getAuthString();

    $url .= $this->getApiEndpointURL($options);

    $url .= $this->getResourcePath($options);

    return $url;
  }

}
