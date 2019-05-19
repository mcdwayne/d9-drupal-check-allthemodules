<?php

namespace Drupal\social_hub;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface defining a platform entity type.
 */
interface PlatformInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Get the integration plugins.
   *
   * @return array
   *   The integration plugins.
   */
  public function getPlugins();

  /**
   * Set the integration plugins.
   *
   * @param array $plugins
   *   An array of integration plugins.
   */
  public function setPlugins(array $plugins);

  /**
   * Get plugins' configuration.
   *
   * @return array
   *   The configuration from the integration plugins.
   */
  public function getConfiguration();

  /**
   * Set the plugins' configuration.
   *
   * @param array $configuration
   *   An array of plugins configuration.
   */
  public function setConfiguration(array $configuration);

  /**
   * Get the plugin collection instance.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The plugin collection.
   */
  public function getPluginCollection();

  /**
   * Build platform output for a given context.
   *
   * @param array $plugins
   *   Build the entity using these plugins. If none all plugins will be used.
   * @param array $context
   *   Build context.
   *
   * @return array
   *   A render array.
   *
   * @see \Drupal\social_hub\PlatformIntegrationPluginInterface::build()
   */
  public function build(array $plugins = [], array $context = []);

}
