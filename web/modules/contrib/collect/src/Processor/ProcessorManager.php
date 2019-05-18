<?php
/**
 * @file
 * Contains \Drupal\collect\Processor\ProcessorManager.
 */

namespace Drupal\collect\Processor;

use Drupal\collect\Model\ModelPluginInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for Collect post-processors.
 */
class ProcessorManager extends DefaultPluginManager implements ProcessorManagerInterface {

  /**
   * Constructs a ProcessorManager object.
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
    parent::__construct('Plugin/collect/Processor', $namespaces, $module_handler, 'Drupal\collect\Processor\ProcessorInterface', 'Drupal\collect\Annotation\Processor');
    $this->setCacheBackend($cache_backend, 'collect_processor_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array(), ModelPluginInterface $model_plugin = NULL) {
    /** @var \Drupal\collect\Processor\ProcessorInterface $instance */
    $instance = parent::createInstance($plugin_id, $configuration);
    // The $model_plugin parameter is declared as optional to satisfy the method
    // definition, but it is to be considered mandatory. Thus no need for an
    // isset() check before setting the model plugin on the processor instance.
    $instance->setModelPlugin($model_plugin);
    return $instance;
  }

}
