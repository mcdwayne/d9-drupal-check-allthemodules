<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides a trait for interacting with an entity using a bundle plugin.
 */
trait BundlePluginEntityTrait {

  /**
   * The loaded bundle plugin.
   *
   * @var \Drupal\entity\BundlePlugin\BundlePluginInterface
   */
  protected $bundlePlugin = NULL;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if (is_null($this->bundlePlugin) && $bundle_plugin_type = $this->getEntityType()->get('bundle_plugin_type')) {
      $this->bundlePlugin = \Drupal::service('plugin.manager.' . $bundle_plugin_type)->createInstance($this->bundle());
    }
    return $this->bundlePlugin;
  }

}
