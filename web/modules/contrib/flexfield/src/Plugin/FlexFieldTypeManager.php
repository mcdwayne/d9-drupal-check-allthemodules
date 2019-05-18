<?php

namespace Drupal\flexfield\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;


/**
 * Provides the Flexfield Type plugin manager.
 */
class FlexFieldTypeManager extends DefaultPluginManager implements FlexFieldTypeManagerInterface {


  /**
   * Constructs a new FlexFieldTypeManager object.
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
    parent::__construct(
      'Plugin/FlexFieldType',
      $namespaces,
      $module_handler,
      'Drupal\flexfield\Plugin\FlexFieldTypeInterface',
      'Drupal\flexfield\Annotation\FlexFieldType'
    );

    $this->alterInfo('flexfield_info');
    $this->setCacheBackend($cache_backend, 'flexfield_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFlexFieldItems(array $settings) {
    $items = [];
    $definitions = $this->getDefinitions();
    $field_settings = isset($settings['field_settings']) ? $settings['field_settings'] : [];
    foreach ($settings['columns'] as $name => $column) {
      $settings = isset($field_settings[$name]) ? $field_settings[$name] : [];
      // @todo: How to define default?
      $type = isset($settings['type']) && isset($definitions[$settings['type']]) ? $settings['type'] : 'text';
      $items[$name] = $this->createInstance($type, [
        'name' => $column['name'],
        'max_length' => $column['max_length'],
        'widget_settings' => isset($settings['widget_settings']) ? $settings['widget_settings'] : [],
        'formatter_settings' => isset($settings['formatter_settings']) ? $settings['formatter_settings'] : [],
      ]);
    }
    // $plugin_definitions = $type->getDefinitions();
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlexFieldWidgetOptions() {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }
    return $options;
  }

}
