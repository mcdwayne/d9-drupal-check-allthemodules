<?php

declare(strict_types = 1);

namespace Drupal\field_autovalue\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Field Autovalue plugin manager.
 */
class FieldAutovalueManager extends DefaultPluginManager {

  /**
   * Constructs a new FieldAutovalueManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldAutovalue', $namespaces, $module_handler, 'Drupal\field_autovalue\Plugin\FieldAutovalueInterface', 'Drupal\field_autovalue\Annotation\FieldAutovalue');

    $this->alterInfo('field_autovalue_field_autovalue_info');
    $this->setCacheBackend($cache_backend, 'field_autovalue_field_autovalue_plugins');
  }

  /**
   * Returns all the definitions for a given field type.
   *
   * @param string $field_type
   *   The field type these definitions can handle.
   *
   * @return array
   *   The definitions.
   */
  public function getDefinitionsForFieldType(string $field_type): array {
    $definitions = $this->getDefinitions();
    if (!$definitions) {
      return [];
    }

    return array_filter($definitions, function ($definition) use ($field_type) {
      return in_array($field_type, $definition['field_types']);
    });
  }

  /**
   * Creates a list of select options for the field config.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return array
   *   The select options.
   */
  public function getSelectOptionsForFieldType(string $field_type): array {
    $definitions = $this->getDefinitionsForFieldType($field_type);
    if (!$definitions) {
      return [];
    }

    $options = [];
    foreach ($definitions as $name => $definition) {
      $options[$name] = $definition['label'];
    }

    return $options;
  }

}
