<?php

namespace Drupal\sender\Plugin\SenderMethod;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sender\Entity\MessageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for sender methods.
 */
abstract class SenderMethodBase implements ContainerFactoryPluginInterface, SenderMethodInterface {

  protected $configuration;
  protected $pluginId;
  protected $pluginDefinition;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function send(array $data, AccountInterface $recipient, MessageInterface $message);

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Instantiates a plugin object.
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
