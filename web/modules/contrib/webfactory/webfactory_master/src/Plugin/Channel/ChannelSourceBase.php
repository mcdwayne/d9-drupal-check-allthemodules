<?php

namespace Drupal\webfactory_master\Plugin\Channel;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\webfactory_master\Entity\ChannelEntity;
use Drupal\webfactory_master\Plugin\ChannelSourceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common base class for channel source plugins.
 *
 * @see \Drupal\webfactory_master\Annotation\ChannelSource
 * @see \Drupal\webfactory_master\Plugin\ChannelSourcePluginManager
 * @see \Drupal\webfactory_master\Plugin\ChannelSourceInterface
 * @see plugin_api
 *
 * @ingroup third_party
 */
abstract class ChannelSourceBase extends PluginBase implements ContainerFactoryPluginInterface, ChannelSourceInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Plugin settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * ChannelEntity.
   *
   * @var ChannelEntity
   */
  protected $channelEntity;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('logger.factory')->get('webfactory_master')
    );
  }

  /**
   * Initialize plugin with settings.
   *
   * @param ChannelEntity $entity
   *   The channel entity.
   * @param array $settings
   *   List of settings.
   */
  public function setConfiguration(ChannelEntity $entity, array $settings) {
    $this->settings = $settings;
    $this->channelEntity = $entity;
  }

}
