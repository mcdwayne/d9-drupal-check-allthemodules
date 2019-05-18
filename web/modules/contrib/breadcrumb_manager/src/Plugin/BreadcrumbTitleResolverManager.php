<?php

namespace Drupal\breadcrumb_manager\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Breadcrumb title resolver plugin manager.
 */
class BreadcrumbTitleResolverManager extends DefaultPluginManager {

  protected $config;

  /**
   * Constructs a new BreadcrumbTitleResolverManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/BreadcrumbTitleResolver', $namespaces, $module_handler, 'Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverInterface', 'Drupal\breadcrumb_manager\Annotation\BreadcrumbTitleResolver');
    $this->config = $config_factory->get('breadcrumb_manager.config');

    $this->alterInfo('breadcrumb_manager_breadcrumb_title_resolver_info');
    $this->setCacheBackend($cache_backend, 'breadcrumb_manager_breadcrumb_title_resolver_plugins');
  }

  /**
   * Get instances.
   *
   * @return \Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverInterface[]
   *   An array of breadcrumb title resolvers.
   */
  public function getInstances() {
    $instances = [];
    foreach ($this->getDefinitions() as $pluginId => $definition) {
      try {
        $instance = $this->createInstance($pluginId);
        $instance->setActive($definition['enabled']);
        $instances[] = $instance;
      }
      catch (PluginException $e) {
        \Drupal::messenger()->addError($e->getMessage());
      }
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    $resolvers = $this->config->get('title_resolvers');

    foreach ($definitions as $id => $definition) {
      if (isset($resolvers[$id]['weight'])) {
        $definitions[$id]['weight'] = $resolvers[$id]['weight'];
      }
      if (isset($resolvers[$id]['enabled'])) {
        $definitions[$id]['enabled'] = $resolvers[$id]['enabled'];
      }
    }
    uasort($definitions, [$this, 'sortByWeight']);
    return $definitions;
  }

  /**
   * Sort by weight.
   *
   * @param array $a
   *   The first plugin definition.
   * @param array $b
   *   The second plugin definition.
   *
   * @return int
   *   The sorting order.
   */
  public function sortByWeight(array $a, array $b) {
    if ($a['weight'] == $b['weight']) {
      return 0;
    }
    return ($a['weight'] < $b['weight']) ? -1 : 1;
  }

}
