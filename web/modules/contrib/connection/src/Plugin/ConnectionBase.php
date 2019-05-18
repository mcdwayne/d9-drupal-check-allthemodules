<?php

namespace Drupal\connection\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for Connection plugins.
 */
abstract class ConnectionBase extends PluginBase implements ConnectionInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, ConfigFactory $config_factory, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->translation = $string_translation;
    $this->config = $config_factory;
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('system');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

  /**
   * Return the name.
   *
   * @return string
   *   returns the name as a string.
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Returns the status of the connection.
   *
   * @return bool
   */
  public function getStatus() {
    return TRUE;
  }

  /**
   * @param $url
   *
   * @return array|mixed
   */
  public function getParams($url) {
    return ['url' => $url];
  }

  /**
   * @param $params
   *
   * @return bool|\Psr\Http\Message\ResponseInterface|\Psr\Http\Message\StreamInterface|string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function request($params) {
    if (isset($params['url']) && $url = $params['url']) {
      return $this->response($url);
    }
    return FALSE;
  }

  /**
   * @param $url
   *
   * @return \Psr\Http\Message\ResponseInterface|\Psr\Http\Message\StreamInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function response($url) {
    try {
      $response = $this->httpClient->request('GET', $url, ['verify' => FALSE]);
      $status = $response->getStatusCode();
      if ($status == 200) {
        return $response->getBody();
      }
      else {
        return $response->withStatus($status);
      }
    } catch (RequestException $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
