<?php

namespace Drupal\ui_components\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * UiComponentsRegistry.
 *
 * Add components to theme registry.
 */
class UiComponentsRegistry {

  use StringTranslationTrait;

  /**
   * The module handler to use to load modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Theme component discovery.
   *
   * @var \Drupal\ui_components\Theme\ComponentDiscoveryInterface
   */
  protected $themeComponentDiscovery;

  /**
   * Constructs a \Drupal\Core\Theme\UiComponentsRegistry object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use to load modules.
   * @param \Drupal\ui_components\Theme\ComponentDiscoveryInterface $theme_component_discovery
   *   Theme component discovery service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ComponentDiscoveryInterface $theme_component_discovery) {
    $this->moduleHandler = $module_handler;
    $this->themeComponentDiscovery = $theme_component_discovery;
  }

  /**
   * Add components to theme registry.
   *
   * @param array $cache
   *   Theme registry cache.
   */
  public function build(array &$cache) {
    // Integrate components with theme registry for BC, this is a transitional
    // phase.
    $modules = array_keys($this->moduleHandler->getModuleList());
    foreach ($this->themeComponentDiscovery->getComponents() as $resolved_component) {
      foreach ($resolved_component['_provider tree'] as $extension_name => $component) {
        // 1. Module components.
        if (in_array($extension_name, $modules)) {
          if (isset($cache[$resolved_component['_theme_id']])) {
            throw new \LogicException('Component ' . $resolved_component['_theme_id'] . ' already defined as a theme hook.');
          }
          $type = 'module';
          $path = $this->moduleHandler->invoke($extension_name, 'ui_components_path');
          if (!$path) {
            $path = drupal_get_path('module', $extension_name);
          }
        }
        // 2. Theme components (include base themes).
        elseif (drupal_get_path('theme', $extension_name)) {
          $index = array_search($extension_name, array_keys($resolved_component['_provider tree']));
          $type = $index < count(array_keys($resolved_component['_provider tree'])) - 1 ? 'base_theme' : 'theme';
          $path = drupal_get_path('theme', $extension_name);
        }
        $cache = $this->updateRegistryWithComponent($cache, $resolved_component, $type, $path);
      }
    }

  }

  /**
   * Map component variables to theme variables.
   *
   * @param array $component_variables
   *   Component variables.
   *
   * @return array
   *   Theme variables.
   */
  protected function mapComponentVariablesToThemeVariables(array $component_variables) {
    $variables = array_flip(array_keys($component_variables));
    foreach (array_keys($variables) as $variable) {
      $variables[$variable] = NULL;

      if (isset($component_variables[$variable]['default'])) {
        if (is_string($component_variables[$variable]['default'])) {
          // @codingStandardsIgnoreLine
          $variables[$variable] = $this->t($component_variables[$variable]['default'], [], ['context' => 'ui-component']);
        }
        else {
          $variables[$variable] = $component_variables[$variable]['default'];
        }
      }
    }
    return $variables;
  }

  /**
   * Update registry with component.
   *
   * @param array $registry
   *   Registry.
   * @param array $component_definition
   *   Component definition.
   * @param string $provider_type
   *   Provider type.
   * @param string $provider_path
   *   Provider path.
   *
   * @return array
   *   Registry.
   */
  protected function updateRegistryWithComponent(array $registry, array $component_definition, $provider_type, $provider_path) {
    $id = $component_definition['id'];
    $bc_component_name = str_replace('-', '_', $component_definition['_theme_id']);

    $path = $provider_path . '/components/' . $id;
    if (!file_exists($path . '/' . $id . '.html.twig')) {
      if (isset($registry[$bc_component_name])) {
        $path = $registry[$bc_component_name]['path'];
      }
      else {
        return $registry;
      }
    }

    $registry[$bc_component_name] = [
      // New variables are always appended to the list of variables.
      'variables' => $this->mapComponentVariablesToThemeVariables($component_definition['variables']),
      'type' => $provider_type,
      'theme path' => $provider_path,
      'template' => $id,
      // Allow themes to extend components without repeating the Twig template.
      'path' => $path,
      // Allow inspectors of the theme registry to detect components.
      'component' => TRUE,
    ];

    return $registry;
  }

}
