<?php

namespace Drupal\dream_fields;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager for Dream fields.
 */
class DreamFieldsPluginManager extends DefaultPluginManager {

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct('Plugin/DreamField', $namespaces, $module_handler, 'Drupal\dream_fields\DreamFieldPluginInterface', 'Drupal\dream_fields\Annotation\DreamField');
    $this->alterInfo('dream_fields_info');
    $this->setCacheBackend($cache_backend, 'dream_fields');
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    foreach ($definitions as $plugin_id => $plugin_definition) {
      // Check custom dependency on the field types.
      if (!$this->dependenciesFulfilled($plugin_definition)) {
        unset($definitions[$plugin_id]);
      }
    }
    uasort($definitions, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
    return $definitions;
  }

  /**
   * Check if the dependencies for a plugin are fulfilled.
   *
   * @param array $plugin
   *   The plugin definition.
   *
   * @return bool
   *   If the plugin dependencies are fulfilled.
   */
  protected function dependenciesFulfilled($plugin) {
    $enabled_fields = $this->getEnabledFields();
    foreach ($plugin['field_types'] as $field_type) {
      if (!in_array($field_type, $enabled_fields)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the enabled fields.
   *
   * @return array
   *   An array of enabled fields.
   */
  protected function getEnabledFields() {
    return array_keys($this->fieldTypeManager->getDefinitions());
  }

}
