<?php

namespace Drupal\icons;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Icon Library plugin manager.
 *
 * @see plugin_api
 */
class IconLibraryPluginManager extends DefaultPluginManager {

  /**
   * An array of icon library options.
   *
   * @var array
   */
  protected $iconLibraryPluginOptions;

  /**
   * Constructs a IconLibraryPluginManager object.
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
      'Plugin/IconLibrary',
      $namespaces,
      $module_handler,
      'Drupal\icons\IconLibraryPluginInterface',
      'Drupal\icons\Annotation\IconLibrary'
    );
    $this->alterInfo('icon_library');
    $this->setCacheBackend($cache_backend, 'icon_set_libraries');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

  /**
   * Returns an array of widget type options for a field type.
   *
   * @return array
   *   If no field type is provided, returns a nested array of all widget types,
   *   keyed by field type human name.
   */
  public function getOptions() {
    if (!isset($this->iconLibraryPluginOptions)) {
      $options = array();
      $icon_library_types = $this->getDefinitions();
      foreach ($icon_library_types as $name => $icon_library_plugin) {
        // Check that the field type exists.
        $options[$name] = $icon_library_plugin['label'];
      }

      $this->iconLibraryPluginOptions = $options;
    }
    return !empty($this->iconLibraryPluginOptions) ? $this->iconLibraryPluginOptions : array();
  }

}
