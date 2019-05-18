<?php

namespace Drupal\integro\Plugin\Integro\Client;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @IntegroClient(
 *   id = "integro_api",
 *   label = "API client",
 * )
 */
class Api extends RestBase {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs an API Client.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, HttpClientInterface $http_client, CacheBackendInterface $cache_backend) {
    $this->client = $http_client;
    $this->cache = $cache_backend;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('cache.integro')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function requestPrepare() {
    $this->clientConfiguration = [];

    $request_options = [];

    // URI.
    $uri = $this->configuration['rest_protocol'] . '://' . $this->configuration['rest_domain'] . '/';
    if (isset($this->configuration['rest_base_path']) && $this->configuration['rest_base_path']) {
      $uri .= $this->configuration['rest_base_path'] . '/';
    }

    if (isset($this->configuration['operation']['definition']['path']) && $this->configuration['operation']['definition']['path']) {
      // Prepare path first.
      $operation_path = $this->configuration['operation']['definition']['path'];
      foreach ($this->configuration['operation']['arguments'] as $argument_key => $argument_value) {
        $operation_path = str_replace('{'.$argument_key.'}', $argument_value, $operation_path);
      }
      $uri .= $operation_path;
    }

    $url_options = [
      'absolute' => TRUE,
      'https' => $this->configuration['rest_protocol'] == 'https',
    ];

    // Put api key to query if the placement is 'query'.
    if ($this->configuration['api_key_placement'] == 'query') {
      $url_options['query'][$this->configuration['api_key_variable_name']] = $this->configuration['api_key'];
    }

    $url = Url::fromUri($uri, $url_options)->toString();

    // Put api key to header if the placement is 'header'
    if ($this->configuration['api_key_placement'] == 'header') {
      $request_options['headers'][$this->configuration['api_key_variable_name']] = $this->configuration['api_key'];
    }

    // Accept data format.
    if (isset($this->configuration['operation']['definition']['accept']) && $this->configuration['operation']['definition']['accept']) {
      $request_options['headers']['Accept'] = $this->configuration['operation']['definition']['accept'];
    }

    // Compose the request.
    $this->clientConfiguration['request'] = [
      'method' => $this->configuration['operation']['definition']['method'],
      'url' => $url,
      'options' => $request_options,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function request() {
    // To be implemented in descendants.
    $this->requestPrepare();

    $response = $this->client->request(
      $this->clientConfiguration['request']['method'],
      $this->clientConfiguration['request']['url'],
      $this->clientConfiguration['request']['options']
    );

    $response_string = (string) $response->getBody();

    $result = $this->requestHandle($response_string);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'api_key_variable_name' => '',
      'api_key_placement' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Api key.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    // Variable name for the api key.
    $form['api_key_variable_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Variable name for API key'),
      '#default_value' => $this->configuration['api_key_variable_name'],
      '#required' => TRUE,
    ];

    // Api key placement.
    $form['api_key_placement'] = [
      '#type' => 'select',
      '#title' => $this->t('API key placement'),
      '#options' => [
        'query' => $this->t('Query parameter'),
        'header' => $this->t('Header parameter'),
      ],
      '#empty_option' => $this->t('- Choose the placement -'),
      '#default_value' => $this->configuration['api_key_placement'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['api_key_variable_name'] = $values['api_key_variable_name'];
      $this->configuration['api_key_placement'] = $values['api_key_placement'];
    }
  }

}
