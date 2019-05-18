<?php

namespace Drupal\monster_menus\MMTreeBrowserDisplay;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides an MMTreeBrowserDisplay plugin manager.
 *
 * @see plugin_api
 */
class MMTreeBrowserDisplayManager extends DefaultPluginManager {

  /**
   * Constructs a MMTreeBrowserDisplayManager object.
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
    parent::__construct('Plugin/MMTreeBrowserDisplay', $namespaces, $module_handler, 'Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface', 'Drupal\monster_menus\Annotation\MMTreeBrowserDisplay');
    $this->alterInfo('mm_tree_browser_display');
    $this->setCacheBackend($cache_backend, 'mm_tree_browser_display_plugins');
  }

  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    foreach ($definitions as $type => $definition) {
      $definitions[$type]['supported_modes'] = $definition['class']::supportedModes();
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    return new $plugin_class();
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (in_array($options['mode'], $definition['supported_modes'])) {
        return $this->createInstance($plugin_id, $options);
      }
      if (!$definition['supported_modes']) {
        $default_plugin = $plugin_id;
      }
    }

    if (isset($default_plugin)) {
      return $this->createInstance($default_plugin, $options);
    }

    if ($options['mode']) {
      throw new \Exception("No plugin was found for the $options[mode] mode");
    }
    throw new NotFoundHttpException();
  }

}
