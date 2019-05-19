<?php
/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCountersPluginBase.
 */

namespace Drupal\social_counters\Plugin;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\social_counters\SocialCountersInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class SocialCountersPluginBase extends PluginBase implements SocialCountersInterface, ContainerFactoryPluginInterface {
  /**
   * Http client.
   */
  protected $http_client;

  /**
   * Json serializer.
   */
  protected $json_serializer;

  /**
   * Logger.
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, SerializationInterface $json_serializer,
    LoggerChannelInterface $logger) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->http_client = $http_client;
    $this->json_serializer = $json_serializer;
    $this->logger = $logger;
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
      $container->get('serialization.json'),
      $container->get('logger.factory')->get('social_counters')
    );
  }
}
