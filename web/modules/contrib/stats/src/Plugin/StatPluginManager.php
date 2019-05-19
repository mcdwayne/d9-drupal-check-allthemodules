<?php
/**
 * @file
 * StatPluginManager.php for kartslalom
 */

namespace Drupal\stats\Plugin;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\stats\StatExecution;

abstract class StatPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = [], StatExecution $execution = NULL) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition, $execution);
    }
    else {
      $plugin = new $plugin_class($configuration, $plugin_id, $plugin_definition, $execution);
    }
    return $plugin;
  }

}
