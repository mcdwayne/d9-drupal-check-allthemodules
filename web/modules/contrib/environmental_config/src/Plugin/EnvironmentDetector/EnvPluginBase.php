<?php

namespace Drupal\environmental_config\Plugin\EnvironmentDetector;

use Drupal\environmental_config\EnvironmentDetectorInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Class PluginBase.
 *
 * @package Drupal\environmental_config\Plugin\EnvironmentDetector
 */
abstract class EnvPluginBase extends PluginBase implements EnvironmentDetectorInterface {

  /**
   * Gets the plugin name.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Gets the plugin name.
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Gets the plugin description.
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}
