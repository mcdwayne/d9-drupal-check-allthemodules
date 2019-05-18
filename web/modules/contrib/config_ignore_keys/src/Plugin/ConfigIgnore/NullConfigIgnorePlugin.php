<?php

namespace Drupal\config_ignore_keys\Plugin\ConfigIgnore;

use Drupal\Core\Plugin\PluginBase;
use Drupal\config_ignore_keys\Plugin\ConfigurationIgnorePluginInterface;

/**
 * Null config ignore plugin.
 *
 * @ConfigurationIgnorePlugin(
 *   id = "null_config_ignore",
 *   description = "The configuration for ignoring nothing",
 * )
 */
class NullConfigIgnorePlugin extends PluginBase implements ConfigurationIgnorePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfigurations() {
    return [];
  }

}
