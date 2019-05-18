<?php

namespace Drupal\authorization_code;

use Drupal\Component\Utility\NestedArray;

/**
 * Implements ConfigurablePluginTrait.
 *
 * @see \Drupal\Component\Plugin\ConfigurablePluginInterface
 */
trait ConfigurablePluginTrait {

  /**
   * The configuration array.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   *
   * @see \Drupal\Component\Plugin\ConfigurablePluginInterface::defaultConfiguration
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * Gets this plugin's configuration.
   *
   * @return array
   *   An array of this plugin's configuration.
   *
   * @see \Drupal\Component\Plugin\ConfigurablePluginInterface::getConfiguration
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * Sets the configuration for this plugin instance.
   *
   * @param array $configuration
   *   An associative array containing the plugin's configuration.
   *
   * @see \Drupal\Component\Plugin\ConfigurablePluginInterface::setConfiguration
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * Calculates dependencies for the configured plugin.
   *
   * @return array
   *   An array of dependencies grouped by type (config, content, module, theme)
   *
   * @see \Drupal\Component\Plugin\DependentPluginInterface::calculateDependencies
   */
  public function calculateDependencies() {
    return ['module' => ['authorization_code']];
  }

}
