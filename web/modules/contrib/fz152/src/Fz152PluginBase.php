<?php

namespace Drupal\fz152;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for all types of plugin settings.
 */
abstract class Fz152PluginBase extends PluginBase implements Fz152PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsPage() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getForms() {
    return [];
  }

}
