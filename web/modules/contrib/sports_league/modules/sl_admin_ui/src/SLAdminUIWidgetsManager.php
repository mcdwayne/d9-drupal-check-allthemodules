<?php
/**
 * @file
 * Contains \Drupal\sl_admin_ui\SLAdminUIWidgetsManager.
 */
namespace Drupal\sl_admin_ui;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sl_admin_ui\SLAdminUIWidgetPluginInterface;
/**
 * Defines the SLAdminUIWidgetsManager plugin manager.
 */

class SLAdminUIWidgetsManager extends DefaultPluginManager {
  /**
   * Constructs an SLAdminUIWidgetsManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SLAdminUIWidget', $namespaces, $module_handler, 'Drupal\sl_admin_ui\SLAdminUIWidgetPluginInterface', 'Drupal\sl_admin_ui\Annotation\SLAdminUIWidget');
    $this->alterInfo('reusable_forms_info');
    $this->setCacheBackend($cache_backend, 'sl_admin_ui_widgets');
  }
}