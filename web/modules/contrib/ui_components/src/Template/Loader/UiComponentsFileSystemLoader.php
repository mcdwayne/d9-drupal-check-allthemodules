<?php

namespace Drupal\ui_components\Template\Loader;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\ui_components\Theme\ComponentDiscoveryInterface;

/**
 * Class UiComponentsFileSystemLoader.
 */
class UiComponentsFileSystemLoader extends \Twig_Loader_Filesystem {

  /**
   * Component discovery.
   *
   * @var \Drupal\ui_components\Theme\ComponentDiscoveryInterface
   */
  protected $componentDiscovery;

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreLine
  public function __construct($paths = [], ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ComponentDiscoveryInterface $component_discovery) {
    parent::__construct();
    $this->componentDiscovery = $component_discovery;

    // Add paths for each component's namespace.
    // Components that could be used by non-Drupal applications may use
    // namespaces.
    $modules = array_keys($module_handler->getModuleList());
    foreach ($this->componentDiscovery->getComponents() as $id => $resolved_component) {
      foreach ($resolved_component['_provider tree'] as $extension_name => $component) {
        // 1. Module components.
        if (in_array($extension_name, $modules)) {
          $path = $module_handler->invoke($extension_name, 'ui_components_path');
          $namespace = $module_handler->invoke($extension_name, 'ui_components_namespace');
          if (!$path) {
            $path = drupal_get_path('module', $extension_name);
          }
        }
        // 2. Theme components.
        elseif (drupal_get_path('theme', $extension_name)) {
          $path = drupal_get_path('theme', $extension_name);
          $namespace = NULL;
        }
        if ($path) {
          $this->addPath($path . '/components/' . $id, $namespace);
        }
      }
    }
  }

}
