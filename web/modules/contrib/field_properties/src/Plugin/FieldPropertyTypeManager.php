<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\ViewsPluginManager.
 */

namespace Drupal\field_properties\Plugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for all views plugins.
 */
class FieldPropertyTypeManager extends DefaultPluginManager {

  /**
   * Provides some default values for all local action plugins.
   *
   * @var array
   */
  protected $defaults = array(
    // The plugin id. Set by the plugin system based on the top-level YAML key.
    'id' => NULL,
    // The static title for the local action.
    'title' => '',
    // The weight of the local action.
    'weight' => 0,
  );

  /**
   * FieldPropertyTypeManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldProperties/Type', $namespaces, $module_handler, 'Drupal\field_properties\Plugin\FieldPropertiesTypeInterface');
  }

  /**
   * Build a sorted list of property types.
   *
   * @return array
   *   the list of property type plugins sorted by weight
   */
  public function getSortedDefinitions() {
    // Sort the plugins first by weight, then by label.
    $definitions = $this->getDefinitions();
    uasort($definitions, function ($a, $b) {
      if ($a['weight'] != $b['weight']) {
        return $a['weight'] - $b['weight'];
      }
      return strnatcasecmp($a['label'], $b['label']);
    });
    return $definitions;
  }

}
