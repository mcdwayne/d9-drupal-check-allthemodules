<?php

namespace Drupal\commerce_xero\Entity;

use Drupal\commerce_xero\CommerceXeroProcessorPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Commerce Xero strategy entity interface.
 */
interface CommerceXeroStrategyInterface extends ConfigEntityInterface {

  /**
   * Gets the plugin settings for the specified plugin ID.
   *
   * @param string $plugin_id
   *   The processor plugin ID.
   *
   * @return \Drupal\Core\Config\Schema\Mapping
   *   Returns the plugin mapping object or FALSE if it does not exist.
   */
  public function getEnabledPlugin($plugin_id);

  /**
   * Get the plugin weight for the plugin.
   *
   * @param \Drupal\commerce_xero\CommerceXeroProcessorPluginInterface $plugin
   *   The plugin instance.
   *
   * @return int
   *   The plugin weight.
   */
  public function getPluginWeight(CommerceXeroProcessorPluginInterface $plugin);

}
