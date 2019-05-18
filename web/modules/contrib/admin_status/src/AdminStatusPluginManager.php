<?php

namespace Drupal\admin_status;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;

/**
 * Manages admin_status plugins.
 *
 * The AdminStatusPluginManager class extends the DefaultPluginManager to
 * provide a way to manage admin_status plugins.
 *
 * As well as this class definition, we need to declare our plugin manager class
 * as a service, in the plugin_type_admin_status.services.yml file.
 */
class AdminStatusPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    // We replace the $subdir parameter with our own value. This tells the
    // plugin system to look for plugins in the 'Plugin/AdminStatus' subfolder
    // inside the modules' 'src' folder.
    $subdir = 'Plugin/AdminStatus';

    // The name of the interface that plugins should adhere to. Drupal will
    // enforce this as a requirement. If a plugin does not implement this
    // interface, then Drupal will throw an error.
    $plugin_interface = 'Drupal\admin_status\AdminStatusInterface';

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin';

    parent::__construct($subdir,
                        $namespaces,
                        $module_handler,
                        $plugin_interface,
                        $plugin_definition_annotation_name);

    // This sets the caching method for our plugin definitions. Plugin
    // definitions are cached using the provided cache backend. For our
    // AdminStatus plugin type, we have specified the @cache.default service be
    // used in the plugin_type_admin_status.services.yml file. The second
    // argument is a cache key prefix. Out-of-the-box Drupal with the default
    // cache backend setup will store our plugin definition in the cache_default
    // table using the admin_status_info key. All that is implementation
    // details however, all we care about it that caching for our plugin
    // definition is taken care of by this call.
    $this->setCacheBackend($cache_backend, 'admin_status_info');
  }

}
