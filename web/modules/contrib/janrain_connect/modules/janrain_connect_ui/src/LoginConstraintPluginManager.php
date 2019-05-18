<?php

namespace Drupal\janrain_connect_ui;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager that controls janrain login constraints.
 */
class LoginConstraintPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new LoginConstraintPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/LoginConstraint',
      $namespaces,
      $module_handler,
      'Drupal\janrain_connect_ui\Plugin\LoginConstraintInterface',
      'Drupal\janrain_connect_ui\Annotation\LoginConstraint'
    );
    $this->alterInfo('janrain_connect_ui_login_constraint_info');
    $this->setCacheBackend($cache_backend, 'janrain_connect_ui_login_constraint');
  }

}
