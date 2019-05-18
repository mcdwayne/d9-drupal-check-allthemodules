<?php

namespace Drupal\config_provider\Plugin;

/**
 * Class for invoking configuration providers..
 */
interface ConfigCollectorInterface {

  /**
   * Gets all configuration provider plugins.
   *
   * @return \Drupal\config_provider\Plugin\ConfigProviderInterface[]
   *   An array of fully initialized configuration provider instances.
   */
  public function getConfigProviders();

  /**
   * Adds installable configuration from all provider plugins.
   *
   * Using the InMemoryStorage permits implementing plugins to add
   * configuration to collections other than the default by calling
   * ::writeToCollection().
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   (Optional) An associative array of Extension objects, keyed by extension
   *   name. If provided, data loaded will be limited to these extensions.
   */
  public function addInstallableConfig(array $extensions = []);

}
