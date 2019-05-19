<?php

namespace Drupal\widget_api\Plugin\Widget;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;

/**
 * Plugin type manager for all layouts.
 */
class WidgetPluginManager extends DefaultPluginManager implements WidgetPluginManagerInterface {

  /**
   * Constructs a WidgetPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handle to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $plugin_interface = 'Drupal\widget_api\Plugin\Widget\WidgetInterface';
    $plugin_definition_annotation_name = 'Drupal\widget_api\Annotation\Widget';
    parent::__construct("Plugin/widget_api", $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    $this->discovery = new YamlDiscoveryDecorator($this->getDiscovery(), 'widget_api', $module_handler->getModuleDirectories() + $theme_handler->getThemeDirectories());
    $this->themeHandler = $theme_handler;

    $this->defaults += [
      'class' => 'Drupal\widget_api\Plugin\Widget\WidgetDefault',
    ];

    $this->setCacheBackend($cache_backend, 'widget_api');
    $this->alterInfo('widget_api');
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Add the module or theme path to the 'path'.
    if ($this->moduleHandler->moduleExists($definition['provider'])) {
      $definition['provider_type'] = 'module';
      $base_path = $this->moduleHandler->getModule($definition['provider'])
        ->getPath();
    }
    elseif ($this->themeHandler->themeExists($definition['provider'])) {
      $definition['provider_type'] = 'theme';
      $base_path = $this->themeHandler->getTheme($definition['provider'])
        ->getPath();
    }
    else {
      $base_path = '';
    }
    $definition['path'] = !empty($definition['path']) ? $base_path . '/' . $definition['path'] : $base_path;

    // Manage the fields and set some default config if required.
    foreach ($definition['fields'] as $fid => &$field) {
      if ($field['type'] == 'managed_file') {
        $field['upload_location'] = 'public://widget_files/';
      }
    }

    // If 'template' is set, then we'll derive 'template_path' and 'theme'.
    if (!empty($definition['template'])) {
      $template_parts = explode('/', $definition['template']);

      $definition['template'] = array_pop($template_parts);
      $definition['theme'] = strtr($definition['template'], '-', '_');
      $definition['template_path'] = $definition['path'];
      if (count($template_parts) > 0) {
        $definition['template_path'] .= '/' . implode('/', $template_parts);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetOptions(array $params = []) {
    $group_by_category = !empty($params['group_by_category']);
    $plugins = $this->getDefinitions();

    // Sort the plugins first by category, then by label.
    $options = [];
    foreach ($plugins as $id => $plugin) {
      if ($group_by_category) {
        $category = isset($plugin['category']) ? (string) $plugin['category'] : 'default';
        if (!isset($options[$category])) {
          $options[$category] = [];
        }
        $options[$category][$id] = $plugin['label'];
      }
      else {
        $options[$id] = $plugin['label'];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeImplementations() {
    $plugins = $this->getDefinitions();

    $theme_registry = [];
    foreach ($plugins as $id => $definition) {
      if (!empty($definition['template']) && !empty($definition['theme'])) {
        $theme_registry[$definition['theme']] = [
          'render element' => 'content',
          'template' => $definition['template'],
          'path' => $definition['template_path'],
        ];
      }
    }

    return $theme_registry;
  }

  /**
   * {@inheritdoc}
   */
  public function alterThemeImplementations(array &$theme_registry) {
    $plugins = $this->getDefinitions();

    // Find all the theme hooks which are for automatically registered templates
    // (we ignore manually set theme hooks because we don't know how they were
    // registered).
    $layout_theme_hooks = [];
    foreach ($plugins as $id => $definition) {
      if (!empty($definition['template']) && !empty($definition['theme']) && isset($theme_registry[$definition['theme']])) {
        $layout_theme_hooks[] = $definition['theme'];
      }
    }

    // Go through the theme registry looking for our theme hooks and any
    // suggestions based on them.
    foreach ($theme_registry as $theme_hook => &$info) {
      if (in_array($theme_hook, $layout_theme_hooks) || (!empty($info['base hook']) && in_array($info['base hook'], $layout_theme_hooks))) {
        // If 'template_preprocess' is included, we want to put our preprocess
        // after to not mess up the expectation that 'template_process' always
        // runs first.
        if (($index = array_search('template_preprocess', $info['preprocess functions'])) !== FALSE) {
          $index++;
        }
        else {
          // Otherwise, put our preprocess function first.
          $index = 0;
        }

        array_splice($info['preprocess functions'], $index, 0, '_widget_api_plugin_preprocess_widget');
      }
    }
  }

}
