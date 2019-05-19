<?php

namespace Drupal\solr_qb\Plugin\SolrQbDriver;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\solr_qb\Plugin\SolrQbDriverBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomDriver
 *
 * @SolrQbDriver(
 *   id = "custom",
 *   title = @Translation("Custom"),
 *   configName = "solr_qb.custom.credentials"
 * )
 */
class CustomDriver extends SolrQbDriverBase {

  const FORMAT = 'json';

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'protocol' => 'http',
      'host' => NULL,
      'port' => NULL,
      'path' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array $config) {

    $form['protocol'] = [
      '#title' => $this->t('Protocol'),
      '#type' => 'select',
      '#options' => [
        'http' => 'HTTP',
        'https' => 'HTTPS',
      ],
      '#default_value' => $config['protocol'] ?: 'http',
    ];
    $form['host'] = [
      '#title' => $this->t('Host'),
      '#type' => 'textfield',
      '#default_value' => $config['host'] ?: '',
    ];
    $form['port'] = [
      '#title' => $this->t('Port'),
      '#type' => 'textfield',
      '#default_value' => $config['port'] ?: '',
    ];
    $form['path'] = [
      '#title' => $this->t('Path'),
      '#type' => 'textfield',
      '#default_value' => $config['path'] ?: '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $values) {
    $uri = $this->createQueryURI($values['qt']);

    $response = $this->httpClient->request('GET', $uri, [
      'query' => $this->prepareQueryValues($values),
    ]);

    return $response->getBody();
  }

  /**
   * Create full Solr URI.
   *
   * @param string $request_handler
   *   Request handler.
   *
   * @return string
   *   Full Solr URI.
   */
  protected function createQueryURI($request_handler) {
    return $this->configuration['protocol']
    . '://'
    . $this->configuration['host']
    . ':'
    . $this->configuration['port']
    . $this->configuration['path']
    . $request_handler;
  }

  /**
   * Prepare query values.
   *
   * @param array $values
   *   Builder form values.
   *
   * @return array
   *   Processed form values.
   */
  protected function prepareQueryValues(array $values) {
    $values['common']['wt'] = self::FORMAT;
    $query_values = array_filter($values['common']);
    $additional = [
      'dismax',
      'edismax',
      'hl',
      'facet',
      'spatial',
      'spellcheck',
    ];
    foreach ($additional as $name) {
      if (!empty($values[$name])) {
        $additional_values = [];
        $additional_values[$name] = 'true';
        $additional_values = array_merge($additional_values, $values[$name . '_wrapper']);
        $query_values = array_merge($query_values, array_filter($additional_values));
      }
    }

    return $query_values;
  }

}
