<?php

namespace Drupal\icon;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IconProviderManager
 */
class IconProviderManager extends IconBasePluginManager {

  /**
   * Constructs a new \Drupal\icon\IconProviderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme manager used to invoke the alter hook with.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager used to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager) {
    parent::__construct($namespaces, $cache_backend, $module_handler, $theme_handler, $theme_manager, 'Drupal\icon\Plugin\Icon\IconProviderInterface', 'Drupal\icon\Annotation\IconProvider');
    $this->alterInfo('icon_provider_handler_info');
    $this->setCacheBackend($cache_backend, 'icon_provider_handler_info');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('container.namespaces'),
      $container->get('cache.discovery'),
      $container->get('module_handler'),
      $container->get('theme_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    if (isset($definitions['default'])) {
      // Always put default first.
      $definitions = ['default' => $definitions['default']] + $definitions;
    }
    return $definitions;
  }

  /**
   * Retrieves all available handler instances.
   *
   * @return \Drupal\icon\Plugin\Icon\IconProviderInterface[]
   */
  public function getInstances() {
    $instances = [];
    foreach (array_keys($this->getDefinitions()) as $plugin_id) {
      $instances[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $instances;
  }

}
