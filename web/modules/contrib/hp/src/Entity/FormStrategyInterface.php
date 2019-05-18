<?php

namespace Drupal\hp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an FormStrategy entity.
 */
interface FormStrategyInterface extends ConfigEntityInterface {

  /**
   * Get plugin ID.
   *
   * @return string
   *   The form strategy plugin id.
   */
  public function getPluginId();

  /**
   * Set plugin ID.
   *
   * @var string $plugin_id
   *   The plugin ID.
   */
  public function setPluginId($plugin_id);

  /**
   * Get plugin.
   *
   * @return \Drupal\hp\Plugin\hp\FormStrategyInterface
   *   The form strategy interface.
   */
  public function getPlugin();

  /**
   * Get plugin configuration.
   *
   * @return array
   *   The plugin configuration array.
   */
  public function getPluginConfiguration();

  /**
   * Plugin configuration setter.
   *
   * @var array $configuration
   *   The plugin configuration array.
   */
  public function setPluginConfiguration(array $configuration);


  /**
   * ID setter.
   *
   * @var string $id
   *   The (form) ID.
   */
  public function setId($id);

}
