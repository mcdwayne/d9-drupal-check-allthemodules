<?php

namespace Drupal\extra_field_plus\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Exception;

/**
 * Manages Extra Field Plus plugins settings forms.
 */
class ExtraFieldPlusFormManager extends DefaultPluginManager {

  /**
   * Constructor for ExtraFieldPlusFormManager objects.
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

    parent::__construct('Plugin/ExtraField/Display', $namespaces, $module_handler, 'Drupal\extra_field_plus\Plugin\ExtraFieldPlusDisplayInterface', 'Drupal\extra_field\Annotation\ExtraFieldDisplay');

    $this->alterInfo('extra_field_display_info');
    $this->setCacheBackend($cache_backend, 'extra_field_display_plugins');
  }

  /**
   * Gets extra field settings form.
   *
   * @param string $field_name
   *   The extra field machine name.
   *
   * @return array
   *   Array with form fields or empty array.
   */
  public function getSettingsForm($field_name) {
    if (substr($field_name, 0, 12) === 'extra_field_') {
      $plugin_id = substr($field_name, 12);
    }
    else {
      return [];
    }

    try {
      $plugin = $this->getFactory()->createInstance($plugin_id);
    }
    catch (Exception $e) {
      // Return empty array for wrong plugins.
      return [];
    }

    return $plugin->getSettingsForm();
  }

  /**
   * Checks if extra field has settings form.
   *
   * @param string $plugin_id
   *   The extra field plugin id.
   *
   * @return bool
   *   True if plugin has form, false otherwise.
   */
  public function hasSettingsForm($plugin_id) {
    try {
      $plugin = $this->getFactory()->createInstance($plugin_id);
    }
    catch (Exception $e) {
      // Return false for wrong plugins.
      return FALSE;
    }

    return !empty($plugin->getSettingsForm()) ? TRUE : FALSE;
  }

}
