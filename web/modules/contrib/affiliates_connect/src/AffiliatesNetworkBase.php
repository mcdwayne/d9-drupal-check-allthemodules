<?php

namespace Drupal\affiliates_connect;

use Drupal\Component\Plugin\PluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Base class for Affiliates network plugins.
 */
abstract class AffiliatesNetworkBase extends PluginBase implements AffiliatesNetworkInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructs an AffiliatesNetworkBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle client.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->loggerFactory = $logger_factory;
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
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $url, array $options = []) {
    try {
      $response = $this->client->get($url, $options);
      return $response;
    }
    catch (RequestException $e) {
      $this->loggerFactory
        ->get('affiliates_connect')
        ->error($e->getMessage());
      drupal_set_message('There is an error in Affiliates API, Check the logs', 'error');
    }
    return;
  }
}
