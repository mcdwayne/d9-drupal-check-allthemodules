<?php

namespace Drupal\vault\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Base class for Vault Authentication plugins.
 */
abstract class VaultAuthBase extends PluginBase implements VaultAuthInterface, ContainerFactoryPluginInterface, ConfigurablePluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Sets the configFactory property.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the configFactory property.
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $configFactory = \Drupal::configFactory();
    $instance->setConfigFactory($configFactory);
    return $instance;
  }

}
